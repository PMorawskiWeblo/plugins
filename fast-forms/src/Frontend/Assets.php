<?php
/**
 * Frontend assets.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Frontend;

use Weblo\FastForms\FormBuilder\RestApi;
use Weblo\FastForms\Support\AssetVersion;
use Weblo\FastForms\Support\GlobalSettings;

/**
 * Ładuje style i skrypty formularza na froncie.
 */
final class Assets {

	/** @var array<int, string> */
	private static array $instances = array();

	private static bool $enqueued = false;

	/**
	 * Rejestruje instancję formularza i ładuje assety (również po shortcode).
	 *
	 * @param string $instance_id Identyfikator instancji.
	 */
	public static function register_instance( string $instance_id ): void {
		self::$instances[] = $instance_id;
		self::ensure_enqueued();
	}

	/**
	 * Rejestruje hook enqueue.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_early' ), 20 );
		add_action( 'wp_footer', array( self::class, 'ensure_enqueued' ), 5 );
		add_filter( 'script_loader_tag', array( $this, 'defer_public_script' ), 10, 3 );
	}

	/**
	 * Wczesne ładowanie, gdy shortcode jest w treści wpisu.
	 */
	public function maybe_enqueue_early(): void {
		if ( ! empty( self::$instances ) ) {
			self::ensure_enqueued();
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( has_shortcode( $post->post_content, 'smart_form' ) ) {
			self::ensure_enqueued();
		}
	}

	/**
	 * Ładuje style i skrypt formularza (tylko raz).
	 */
	public static function ensure_enqueued(): void {
		if ( self::$enqueued ) {
			return;
		}

		if ( empty( self::$instances ) && ! self::page_has_shortcode() ) {
			return;
		}

		self::$enqueued = true;

		wp_enqueue_style(
			'fast-forms-public',
			FF_PLUGIN_URL . 'assets/public/css/form.css',
			array(),
			AssetVersion::get( 'assets/public/css/form.css' )
		);

		$public_config = array(
			'restUrl'         => rest_url( RestApi::NAMESPACE ),
			'captchaProvider' => GlobalSettings::get_captcha_provider(),
			'i18n'            => array(
				'close'           => __( 'Close', 'fast-forms' ),
				'sending'         => __( 'Sending…', 'fast-forms' ),
				'validationError' => __( 'Please fill in the required fields.', 'fast-forms' ),
				'required'        => __( 'This field is required.', 'fast-forms' ),
				'invalidFile'     => __( 'This file type is not allowed.', 'fast-forms' ),
				'fileTooLarge'    => __( 'The file is too large.', 'fast-forms' ),
				'fileMaxSize'     => __( 'Maximum size:', 'fast-forms' ),
				'chooseFile'      => __( 'Choose file', 'fast-forms' ),
				'chooseFiles'     => __( 'Choose files', 'fast-forms' ),
				'noFileSelected'  => __( 'No file selected', 'fast-forms' ),
				'removeFile'      => __( 'Remove file', 'fast-forms' ),
				'tooManyFiles'    => __( 'Too many files selected.', 'fast-forms' ),
				'tooFewFiles'     => __( 'Upload at least the minimum number of files.', 'fast-forms' ),
				'minSelections'   => __( 'Select at least %d option(s).', 'fast-forms' ),
				'maxSelections'   => __( 'Select at most %d option(s).', 'fast-forms' ),
				'antiSpamError'   => __( 'Anti-spam verification error.', 'fast-forms' ),
				'configError'     => __( 'Form configuration error.', 'fast-forms' ),
				'submitFailed'    => __( 'Submission error.', 'fast-forms' ),
				'submitError'     => __( 'An error occurred while submitting. Please try again.', 'fast-forms' ),
				'success'         => __( 'The form has been submitted. Thank you!', 'fast-forms' ),
			),
		);

		$script_deps = array( 'jquery' );

		if ( GlobalSettings::is_recaptcha_active() ) {
			$recaptcha = GlobalSettings::get();

			wp_enqueue_script(
				'google-recaptcha-v3',
				'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( (string) $recaptcha['recaptchaSiteKey'] ),
				array(),
				null,
				true
			);

			$script_deps[] = 'google-recaptcha-v3';

			$public_config['recaptcha'] = array(
				'siteKey' => (string) $recaptcha['recaptchaSiteKey'],
				'action'  => (string) ( $recaptcha['recaptchaAction'] ?? 'fast_forms_submit' ),
			);
		}

		if ( GlobalSettings::is_turnstile_active() ) {
			$turnstile = GlobalSettings::get();

			wp_enqueue_script(
				'cloudflare-turnstile',
				'https://challenges.cloudflare.com/turnstile/v0/api.js',
				array(),
				null,
				true
			);

			$script_deps[] = 'cloudflare-turnstile';

			$public_config['turnstile'] = array(
				'siteKey' => (string) $turnstile['turnstileSiteKey'],
			);
		}

		wp_enqueue_script(
			'fast-forms-public',
			FF_PLUGIN_URL . 'assets/public/js/form.js',
			$script_deps,
			AssetVersion::get( 'assets/public/js/form.js' ),
			true
		);

		wp_localize_script(
			'fast-forms-public',
			'fastFormsPublic',
			$public_config
		);
	}

	/**
	 * Sprawdza, czy bieżąca strona zawiera shortcode formularza.
	 */
	private static function page_has_shortcode(): bool {
		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post();

		return $post instanceof \WP_Post && has_shortcode( $post->post_content, 'smart_form' );
	}

	/**
	 * Dodaje atrybut defer do skryptu formularza (jQuery ładuje się wcześniej jako zależność).
	 *
	 * @param string $tag    Tag skryptu.
	 * @param string $handle Handle.
	 * @param string $src    URL skryptu.
	 */
	public function defer_public_script( string $tag, string $handle, string $src ): string {
		if ( 'fast-forms-public' !== $handle || str_contains( $tag, ' defer' ) ) {
			return $tag;
		}

		return str_replace( ' src', ' defer src', $tag );
	}
}
