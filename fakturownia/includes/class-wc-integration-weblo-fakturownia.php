<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integracja WooCommerce z Fakturownią.
 */
class WC_Integration_Weblo_Fakturownia extends WC_Integration {

	/**
	 * Wylicza wersję assetów (cache busting).
	 *
	 * - Gdy dev mode = yes: losowa wersja na każde załadowanie strony.
	 * - Gdy dev mode = no: wersja z pola tekstowego.
	 *
	 * @return string
	 */
	protected function get_assets_version() {
		$plugin_version = defined( 'WEBLO_FAKTUROWNIA_VERSION' ) ? (string) WEBLO_FAKTUROWNIA_VERSION : '1.0.0';
		$dev_mode = $this->get_option( 'weblo_fakturownia_dev_mode', 'no' );
		if ( 'yes' === $dev_mode ) {
			// Krótki "losowy" suffix, żeby zawsze ominąć cache.
			return (string) wp_rand( 100000, 999999 );
		}

		$version = (string) $this->get_option( 'weblo_fakturownia_assets_version', $plugin_version );
		$version = trim( $version );

		return '' !== $version ? $version : $plugin_version;
	}

	/**
	 * Definicje zakładek UI.
	 *
	 * @return array<string, array{label:string, fields:string[]}>
	 */
	protected function get_tabs_definition() {
		return array(
			'connection'  => array(
				'label'  => __( 'Connection', 'weblo-fakturownia' ),
				'fields' => array(
					'weblo_fakturownia_domain',
					'weblo_fakturownia_api_token',
					'weblo_fakturownia_department_id',
				),
			),
			'invoices'    => array(
				'label'  => __( 'Invoices', 'weblo-fakturownia' ),
				'fields' => array(
					'weblo_fakturownia_auto_issue_invoices',
					'weblo_fakturownia_invoice_statuses',
					'weblo_fakturownia_invoice_date_source',
					'weblo_fakturownia_cod_invoices',
					'weblo_fakturownia_cod_statuses',
					'weblo_fakturownia_auto_send_invoice_email',
					'weblo_fakturownia_send_from_woocommerce',
					'weblo_fakturownia_shortcode_invoice_pdf',
					'weblo_fakturownia_wc_email_template',
					'weblo_fakturownia_invoice_notes',
				),
			),
			'corrections' => array(
				'label'  => __( 'Corrections', 'weblo-fakturownia' ),
				'fields' => array(
					'weblo_fakturownia_auto_issue_corrections',
					'weblo_fakturownia_correction_mode',
					'weblo_fakturownia_auto_send_correction_email',
					'weblo_fakturownia_send_corrections_from_woocommerce',
					'weblo_fakturownia_wc_correction_email_template',
					'weblo_fakturownia_correction_shipping_mode',
					'weblo_fakturownia_correction_shipping_amount',
				),
			),
			'bulk'        => array(
				'label'  => __( 'Bulk operations', 'weblo-fakturownia' ),
				'fields' => array(
					'weblo_fakturownia_bulk_invoice_statuses',
					'weblo_fakturownia_bulk_batch_size',
					'weblo_fakturownia_bulk_invoice_date',
					'weblo_fakturownia_bulk_correction_statuses',
					'weblo_fakturownia_bulk_correction_date',
				),
			),
			'logs'        => array(
				'label'  => __( 'Logs', 'weblo-fakturownia' ),
				'fields' => array(
					'weblo_fakturownia_error_notification_email',
					'weblo_fakturownia_error_notifications_enabled',
					'weblo_fakturownia_debug_logging_enabled',
					'weblo_fakturownia_dev_mode',
					'weblo_fakturownia_assets_version',
				),
			),
		);
	}

	/**
	 * Sprawdza, czy połączenie zostało przetestowane poprawnie.
	 *
	 * @return bool
	 */
	protected function is_connection_ok() {
		return 'yes' === $this->get_option( 'weblo_fakturownia_connection_ok', 'no' );
	}

	/**
	 * Konstruktor integracji.
	 */
	public function __construct() {
		$this->id                 = 'weblo_fakturownia';
		$this->method_title       = __( 'Fakturownia by Weblo', 'weblo-fakturownia' );
		$this->method_description = __( 'WooCommerce integration with Fakturownia – connection and automation settings.', 'weblo-fakturownia' );

		// Ładowanie ustawień.
		$this->init_form_fields();
		$this->init_settings();

		// Hook zapisu ustawień.
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );

		// Skrypty JS tylko na stronie integracji WooCommerce.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX: test połączenia.
		add_action( 'wp_ajax_weblo_fakturownia_test_connection', array( $this, 'ajax_test_connection' ) );

		// AJAX: masowe wystawianie faktur.
		add_action( 'wp_ajax_weblo_fakturownia_bulk_issue_invoices', array( $this, 'ajax_bulk_issue_invoices' ) );

		// AJAX: masowe wystawianie korekt.
		add_action( 'wp_ajax_weblo_fakturownia_bulk_issue_corrections', array( $this, 'ajax_bulk_issue_corrections' ) );

		// AJAX: pobieranie logów do zakładki "Logi".
		add_action( 'wp_ajax_weblo_fakturownia_fetch_logs', array( $this, 'ajax_fetch_logs' ) );

		// AJAX: czyszczenie pliku debug.log.
		add_action( 'wp_ajax_weblo_fakturownia_clear_debug_log', array( $this, 'ajax_clear_debug_log' ) );
		// AJAX: czyszczenie logów błędów z tabeli bazy danych.
		add_action( 'wp_ajax_weblo_fakturownia_clear_db_logs', array( $this, 'ajax_clear_db_logs' ) );
	}

	/**
	 * Definicja pól formularza ustawień (wszystkie zakładki).
	 */
	public function init_form_fields() {
		$order_statuses = wc_get_order_statuses();

		$this->form_fields = array(
			// Connection.
			'weblo_fakturownia_domain'         => array(
				'title'       => __( 'Fakturownia domain', 'weblo-fakturownia' ),
				'type'        => 'text',
				'description' => __( 'Fakturownia domain without https://, for example yourshop.fakturownia.pl', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'weblo_fakturownia_api_token'      => array(
				'title'       => __( 'API token', 'weblo-fakturownia' ),
				'type'        => 'text',
				'description' => __( 'API authorization token from Fakturownia.', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'weblo_fakturownia_department_id'  => array(
				'title'       => __( 'Company / department ID (optional)', 'weblo-fakturownia' ),
				'type'        => 'text',
				'description' => __( 'Optional company or department ID in Fakturownia.', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => '',
			),

			// Invoices.
			'weblo_fakturownia_auto_issue_invoices' => array(
				'title'       => __( 'Automatically issue invoices', 'weblo-fakturownia' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable', 'weblo-fakturownia' ),
				'default'     => 'no',
			),
			'weblo_fakturownia_invoice_statuses'    => array(
				'title'             => __( 'Statuses that trigger invoice', 'weblo-fakturownia' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'min-width: 350px;',
				'description'       => __( 'Select order statuses that should trigger invoice issuing.', 'weblo-fakturownia' ),
				'desc_tip'          => true,
				'options'           => $order_statuses,
				'default'           => array(),
				'custom_attributes' => array(
					'multiple' => 'multiple',
				),
			),
			'weblo_fakturownia_invoice_date_source' => array(
				'title'       => __( 'Invoice issue date', 'weblo-fakturownia' ),
				'type'        => 'select',
				'description' => __( 'Choose which date should be set on invoices when issuing them automatically.', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => 'status_date',
				'options'     => array(
					'status_date' => __( 'Order status change date (default)', 'weblo-fakturownia' ),
					'order_date'  => __( 'Order creation date', 'weblo-fakturownia' ),
				),
			),
			'weblo_fakturownia_cod_invoices'        => array(
				'title'       => __( 'Issue invoices for COD orders at different status', 'weblo-fakturownia' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable', 'weblo-fakturownia' ),
				'default'     => 'no',
			),
			'weblo_fakturownia_cod_statuses'        => array(
				'title'             => __( 'Statuses for COD orders', 'weblo-fakturownia' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'min-width: 350px;',
				'description'       => __( 'Select statuses of COD orders that should trigger invoice issuing.', 'weblo-fakturownia' ),
				'desc_tip'          => true,
				'options'           => $order_statuses,
				'default'           => array(),
				'custom_attributes' => array(
					'multiple' => 'multiple',
				),
			),
			'weblo_fakturownia_auto_send_invoice_email' => array(
				'title'       => __( 'Automatically send invoice to customer', 'weblo-fakturownia' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable', 'weblo-fakturownia' ),
				'default'     => 'no',
				'description' => __( 'After automatically issuing an invoice, send it to the customer (using Fakturownia and/or WooCommerce).', 'weblo-fakturownia' ),
				'desc_tip'    => true,
			),
			'weblo_fakturownia_send_from_woocommerce' => array(
				'title'       => __( 'Send invoices from WooCommerce', 'weblo-fakturownia' ),
				'type'        => 'checkbox',
				'label'       => __( 'Additionally send WooCommerce email with invoice PDF attached', 'weblo-fakturownia' ),
				'description' => __( 'When enabled, besides Fakturownia email we also send a WordPress email with invoice PDF attached.', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			'weblo_fakturownia_wc_email_template' => array(
				'title'       => __( 'WooCommerce email content (template)', 'weblo-fakturownia' ),
				'type'        => 'textarea',
				'css'         => 'min-width: 350px; height: 180px;',
				'description' => __( 'Content of the email sent from WooCommerce. Available shortcodes: [order_id], [order_number], [order_url], [order_link], [invoice_id], [invoice_number], [invoice_url], [invoice_link].', 'weblo-fakturownia' ),
				'desc_tip'    => false,
				'default'     => __( "Hello,\n\nWe are sending you the invoice [invoice_number] for order [order_number] in the attachment.\n\nOrder: [order_link]\nInvoice: [invoice_link]\n\nBest regards,\nStore support", 'weblo-fakturownia' ),
			),
			'weblo_fakturownia_invoice_notes'       => array(
				'title'       => __( 'Document notes', 'weblo-fakturownia' ),
				'type'        => 'textarea',
				'css'         => 'min-width: 350px; height: 120px;',
				'description' => __( 'Notes text added to the invoice (sent to Fakturownia as internal_note). Available shortcodes: [order_id], [order_number], [customer_name], [customer_email], [billing_company], [billing_phone].', 'weblo-fakturownia' ),
				'desc_tip'    => false,
				'default'     => '',
				'placeholder' => __( 'Example: Order [order_number] – customer [customer_name] ([customer_email])', 'weblo-fakturownia' ),
			),

			// Corrections.
			'weblo_fakturownia_auto_issue_corrections' => array(
				'title'   => __( 'Automatically issue corrections after order refund', 'weblo-fakturownia' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', 'weblo-fakturownia' ),
				'default' => 'no',
			),
			'weblo_fakturownia_correction_mode' => array(
				'title'       => __( 'Correction mode', 'weblo-fakturownia' ),
				'type'        => 'select',
				'description' => __( 'Choose how correction positions should be generated.', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => 'full',
				'options'     => array(
					'difference'   => __( 'Differential correction (only changed amounts)', 'weblo-fakturownia' ),
					'full'         => __( 'Full correction (new order value)', 'weblo-fakturownia' ),
				),
			),
			'weblo_fakturownia_auto_send_correction_email' => array(
				'title'   => __( 'Automatically send correction to customer', 'weblo-fakturownia' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', 'weblo-fakturownia' ),
				'default' => 'no',
			),
			'weblo_fakturownia_send_corrections_from_woocommerce' => array(
				'title'       => __( 'Send corrections from WooCommerce', 'weblo-fakturownia' ),
				'type'        => 'checkbox',
				'label'       => __( 'Additionally send WooCommerce email with correction PDF attached', 'weblo-fakturownia' ),
				'description' => __( 'When enabled, besides Fakturownia email we also send a WordPress email with correction PDF attached.', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			'weblo_fakturownia_wc_correction_email_template' => array(
				'title'       => __( 'WooCommerce correction email content (template)', 'weblo-fakturownia' ),
				'type'        => 'textarea',
				'css'         => 'min-width: 350px; height: 180px;',
				'description' => __( 'Content of the email sent from WooCommerce (correction). Available shortcodes: [order_id], [order_number], [order_url], [order_link], [invoice_id], [invoice_number], [invoice_url], [invoice_link].', 'weblo-fakturownia' ),
				'desc_tip'    => false,
				'default'     => __( "Hello,\n\nWe are sending you the correction document [invoice_number] for order [order_number] in the attachment.\n\nOrder: [order_link]\nDocument: [invoice_link]\n\nBest regards,\nStore support", 'weblo-fakturownia' ),
			),
			'weblo_fakturownia_correction_shipping_mode' => array(
				'title'       => __( 'Shipping correction mode', 'weblo-fakturownia' ),
				'type'        => 'select',
				'description' => __( 'How the shipping cost should be corrected on the correction invoice.', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => 'as_order',
				'options'     => array(
					'do_0'          => __( 'To 0', 'weblo-fakturownia' ),
					'as_order'      => __( 'Same as on order', 'weblo-fakturownia' ),
					'custom_amount' => __( 'To custom amount', 'weblo-fakturownia' ),
				),
			),
			'weblo_fakturownia_correction_shipping_amount' => array(
				'title'       => __( 'Shipping amount on correction', 'weblo-fakturownia' ),
				'type'        => 'number',
				'css'         => 'min-width: 120px;',
				'description' => __( 'Used only when "To custom amount" is selected.', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => '',
				'custom_attributes' => array(
					'step' => '0.01',
					'min'  => '0',
				),
			),

			// Bulk operations (UI).
			'weblo_fakturownia_bulk_invoice_statuses' => array(
				'title'       => __( 'Statuses for bulk issuing missing invoices', 'weblo-fakturownia' ),
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => __( 'Select order status for which you want to search missing invoices.', 'weblo-fakturownia' ),
				'options'     => $order_statuses,
				'default'     => '',
			),
			'weblo_fakturownia_bulk_batch_size' => array(
				'title'       => __( 'How many invoices per batch', 'weblo-fakturownia' ),
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => __( 'For large stores 10–50 is recommended. Higher value = faster, but more server load.', 'weblo-fakturownia' ),
				'options'     => array(
					'5'  => '5',
					'10' => '10',
					'20' => '20',
					'50' => '50',
				),
				'default'     => '20',
			),
			'weblo_fakturownia_bulk_invoice_date'     => array(
				'title'       => __( 'Invoice date', 'weblo-fakturownia' ),
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => __( 'Which date should be set on documents in bulk issuing.', 'weblo-fakturownia' ),
				'options'     => array(
					'today'       => __( 'Today', 'weblo-fakturownia' ),
					'status_date' => __( 'Order status change date', 'weblo-fakturownia' ),
				),
				'default'     => 'today',
			),
			'weblo_fakturownia_bulk_correction_statuses' => array(
				'title'       => __( 'Refund statuses for bulk corrections', 'weblo-fakturownia' ),
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => __( 'Select refund status to include in bulk corrections.', 'weblo-fakturownia' ),
				'options'     => $order_statuses,
				'default'     => '',
			),
			'weblo_fakturownia_bulk_correction_date'  => array(
				'title'       => __( 'Correction date', 'weblo-fakturownia' ),
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => __( 'Which date should be set on corrections in bulk operations.', 'weblo-fakturownia' ),
				'options'     => array(
					'today'       => __( 'Today', 'weblo-fakturownia' ),
					'refund_date' => __( 'Refund date', 'weblo-fakturownia' ),
				),
				'default'     => 'today',
			),

			// Logs.
			'weblo_fakturownia_error_notification_email' => array(
				'title'       => __( 'Error notification email', 'weblo-fakturownia' ),
				'type'        => 'email',
				'description' => __( 'Email address for integration error notifications.', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'weblo_fakturownia_error_notifications_enabled' => array(
				'title'   => __( 'Enable email error notifications', 'weblo-fakturownia' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', 'weblo-fakturownia' ),
				'default' => 'no',
			),
			'weblo_fakturownia_debug_logging_enabled' => array(
				'title'       => __( 'Enable debug.log logging', 'weblo-fakturownia' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable', 'weblo-fakturownia' ),
				'description' => __( 'When enabled, the plugin writes diagnostic logs to fakturownia/logs/debug.log (max 2 MB). Use this only for troubleshooting.', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			'weblo_fakturownia_dev_mode' => array(
				'title'       => __( 'Developer mode (cache busting)', 'weblo-fakturownia' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable random asset versioning', 'weblo-fakturownia' ),
				'description' => __( 'When enabled, JS/CSS files receive a random ver parameter to bypass cache (useful on heavy cache environments).', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			'weblo_fakturownia_assets_version' => array(
				'title'       => __( 'Assets version (cache)', 'weblo-fakturownia' ),
				'type'        => 'text',
				'description' => __( 'Used as ver parameter when developer mode is disabled (e.g. 1.0.1). Change this value to force cache refresh.', 'weblo-fakturownia' ),
				'desc_tip'    => true,
				'default'     => defined( 'WEBLO_FAKTUROWNIA_VERSION' ) ? (string) WEBLO_FAKTUROWNIA_VERSION : '1.0.0',
			),
		);
	}

	/**
	 * Render textarea settings. For email template fields we use WordPress editor (Visual + HTML).
	 *
	 * @param string $key
	 * @param array  $data
	 * @return string
	 */
	public function generate_textarea_html( $key, $data ) {
		$is_wp_editor = in_array(
			$key,
			array(
				'weblo_fakturownia_wc_email_template',
				'weblo_fakturownia_wc_correction_email_template',
			),
			true
		);

		if ( ! $is_wp_editor ) {
			return parent::generate_textarea_html( $key, $data );
		}

		$field_key = $this->get_field_key( $key );
		$value     = $this->get_option( $key );
		$default_value = isset( $data['default'] ) ? (string) $data['default'] : '';
		if ( '' === trim( (string) $value ) && '' !== $default_value ) {
			$value = $default_value;
		}
		$default_json  = wp_json_encode( $default_value );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ?? '' ); ?></label>
			</th>
			<td class="forminp">
				<?php
				wp_editor(
					(string) $value,
					$field_key,
					array(
						'textarea_name' => $field_key,
						'textarea_rows' => 10,
						'media_buttons' => false,
						'tinymce'       => true,
						'quicktags'     => true, // HTML tab
					)
				);
				?>
				<p style="margin-top:8px;">
					<button
						type="button"
						class="button weblo-fakturownia-restore-default-template"
						data-editor-id="<?php echo esc_attr( $field_key ); ?>"
						data-default-json="<?php echo esc_attr( $default_json ); ?>"
					>
						<?php echo esc_html__( 'Restore default template', 'weblo-fakturownia' ); ?>
					</button>
				</p>
				<?php if ( ! empty( $data['description'] ) ) : ?>
					<p class="description"><?php echo wp_kses_post( $data['description'] ); ?></p>
				<?php endif; ?>
				<script>
				(function(){
					if (window.__webloFakturowniaRestoreDefaultBound) return;
					window.__webloFakturowniaRestoreDefaultBound = true;
					document.addEventListener('click', function(e){
						var btn = e.target && e.target.closest ? e.target.closest('.weblo-fakturownia-restore-default-template') : null;
						if (!btn) return;
						var editorId = btn.getAttribute('data-editor-id');
						var defJson = btn.getAttribute('data-default-json') || '""';
						var def = '';
						try { def = JSON.parse(defJson); } catch (err) { def = ''; }
						if (!editorId) return;

						// 1) Update textarea value (Quicktags / Text tab)
						var textarea = document.getElementById(editorId);
						if (textarea) textarea.value = def;

						// 2) Update TinyMCE (Visual tab)
						if (window.tinyMCE && tinyMCE.get(editorId)) {
							tinyMCE.get(editorId).setContent(def.replace(/\n/g, '<br />'));
							tinyMCE.get(editorId).save();
						}
					});
				})();
				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Zapis ustawień:
	 * - gdy połączenie NIE jest potwierdzone, aktualizujemy tylko pola z zakładki "Connection"
	 *   (żeby nie wyzerować ukrytych pól z pozostałych zakładek),
	 * - w pozostałych przypadkach używamy standardowego mechanizmu WooCommerce.
	 *
	 * @return bool
	 */
	public function process_admin_options() {
		$connection_ok = $this->is_connection_ok();
		if ( $connection_ok ) {
			return parent::process_admin_options();
		}

		$this->init_settings();
		$post_data = $this->get_post_data();
		$tabs      = $this->get_tabs_definition();
		$editable_keys = isset( $tabs['connection']['fields'] ) && is_array( $tabs['connection']['fields'] )
			? $tabs['connection']['fields']
			: array();

		foreach ( $editable_keys as $key ) {
			if ( ! isset( $this->form_fields[ $key ] ) ) {
				continue;
			}
			$field = $this->form_fields[ $key ];
			if ( 'title' === $this->get_field_type( $field ) ) {
				continue;
			}
			try {
				$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
			} catch ( Exception $e ) {
				$this->add_error( $e->getMessage() );
			}
		}

		$option_key = $this->get_option_key();
		do_action( 'woocommerce_update_option', array( 'id' => $option_key ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		return update_option( $option_key, apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
	}

	/**
	 * Renderuje opcje admina wraz z przyciskiem testu połączenia.
	 */
	public function admin_options() {
		echo '<div class="weblo-fakturownia-admin">';
		echo '<div class="weblo-fakturownia-admin-head">';
		echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		echo '<p>' . esc_html( $this->get_method_description() ) . '</p>';
		echo '</div>';

		// Kontener na wynik testu połączenia.
		echo '<div id="weblo-fakturownia-test-result" class="weblo-fakturownia-test-result"></div>';
		echo '<div id="weblo-fakturownia-admin-marker" style="display:none" data-id="' . esc_attr( $this->id ) . '"></div>';

		$tabs        = $this->get_tabs_definition();
		$connection_ok = $this->is_connection_ok();

		if ( ! $connection_ok ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Test the connection to unlock the remaining tabs.', 'weblo-fakturownia' ) . '</p></div>';
		}

		echo '<div class="weblo-fakturownia-tabs" data-connection-ok="' . esc_attr( $connection_ok ? '1' : '0' ) . '">';
		echo '<div class="weblo-fakturownia-tabbar" role="tablist" aria-label="' . esc_attr__( 'Fakturownia by Weblo integration settings', 'weblo-fakturownia' ) . '">';
		foreach ( $tabs as $tab_key => $tab_def ) {
			if ( ! $connection_ok && 'connection' !== $tab_key ) {
				continue;
			}

			$is_active = ( 'connection' === $tab_key );
			echo '<button type="button" class="weblo-fakturownia-tab' . ( $is_active ? ' is-active' : '' ) . '" data-tab="' . esc_attr( $tab_key ) . '" role="tab" aria-selected="' . esc_attr( $is_active ? 'true' : 'false' ) . '">';
			echo esc_html( $tab_def['label'] );
			echo '</button>';
		}
		echo '</div>';

		// Panele.
		foreach ( $tabs as $tab_key => $tab_def ) {
			if ( ! $connection_ok && 'connection' !== $tab_key ) {
				continue;
			}

			$style = ( 'connection' === $tab_key ) ? '' : 'display:none;';
			echo '<div class="weblo-fakturownia-tab-panel" data-tab="' . esc_attr( $tab_key ) . '" style="' . esc_attr( $style ) . '">';
			echo '<div class="weblo-fakturownia-panel-card">';

			$fields_for_tab = array();
			foreach ( $tab_def['fields'] as $field_key ) {
				if ( isset( $this->form_fields[ $field_key ] ) ) {
					$fields_for_tab[ $field_key ] = $this->form_fields[ $field_key ];
				}
			}

			echo '<table class="form-table">';
			$this->generate_settings_html( $fields_for_tab );
			echo '</table>';

			if ( 'bulk' === $tab_key ) {
				$bulk_nonce_invoices    = wp_create_nonce( 'weblo_fakturownia_bulk_issue_invoices' );
				$bulk_nonce_corrections = wp_create_nonce( 'weblo_fakturownia_bulk_issue_invoices' );

				// Block: bulk invoices.
				echo '<hr class="weblo-fakturownia-divider">';
				echo '<h3 style="margin:10px 0 6px;">' . esc_html__( 'Bulk issuing of missing invoices', 'weblo-fakturownia' ) . '</h3>';
				echo '<p style="margin:0 0 10px;">' . esc_html__( 'This operation runs in the background (AJAX) and issues invoices in batches to avoid overloading the server.', 'weblo-fakturownia' ) . '</p>';

				echo '<p style="margin:0 0 10px;">';
				echo '<button type="button" class="button button-primary" id="weblo-fakturownia-bulk-start" data-nonce="' . esc_attr( $bulk_nonce_invoices ) . '">' . esc_html__( 'Issue missing invoices', 'weblo-fakturownia' ) . '</button> ';
				echo '<button type="button" class="button" id="weblo-fakturownia-bulk-stop" disabled="disabled">' . esc_html__( 'Stop', 'weblo-fakturownia' ) . '</button>';
				echo '</p>';

				echo '<div id="weblo-fakturownia-bulk-status" style="margin:10px 0;"></div>';
				echo '<div id="weblo-fakturownia-bulk-progress" style="max-width:520px;display:none;">';
				echo '<div style="background:#e5e5e5;border-radius:4px;overflow:hidden;height:14px;">';
				echo '<div id="weblo-fakturownia-bulk-progress-bar" style="width:0%;height:14px;background:#2271b1;"></div>';
				echo '</div>';
				echo '<div id="weblo-fakturownia-bulk-progress-text" style="margin-top:6px;font-size:12px;color:#50575e;"></div>';
				echo '</div>';

				// Block: bulk corrections.
				echo '<hr class="weblo-fakturownia-divider">';
				echo '<h3 style="margin:10px 0 6px;">' . esc_html__( 'Bulk issuing of missing corrections', 'weblo-fakturownia' ) . '</h3>';
				echo '<p style="margin:0 0 10px;">' . esc_html__( 'This operation runs in the background (AJAX) and issues corrections in batches based on existing invoices and refunds.', 'weblo-fakturownia' ) . '</p>';

				echo '<p style="margin:0 0 10px;">';
				echo '<button type="button" class="button button-primary" id="weblo-fakturownia-bulk-corr-start" data-nonce="' . esc_attr( $bulk_nonce_corrections ) . '">' . esc_html__( 'Issue missing corrections', 'weblo-fakturownia' ) . '</button> ';
				echo '<button type="button" class="button" id="weblo-fakturownia-bulk-corr-stop" disabled="disabled">' . esc_html__( 'Stop', 'weblo-fakturownia' ) . '</button>';
				echo '</p>';

				echo '<div id="weblo-fakturownia-bulk-corr-status" style="margin:10px 0;"></div>';
				echo '<div id="weblo-fakturownia-bulk-corr-progress" style="max-width:520px;display:none;">';
				echo '<div style="background:#e5e5e5;border-radius:4px;overflow:hidden;height:14px;">';
				echo '<div id="weblo-fakturownia-bulk-corr-progress-bar" style="width:0%;height:14px;background:#2271b1;"></div>';
				echo '</div>';
				echo '<div id="weblo-fakturownia-bulk-corr-progress-text" style="margin-top:6px;font-size:12px;color:#50575e;"></div>';
				echo '</div>';
			}

			if ( 'logs' === $tab_key ) {
				$logs_nonce        = wp_create_nonce( 'weblo_fakturownia_fetch_logs' );
				$clear_debug_nonce = wp_create_nonce( 'weblo_fakturownia_clear_debug_log' );
				echo '<hr class="weblo-fakturownia-divider">';
				echo '<h3 style="margin:10px 0 6px;">' . esc_html__( 'Integration errors (logs)', 'weblo-fakturownia' ) . '</h3>';
				echo '<p style="margin:0 0 10px;">' . esc_html__( 'Below you can find a list of recent errors saved by the plugin. Details for a specific error are also visible in the order metabox.', 'weblo-fakturownia' ) . '</p>';

				echo '<div id="weblo-fakturownia-logs" data-nonce="' . esc_attr( $logs_nonce ) . '">';
				echo '<div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin:10px 0;">';
				echo '<label style="display:block;">';
				echo '<span style="display:block;font-size:12px;color:#50575e;margin-bottom:4px;">' . esc_html__( 'Type', 'weblo-fakturownia' ) . '</span>';
				echo '<select id="weblo-fakturownia-logs-type">';
				echo '<option value="">' . esc_html__( 'All', 'weblo-fakturownia' ) . '</option>';
				echo '<option value="auto_issue">auto_issue</option>';
				echo '<option value="auto_correction">auto_correction</option>';
				echo '<option value="bulk_invoice">bulk_invoice</option>';
				echo '<option value="bulk_correction">bulk_correction</option>';
				echo '<option value="metabox_issue_invoice">metabox_issue_invoice</option>';
				echo '<option value="metabox_issue_correction">metabox_issue_correction</option>';
				echo '</select>';
				echo '</label>';

				echo '<label style="display:block;">';
				echo '<span style="display:block;font-size:12px;color:#50575e;margin-bottom:4px;">' . esc_html__( 'Limit', 'weblo-fakturownia' ) . '</span>';
				echo '<select id="weblo-fakturownia-logs-limit">';
				echo '<option value="50">50</option>';
				echo '<option value="100" selected>100</option>';
				echo '<option value="200">200</option>';
				echo '<option value="500">500</option>';
				echo '</select>';
				echo '</label>';

				echo '<button type="button" class="button" id="weblo-fakturownia-logs-refresh">' . esc_html__( 'Refresh', 'weblo-fakturownia' ) . '</button>';
				echo '<button type="button" class="button button-secondary" id="weblo-fakturownia-logs-clear" data-nonce="' . esc_attr( wp_create_nonce( 'weblo_fakturownia_clear_db_logs' ) ) . '">' . esc_html__( 'Clear database logs', 'weblo-fakturownia' ) . '</button>';
				echo '</div>';

				echo '<div id="weblo-fakturownia-logs-result"></div>';
				echo '<p style="margin-top:10px;">';
				echo '<button type="button" class="button button-secondary" id="weblo-fakturownia-debug-clear" data-nonce="' . esc_attr( $clear_debug_nonce ) . '">';
				echo esc_html__( 'Clear debug.log file', 'weblo-fakturownia' );
				echo '</button>';
				echo ' <span style="font-size:11px;color:#50575e;">' . esc_html__( 'This removes the local debug.log file created by the plugin. It does not affect the integration error logs stored in the database.', 'weblo-fakturownia' ) . '</span>';
				echo '</p>';
				echo '</div>';
			}

			echo '</div>';
			echo '</div>';
		}

		echo '</div>';

		// Test connection button.
		$nonce = wp_create_nonce( 'weblo_fakturownia_test_connection' );

		echo '<p class="weblo-fakturownia-test-connection-wrap">';
		echo '<button type="button" class="button button-secondary" id="weblo-fakturownia-test-connection" data-nonce="' . esc_attr( $nonce ) . '">';
		echo esc_html__( 'Test connection', 'weblo-fakturownia' );
		echo '</button>';
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Kolejka skryptów w panelu admina tylko na stronie ustawień WooCommerce -> Integracja.
	 *
	 * @param string $hook_suffix Aktualny hook ekranu.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Strona ustawień WooCommerce.
		if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
			return;
		}

		// Sprawdź, czy jesteśmy na zakładce Integracja oraz tej konkretnej integracji.
		$current_tab     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'integration' !== $current_tab || $this->id !== $current_section ) {
			return;
		}

		// Bezpieczne wyliczenie URL zasobu względem głównego pliku wtyczki.
		$plugin_main_file = trailingslashit( dirname( __FILE__, 2 ) ) . 'fakturownia.php';
		$assets_version   = $this->get_assets_version();

		wp_enqueue_script(
			'weblo-fakturownia-admin',
			plugins_url( 'assets/js/weblo-fakturownia-admin.js', $plugin_main_file ),
			array( 'jquery' ),
			$assets_version,
			true
		);
		wp_enqueue_style(
			'weblo-fakturownia-admin',
			plugins_url( 'assets/css/weblo-fakturownia-admin.css', $plugin_main_file ),
			array(),
			$assets_version
		);

		// Przekazanie URL do AJAX (choć w JS możemy użyć globalnego ajaxurl).
		wp_localize_script(
			'weblo-fakturownia-admin',
			'WebloFakturowniaSettings',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'assets_version' => $assets_version,
				'dev_mode' => ( 'yes' === $this->get_option( 'weblo_fakturownia_dev_mode', 'no' ) ),
				'nonce_fetch_logs' => wp_create_nonce( 'weblo_fakturownia_fetch_logs' ),
				'i18n' => array(
					'loading_logs' => __( 'Loading logs...', 'weblo-fakturownia' ),
					'error_loading_logs' => __( 'Error while loading logs.', 'weblo-fakturownia' ),
					'no_logs' => __( 'No logs to display.', 'weblo-fakturownia' ),
					'ajax_error_loading_logs' => __( 'AJAX error while loading logs.', 'weblo-fakturownia' ),
					'testing_connection' => __( 'Testing connection...', 'weblo-fakturownia' ),
					'unexpected_connection_error' => __( 'An unexpected error occurred while testing the connection.', 'weblo-fakturownia' ),
					'progress_format' => __( 'Progress: {processed}/{total} | OK: {ok} | Errors: {errors}', 'weblo-fakturownia' ),
					'generic_error' => __( 'Error.', 'weblo-fakturownia' ),
					'finished' => __( 'Finished.', 'weblo-fakturownia' ),
					'ajax_error' => __( 'AJAX error.', 'weblo-fakturownia' ),
					'stopping' => __( 'Stopping...', 'weblo-fakturownia' ),
					'confirm_clear_debug' => __( 'Are you sure you want to remove the debug.log file? This action cannot be undone.', 'weblo-fakturownia' ),
					'operation_finished' => __( 'Operation finished.', 'weblo-fakturownia' ),
					'ajax_error_clearing_debug' => __( 'AJAX error while clearing debug.log file.', 'weblo-fakturownia' ),
					'confirm_clear_db_logs' => __( 'Are you sure you want to remove all integration logs from the database?', 'weblo-fakturownia' ),
					'ajax_error_clearing_db_logs' => __( 'AJAX error while clearing database logs.', 'weblo-fakturownia' ),
				),
			)
		);
	}

	/**
	 * AJAX – test połączenia z Fakturownią.
	 */
	public function ajax_test_connection() {
		// Sprawdzenie nonce.
		check_ajax_referer( 'weblo_fakturownia_test_connection', 'nonce' );

		// Sprawdzenie uprawnień.
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'You do not have permission to perform this action.', 'weblo-fakturownia' ),
				)
			);
		}

		$client = Weblo_Fakturownia_Plugin::instance()->get_api_client();
		$res    = $client->test_connection();

		// Zapisz wynik testu połączenia w sposób odporny na cache/stare instancje ustawień.
		$connection_ok = ! empty( $res['success'] ) ? 'yes' : 'no';
		$this->update_option( 'weblo_fakturownia_connection_ok', $connection_ok );
		$all_settings = get_option( 'woocommerce_weblo_fakturownia_settings', array() );
		if ( ! is_array( $all_settings ) ) {
			$all_settings = array();
		}
		$all_settings['weblo_fakturownia_connection_ok'] = $connection_ok;
		update_option( 'woocommerce_weblo_fakturownia_settings', $all_settings, 'yes' );

		wp_send_json(
			array(
				'success' => (bool) $res['success'],
				'message' => (string) ( $res['message'] ?? '' ),
			)
		);
	}

	/**
	 * AJAX: zwraca logi z tabeli wp_weblo_fakturownia_logs.
	 */
	public function ajax_fetch_logs() {
		check_ajax_referer( 'weblo_fakturownia_fetch_logs', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'You do not have permission.', 'weblo-fakturownia' ),
				)
			);
		}

		global $wpdb;
		$table = $wpdb->prefix . 'weblo_fakturownia_logs';

		$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$limit = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 100;
		$limit = min( 500, max( 1, $limit ) );

		$where_sql = '1=1';
		$params    = array();

		if ( '' !== $type ) {
			$where_sql .= ' AND type = %s';
			$params[] = $type;
		}

		$sql = "SELECT id, order_id, type, error, created_at FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT {$limit}";
		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = is_array( $rows ) ? $rows : array();

		// Pomijamy logi dla zamówień w koszu (status "trash").
		// W praktyce nie chcemy ich już przetwarzać ani eksponować w UI.
		if ( ! empty( $rows ) ) {
			$filtered = array();
			foreach ( $rows as $row ) {
				$order_id = isset( $row['order_id'] ) ? (int) $row['order_id'] : 0;
				if ( $order_id > 0 ) {
					$order = wc_get_order( $order_id );
					// Jeśli zamówienie zostało usunięte (brak obiektu) lub jest w koszu, pomijamy wpis w UI.
					if ( ! $order ) {
						continue;
					}
					if ( method_exists( $order, 'get_status' ) ) {
						$status = (string) $order->get_status();
						if ( 'trash' === $status ) {
							continue;
						}
					}
				}
				$filtered[] = $row;
			}
			$rows = $filtered;
		}

		wp_send_json(
			array(
				'success' => true,
				'rows'    => $rows,
			)
		);
	}

	/**
	 * AJAX: usuwa plik debug.log używany wyłącznie do lokalnego debugowania.
	 */
	public function ajax_clear_debug_log() {
		check_ajax_referer( 'weblo_fakturownia_clear_debug_log', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'You do not have permission.', 'weblo-fakturownia' ),
				)
			);
		}

		$dir  = trailingslashit( dirname( __FILE__, 2 ) ) . 'logs';
		$file = trailingslashit( $dir ) . 'debug.log';

		if ( file_exists( $file ) ) {
			if ( ! @unlink( $file ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'Failed to remove debug.log file.', 'weblo-fakturownia' ),
					)
				);
			}
		}

		wp_send_json(
			array(
				'success' => true,
				'message' => __( 'debug.log file has been removed.', 'weblo-fakturownia' ),
			)
		);
	}

	/**
	 * AJAX: usuwa wszystkie logi integracji z tabeli bazy danych.
	 */
	public function ajax_clear_db_logs() {
		check_ajax_referer( 'weblo_fakturownia_clear_db_logs', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'You do not have permission.', 'weblo-fakturownia' ),
				)
			);
		}

		global $wpdb;
		$table = $wpdb->prefix . 'weblo_fakturownia_logs';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( "DELETE FROM {$table}" );
		if ( false === $result ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Failed to clear database logs.', 'weblo-fakturownia' ),
				)
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'message' => __( 'Database logs have been cleared.', 'weblo-fakturownia' ),
			)
		);
	}

	/**
	 * AJAX: masowe wystawianie brakujących faktur (batch).
	 */
	public function ajax_bulk_issue_invoices() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'weblo_fakturownia_bulk_issue_invoices' ) ) {
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'Invalid security token.', 'weblo-fakturownia' ),
					)
				);
			}

			if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'You do not have permission.', 'weblo-fakturownia' ),
					)
				);
			}

			$settings = Weblo_Fakturownia_Plugin::instance()->get_settings();
			if ( empty( $settings['weblo_fakturownia_connection_ok'] ) || 'yes' !== $settings['weblo_fakturownia_connection_ok'] ) {
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'Connection with Fakturownia has not been confirmed. Please test the connection on the "Connection" tab.', 'weblo-fakturownia' ),
					)
				);
			}

			$step = isset( $_POST['step'] ) ? sanitize_text_field( wp_unslash( $_POST['step'] ) ) : 'run';
			$user_id       = get_current_user_id();
			$transient_key = 'weblo_fakturownia_bulk_' . $user_id;

			if ( 'stop' === $step ) {
				delete_transient( $transient_key );
				wp_send_json(
					array(
						'success' => true,
						'done'    => true,
						'message' => __( 'Stopped.', 'weblo-fakturownia' ),
					)
				);
			}

			$status     = isset( $settings['weblo_fakturownia_bulk_invoice_statuses'] ) ? (string) $settings['weblo_fakturownia_bulk_invoice_statuses'] : '';
			$date_mode  = isset( $settings['weblo_fakturownia_bulk_invoice_date'] ) ? (string) $settings['weblo_fakturownia_bulk_invoice_date'] : 'today';
			$batch_size = isset( $settings['weblo_fakturownia_bulk_batch_size'] ) ? (int) $settings['weblo_fakturownia_bulk_batch_size'] : 20;
			if ( $batch_size <= 0 ) {
				$batch_size = 20;
			}
			$batch_size = min( 100, max( 1, $batch_size ) );
			$job = get_transient( $transient_key );

			if ( 'start' === $step || ! is_array( $job ) ) {
				// Ustal total w miarę tanio przez paginate.
				$args = array(
					'return'   => 'ids',
					'paginate' => true,
					'limit'    => 1,
					'orderby'  => 'ID',
					'order'    => 'ASC',
					'type'     => 'shop_order',
					'status'   => $status ? array( $status ) : array_keys( wc_get_order_statuses() ),
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'relation' => 'AND',
						array(
							'relation' => 'OR',
							array(
								'key'     => '_weblo_fakturownia_invoice_id',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => '_weblo_fakturownia_invoice_id',
								'value'   => '',
								'compare' => '=',
							),
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => '_weblo_fakturownia_last_error',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => '_weblo_fakturownia_last_error',
								'value'   => '',
								'compare' => '=',
							),
						),
					),
				);
				$results = wc_get_orders( $args );
				$total   = isset( $results->total ) ? (int) $results->total : 0;

				$job = array(
					'total'     => $total,
					'processed' => 0,
					'success'   => 0,
					'failed'    => 0,
					'status'    => $status,
					'date_mode' => $date_mode,
					'batch'     => $batch_size,
					'started'   => time(),
				);
				set_transient( $transient_key, $job, HOUR_IN_SECONDS );

			}

		// Pobierz kolejną paczkę "brakujących" (bez offsetów, żeby nie psuć kolejki przy zmianach).
		$query_args = array(
			'return'  => 'ids',
			'limit'   => (int) $job['batch'],
			'orderby' => 'ID',
			'order'   => 'ASC',
			'type'    => 'shop_order',
			'status'  => $job['status'] ? array( $job['status'] ) : array_keys( wc_get_order_statuses() ),
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => '_weblo_fakturownia_invoice_id',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_weblo_fakturownia_invoice_id',
						'value'   => '',
						'compare' => '=',
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_weblo_fakturownia_last_error',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_weblo_fakturownia_last_error',
						'value'   => '',
						'compare' => '=',
					),
				),
			),
		);

		$order_ids = wc_get_orders( $query_args );
		if ( empty( $order_ids ) ) {
			delete_transient( $transient_key );
			wp_send_json(
				array(
					'success'       => true,
					'done'          => true,
					'processed'     => (int) $job['processed'],
					'total'         => (int) $job['total'],
					'success_count' => (int) $job['success'],
					'failed_count'  => (int) $job['failed'],
					'message'       => __( 'Finished – no more orders to issue invoices for.', 'weblo-fakturownia' ),
				)
			);
		}

		$client = Weblo_Fakturownia_Plugin::instance()->get_api_client();

		foreach ( $order_ids as $oid ) {
			$order = wc_get_order( $oid );
			if ( ! $order ) {
				$job['processed']++;
				$job['failed']++;
				continue;
			}

			$issue_date_override = current_time( 'Y-m-d' );
			if ( 'status_date' === (string) $job['date_mode'] ) {
				// Przy masowych operacjach nie mamy historycznej daty wejścia w status – używamy date_modified jako proxy.
				$modified = $order->get_date_modified();
				$issue_date_override = $modified ? $modified->date_i18n( 'Y-m-d' ) : current_time( 'Y-m-d' );
			} elseif ( 'today' === (string) $job['date_mode'] ) {
				$issue_date_override = current_time( 'Y-m-d' );
			}

			$res = $client->create_invoice( (int) $oid, $issue_date_override );

			if ( ! empty( $res['success'] ) ) {
				if ( ! empty( $res['invoice_id'] ) ) {
					$order->update_meta_data( '_weblo_fakturownia_invoice_id', $res['invoice_id'] );
				}
				if ( ! empty( $res['invoice_number'] ) ) {
					$order->update_meta_data( '_weblo_fakturownia_invoice_number', $res['invoice_number'] );
				}
				$order->delete_meta_data( '_weblo_fakturownia_last_error' );
				$order->save();

				$job['success']++;
			} else {
				$error_value = is_string( $res['error'] ?? null ) ? $res['error'] : wp_json_encode( $res['error'] ?? 'Error' );
				$order->update_meta_data( '_weblo_fakturownia_last_error', $error_value );
				$order->save();

				$job['failed']++;
			}

			$job['processed']++;
		}

		set_transient( $transient_key, $job, HOUR_IN_SECONDS );

			wp_send_json(
				array(
					'success'       => true,
					'done'          => false,
					'processed'     => (int) $job['processed'],
					'total'         => (int) $job['total'],
					'success_count' => (int) $job['success'],
					'failed_count'  => (int) $job['failed'],
					'batch'         => (int) $job['batch'],
				)
			);
		} catch ( Throwable $e ) {
			if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
				weblo_fakturownia_debug_log(
					array(
						'event'   => 'bulk_invoice_exception',
						'message' => $e->getMessage(),
						'file'    => $e->getFile(),
						'line'    => $e->getLine(),
					),
					'bulk'
				);
			}

			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Unexpected server error during bulk operation.', 'weblo-fakturownia' ),
				)
			);
		}
	}

	/**
	 * AJAX: masowe wystawianie brakujących korekt (batch).
	 */
	public function ajax_bulk_issue_corrections() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'weblo_fakturownia_bulk_issue_invoices' ) ) {
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'Invalid security token.', 'weblo-fakturownia' ),
					)
				);
			}

			if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'You do not have permission.', 'weblo-fakturownia' ),
					)
				);
			}

			$settings = Weblo_Fakturownia_Plugin::instance()->get_settings();
			if ( empty( $settings['weblo_fakturownia_connection_ok'] ) || 'yes' !== $settings['weblo_fakturownia_connection_ok'] ) {
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'Connection with Fakturownia has not been confirmed. Please test the connection on the "Connection" tab.', 'weblo-fakturownia' ),
					)
				);
			}

			$step = isset( $_POST['step'] ) ? sanitize_text_field( wp_unslash( $_POST['step'] ) ) : 'run';
			$user_id       = get_current_user_id();
			$transient_key = 'weblo_fakturownia_bulk_corr_' . $user_id;

			if ( 'stop' === $step ) {
				delete_transient( $transient_key );
				wp_send_json(
					array(
						'success' => true,
						'done'    => true,
						'message' => __( 'Stopped.', 'weblo-fakturownia' ),
					)
				);
			}

			$status     = isset( $settings['weblo_fakturownia_bulk_correction_statuses'] ) ? (string) $settings['weblo_fakturownia_bulk_correction_statuses'] : '';
			$date_mode  = isset( $settings['weblo_fakturownia_bulk_correction_date'] ) ? (string) $settings['weblo_fakturownia_bulk_correction_date'] : 'today';
			$batch_size = isset( $settings['weblo_fakturownia_bulk_batch_size'] ) ? (int) $settings['weblo_fakturownia_bulk_batch_size'] : 20;
			if ( $batch_size <= 0 ) {
				$batch_size = 20;
			}
			$batch_size = min( 100, max( 1, $batch_size ) );

		$job = get_transient( $transient_key );

		if ( 'start' === $step || ! is_array( $job ) ) {
			// Ustal dokładny total: tylko zamówienia spełniające warunki + posiadające co najmniej jeden refund.
			$base_query = array(
				'return'   => 'ids',
				'paginate' => true,
				'limit'    => 200,
				'page'     => 1,
				'orderby'  => 'ID',
				'order'    => 'ASC',
				'type'     => 'shop_order',
				'status'   => $status ? array( $status ) : array_keys( wc_get_order_statuses() ),
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					// Wymagana faktura bazowa.
					array(
						'key'     => '_weblo_fakturownia_invoice_id',
						'value'   => '',
						'compare' => '!=',
					),
					// Pomijamy zamówienia, które wcześniej oznaczyliśmy jako "skip" (np. brak refundów).
					array(
						'relation' => 'OR',
						array(
							'key'     => '_weblo_fakturownia_bulk_correction_skip',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_weblo_fakturownia_bulk_correction_skip',
							'value'   => '',
							'compare' => '=',
						),
					),
					// Brak korekty.
					array(
						'relation' => 'OR',
						array(
							'key'     => '_weblo_fakturownia_correction_id',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_weblo_fakturownia_correction_id',
							'value'   => '',
							'compare' => '=',
						),
					),
					// Brak błędu korekty.
					array(
						'relation' => 'OR',
						array(
							'key'     => '_weblo_fakturownia_correction_last_error',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_weblo_fakturownia_correction_last_error',
							'value'   => '',
							'compare' => '=',
						),
					),
				),
			);

			$total_candidates   = 0;
			$total_with_refunds = 0;

			do {
				$results = wc_get_orders( $base_query );
				$ids     = is_object( $results ) && isset( $results->orders ) ? (array) $results->orders : (array) $results;
				$total_candidates = is_object( $results ) && isset( $results->total ) ? (int) $results->total : $total_candidates;

				foreach ( $ids as $oid ) {
					$order = wc_get_order( (int) $oid );
					if ( ! $order ) {
						continue;
					}
					$refunds = $order->get_refunds();
					if ( ! empty( $refunds ) ) {
						$total_with_refunds++;
					}
				}

				$base_query['page'] = (int) $base_query['page'] + 1;
				$max_pages = is_object( $results ) && isset( $results->max_num_pages ) ? (int) $results->max_num_pages : 1;
			} while ( $base_query['page'] <= $max_pages );

			$total = $total_with_refunds;

			$job = array(
				'total'     => $total,
				'processed' => 0,
				'success'   => 0,
				'failed'    => 0,
				'status'    => $status,
				'date_mode' => $date_mode,
				'batch'     => $batch_size,
				'started'   => time(),
			);
			set_transient( $transient_key, $job, HOUR_IN_SECONDS );

		}

		$query_args = array(
			'return'  => 'ids',
			'limit'   => (int) $job['batch'],
			'orderby' => 'ID',
			'order'   => 'ASC',
			'type'    => 'shop_order',
			'status'  => $job['status'] ? array( $job['status'] ) : array_keys( wc_get_order_statuses() ),
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'     => '_weblo_fakturownia_invoice_id',
					'value'   => '',
					'compare' => '!=',
				),
				// Pomijamy zamówienia, które wcześniej oznaczyliśmy jako "skip" (np. brak refundów).
				array(
					'relation' => 'OR',
					array(
						'key'     => '_weblo_fakturownia_bulk_correction_skip',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_weblo_fakturownia_bulk_correction_skip',
						'value'   => '',
						'compare' => '=',
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_weblo_fakturownia_correction_id',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_weblo_fakturownia_correction_id',
						'value'   => '',
						'compare' => '=',
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_weblo_fakturownia_correction_last_error',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_weblo_fakturownia_correction_last_error',
						'value'   => '',
						'compare' => '=',
					),
				),
			),
		);

		$order_ids = wc_get_orders( $query_args );
		if ( empty( $order_ids ) ) {
			delete_transient( $transient_key );
			$total_out = max( (int) $job['total'], (int) $job['processed'] );
			$message = __( 'Finished – no more orders to issue corrections for.', 'weblo-fakturownia' );
			if ( (int) $job['processed'] === 0 && (int) $job['success'] === 0 && (int) $job['failed'] === 0 ) {
				$message = __( 'No eligible orders found for bulk corrections. Note: the selected status filters orders (not refunds). Orders must have an invoice and at least one refund.', 'weblo-fakturownia' );
			}
			wp_send_json(
				array(
					'success'       => true,
					'done'          => true,
					'processed'     => (int) $job['processed'],
					'total'         => $total_out,
					'success_count' => (int) $job['success'],
					'failed_count'  => (int) $job['failed'],
					'message'       => $message,
				)
			);
		}

		$client  = Weblo_Fakturownia_Plugin::instance()->get_api_client();
		$plugin  = Weblo_Fakturownia_Plugin::instance();

		foreach ( $order_ids as $oid ) {
			$order = wc_get_order( $oid );
			if ( ! $order ) {
				$job['processed']++;
				$job['failed']++;
				continue;
			}

			$refunds = $order->get_refunds();
			if ( empty( $refunds ) ) {
				// Brak refundu – oznacz jako "skip" (bez pokazywania błędu w metaboxie),
				// żeby masówka nie wracała do tego zamówienia w kolejnych batchach.
				$order->update_meta_data( '_weblo_fakturownia_bulk_correction_skip', 'no_refunds' );
				// Jeśli wcześniej zapisaliśmy marker jako "error", usuń go, żeby nie straszyć w UI.
				$prev = (string) $order->get_meta( '_weblo_fakturownia_correction_last_error', true );
				if ( __( 'No refunds found for this order.', 'weblo-fakturownia' ) === $prev ) {
					$order->delete_meta_data( '_weblo_fakturownia_correction_last_error' );
				}
				$order->save();
				$job['processed']++;
				continue;
			}

			$refund     = $refunds[0];
			$refund_id  = $refund->get_id();
			$issue_date = current_time( 'Y-m-d' );
			if ( 'refund_date' === (string) $job['date_mode'] ) {
				$rd = $refund->get_date_created();
				$issue_date = $rd ? $rd->date_i18n( 'Y-m-d' ) : current_time( 'Y-m-d' );
			}

			$options = array(
				'shipping_mode'   => (string) ( $settings['weblo_fakturownia_correction_shipping_mode'] ?? 'as_order' ),
				'shipping_amount' => (float) ( $settings['weblo_fakturownia_correction_shipping_amount'] ?? 0 ),
				'correction_mode' => (string) ( $settings['weblo_fakturownia_correction_mode'] ?? 'full' ),
				'reason'          => __( 'Order refund (bulk)', 'weblo-fakturownia' ),
			);

			$res = $client->create_correction( (int) $oid, (int) $refund_id, $issue_date, $options );

			if ( ! empty( $res['success'] ) ) {
				if ( ! empty( $res['invoice_id'] ) ) {
					$order->update_meta_data( '_weblo_fakturownia_correction_id', $res['invoice_id'] );
				}
				if ( ! empty( $res['invoice_number'] ) ) {
					$order->update_meta_data( '_weblo_fakturownia_correction_number', $res['invoice_number'] );
				}
				$order->delete_meta_data( '_weblo_fakturownia_correction_last_error' );
				$order->save();

				$job['success']++;
			} else {
				$error_value = is_string( $res['error'] ?? null ) ? $res['error'] : wp_json_encode( $res['error'] ?? 'Error' );
				$order->update_meta_data( '_weblo_fakturownia_correction_last_error', $error_value );
				$order->save();
				$plugin->report_error( (int) $oid, 'bulk_correction', $error_value );

				$job['failed']++;
			}

			$job['processed']++;
		}

		set_transient( $transient_key, $job, HOUR_IN_SECONDS );

		$total_out = max( (int) $job['total'], (int) $job['processed'] );
		wp_send_json(
			array(
				'success'       => true,
				'done'          => false,
				'processed'     => (int) $job['processed'],
				'total'         => $total_out,
				'success_count' => (int) $job['success'],
				'failed_count'  => (int) $job['failed'],
				'batch'         => (int) $job['batch'],
			)
		);
		} catch ( Throwable $e ) {
			if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
				weblo_fakturownia_debug_log(
					array(
						'event'   => 'bulk_correction_exception',
						'message' => $e->getMessage(),
						'file'    => $e->getFile(),
						'line'    => $e->getLine(),
					),
					'bulk'
				);
			}

			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Unexpected server error during bulk operation.', 'weblo-fakturownia' ),
				)
			);
		}
	}
}

