<?php

namespace Weblo\QuickReturns\Admin;

use Weblo\QuickReturns\Infrastructure\PostType\ReturnRequestPostType;
use Weblo\QuickReturns\Infrastructure\Repository\SettingsRepository;
use Weblo\QuickReturns\Support\Helpers;

class SettingsPage {

	public function register_hooks(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	public function add_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Quick Returns', 'quick-returns' ),
			__( 'Quick Returns', 'quick-returns' ),
			'manage_woocommerce',
			'quick-returns-settings',
			[ $this, 'render_page' ]
		);
	}

	public function enqueue_admin_assets( string $hook ): void {
		if ( 'woocommerce_page_quick-returns-settings' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'quick-returns-admin',
			QUICK_RETURNS_URL . 'assets/js/admin.js',
			[ 'wp-color-picker' ],
			Helpers::asset_version( 'assets/js/admin.js' ),
			true
		);
	}

	public function register_settings(): void {
		register_setting(
			'quick_returns_settings_group',
			SettingsRepository::OPTION_KEY,
			[ $this, 'sanitize' ]
		);
	}

	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return SettingsRepository::defaults();
		}

		$defaults = SettingsRepository::defaults();
		$output   = [];

		$output['accent_color'] = sanitize_hex_color( $input['accent_color'] ?? $defaults['accent_color'] ) ?: $defaults['accent_color'];
		$output['trigger_text'] = sanitize_text_field( $input['trigger_text'] ?? $defaults['trigger_text'] );
		$output['trigger_class'] = sanitize_text_field( $input['trigger_class'] ?? $defaults['trigger_class'] );
		$output['trigger_selectors'] = sanitize_textarea_field( $input['trigger_selectors'] ?? '' );
		$output['return_address'] = sanitize_textarea_field( $input['return_address'] ?? '' );
		$output['confirmation_message'] = sanitize_textarea_field( $input['confirmation_message'] ?? $defaults['confirmation_message'] );
		$output['excluded_products'] = sanitize_text_field( $input['excluded_products'] ?? '' );
		$output['excluded_categories'] = sanitize_text_field( $input['excluded_categories'] ?? '' );
		$output['auto_select_all'] = ! empty( $input['auto_select_all'] );
		$output['withdrawal_days'] = absint( $input['withdrawal_days'] ?? 14 );
		$output['email_customer_enabled'] = ! empty( $input['email_customer_enabled'] );
		$output['email_admin_enabled'] = ! empty( $input['email_admin_enabled'] );
		$output['email_customer_subject'] = sanitize_text_field( $input['email_customer_subject'] ?? $defaults['email_customer_subject'] );
		$output['email_admin_subject'] = sanitize_text_field( $input['email_admin_subject'] ?? $defaults['email_admin_subject'] );
		$output['email_status_change_enabled'] = ! empty( $input['email_status_change_enabled'] );
		$output['email_status_change_subject'] = sanitize_text_field( $input['email_status_change_subject'] ?? $defaults['email_status_change_subject'] );
		$output['email_status_change_message'] = sanitize_textarea_field( $input['email_status_change_message'] ?? $defaults['email_status_change_message'] );
		$output['intro_description'] = sanitize_textarea_field( $input['intro_description'] ?? $defaults['intro_description'] );
		$output['ship_back_notice'] = sanitize_textarea_field( $input['ship_back_notice'] ?? $defaults['ship_back_notice'] );
		$output['refund_hold_notice'] = sanitize_textarea_field( $input['refund_hold_notice'] ?? $defaults['refund_hold_notice'] );

		$reasons = [];
		if ( ! empty( $input['return_reasons'] ) && is_array( $input['return_reasons'] ) ) {
			foreach ( $input['return_reasons'] as $reason ) {
				$reason = sanitize_text_field( $reason );
				if ( $reason ) {
					$reasons[] = $reason;
				}
			}
		}
		$output['return_reasons'] = ! empty( $reasons ) ? $reasons : $defaults['return_reasons'];

		$statuses = [];
		if ( ! empty( $input['eligible_order_statuses'] ) && is_array( $input['eligible_order_statuses'] ) ) {
			foreach ( $input['eligible_order_statuses'] as $status ) {
				$status = sanitize_text_field( $status );
				if ( $status ) {
					$statuses[] = $status;
				}
			}
		}
		$output['eligible_order_statuses'] = ! empty( $statuses ) ? $statuses : $defaults['eligible_order_statuses'];

		return $output;
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings = SettingsRepository::get_all();
		$wc_statuses = wc_get_order_statuses();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Quick Returns Settings', 'quick-returns' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'quick_returns_settings_group' ); ?>

				<h2 class="title"><?php esc_html_e( 'Appearance', 'quick-returns' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="qr_accent_color"><?php esc_html_e( 'Accent color', 'quick-returns' ); ?></label>
						</th>
						<td>
							<input type="text" id="qr_accent_color" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[accent_color]" value="<?php echo esc_attr( $settings['accent_color'] ); ?>" class="qr-color-picker" data-default-color="#F68B2F" />
							<p class="description"><?php esc_html_e( 'Primary accent color used in the return form UI.', 'quick-returns' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qr_intro_description"><?php esc_html_e( 'Intro description', 'quick-returns' ); ?></label>
						</th>
						<td>
							<textarea id="qr_intro_description" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[intro_description]" rows="3" class="large-text"><?php echo esc_textarea( $settings['intro_description'] ); ?></textarea>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Triggers', 'quick-returns' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="qr_trigger_text"><?php esc_html_e( 'Default trigger text', 'quick-returns' ); ?></label>
						</th>
						<td>
							<input type="text" id="qr_trigger_text" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[trigger_text]" value="<?php echo esc_attr( $settings['trigger_text'] ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qr_trigger_class"><?php esc_html_e( 'Default trigger CSS class', 'quick-returns' ); ?></label>
						</th>
						<td>
							<input type="text" id="qr_trigger_class" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[trigger_class]" value="<?php echo esc_attr( $settings['trigger_class'] ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qr_trigger_selectors"><?php esc_html_e( 'CSS selectors for modal trigger', 'quick-returns' ); ?></label>
						</th>
						<td>
							<textarea id="qr_trigger_selectors" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[trigger_selectors]" rows="4" class="large-text code"><?php echo esc_textarea( $settings['trigger_selectors'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One selector per line, e.g. .open-return-modal', 'quick-returns' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Return reasons', 'quick-returns' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Reasons list', 'quick-returns' ); ?></th>
						<td>
							<div id="qr-reasons-list">
								<?php foreach ( $settings['return_reasons'] as $index => $reason ) : ?>
									<p>
										<input type="text" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[return_reasons][]" value="<?php echo esc_attr( $reason ); ?>" class="regular-text" />
									</p>
								<?php endforeach; ?>
							</div>
							<button type="button" class="button" id="qr-add-reason"><?php esc_html_e( 'Add reason', 'quick-returns' ); ?></button>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Eligibility', 'quick-returns' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Withdrawal period (days)', 'quick-returns' ); ?></th>
						<td>
							<input type="number" min="1" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[withdrawal_days]" value="<?php echo esc_attr( $settings['withdrawal_days'] ); ?>" class="small-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Eligible order statuses', 'quick-returns' ); ?></th>
						<td>
							<?php foreach ( $wc_statuses as $status_key => $status_label ) : ?>
								<?php $status = str_replace( 'wc-', '', $status_key ); ?>
								<label style="display:block;margin-bottom:4px;">
									<input type="checkbox" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[eligible_order_statuses][]" value="<?php echo esc_attr( $status ); ?>" <?php checked( in_array( $status, $settings['eligible_order_statuses'], true ) ); ?> />
									<?php echo esc_html( $status_label ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qr_excluded_products"><?php esc_html_e( 'Excluded product IDs', 'quick-returns' ); ?></label>
						</th>
						<td>
							<input type="text" id="qr_excluded_products" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[excluded_products]" value="<?php echo esc_attr( $settings['excluded_products'] ); ?>" class="large-text" />
							<p class="description"><?php esc_html_e( 'Comma-separated product IDs.', 'quick-returns' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qr_excluded_categories"><?php esc_html_e( 'Excluded category IDs', 'quick-returns' ); ?></label>
						</th>
						<td>
							<input type="text" id="qr_excluded_categories" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[excluded_categories]" value="<?php echo esc_attr( $settings['excluded_categories'] ); ?>" class="large-text" />
							<p class="description"><?php esc_html_e( 'Comma-separated category IDs.', 'quick-returns' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto-select all products', 'quick-returns' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[auto_select_all]" value="1" <?php checked( $settings['auto_select_all'] ); ?> />
								<?php esc_html_e( 'Pre-select all eligible products for logged-in customers in "return order" mode.', 'quick-returns' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Messages & emails', 'quick-returns' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="qr_return_address"><?php esc_html_e( 'Return address', 'quick-returns' ); ?></label>
						</th>
						<td>
							<textarea id="qr_return_address" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[return_address]" rows="3" class="large-text"><?php echo esc_textarea( $settings['return_address'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qr_confirmation_message"><?php esc_html_e( 'Confirmation message', 'quick-returns' ); ?></label>
						</th>
						<td>
							<textarea id="qr_confirmation_message" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[confirmation_message]" rows="4" class="large-text"><?php echo esc_textarea( $settings['confirmation_message'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Available placeholders: {order_number}, {request_number}', 'quick-returns' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Ship-back notice', 'quick-returns' ); ?></th>
						<td>
							<textarea name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[ship_back_notice]" rows="2" class="large-text"><?php echo esc_textarea( $settings['ship_back_notice'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Refund hold notice', 'quick-returns' ); ?></th>
						<td>
							<textarea name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[refund_hold_notice]" rows="2" class="large-text"><?php echo esc_textarea( $settings['refund_hold_notice'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Customer email', 'quick-returns' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[email_customer_enabled]" value="1" <?php checked( $settings['email_customer_enabled'] ); ?> />
								<?php esc_html_e( 'Send confirmation email to customer', 'quick-returns' ); ?>
							</label>
							<br />
							<input type="text" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[email_customer_subject]" value="<?php echo esc_attr( $settings['email_customer_subject'] ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'Email subject', 'quick-returns' ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Admin email', 'quick-returns' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[email_admin_enabled]" value="1" <?php checked( $settings['email_admin_enabled'] ); ?> />
								<?php esc_html_e( 'Send notification email to store admin', 'quick-returns' ); ?>
							</label>
							<br />
							<input type="text" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[email_admin_subject]" value="<?php echo esc_attr( $settings['email_admin_subject'] ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'Email subject', 'quick-returns' ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status change email', 'quick-returns' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[email_status_change_enabled]" value="1" <?php checked( $settings['email_status_change_enabled'] ); ?> />
								<?php esc_html_e( 'Send email to customer when return status is updated', 'quick-returns' ); ?>
							</label>
							<br />
							<input type="text" name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[email_status_change_subject]" value="<?php echo esc_attr( $settings['email_status_change_subject'] ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'Email subject', 'quick-returns' ); ?>" />
							<br /><br />
							<textarea name="<?php echo esc_attr( SettingsRepository::OPTION_KEY ); ?>[email_status_change_message]" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'Email message', 'quick-returns' ); ?>"><?php echo esc_textarea( $settings['email_status_change_message'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Available placeholders: {request_number}, {order_number}, {status}, {status_label}, {previous_status}, {previous_status_label}, {admin_note}', 'quick-returns' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Shortcodes', 'quick-returns' ); ?></h2>
			<p><code>[quick_returns_form]</code> — <?php esc_html_e( 'Full inline return wizard.', 'quick-returns' ); ?></p>
			<p><code>[quick_returns_trigger text="Start request" class="btn" mode="manual_select" order_id=""]</code> — <?php esc_html_e( 'Button that opens the return modal.', 'quick-returns' ); ?></p>
		</div>
		<?php
	}
}
