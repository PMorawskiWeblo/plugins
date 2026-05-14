<?php

/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined('ABSPATH') || exit;
?>
<section class="section_breadcums section_breadcums_default">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <?php get_template_part('components/breadcums'); ?>
            </div>
        </div>
    </div>
</section>
<?php
do_action('woocommerce_before_cart');
?>
<div class="woocommerce-notices-wrapper">
    <?php wc_print_notices(); ?>
</div>
<div class="new-cart-layout-wrapper d-flex flex-column">
    <div class="new-cart-layout">
        <div class="left-col-cart">
            <div class="woocommerce-cart-form-wrapper">
                <div class="wrap_cart_empty_content d-flex flex-column align-items-center justify-content-center gap-4">
                    <h1 class="cart-title"><?php _e('Your cart is empty', 'gift-in-cart-plugin'); ?></h1>

                    <?php if (wc_get_page_id('shop') > 0) : ?>
                    <p class="return-to-shop">
                        <a class="button wc-backward btn btn-big<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
                            href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>">
                            <?php
								/**
								 * Filter "Return To Shop" text.
								 *
								 * @since 4.6.0
								 * @param string $default_text Default text.
								 */
								echo esc_html(apply_filters('woocommerce_return_to_shop_text', __('Return to shop', 'woocommerce')));
								?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php do_action('woocommerce_after_cart'); ?>