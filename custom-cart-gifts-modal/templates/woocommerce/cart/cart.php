<?php

/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');
$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
  <path fill-rule="evenodd" clip-rule="evenodd" d="M2 4.25C1.58579 4.25 1.25 4.58579 1.25 5C1.25 5.41421 1.58579 5.75 2 5.75H12.25V6V11V16.25H9.64648C9.32002 15.0957 8.25878 14.25 7 14.25C5.74122 14.25 4.67998 15.0957 4.35352 16.25H3.75V13C3.75 12.5858 3.41421 12.25 3 12.25C2.58579 12.25 2.25 12.5858 2.25 13V17C2.25 17.4142 2.58579 17.75 3 17.75H4.35352C4.67998 18.9043 5.74122 19.75 7 19.75C8.25878 19.75 9.32002 18.9043 9.64648 17.75H13H14.3535C14.68 18.9043 15.7412 19.75 17 19.75C18.2588 19.75 19.32 18.9043 19.6465 17.75H21C21.4142 17.75 21.75 17.4142 21.75 17V11.0123C21.7512 10.9413 21.7424 10.8696 21.7229 10.7994C21.7035 10.7295 21.6743 10.6637 21.6368 10.6036L18.6431 5.61413C18.5076 5.38822 18.2634 5.25 18 5.25H13.75V5C13.75 4.58579 13.4142 4.25 13 4.25H2ZM19.6465 16.25H20.25V11.75H13.75V16.25H14.3535C14.68 15.0957 15.7412 14.25 17 14.25C18.2588 14.25 19.32 15.0957 19.6465 16.25ZM15.75 17C15.75 16.3096 16.3096 15.75 17 15.75C17.6904 15.75 18.25 16.3096 18.25 17C18.25 17.6904 17.6904 18.25 17 18.25C16.3096 18.25 15.75 17.6904 15.75 17ZM5.75 17C5.75 17.6904 6.30964 18.25 7 18.25C7.69036 18.25 8.25 17.6904 8.25 17C8.25 16.3096 7.69036 15.75 7 15.75C6.30964 15.75 5.75 16.3096 5.75 17ZM13.75 10.25V6.75H17.5754L19.6754 10.25H13.75Z" fill="#FE6645"/>
</svg>';
$icon_arrow = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
  <path d="M5 12H19" stroke="#FE6645" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M13 18L19 12" stroke="#FE6645" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M13 6L19 12" stroke="#FE6645" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';
?>
<div class="wrap_custom_cart_head">
	<h1><?php the_title(); ?></h1>
	<?php
	$show_free_shipping = get_option('cgm_show_free_shipping', false) == 'on';
	$free_shipping_threshold = get_option('cgm_free_shipping_threshold', 0);

	$cart_total = 0;
	if (class_exists('CustomCart')) {
		$cart_total = new CustomCart();
		$cart_total = $cart_total->get_cart_total_value(true);
	} else {
		$cart = WC()->cart;
		$subtotal_with_tax = $cart->get_subtotal() + $cart->get_subtotal_tax();
		$cart_total = $subtotal_with_tax;
	}

	if ($show_free_shipping && $free_shipping_threshold > 0) {
		$remaining = $free_shipping_threshold - $cart_total;

		$free_shipping_message = __('To get free shipping you need: {remaining}', 'custom-cart-gifts-modal');
		$free_shipping_message = str_replace('{remaining}', wc_price($remaining), $free_shipping_message);

		if ($remaining > 0) {
	?>
			<div class="free-shipping-notice">
			<div class="free-shipping-notice-text">
				<?= $icon; ?><p>
					<?= $free_shipping_message; ?> 
			
				</p>
				</div>
				<div class="wrap_btn">
						<a
							href="<?php echo get_permalink(wc_get_page_id('shop')); ?>"><?php esc_html_e('Go to shop', 'custom-cart-gifts-modal'); ?>
							<?= $icon_arrow; ?></a>
					</div>
			</div>
		<?php
		} else {
		?>
			<div class="free-shipping-notice">
				<?= $icon; ?><p><?php echo __('Congratulations! You get free delivery!', 'custom-cart-gifts-modal');; ?></p>
			</div>
	<?php
		}
	}
	?>
</div>
<div class="custom-cart-wrap">

	<form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">




		<?php do_action('woocommerce_before_cart_table'); ?>
		<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
			<tbody>
				<?php do_action('woocommerce_before_cart_contents'); ?>

				<?php
				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					include plugin_dir_path(__FILE__) . 'cart-item.php';
				}
				?>

				<?php do_action('woocommerce_cart_contents'); ?>

				<tr>
					<td colspan="6" class="actions">

						<button type="submit"
							class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
							name="update_cart"
							value="<?php esc_attr_e('Update cart', 'custom-cart-gifts-modal'); ?>"><?php esc_html_e('Update cart', 'custom-cart-gifts-modal'); ?></button>

						<?php do_action('woocommerce_cart_actions'); ?>
						<?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
					</td>
				</tr>

				<?php do_action('woocommerce_after_cart_contents'); ?>
			</tbody>
		</table>

		<?php include plugin_dir_path(__FILE__) . 'gifts-template.php'; ?>

		<?php do_action('woocommerce_after_cart_table'); ?>

	</form>

	<?php do_action('woocommerce_before_cart_collaterals'); ?>

	<div class="cart-collaterals">
		<?php
		remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10);
		/**
		 * Cart collaterals hook.
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action('woocommerce_cart_collaterals');
		?>
	</div>

	<?php do_action('woocommerce_after_cart'); ?>

</div>