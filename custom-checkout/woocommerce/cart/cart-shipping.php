<?php

/**
 * Shipping Methods Display
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.8.0
 */

defined('ABSPATH') || exit;

// Użyj globalnego indeksu paczki jeśli nie jest ustawiony
if (!isset($index)) {
	$index = 0;
}

$formatted_destination    = isset($formatted_destination) ? $formatted_destination : WC()->countries->get_formatted_address($package['destination'], ', ');
$has_calculated_shipping  = ! empty($has_calculated_shipping);
$show_shipping_calculator = ! empty($show_shipping_calculator);
$calculator_text          = '';
?>
<tr class="woocommerce-shipping-totals shipping">
    <td data-title="<?php echo esc_attr(isset($package_name) ? $package_name : ''); ?>">
        <?php if (! empty($available_methods) && is_array($available_methods)) : ?>
        <ul id="shipping_method" class="woocommerce-shipping-methods">
            <?php foreach ($available_methods as $method) : ?>
            <li>
                <?php
						$method_label = isset($method) ? strip_tags(wc_cart_totals_shipping_method_label($method)) : '';
						$first_word   = '';
						if (!empty($method_label)) {
							$words = preg_split('/\s+/', $method_label, 2);
							$first_word = !empty($words[0]) ? strtolower(sanitize_title($words[0])) : '';
						}
						$extra_class = $first_word ? ' shipping-method-' . esc_attr($first_word) : '';

						if (count($available_methods) > 1) {
							printf(
								'<div class="shipping-method-input-wrap%1$s">
                <input type="radio" name="shipping_method[%2$d]" data-index="%2$d" id="shipping_method_%2$d_%3$s" value="%4$s" class="shipping_method" %5$s />
                <div class="wrap_label">
                    <label for="shipping_method_%2$d_%3$s">
                        <div class="label_text">%6$s</div>
                        <div class="div-shipping-method-description">%7$s</div>
                    </label>
                </div>
         <div class="label_thumb">
				<div class="label_thumb_img"></div>
				</div>
            </div>',
								$extra_class,
								$index,
								esc_attr(sanitize_title($method->id)),
								esc_attr($method->id),
								checked($method->id, isset($chosen_method) ? $chosen_method : '', false),
								wc_cart_totals_shipping_method_label($method),
								$method->get_description()
							);
						} else {
							printf(
								'<div class="shipping-method-input-wrap%1$s">
                <input type="hidden" name="shipping_method[%2$d]" data-index="%2$d" id="shipping_method_%2$d_%3$s" value="%4$s" class="shipping_method" />
                <div class="wrap_label">
                    <label for="shipping_method_%2$d_%3$s">
                        <div class="label_text">%5$s</div>
                        <div class="div-shipping-method-description">%6$s</div>
                    </label>
                </div>
                <div class="label_thumb">
				<div class="label_thumb_img"></div>
				</div>
            </div>',
								$extra_class,
								$index,
								esc_attr(sanitize_title($method->id)),
								esc_attr($method->id),
								wc_cart_totals_shipping_method_label($method),
								$method->get_description()
							);
						}
						do_action('woocommerce_after_shipping_rate', $method, $index);
						?>
            </li>

            <?php endforeach; ?>
        </ul>
        <?php if (function_exists('is_cart') && is_cart()) : ?>
        <p class="woocommerce-shipping-destination">
            <?php
					if ($formatted_destination) {
						// Translators: $s shipping destination.
						printf(esc_html__('Shipping to %s.', 'woocommerce') . ' ', '<strong>' . esc_html($formatted_destination) . '</strong>');
						$calculator_text = esc_html__('Change address', 'woocommerce');
					} else {
						echo wp_kses_post(apply_filters('woocommerce_shipping_estimate_html', __('Shipping options will be updated during checkout.', 'woocommerce')));
					}
					?>
        </p>
        <?php endif; ?>
        <?php
		elseif (! $has_calculated_shipping || ! $formatted_destination) :
			if ((function_exists('is_cart') && is_cart()) && 'no' === get_option('woocommerce_enable_shipping_calc')) {
				echo wp_kses_post(apply_filters('woocommerce_shipping_not_enabled_on_cart_html', __('Shipping costs are calculated during checkout.', 'woocommerce')));
			} else {
				echo wp_kses_post(apply_filters('woocommerce_shipping_may_be_available_html', __('Enter your address to view shipping options.', 'woocommerce')));
			}
		elseif (!function_exists('is_cart') || !is_cart()) :
			echo wp_kses_post(apply_filters('woocommerce_no_shipping_available_html', __('There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce')));
		else :
			echo wp_kses_post(
				apply_filters(
					'woocommerce_cart_no_shipping_available_html',
					sprintf(esc_html__('No shipping options were found for %s.', 'woocommerce') . ' ', '<strong>' . esc_html($formatted_destination) . '</strong>'),
					$formatted_destination
				)
			);
			$calculator_text = esc_html__('Enter a different address', 'woocommerce');
		endif;
		?>

        <?php if (!empty($show_package_details) && !empty($package_details)) : ?>
        <?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html($package_details) . '</small></p>'; ?>
        <?php endif; ?>

        <?php if ($show_shipping_calculator) : ?>
        <?php woocommerce_shipping_calculator($calculator_text); ?>
        <?php endif; ?>
    </td>
</tr>