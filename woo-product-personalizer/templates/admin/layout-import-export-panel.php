<?php
/**
 * Import / export panel below layouts list table.
 *
 * @package WooProductPersonalizer
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wpp-layout-import-export">
	<h3 class="wpp-layout-import-export__title"><?php esc_html_e( 'Transfer layouts', 'woo-product-personalizer' ); ?></h3>
	<p class="description">
		<?php esc_html_e( 'Export a layout from the row actions above, then import the .json file on another site. Images and masks are included in the export file.', 'woo-product-personalizer' ); ?>
	</p>
	<form method="post" enctype="multipart/form-data" class="wpp-layout-import-form">
		<?php wp_nonce_field( 'wpp_layout_import', 'wpp_layout_import_nonce' ); ?>
		<input type="hidden" name="wpp_layout_import_submit" value="1" />
		<label class="screen-reader-text" for="wpp_layout_import_file"><?php esc_html_e( 'Layout export file', 'woo-product-personalizer' ); ?></label>
		<input type="file" name="wpp_layout_import_file" id="wpp_layout_import_file" accept=".json,application/json" required />
		<?php submit_button( __( 'Import layout', 'woo-product-personalizer' ), 'secondary', 'submit', false ); ?>
	</form>
</div>
