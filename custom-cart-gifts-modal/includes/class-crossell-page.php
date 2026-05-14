<?php

class CrossSellPage
{
    public function __construct()
    {
        add_shortcode('crossel_page_shortcode', array($this, 'render_cross_sell_products'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        update_option('cgm_crossel_page_shortcode', '[crossel_page_shortcode]');

        add_filter('body_class', array($this, 'add_custom_wc_class'));
    }

    public function add_custom_wc_class($classes)
    {
        $classes[] = 'notification-' . get_option('cgm_notification_position', 'standard');
        return $classes;
    }

    public function enqueue_scripts()
    {
        $version = '2.40';
        wp_enqueue_style('cgm-cross-sell-page-css', plugin_dir_url(__FILE__) . '../assets/css/cross-sell-page.min.css', array(), $version);
        wp_enqueue_script('cgm-cross-sell-page-js', plugin_dir_url(__FILE__) . '../assets/js/cross-sell-page.js', array('jquery'), $version, true);
    }

    public function render_cross_sell_products()
    {

        if (get_option('cgm_show_cross_sell_page', 'off') !== 'on') {
            return '';
        }

            ob_start();
?>
                <div class="woocommerce-notices-wrapper">
                </div>
                <div class="cart-total-on-cross-sell-page">
                    <h3>
                        <?php echo __('Your cart total', 'custom-cart-gifts-modal'); ?>
                    </h3>
                    <div>
                        <?php echo __('Total:', 'custom-cart-gifts-modal'); ?>
                        <span><?php echo wc_price(WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() + WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax()); ?></span>
                    </div>
                </div>
            <div class="cross-sell-page-wrap">
                <?php echo $this->render_cross_sell_nav(); ?>
                <div class="cross-sell-page-products" >
                    <?php echo $this->get_html_cross_sell_products(); ?>
                </div>
                <?php echo $this->render_cross_sell_nav(); ?>
            </div>
        <?php
            return ob_get_clean();
    }

    public function get_cross_sell_products_from_active_levels()
    {

        if (!WC()->cart) {
            return [];
        }

        $cross_sell_products = array();
        $cart_totals = (new CustomCart())->get_cart_total_value(true);

        $levels = get_option('cgm_cart_gifts_levels', array());
        $cart_items = WC()->cart->get_cart();

        foreach ($levels as $index => $level) {
            
            if ($level['prog'] <= $cart_totals) {
                
                $level_cross_sells_products = isset($level['crossSellProducts']) ? $level['crossSellProducts'] : [];
            
                foreach ($level_cross_sells_products as $cross_sell_product) {

                    $product_id = $cross_sell_product['id'];
                    $level_index = $index;
                    $cross_sell_product['level_index'] = $level_index;

                    foreach ($cart_items as $cart_item) {

                        $is_cross_sell = isset($cart_item['is_cross_sell']) ? $cart_item['is_cross_sell'] : false;
                        $cart_level_index = isset($cart_item['level_index']) ? $cart_item['level_index'] : null;

                        if ($is_cross_sell == $product_id && $cart_level_index == $level_index && $is_cross_sell) {
                            $cross_sell_product['product_in_cart'] = true;
                        }
                    }
                    

                    $product = wc_get_product($product_id);
                    if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
                        continue;
                    }

                    $cross_sell_products[$product_id] = $cross_sell_product;
                }
            }
        }
        return $cross_sell_products;
    }

    public function render_cross_sell_nav()
    {
        ob_start();
        ?>
        <div class="cross-sell-nav">
            <a href="<?php echo wc_get_cart_url(); ?>" class="button return-to-cart"><?php _e('Return to cart', 'custom-cart-gifts-modal'); ?></a>

            <div>
                <a href="<?php echo wc_get_checkout_url(); ?>" class="button next"><?php esc_html_e('Proceed to shipping and payment', 'custom-cart-gifts-modal'); ?></a>
                
            </div>
        
        </div>
<?php
        return ob_get_clean();
    }

    public function get_html_cross_sell_products()
    {

        $cross_sell_products = $this->get_cross_sell_products_from_active_levels();
        if (empty($cross_sell_products)) {
            return '';
        }

        ob_start();
        foreach ($cross_sell_products as $cross_sell_product) {

            $product_id = $cross_sell_product['id'];
            $product_discount = $cross_sell_product['discount_value']; 
            $product_discount_type = $cross_sell_product['discount_type'];
            $level_index = $cross_sell_product['level_index'];
            $product_in_cart = isset($cross_sell_product['product_in_cart']) ? $cross_sell_product['product_in_cart'] : false;

            if (
                !isset($product_id) || $product_id=='' ||
                !isset($product_discount) || $product_discount=='' ||
                !isset($product_discount_type) || $product_discount_type=='' ||
                !isset($level_index) || $level_index==''
            ) {
                continue;
            }
            include plugin_dir_path(__FILE__) . '../templates/cross-sell-product-template.php';
        }
        return ob_get_clean();
    }
}