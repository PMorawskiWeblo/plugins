<?php
/**
 * Form manager admin template.
 *
 * @package FastForms
 *
 * @var string               $title Page title.
 * @var array<int, \WP_Post> $forms Lista formularzy.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error = isset( $_GET['error'] ) ? sanitize_key( wp_unslash( $_GET['error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap ff-admin-wrap">
	<h1><?php echo esc_html( $title ); ?></h1>

	<?php if ( 'missing_form' === $error ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Select a form before exporting.', 'fast-forms' ); ?></p></div>
	<?php endif; ?>

	<div class="ff-admin-card">
		<h2><?php esc_html_e( 'Export submissions to CSV', 'fast-forms' ); ?></h2>
		<p><?php esc_html_e( 'Download submissions for the selected form as CSV (UTF-8, semicolon separator).', 'fast-forms' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'ff_export_entries_csv' ); ?>
			<input type="hidden" name="action" value="ff_export_entries_csv" />

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ff-export-form-id"><?php esc_html_e( 'Form', 'fast-forms' ); ?> <span class="description">(<?php esc_html_e( 'required', 'fast-forms' ); ?>)</span></label></th>
					<td>
						<select name="ff_export_form_id" id="ff-export-form-id" required>
							<option value=""><?php esc_html_e( '— Select form —', 'fast-forms' ); ?></option>
							<?php foreach ( $forms as $form ) : ?>
								<option value="<?php echo esc_attr( (string) $form->ID ); ?>"><?php echo esc_html( $form->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ff-export-date-from"><?php esc_html_e( 'Date from', 'fast-forms' ); ?></label></th>
					<td><input type="date" id="ff-export-date-from" name="ff_export_date_from" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ff-export-date-to"><?php esc_html_e( 'Date to', 'fast-forms' ); ?></label></th>
					<td>
						<input type="date" id="ff-export-date-to" name="ff_export_date_to" />
						<p class="description"><?php esc_html_e( 'Filters by submission date (Submitted at). Leave empty to include all dates.', 'fast-forms' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Export CSV', 'fast-forms' ), 'primary', 'submit', false ); ?>
		</form>
	</div>
</div>
