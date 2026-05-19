<?php
/**
 * Product personalization panel.
 *
 * @package WooProductPersonalizer
 * @var ProductConfiguration $config
 * @var array<int,string>    $choices
 * @var array                $defaults
 */

use WooProductPersonalizer\Domain\Product\ProductConfiguration;

defined( 'ABSPATH' ) || exit;
?>
<div id="wpp_personalization_panel" class="panel woocommerce_options_panel hidden">
	<div class="options_group">
		<?php
		woocommerce_wp_checkbox(
			array(
				'id'          => '_wpp_enabled',
				'label'       => __( 'Enable personalization', 'woo-product-personalizer' ),
				'description' => __( 'Allow customers to personalize this product.', 'woo-product-personalizer' ),
				'value'       => $config->is_enabled() ? 'yes' : 'no',
			)
		);

		woocommerce_wp_select(
			array(
				'id'      => '_wpp_layout_id',
				'label'   => __( 'Assigned layout', 'woo-product-personalizer' ),
				'options' => array( '' => __( '— Select layout —', 'woo-product-personalizer' ) ) + $choices,
				'value'   => $config->get_layout_id() ?: '',
			)
		);

		woocommerce_wp_checkbox(
			array(
				'id'          => '_wpp_validation_enabled',
				'label'       => __( 'Require validation before add to cart', 'woo-product-personalizer' ),
				'description' => __( 'Disable add to cart until personalization is valid.', 'woo-product-personalizer' ),
				'value'       => $config->is_validation_enabled() ? 'yes' : 'no',
			)
		);

		woocommerce_wp_checkbox(
			array(
				'id'    => '_wpp_acceptance_required',
				'label' => __( 'Require acceptance checkbox', 'woo-product-personalizer' ),
				'value' => $config->is_acceptance_required() ? 'yes' : 'no',
			)
		);

		woocommerce_wp_textarea_input(
			array(
				'id'          => '_wpp_acceptance_text',
				'label'       => __( 'Acceptance checkbox text', 'woo-product-personalizer' ),
				'placeholder' => $defaults['default_accept_text'],
				'value'       => $config->get_acceptance_text(),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => '_wpp_button_label',
				'label'       => __( 'Personalize button label', 'woo-product-personalizer' ),
				'placeholder' => $defaults['default_button_label'],
				'value'       => $config->get_button_label(),
			)
		);
		?>
	</div>
</div>
