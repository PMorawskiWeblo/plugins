<?php
/**
 * REST API registration.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\FormBuilder;

use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\Capabilities;
use Weblo\FastForms\Support\DebugLog;

/**
 * Rejestruje endpointy REST API buildera.
 */
final class RestApi {

	public const NAMESPACE = 'fast-forms/v1';

	/**
	 * Rejestruje hooki REST.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Rejestruje trasy API.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/forms/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_form' ),
					'permission_callback' => array( $this, 'can_edit_form' ),
					'args'                => array(
						'id' => array(
							'validate_callback' => array( $this, 'validate_form_id' ),
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_form' ),
					'permission_callback' => array( $this, 'can_edit_form' ),
					'args'                => array(
						'id'     => array(
							'validate_callback' => array( $this, 'validate_form_id' ),
						),
						'schema' => array(
							'required' => false,
						),
						'formSettings' => array(
							'required' => false,
						),
					),
				),
			)
		);

		if ( DebugLog::is_enabled() ) {
			register_rest_route(
				self::NAMESPACE,
				'/debug',
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'log_client_debug' ),
					'permission_callback' => static function (): bool {
						return Capabilities::can_manage();
					},
					'args'                => array(
						'message' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'context' => array(
							'default' => array(),
						),
					),
				)
			);
		}
	}

	/**
	 * Sprawdza uprawnienia do edycji formularza.
	 *
	 * @param \WP_REST_Request $request Żądanie REST.
	 */
	public function can_edit_form( \WP_REST_Request $request ): bool {
		$form_id = (int) $request->get_param( 'id' );

		return Capabilities::can_edit_form( $form_id );
	}

	/**
	 * Waliduje ID formularza.
	 *
	 * @param mixed            $value   Wartość parametru.
	 * @param \WP_REST_Request $request Żądanie REST.
	 * @param string           $param   Nazwa parametru.
	 */
	public function validate_form_id( $value, \WP_REST_Request $request, string $param ): bool {
		$form_id = (int) $value;
		$post    = get_post( $form_id );

		return $post instanceof \WP_Post && FormPostType::POST_TYPE === $post->post_type;
	}

	/**
	 * Zwraca dane formularza.
	 *
	 * @param \WP_REST_Request $request Żądanie REST.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_form( \WP_REST_Request $request ) {
		$form_id = (int) $request->get_param( 'id' );
		$post    = get_post( $form_id );

		if ( ! $post instanceof \WP_Post ) {
			return new \WP_Error( 'ff_form_not_found', __( 'The form does not exist.', 'fast-forms' ), array( 'status' => 404 ) );
		}

		$schema_raw = get_post_meta( $form_id, FormPostType::META_SCHEMA, true );
		$schema     = FormSchemaStorage::get( $form_id );

		DebugLog::info(
			'REST GET form schema',
			array(
				'form_id'      => $form_id,
				'raw_type'     => gettype( $schema_raw ),
				'field_count'  => DebugLog::count_schema_fields( $schema ),
				'row_count'    => count( $schema['rows'] ?? array() ),
				'schema_version' => (int) get_post_meta( $form_id, FormPostType::META_SCHEMA_VERSION, true ),
			)
		);

		return rest_ensure_response(
			array(
				'id'            => $form_id,
				'title'         => $post->post_title,
				'schema'        => $schema,
				'schemaVersion' => (int) get_post_meta( $form_id, FormPostType::META_SCHEMA_VERSION, true ) ?: Schema::VERSION,
				'formSettings'  => FormSettingsStorage::get_all( $form_id ),
			)
		);
	}

	/**
	 * Aktualizuje schemę formularza.
	 *
	 * @param \WP_REST_Request $request Żądanie REST.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_form( \WP_REST_Request $request ) {
		$form_id = (int) $request->get_param( 'id' );
		$schema  = $request->get_param( 'schema' );
		$result  = null;

		if ( is_array( $schema ) ) {
			DebugLog::info(
				'REST PUT form schema — incoming',
				array(
					'form_id'         => $form_id,
					'raw_field_count' => DebugLog::count_schema_fields( $schema ),
					'raw_row_count'   => count( $schema['rows'] ?? array() ),
				)
			);

			$result = FormSchemaStorage::save( $form_id, $schema );

			if ( false === $result ) {
				DebugLog::schema(
					'REST PUT form schema — save rejected',
					array(
						'form_id'         => $form_id,
						'raw_field_count' => DebugLog::count_schema_fields( $schema ),
					),
					'ERROR'
				);

				return new \WP_Error(
					'ff_schema_save_failed',
					__( 'Could not save the form schema.', 'fast-forms' ),
					array( 'status' => 500 )
				);
			}

			DebugLog::info(
				'REST PUT form schema — saved',
				array(
					'form_id'           => $form_id,
					'saved_field_count' => DebugLog::count_schema_fields( $result['schema'] ),
					'schema_version'    => $result['schemaVersion'],
					'save_source'       => 'rest_api',
				)
			);
		}

		$form_settings = $request->get_param( 'formSettings' );
		if ( is_array( $form_settings ) ) {
			FormSettingsStorage::save_all( $form_id, $form_settings );
			DebugLog::info( 'REST PUT form settings saved', array( 'form_id' => $form_id ) );
		}

		$schema_response = $result ? $result['schema'] : FormSchemaStorage::get( $form_id );
		$version         = $result ? $result['schemaVersion'] : (int) get_post_meta( $form_id, FormPostType::META_SCHEMA_VERSION, true );

		return rest_ensure_response(
			array(
				'success'       => true,
				'schema'        => $schema_response,
				'schemaVersion' => $version,
				'formSettings'  => FormSettingsStorage::get_all( $form_id ),
				'message'       => __( 'The form has been saved.', 'fast-forms' ),
			)
		);
	}

	/**
	 * Zapisuje komunikat debug z buildera JS.
	 *
	 * @param \WP_REST_Request $request Żądanie REST.
	 * @return \WP_REST_Response
	 */
	public function log_client_debug( \WP_REST_Request $request ): \WP_REST_Response {
		$message = (string) $request->get_param( 'message' );
		$context = $request->get_param( 'context' );

		if ( ! is_array( $context ) ) {
			$context = array();
		}

		DebugLog::info( '[JS] ' . $message, $context );

		return rest_ensure_response( array( 'logged' => true ) );
	}
}
