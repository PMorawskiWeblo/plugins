<?php
/**
 * Public form submit REST API.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Submission;

use Weblo\FastForms\FormBuilder\FormSchemaStorage;
use Weblo\FastForms\FormBuilder\FormSettingsStorage;
use Weblo\FastForms\FormBuilder\RestApi;
use Weblo\FastForms\Integrations\RecaptchaVerifier;
use Weblo\FastForms\Integrations\TurnstileVerifier;
use Weblo\FastForms\Support\DebugLog;
use Weblo\FastForms\Support\GlobalSettings;
use Weblo\FastForms\Support\RedirectResolver;
use Weblo\FastForms\PostTypes\FormPostType;

/**
 * Endpoint wysyłki formularza z frontu.
 */
final class SubmitRestApi {

	/**
	 * Rejestruje hook REST.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Rejestruje trasę submit.
	 */
	public function register_routes(): void {
		register_rest_route(
			RestApi::NAMESPACE,
			'/forms/(?P<id>\d+)/submit',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'submit' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'validate_callback' => array( $this, 'validate_form_id' ),
					),
				),
			)
		);
	}

	/**
	 * Waliduje ID formularza.
	 *
	 * @param mixed $value Wartość.
	 */
	public function validate_form_id( $value ): bool {
		$form_id = (int) $value;
		$post    = get_post( $form_id );

		return $post instanceof \WP_Post && FormPostType::POST_TYPE === $post->post_type;
	}

	/**
	 * Obsługuje wysyłkę formularza.
	 *
	 * @param \WP_REST_Request $request Żądanie.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function submit( \WP_REST_Request $request ) {
		$form_id = (int) $request->get_param( 'id' );
		$post    = get_post( $form_id );

		if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
			return new \WP_Error( 'ff_form_unavailable', __( 'The form is unavailable.', 'fast-forms' ), array( 'status' => 404 ) );
		}

		$token = $this->get_submit_token( $request );

		if ( ! SubmitToken::verify( $form_id, $token ) ) {
			DebugLog::info(
				'Submit token invalid',
				array(
					'form_id'   => $form_id,
					'has_token' => '' !== $token,
				)
			);

			return new \WP_Error( 'ff_invalid_nonce', __( 'Your session has expired. Refresh the page and try again.', 'fast-forms' ), array( 'status' => 403 ) );
		}

		$post_data = $this->get_post_data( $request );

		if ( Honeypot::is_triggered( $post_data ) ) {
			DebugLog::info( 'Honeypot triggered', array( 'form_id' => $form_id ) );

			return rest_ensure_response( Honeypot::fake_success_response( $form_id ) );
		}

		$form_settings = FormSettingsStorage::get_form( $form_id );
		$limiter       = new SubmitLimiter( $form_id, $form_settings, $post_data );
		$limit_message = $limiter->check();

		if ( null !== $limit_message ) {
			return new \WP_Error( 'ff_rate_limited', $limit_message, array( 'status' => 429 ) );
		}

		$limiter->bump_rate_limit();

		$captcha_score = $this->verify_captcha( $request );
		if ( is_wp_error( $captcha_score ) ) {
			return $captcha_score;
		}

		$schema              = FormSchemaStorage::get( $form_id );
		$validation_messages = FormSettingsStorage::get_validation( $form_id );
		$validator           = new Validator( $schema, $validation_messages );
		$files               = $this->get_uploaded_files( $request );
		$result              = $validator->validate( $post_data, $files );

		if ( ! $result['valid'] ) {
			DebugLog::info( 'Submit validation failed', array( 'form_id' => $form_id, 'errors' => array_keys( $result['errors'] ) ) );

			return new \WP_REST_Response(
				array(
					'success' => false,
					'errors'  => $result['errors'],
					'message' => __( 'Please correct the errors in the form.', 'fast-forms' ),
				),
				422
			);
		}

		$email_settings = FormSettingsStorage::get_email( $form_id );
		$skip_entry_save  = ! empty( $email_settings['skipEntrySave'] );
		$entry_id         = 0;

		if ( ! $skip_entry_save ) {
			$entry_id = EntrySaver::create(
				$form_id,
				$result['payload'],
				$result['files'],
				$schema,
				null !== $captcha_score ? (float) $captcha_score : null
			);

			if ( is_wp_error( $entry_id ) ) {
				DebugLog::error( 'Submit save failed', array( 'form_id' => $form_id, 'error' => $entry_id->get_error_message() ) );

				return new \WP_Error(
					'ff_save_failed',
					$validation_messages['submitError'] ?? __( 'Could not save the submission.', 'fast-forms' ),
					array( 'status' => 500 )
				);
			}
		}

		$limiter->record();

		EntryMailer::send( $form_id, $entry_id, $result['payload'], $schema );

		$notifications = FormSettingsStorage::get_notifications( $form_id );

		DebugLog::info(
			$skip_entry_save ? 'Submit processed without CPT save' : 'Submit saved',
			array(
				'form_id'         => $form_id,
				'entry_id'        => $entry_id,
				'skip_entry_save' => $skip_entry_save,
			)
		);

		$show_success = ! isset( $notifications['showSuccessMessage'] ) || ! empty( $notifications['showSuccessMessage'] );

		$response = array(
			'success'  => true,
			'entryId'  => $entry_id,
			'message'  => $show_success
				? (string) ( $notifications['successMessage'] ?? __( 'The form has been submitted. Thank you!', 'fast-forms' ) )
				: '',
			'hideForm' => ! empty( $notifications['hideFormAfterSubmit'] ),
		);

		if ( ! empty( $notifications['showExtraContent'] ) && ! empty( $notifications['extraContent'] ) ) {
			$response['extraContent'] = $notifications['extraContent'];
		}

		$redirect = RedirectResolver::payload_from_notifications( $notifications );

		if ( null !== $redirect ) {
			$response['redirect'] = $redirect;
		}

		/**
		 * Fires after a successful form submission.
		 *
		 * Use for CRM/marketing integrations (e.g. Salesmanago), webhooks, or custom logic.
		 *
		 * @param int                  $form_id  Form ID.
		 * @param int                  $entry_id Entry ID, or 0 when “Do not save submission to database” is enabled.
		 * @param array<string, mixed> $payload  Sanitized field values.
		 * @param array<string, mixed> $schema   Form schema at submit time.
		 * @param array<string, mixed> $response REST response sent to the browser.
		 */
		do_action( 'ff_form_submitted', $form_id, $entry_id, $result['payload'], $schema, $response );

		return rest_ensure_response( $response );
	}

	/**
	 * Pobiera token wysyłki z żądania.
	 *
	 * @param \WP_REST_Request $request Żądanie.
	 */
	private function get_submit_token( \WP_REST_Request $request ): string {
		$token = (string) $request->get_param( 'ff_form_nonce' );

		if ( '' !== $token ) {
			return sanitize_text_field( wp_unslash( $token ) );
		}

		$header = $request->get_header( 'x_ff_form_nonce' );

		if ( is_string( $header ) && '' !== $header ) {
			return sanitize_text_field( wp_unslash( $header ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST['ff_form_nonce'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return sanitize_text_field( wp_unslash( (string) $_POST['ff_form_nonce'] ) );
		}

		return '';
	}

	/**
	 * Zwraca dane POST z żądania REST.
	 *
	 * @param \WP_REST_Request $request Żądanie.
	 * @return array<string, mixed>
	 */
	private function get_post_data( \WP_REST_Request $request ): array {
		$post_data = $request->get_body_params();

		if ( is_array( $post_data ) && ! empty( $post_data ) ) {
			return wp_unslash( $post_data );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! empty( $_POST ) && is_array( $_POST ) ) {
			return wp_unslash( $_POST );
		}

		return array();
	}

	/**
	 * Zwraca przesłane pliki.
	 *
	 * @param \WP_REST_Request $request Żądanie.
	 * @return array<string, mixed>
	 */
	private function get_uploaded_files( \WP_REST_Request $request ): array {
		$files = $request->get_file_params();

		if ( is_array( $files ) && ! empty( $files ) ) {
			return $files;
		}

		return ! empty( $_FILES ) && is_array( $_FILES ) ? $_FILES : array();
	}

	/**
	 * Weryfikuje captchę (reCAPTCHA lub Turnstile) i zwraca score lub błąd.
	 *
	 * @param \WP_REST_Request $request Żądanie.
	 * @return float|\WP_Error|null
	 */
	private function verify_captcha( \WP_REST_Request $request ) {
		$provider = GlobalSettings::get_captcha_provider();

		if ( GlobalSettings::CAPTCHA_NONE === $provider || ! GlobalSettings::is_captcha_active() ) {
			return null;
		}

		if ( GlobalSettings::CAPTCHA_TURNSTILE === $provider ) {
			$token = (string) $request->get_param( 'cf-turnstile-response' );

			if ( '' === $token ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$token = isset( $_POST['cf-turnstile-response'] ) ? (string) wp_unslash( $_POST['cf-turnstile-response'] ) : '';
			}

			$verifier = new TurnstileVerifier();
			$result   = $verifier->verify( sanitize_text_field( $token ) );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return (float) ( $result['score'] ?? 1.0 );
		}

		$token = (string) $request->get_param( 'g-recaptcha-response' );

		if ( '' === $token ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$token = isset( $_POST['g-recaptcha-response'] ) ? (string) wp_unslash( $_POST['g-recaptcha-response'] ) : '';
		}

		$verifier = new RecaptchaVerifier();
		$result   = $verifier->verify( sanitize_text_field( $token ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return (float) ( $result['score'] ?? 0 );
	}
}
