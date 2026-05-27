<?php

/**
 * WooCommerce Integration Class
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loyalty Program WooCommerce Class
 */
class Loyalty_Program_WooCommerce
{

    /**
     * Meta key for user's personal coupon
     * 
     * @var string
     */
    const USER_COUPON_META = 'loyalty_program_personal_coupon';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Hook into plugins_loaded to ensure WooCommerce is loaded
        add_action('plugins_loaded', array($this, 'init_hooks'), 20);

        // Also hook into woocommerce_init as a backup
        add_action('woocommerce_init', array($this, 'init_hooks'), 10);
    }

    /**
     * Initialize hooks
     * 
     * @return void
     */
    public function init_hooks()
    {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Check if already initialized to prevent duplicate hooks
        static $initialized = false;
        if ($initialized) {
            return;
        }
        $initialized = true;

        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Loyalty_Program_Logger::info('WooCommerce integration hooks initialized');

        // New customer registration - auto-enroll if enabled
        add_action('woocommerce_created_customer', array($this, 'auto_enroll_new_customer'), 10, 1);
        add_action('user_register', array($this, 'auto_enroll_new_customer'), 10, 1);

        // Order completed - award points
        add_action('woocommerce_order_status_completed', array($this, 'award_points_for_order'), 10, 1);
        
        // Order processing - award points (if option is set)
        add_action('woocommerce_order_status_processing', array($this, 'award_points_for_order'), 10, 1);

        // Order refunded - remove points
        add_action('woocommerce_order_status_refunded', array($this, 'remove_points_for_refund'), 10, 1);
        add_action('woocommerce_order_status_cancelled', array($this, 'remove_points_for_refund'), 10, 1);

        // Coupon used - award points (only when order is completed, handled in award_points_for_order)

        // Validate personal coupons (prevent owner from using their own coupon)
        add_filter('woocommerce_coupon_is_valid', array($this, 'validate_personal_coupon'), 5, 2);
        add_filter('woocommerce_coupon_is_valid_for_cart', array($this, 'validate_personal_coupon_for_cart'), 5, 2);

        // Remove own coupon after it's applied (fallback if validation doesn't work)
        add_action('woocommerce_applied_coupon', array($this, 'remove_own_coupon_after_applied'), 10, 1);

        // Order status changed (any status) - for debugging
        add_action('woocommerce_order_status_changed', array($this, 'log_status_change'), 10, 4);

        // Set custom price for loyalty rewards
        add_action('woocommerce_before_calculate_totals', array($this, 'set_custom_reward_price'), 10, 1);

        // Add custom cart item data
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_loyalty_cart_item_data'), 10, 3);

        // Display custom meta in cart
        add_filter('woocommerce_get_item_data', array($this, 'display_loyalty_cart_item_data'), 10, 2);

        // Add custom CSS class to loyalty cart items
        add_filter('woocommerce_cart_item_class', array($this, 'add_loyalty_cart_item_class'), 10, 3);

        // Save loyalty reward meta data to order items
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_loyalty_meta_to_order_item'), 10, 4);

        // Mark reward as used when order is completed
        add_action('woocommerce_order_status_completed', array($this, 'mark_reward_as_used'), 20, 1);
        add_action('woocommerce_order_status_processing', array($this, 'mark_reward_as_used'), 20, 1);

        // Prevent duplicate loyalty rewards in cart
        add_filter('woocommerce_add_to_cart_validation', array($this, 'prevent_duplicate_loyalty_rewards'), 10, 3);

        // Make each loyalty reward a separate cart item (unique key)
        add_filter('woocommerce_add_cart_item_data', array($this, 'make_loyalty_rewards_unique'), 10, 2);

        // Prevent quantity changes for loyalty rewards
        add_filter('woocommerce_cart_item_quantity', array($this, 'loyalty_reward_quantity_input'), 10, 3);
        add_filter('woocommerce_after_cart_item_quantity_update', array($this, 'prevent_loyalty_reward_quantity_change'), 10, 4);

        // Flash Hunter coupon fields
        add_action('woocommerce_coupon_options', array($this, 'add_flash_hunter_fields'), 10, 2);
        add_action('woocommerce_coupon_options_save', array($this, 'save_flash_hunter_fields'), 10, 2);

        // Custom user account fields - save only (fields are now displayed via shortcode)
        add_action('woocommerce_save_account_details', array($this, 'save_custom_account_fields'), 10, 1);

        // Supplementation Discipline - product checkbox
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_discipline_checkbox'));
        add_action('woocommerce_process_product_meta', array($this, 'save_discipline_checkbox'), 10, 1);

        // Supplementation Discipline - variation checkbox
        add_action('woocommerce_product_after_variable_attributes', array($this, 'add_discipline_checkbox_variation'), 10, 3);
        add_action('woocommerce_save_product_variation', array($this, 'save_discipline_checkbox_variation'), 10, 2);

        // Custom account page in My Account menu
        add_filter('woocommerce_account_menu_items', array($this, 'add_custom_account_page_to_menu'), 10, 1);
        add_filter('woocommerce_get_endpoint_url', array($this, 'custom_account_page_url'), 10, 4);
    }

    /**
     * Auto-enroll new customers to loyalty program if enabled
     * 
     * @param int $user_id User ID
     * @return void
     */
    public function auto_enroll_new_customer($user_id)
    {
        // Check if auto-enroll is enabled
        $auto_enroll = get_option('loyalty_program_auto_enroll', 'no');

        if ($auto_enroll !== 'yes') {
            return;
        }

        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            Loyalty_Program_Logger::debug('Auto-enroll skipped - program disabled', array(
                'user_id' => $user_id,
            ));
            return;
        }

        // Check if user is already a member
        if (Loyalty_Program_Points::is_member($user_id)) {
            Loyalty_Program_Logger::debug('Auto-enroll skipped - already a member', array(
                'user_id' => $user_id,
            ));
            return;
        }

        Loyalty_Program_Logger::info('🎉 Auto-enrolling new customer to loyalty program', array(
            'user_id' => $user_id,
            'trigger' => 'user_registration',
        ));

        // Enroll user and award signup points
        $result = Loyalty_Program_Points::enroll_user($user_id);

        if ($result) {
            Loyalty_Program_Logger::info('✅ New customer auto-enrolled successfully', array(
                'user_id' => $user_id,
                'signup_points' => get_option('loyalty_program_points_signup', 0),
            ));

            // Generate personal coupon
            $coupon_code = self::generate_personal_coupon($user_id);
            if ($coupon_code) {
                Loyalty_Program_Logger::info('Personal coupon generated for new member', array(
                    'user_id' => $user_id,
                    'coupon_code' => $coupon_code,
                ));
            }
        } else {
            Loyalty_Program_Logger::error('❌ Failed to auto-enroll new customer', array(
                'user_id' => $user_id,
            ));
        }
    }

    /**
     * Log order status changes for debugging
     * 
     * @param int $order_id Order ID
     * @param string $old_status Old status
     * @param string $new_status New status
     * @param WC_Order $order Order object
     * @return void
     */
    public function log_status_change($order_id, $old_status, $new_status, $order)
    {
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        $user_id = $order->get_user_id();

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        $is_member = $user_id ? Loyalty_Program_Points::is_member($user_id) : false;

        Loyalty_Program_Logger::debug('Order status changed', array(
            'order_id' => $order_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'user_id' => $user_id,
            'is_member' => $is_member,
            'order_total' => $order->get_total(),
        ));

        // If status changed to completed, log detailed info
        if ($new_status === 'completed') {
            Loyalty_Program_Logger::info('Order status changed to COMPLETED', array(
                'order_id' => $order_id,
                'user_id' => $user_id,
                'is_member' => $is_member,
                'will_award_points' => $is_member && $user_id > 0,
            ));
        }
    }

    /**
     * Award points when order is completed
     * 
     * @param int $order_id Order ID
     * @return void
     */
    public function award_points_for_order($order_id)
    {
        // Load logger first for debugging
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            Loyalty_Program_Logger::debug('Loyalty program disabled - skipping points award', array('order_id' => $order_id));
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            Loyalty_Program_Logger::error('Order not found', array('order_id' => $order_id));
            return;
        }

        // Check if points should be awarded for this status
        $points_award_status = get_option('loyalty_program_points_award_status', 'completed');
        $order_status = $order->get_status();
        
        // Only award points if order status matches the selected option
        if ($points_award_status === 'processing' && $order_status !== 'processing') {
            Loyalty_Program_Logger::debug('Points award skipped - order status does not match setting', array(
                'order_id' => $order_id,
                'order_status' => $order_status,
                'points_award_status' => $points_award_status,
            ));
            return;
        }
        
        if ($points_award_status === 'completed' && $order_status !== 'completed') {
            Loyalty_Program_Logger::debug('Points award skipped - order status does not match setting', array(
                'order_id' => $order_id,
                'order_status' => $order_status,
                'points_award_status' => $points_award_status,
            ));
            return;
        }

        Loyalty_Program_Logger::info('Order status matches points award setting - checking for points award', array(
            'order_id' => $order_id,
            'order_status' => $order_status,
            'points_award_status' => $points_award_status,
        ));

        // Check if points already awarded
        $points_awarded = $order->get_meta('_loyalty_points_awarded', true);
        if ($points_awarded) {
            Loyalty_Program_Logger::debug('Points already awarded for this order', array('order_id' => $order_id));
            return; // Already processed
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            Loyalty_Program_Logger::warning('Guest order - no points awarded', array('order_id' => $order_id));
            return; // Guest order
        }

        // Check if user is a member
        $is_member = Loyalty_Program_Points::is_member($user_id);
        if (!$is_member) {
            Loyalty_Program_Logger::warning('User is not a loyalty program member - no points awarded', array(
                'order_id' => $order_id,
                'user_id' => $user_id,
            ));

            // Add order note for admin
            $order->add_order_note(
                __('Loyalty points not awarded - user is not enrolled in loyalty program', 'loyalty-program')
            );

            return;
        }

        // Check if points should be calculated only for products (excluding shipping)
        $points_only_products = get_option('loyalty_program_points_only_products', 'no') === 'yes';
        
        // Get order total or products total
        if ($points_only_products) {
            // Calculate products total: sum of all product items (after discounts) + product taxes
            // This approach properly handles coupon discounts
            $products_total = 0;
            $product_tax_total = 0;
            
            foreach ($order->get_items() as $item) {
                // Get item total (after discounts)
                $item_total = $item->get_total();
                $products_total += $item_total;
                
                // Get item tax
                $item_tax = $item->get_total_tax();
                $product_tax_total += $item_tax;
            }
            
            // Products total = sum of items (after discounts) + product taxes
            $order_total = $products_total + $product_tax_total;
            
            // Get discount info for logging
            $discount_total = $order->get_discount_total();
            $subtotal_before_discount = $order->get_subtotal();
            $subtotal_after_discount = $products_total;
            
            Loyalty_Program_Logger::debug('Points calculated only for products (excluding shipping)', array(
                'order_id' => $order_id,
                'subtotal_before_discount' => $subtotal_before_discount,
                'discount_total' => $discount_total,
                'subtotal_after_discount' => $subtotal_after_discount,
                'product_tax' => $product_tax_total,
                'shipping_total' => $order->get_shipping_total(),
                'shipping_tax' => $order->get_shipping_tax(),
                'products_total' => $order_total,
                'order_total_with_shipping' => $order->get_total(),
            ));
        } else {
            // Use full order total (including shipping)
            $order_total = $order->get_total();
        }
        
        $order_currency = $order->get_currency();

        // Convert to base currency if using WooCommerce Multi Currency
        $order_total_base = $this->convert_to_base_currency($order_total, $order_currency);

        // Get points per currency setting
        $points_per_currency = get_option('loyalty_program_points_per_currency', 1);

        // Calculate points (round down) - use base currency amount
        $points = floor($order_total_base * floatval($points_per_currency));

        if ($points > 0) {
            // Add points
            if ($points_only_products) {
                $action_desc = sprintf(
                    __('Order #%s completed - products value (%s %s)', 'loyalty-program'),
                    $order->get_order_number(),
                    number_format($order_total, 2, ',', ' '),
                    $order->get_currency()
                );
            } else {
                $action_desc = sprintf(
                    __('Order #%s completed (%s %s)', 'loyalty-program'),
                    $order->get_order_number(),
                    number_format($order_total, 2, ',', ' '),
                    $order->get_currency()
                );
            }

            Loyalty_Program_Points::add_points($user_id, $points, $action_desc, array(
                'order_id' => $order_id,
                'order_total' => $order_total,
                'order_currency' => $order_currency,
                'order_total_base' => $order_total_base,
                'base_currency' => get_option('woocommerce_currency'),
                'points_per_currency' => $points_per_currency,
            ));

            // Mark order as points awarded
            $order->update_meta_data('_loyalty_points_awarded', 'yes');
            $order->update_meta_data('_loyalty_points_amount', $points);
            $order->save();

            // Add order note
            $order->add_order_note(
                sprintf(
                    __('Loyalty points awarded: %d points', 'loyalty-program'),
                    $points
                )
            );

            // Log
            if (!class_exists('Loyalty_Program_Logger')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
            }

            Loyalty_Program_Logger::info('Points awarded for order', array(
                'order_id' => $order_id,
                'user_id' => $user_id,
                'points' => $points,
                'order_total' => $order_total,
                'order_total_full' => $order->get_total(),
                'order_currency' => $order_currency,
                'order_total_base' => $order_total_base,
                'base_currency' => get_option('woocommerce_currency'),
                'conversion_applied' => ($order_currency !== get_option('woocommerce_currency')),
                'points_only_products' => $points_only_products,
            ));
        }

        // Check for "Return for More" bonus (repeat purchases)
        $this->check_return_purchase_bonus($order, $user_id);

        // Also check for coupon usage when order is completed
        $this->check_coupon_usage_on_completion($order_id);
    }

    /**
     * Check for "Return for More" bonus - repeat purchases within 30 days
     * 
     * @param WC_Order $order Order object
     * @param int $user_id User ID
     * @return void
     */
    private function check_return_purchase_bonus($order, $user_id)
    {
        if (!$order || !$user_id) {
            return;
        }

        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Get points for return purchase
        $return_points = absint(get_option('loyalty_program_points_return_purchase', 50));
        if ($return_points <= 0) {
            return; // Feature disabled if points = 0
        }

        // Get user's purchase history (product_id => last_purchase_date)
        $purchase_history = get_user_meta($user_id, 'loyalty_program_purchase_history', true);
        if (!is_array($purchase_history)) {
            $purchase_history = array();
        }

        $current_time = current_time('timestamp');
        $thirty_days_ago = $current_time - (30 * DAY_IN_SECONDS);
        $order_id = $order->get_id();

        // Get products from current order
        $items = $order->get_items();
        $return_bonus_products = array();

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $product_name = $item->get_name();

            // Check if this product was purchased before
            if (isset($purchase_history[$product_id])) {
                $last_purchase_timestamp = $purchase_history[$product_id];

                // Check if last purchase was within 30 days
                if ($last_purchase_timestamp >= $thirty_days_ago && $last_purchase_timestamp < $current_time) {
                    $days_ago = floor(($current_time - $last_purchase_timestamp) / DAY_IN_SECONDS);
                    $return_bonus_products[] = array(
                        'product_id' => $product_id,
                        'product_name' => $product_name,
                        'days_ago' => $days_ago,
                    );

                    Loyalty_Program_Logger::info('Return purchase detected', array(
                        'user_id' => $user_id,
                        'order_id' => $order_id,
                        'product_id' => $product_id,
                        'product_name' => $product_name,
                        'days_since_last_purchase' => $days_ago,
                    ));
                }
            }
        }

        // Award points for return purchases
        if (!empty($return_bonus_products)) {
            foreach ($return_bonus_products as $bonus_product) {
                $action_desc = sprintf(
                    __('Return for More: %s (purchased again after %d days)', 'loyalty-program'),
                    $bonus_product['product_name'],
                    $bonus_product['days_ago']
                );

                Loyalty_Program_Points::add_points($user_id, $return_points, $action_desc, array(
                    'order_id' => $order_id,
                    'product_id' => $bonus_product['product_id'],
                    'type' => 'return_purchase',
                ));

                Loyalty_Program_Logger::info('Return purchase bonus awarded', array(
                    'user_id' => $user_id,
                    'order_id' => $order_id,
                    'product_name' => $bonus_product['product_name'],
                    'points' => $return_points,
                ));
            }

            // Add order note
            $order->add_order_note(
                sprintf(
                    _n(
                        'Return for More bonus: %d points for 1 repeat purchase',
                        'Return for More bonus: %d points for %d repeat purchases',
                        count($return_bonus_products),
                        'loyalty-program'
                    ),
                    $return_points * count($return_bonus_products),
                    count($return_bonus_products)
                )
            );
        }

        // Update purchase history with current order products
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            // Store current purchase timestamp for this product
            $purchase_history[$product_id] = $current_time;
        }

        update_user_meta($user_id, 'loyalty_program_purchase_history', $purchase_history);

        Loyalty_Program_Logger::debug('Purchase history updated', array(
            'user_id' => $user_id,
            'order_id' => $order_id,
            'total_products_tracked' => count($purchase_history),
        ));

        // Check for Supplementation Discipline (3 purchases of same product in 3 months)
        $this->check_supplementation_discipline($user_id, $order);
    }

    /**
     * Remove points when order is refunded or cancelled
     * 
     * @param int $order_id Order ID
     * @return void
     */
    public function remove_points_for_refund($order_id)
    {
        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if points were awarded
        $points_awarded = $order->get_meta('_loyalty_points_awarded', true);
        if (!$points_awarded) {
            return; // No points to remove
        }

        // Check if already removed
        $points_removed = $order->get_meta('_loyalty_points_removed', true);
        if ($points_removed) {
            return; // Already processed
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }

        $points = absint($order->get_meta('_loyalty_points_amount', true));

        if ($points > 0) {
            // Remove points
            $action_desc = sprintf(
                __('Order #%s refunded/cancelled', 'loyalty-program'),
                $order->get_order_number()
            );

            Loyalty_Program_Points::remove_points($user_id, $points, $action_desc, array(
                'order_id' => $order_id,
                'reason' => 'order_refund',
            ));

            // Mark as removed
            $order->update_meta_data('_loyalty_points_removed', 'yes');
            $order->save();

            // Add order note
            $order->add_order_note(
                sprintf(
                    __('Loyalty points removed: %d points (order refunded/cancelled)', 'loyalty-program'),
                    $points
                )
            );

            // Log
            if (!class_exists('Loyalty_Program_Logger')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
            }

            Loyalty_Program_Logger::warning('Points removed for refunded order', array(
                'order_id' => $order_id,
                'user_id' => $user_id,
                'points' => $points,
            ));
        }
    }

    /**
     * Check if user's personal coupon was used and award points
     * Note: This method is no longer called automatically. Points are now awarded
     * only when order is completed via check_coupon_usage_on_completion()
     * 
     * @param int $order_id Order ID
     * @return void
     */
    public function check_coupon_usage($order_id)
    {
        $this->process_coupon_points($order_id, false);
    }

    /**
     * Check coupon usage when order is completed (for status changes)
     * 
     * @param int $order_id Order ID
     * @return void
     */
    public function check_coupon_usage_on_completion($order_id)
    {
        $this->process_coupon_points($order_id, true);
    }

    /**
     * Process coupon points award
     * 
     * @param int $order_id Order ID
     * @param bool $on_completion If true, check if already processed
     * @return void
     */
    private function process_coupon_points($order_id, $on_completion = false)
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // If checking on completion, see if coupon points already awarded
        if ($on_completion) {
            $coupon_points_awarded = $order->get_meta('_loyalty_coupon_points_awarded', true);
            if ($coupon_points_awarded) {
                Loyalty_Program_Logger::debug('Coupon points already awarded for this order', array('order_id' => $order_id));
                return; // Already processed
            }
        }

        // Get used coupons
        $used_coupons = $order->get_coupon_codes();

        if (empty($used_coupons)) {
            Loyalty_Program_Logger::debug('No coupons used in order', array('order_id' => $order_id));
            return;
        }

        Loyalty_Program_Logger::info('Checking coupons for loyalty program', array(
            'order_id' => $order_id,
            'coupons' => $used_coupons,
        ));

        $points_coupon_use = get_option('loyalty_program_points_coupon_use', 10);
        $awarded_to = array();

        foreach ($used_coupons as $coupon_code) {
            // Check if this is a loyalty program personal coupon
            $coupon = new WC_Coupon($coupon_code);
            $coupon_id = $coupon->get_id();
            $coupon_owner_id = $coupon->get_meta('_loyalty_program_owner_id', true);

            Loyalty_Program_Logger::debug('Checking coupon', array(
                'coupon_code' => $coupon_code,
                'coupon_id' => $coupon_id,
                'owner_id' => $coupon_owner_id,
                'is_loyalty_coupon' => !empty($coupon_owner_id),
            ));

            // Check if this is a Flash Hunter coupon
            $is_flash_hunter = get_post_meta($coupon_id, '_is_flash_hunter', true);

            if ($is_flash_hunter === 'yes') {
                $this->process_flash_hunter_coupon($order, $coupon_code, $coupon_id);
            }

            if ($coupon_owner_id) {
                // This is a personal loyalty coupon
                // Award points to coupon owner
                $action_desc = sprintf(
                    __('Personal coupon "%s" used in order #%s', 'loyalty-program'),
                    $coupon_code,
                    $order->get_order_number()
                );

                Loyalty_Program_Points::add_points($coupon_owner_id, $points_coupon_use, $action_desc, array(
                    'order_id' => $order_id,
                    'coupon_code' => $coupon_code,
                    'order_user_id' => $order->get_user_id(),
                ));

                $awarded_to[] = $coupon_owner_id;

                // Add order note
                $coupon_owner = get_userdata($coupon_owner_id);
                if ($coupon_owner) {
                    $order->add_order_note(
                        sprintf(
                            __('Loyalty points awarded to %s (ID: %d) for coupon usage: %d points', 'loyalty-program'),
                            $coupon_owner->display_name,
                            $coupon_owner_id,
                            $points_coupon_use
                        )
                    );
                }

                Loyalty_Program_Logger::info('Points awarded for coupon usage', array(
                    'coupon_owner_id' => $coupon_owner_id,
                    'coupon_code' => $coupon_code,
                    'order_id' => $order_id,
                    'points' => $points_coupon_use,
                ));
            }
        }

        // Mark as processed if any coupon points were awarded
        if (!empty($awarded_to)) {
            $order->update_meta_data('_loyalty_coupon_points_awarded', 'yes');
            $order->update_meta_data('_loyalty_coupon_owners', $awarded_to);
            $order->save();
        }
    }

    /**
     * Generate personal coupon for user
     * 
     * @param int $user_id User ID
     * @return string|false Coupon code or false on failure
     */
    public static function generate_personal_coupon($user_id)
    {
        if (!class_exists('WooCommerce')) {
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        // Check if user already has a coupon
        $existing_coupon = get_user_meta($user_id, self::USER_COUPON_META, true);
        if ($existing_coupon) {
            // Check if coupon still exists in WooCommerce
            $coupon = new WC_Coupon($existing_coupon);
            if ($coupon->get_id()) {
                return $existing_coupon; // Coupon already exists
            }
        }

        // Generate unique coupon code
        $coupon_code = 'LOYALTY-' . strtoupper(wp_generate_password(8, false));

        // Get coupon value from settings
        $coupon_value = get_option('loyalty_program_coupon_value', 10);

        // Get minimum order amount from settings
        $min_order_amount = get_option('loyalty_program_coupon_min_amount', 150);

        // Create WooCommerce coupon
        $coupon = new WC_Coupon();
        $coupon->set_code($coupon_code);
        $coupon->set_description(
            sprintf(__('Personal loyalty coupon for %s', 'loyalty-program'), $user->display_name)
        );
        $coupon->set_discount_type('fixed_cart');
        $coupon->set_amount($coupon_value);
        $coupon->set_individual_use(false);
        $coupon->set_usage_limit(0); // 0 = Unlimited uses
        $coupon->set_usage_limit_per_user(0); // 0 = Unlimited per user
        $coupon->set_minimum_amount($min_order_amount); // Minimum order amount
        $coupon->set_date_expires(null); // No expiry

        // Save coupon
        $coupon_id = $coupon->save();

        if ($coupon_id) {
            // Link coupon to user
            $coupon->update_meta_data('_loyalty_program_owner_id', $user_id);
            $coupon->update_meta_data('_loyalty_program_coupon', 'yes');
            $coupon->save();

            // Save coupon code to user meta
            update_user_meta($user_id, self::USER_COUPON_META, $coupon_code);

            // Log
            if (!class_exists('Loyalty_Program_Logger')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
            }

            Loyalty_Program_Logger::info('Personal coupon generated', array(
                'user_id' => $user_id,
                'coupon_code' => $coupon_code,
                'coupon_value' => $coupon_value,
            ));

            return $coupon_code;
        }

        return false;
    }

    /**
     * Generate single-use coupon for reward redemption
     * 
     * @param int $user_id User ID
     * @param array $coupon_reward Coupon reward configuration
     * @return string|false Coupon code or false on failure
     */
    public static function generate_reward_coupon($user_id, $coupon_reward)
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        if (!class_exists('WooCommerce')) {
            Loyalty_Program_Logger::error('Cannot generate reward coupon - WooCommerce not active', array(
                'user_id' => $user_id,
                'reward_name' => $coupon_reward['name'] ?? 'unknown',
            ));
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            Loyalty_Program_Logger::error('Cannot generate reward coupon - user not found', array(
                'user_id' => $user_id,
                'reward_name' => $coupon_reward['name'] ?? 'unknown',
            ));
            return false;
        }

        // Generate unique coupon code
        $coupon_code = 'REWARD-' . strtoupper(wp_generate_password(10, false));

        // Get coupon settings
        $coupon_apply_to = get_option('loyalty_program_coupon_apply_to', 'cart');
        $coupon_individual_use = get_option('loyalty_program_coupon_individual_use', 'no') === 'yes';
        $coupon_excluded_products = get_option('loyalty_program_coupon_excluded_products', array());
        $coupon_excluded_categories = get_option('loyalty_program_coupon_excluded_categories', array());

        // Get coupon type and value
        $coupon_type = isset($coupon_reward['type']) ? $coupon_reward['type'] : 'fixed_cart';
        $discount_value = isset($coupon_reward['discount_value']) ? floatval($coupon_reward['discount_value']) : 0;
        $min_order_amount = isset($coupon_reward['min_order_amount']) ? floatval($coupon_reward['min_order_amount']) : 0;

        Loyalty_Program_Logger::debug('Generating reward coupon', array(
            'user_id' => $user_id,
            'coupon_code' => $coupon_code,
            'coupon_type' => $coupon_type,
            'discount_value' => $discount_value,
            'min_order_amount' => $min_order_amount,
            'coupon_apply_to' => $coupon_apply_to,
            'coupon_individual_use' => $coupon_individual_use,
            'excluded_products_count' => count($coupon_excluded_products),
            'excluded_categories_count' => count($coupon_excluded_categories),
        ));

        // Create WooCommerce coupon
        try {
            $coupon = new WC_Coupon();
            Loyalty_Program_Logger::debug('WC_Coupon object created', array(
                'coupon_code' => $coupon_code,
            ));
            
            $coupon->set_code($coupon_code);
            $coupon->set_description(
                sprintf(__('Loyalty reward coupon: %s', 'loyalty-program'), $coupon_reward['name'])
            );
            
            // Set discount type and amount
            // For free_shipping, WooCommerce requires a valid discount type (not "free_shipping")
            // So we use "fixed_cart" with amount 0 and set_free_shipping(true)
            if ($coupon_type === 'free_shipping') {
                $coupon->set_discount_type('fixed_cart');
                $coupon->set_amount(0);
                $coupon->set_free_shipping(true);
            } else {
                // For other types (fixed_cart, percent), set normally
                $coupon->set_discount_type($coupon_type);
                $coupon->set_amount($discount_value);
            }

            // Set usage limit to 1 (single use)
            $coupon->set_usage_limit(1);
            $coupon->set_usage_limit_per_user(1);

            // Set individual use
            $coupon->set_individual_use($coupon_individual_use);

            // Set minimum order amount
            if ($min_order_amount > 0) {
                $coupon->set_minimum_amount($min_order_amount);
            }

            // Set apply to cart or products
            if ($coupon_apply_to === 'products') {
                // If products only, we need to set product_ids - but for reward coupons, we'll leave it empty (applies to all)
                // This can be extended later if needed
            }

            // Set excluded products
            if (!empty($coupon_excluded_products)) {
                $coupon->set_excluded_product_ids($coupon_excluded_products);
            }

            // Set excluded categories
            if (!empty($coupon_excluded_categories)) {
                $coupon->set_excluded_product_categories($coupon_excluded_categories);
            }

            // Set expiry date (optional - 30 days from now)
            $coupon->set_date_expires(strtotime('+30 days'));
            
            Loyalty_Program_Logger::debug('Coupon properties set successfully', array(
                'coupon_code' => $coupon_code,
            ));
        } catch (Exception $e) {
            Loyalty_Program_Logger::error('Exception while creating/setting coupon properties', array(
                'coupon_code' => $coupon_code,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ));
            return false;
        } catch (Error $e) {
            Loyalty_Program_Logger::error('Fatal error while creating/setting coupon properties', array(
                'coupon_code' => $coupon_code,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ));
            return false;
        }

        // Save coupon
        try {
            $coupon_id = $coupon->save();
            
            Loyalty_Program_Logger::debug('Coupon save attempt', array(
                'coupon_id' => $coupon_id,
                'coupon_code' => $coupon_code,
                'is_wp_error' => is_wp_error($coupon_id),
                'coupon_get_id' => method_exists($coupon, 'get_id') ? $coupon->get_id() : 'method not available',
            ));
        } catch (Exception $e) {
            Loyalty_Program_Logger::error('Exception while saving coupon', array(
                'coupon_code' => $coupon_code,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ));
            return false;
        } catch (Error $e) {
            Loyalty_Program_Logger::error('Fatal error while saving coupon', array(
                'coupon_code' => $coupon_code,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ));
            return false;
        }

        if ($coupon_id && !is_wp_error($coupon_id)) {
            // Verify coupon was actually saved
            $saved_coupon = new WC_Coupon($coupon_id);
            if (!$saved_coupon->get_id()) {
                Loyalty_Program_Logger::error('Coupon save returned ID but coupon does not exist', array(
                    'coupon_id' => $coupon_id,
                    'coupon_code' => $coupon_code,
                ));
                return false;
            }

            // Mark as loyalty reward coupon
            $coupon->update_meta_data('_loyalty_program_reward_coupon', 'yes');
            $coupon->update_meta_data('_loyalty_program_reward_owner_id', $user_id);
            $coupon->update_meta_data('_loyalty_program_reward_name', $coupon_reward['name']);
            $save_result = $coupon->save();

            Loyalty_Program_Logger::debug('Coupon meta data saved', array(
                'coupon_id' => $coupon_id,
                'save_result' => $save_result,
                'is_wp_error' => is_wp_error($save_result),
            ));

            if (is_wp_error($save_result)) {
                Loyalty_Program_Logger::error('Failed to save coupon meta data', array(
                    'coupon_id' => $coupon_id,
                    'coupon_code' => $coupon_code,
                    'error' => $save_result->get_error_message(),
                ));
                // Still return coupon code as coupon was created, just meta failed
            }

            Loyalty_Program_Logger::info('Reward coupon generated successfully', array(
                'user_id' => $user_id,
                'coupon_id' => $coupon_id,
                'coupon_code' => $coupon_code,
                'coupon_type' => $coupon_type,
                'discount_value' => $discount_value,
                'min_order_amount' => $min_order_amount,
                'reward_name' => $coupon_reward['name'],
            ));

            return $coupon_code;
        }

        // Log error details
        if (is_wp_error($coupon_id)) {
            Loyalty_Program_Logger::error('Failed to save coupon - WP_Error', array(
                'user_id' => $user_id,
                'coupon_code' => $coupon_code,
                'error_message' => $coupon_id->get_error_message(),
                'error_code' => $coupon_id->get_error_code(),
                'error_data' => $coupon_id->get_error_data(),
            ));
        } else {
            Loyalty_Program_Logger::error('Failed to save coupon - no ID returned', array(
                'user_id' => $user_id,
                'coupon_code' => $coupon_code,
                'save_result' => $coupon_id,
            ));
        }

        return false;
    }

    /**
     * Get user's personal coupon code
     * 
     * @param int $user_id User ID
     * @return string|false
     */
    public static function get_user_coupon($user_id)
    {
        return get_user_meta($user_id, self::USER_COUPON_META, true);
    }

    /**
     * Get total count of personal coupons
     * 
     * @return int Total number of personal coupons
     */
    public static function get_personal_coupons_count()
    {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(post_id) FROM {$wpdb->postmeta} 
                 WHERE meta_key = %s 
                 AND meta_value = %s",
                '_loyalty_program_coupon',
                'yes'
            )
        );

        return (int) $count;
    }

    /**
     * Generate personal coupons for all loyalty program members (batch processing)
     * 
     * @param int $offset Offset for batch processing (default: 0)
     * @param int $batch_size Number of users to process per batch (default: 50)
     * @return array Array with 'generated' count, 'skipped' count, 'total' count, 'has_more' boolean
     */
    public static function generate_coupons_batch($offset = 0, $batch_size = 50)
    {
        if (!class_exists('WooCommerce')) {
            return array(
                'error' => true,
                'message' => __('WooCommerce is not active.', 'loyalty-program')
            );
        }

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        global $wpdb;

        // Get total count of loyalty program members
        $total_members = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(user_id) FROM {$wpdb->usermeta} 
                 WHERE meta_key = %s 
                 AND meta_value = %s",
                'loyalty_program_member',
                'yes'
            )
        );

        if ($total_members === 0 || $total_members === null) {
            // Log
            if (!class_exists('Loyalty_Program_Logger')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
            }

            Loyalty_Program_Logger::warning('No loyalty program members found for coupon generation', array(
                'total_members' => $total_members
            ));

            return array(
                'error' => true,
                'message' => __('No loyalty program members found.', 'loyalty-program')
            );
        }

        // Log start of batch
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::debug('Starting coupon generation batch', array(
            'offset' => $offset,
            'batch_size' => $batch_size,
            'total_members' => (int) $total_members
        ));

        // Get batch of member user IDs
        $member_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} 
                 WHERE meta_key = %s 
                 AND meta_value = %s
                 ORDER BY user_id ASC
                 LIMIT %d OFFSET %d",
                'loyalty_program_member',
                'yes',
                $batch_size,
                $offset
            )
        );

        if (empty($member_ids)) {
            return array(
                'generated' => 0,
                'skipped' => 0,
                'total' => (int) $total_members,
                'has_more' => false,
                'processed' => $offset
            );
        }

        $generated_count = 0;
        $skipped_count = 0;

        Loyalty_Program_Logger::debug('Processing member IDs in batch', array(
            'member_ids' => $member_ids,
            'count' => count($member_ids)
        ));

        foreach ($member_ids as $user_id) {
            // Check if user already has a coupon
            $existing_coupon = self::get_user_coupon($user_id);

            if (!empty($existing_coupon)) {
                Loyalty_Program_Logger::debug('User already has coupon - skipping', array(
                    'user_id' => $user_id,
                    'existing_coupon' => $existing_coupon
                ));
                $skipped_count++;
                continue; // Skip - already has coupon
            }

            // Generate coupon
            Loyalty_Program_Logger::debug('Attempting to generate coupon', array(
                'user_id' => $user_id
            ));

            $result = self::generate_personal_coupon($user_id);

            if ($result !== false) {
                Loyalty_Program_Logger::debug('Coupon generated successfully', array(
                    'user_id' => $user_id,
                    'coupon_code' => $result
                ));
                $generated_count++;
            } else {
                Loyalty_Program_Logger::warning('Failed to generate coupon', array(
                    'user_id' => $user_id
                ));
                $skipped_count++;
            }
        }

        $new_offset = $offset + count($member_ids);
        $has_more = $new_offset < $total_members;

        // Log
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::debug('Personal coupons batch generated', array(
            'batch_generated' => $generated_count,
            'batch_skipped' => $skipped_count,
            'offset' => $offset,
            'new_offset' => $new_offset,
            'total_members' => (int) $total_members,
            'has_more' => $has_more,
        ));

        return array(
            'generated' => $generated_count,
            'skipped' => $skipped_count,
            'total' => (int) $total_members,
            'has_more' => $has_more,
            'processed' => $new_offset
        );
    }

    /**
     * Update all existing personal coupons with current settings (batch processing)
     * 
     * @param int $offset Offset for batch processing (default: 0)
     * @param int $batch_size Number of coupons to process per batch (default: 100)
     * @return array Array with 'updated' count, 'total' count, 'has_more' boolean
     */
    public static function update_all_personal_coupons($offset = 0, $batch_size = 100)
    {
        if (!class_exists('WooCommerce')) {
            return array(
                'error' => true,
                'message' => __('WooCommerce is not active.', 'loyalty-program')
            );
        }

        global $wpdb;

        // Get current settings
        $coupon_value = get_option('loyalty_program_coupon_value', 10);
        $min_order_amount = get_option('loyalty_program_coupon_min_amount', 150);

        // Get total count first
        $total_count = self::get_personal_coupons_count();

        if ($total_count === 0) {
            return array(
                'error' => true,
                'message' => __('No personal coupons found to update.', 'loyalty-program')
            );
        }

        // Get batch of loyalty program coupons
        $coupon_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                 WHERE meta_key = %s 
                 AND meta_value = %s
                 LIMIT %d OFFSET %d",
                '_loyalty_program_coupon',
                'yes',
                $batch_size,
                $offset
            )
        );

        if (empty($coupon_ids)) {
            return array(
                'updated' => 0,
                'total' => $total_count,
                'has_more' => false,
                'processed' => $offset
            );
        }

        $updated_count = 0;

        foreach ($coupon_ids as $coupon_id) {
            $coupon = new WC_Coupon($coupon_id);

            if (!$coupon->get_id()) {
                continue;
            }

            // Update coupon settings
            $coupon->set_amount($coupon_value);
            $coupon->set_minimum_amount($min_order_amount);
            $coupon->set_usage_limit(0); // Unlimited
            $coupon->set_usage_limit_per_user(0); // Unlimited per user
            $coupon->save();

            $updated_count++;
        }

        $new_offset = $offset + $updated_count;
        $has_more = $new_offset < $total_count;

        // Log
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::debug('Personal coupons batch updated', array(
            'batch_updated' => $updated_count,
            'offset' => $offset,
            'new_offset' => $new_offset,
            'total' => $total_count,
            'has_more' => $has_more,
            'coupon_value' => $coupon_value,
            'min_amount' => $min_order_amount,
        ));

        return array(
            'updated' => $updated_count,
            'total' => $total_count,
            'has_more' => $has_more,
            'processed' => $new_offset
        );
    }

    /**
     * Set custom price for loyalty reward products
     * 
     * @param WC_Cart $cart Cart object
     * @return void
     */
    public function set_custom_reward_price($cart)
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            // Check if this is a loyalty reward with custom price
            if (isset($cart_item['loyalty_reward_price']) && $cart_item['loyalty_reward_price'] > 0) {
                $cart_item['data']->set_price($cart_item['loyalty_reward_price']);
            }
        }
    }

    /**
     * Add loyalty program custom data to cart item
     * 
     * @param array $cart_item_data Cart item data
     * @param int $product_id Product ID
     * @param int $variation_id Variation ID
     * @return array
     */
    public function add_loyalty_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        // This is handled by JavaScript when adding to cart
        // The data is passed through $_POST
        if (isset($_POST['loyalty_reward_price'])) {
            $cart_item_data['loyalty_reward_price'] = floatval($_POST['loyalty_reward_price']);
        }

        if (isset($_POST['gift_from_loyalty_program'])) {
            $cart_item_data['gift_from_loyalty_program'] = sanitize_text_field($_POST['gift_from_loyalty_program']);
        }

        return $cart_item_data;
    }

    /**
     * Display loyalty program custom meta in cart
     * 
     * @param array $item_data Item data
     * @param array $cart_item Cart item
     * @return array
     */
    public function display_loyalty_cart_item_data($item_data, $cart_item)
    {
        // Check if showing loyalty info in cart is enabled
        $show_cart_info = get_option('loyalty_program_show_cart_info', 'yes');

        if ($show_cart_info === 'yes' && isset($cart_item['gift_from_loyalty_program']) && $cart_item['gift_from_loyalty_program'] === 'yes') {
            $item_data[] = array(
                'key' => __('Loyalty Reward', 'loyalty-program'),
                'value' => __('Gift from Loyalty Program', 'loyalty-program'),
            );
        }

        return $item_data;
    }

    /**
     * Add custom CSS class to loyalty cart items
     * 
     * @param string $class CSS classes
     * @param array $cart_item Cart item data
     * @param string $cart_item_key Cart item key
     * @return string
     */
    public function add_loyalty_cart_item_class($class, $cart_item, $cart_item_key)
    {
        // Check if this is a loyalty reward item
        if (isset($cart_item['gift_from_loyalty_program']) && $cart_item['gift_from_loyalty_program'] === 'yes') {
            $class .= ' loyalty_cart_item_custom';
        }

        return $class;
    }

    /**
     * Save loyalty reward meta data from cart to order item
     * 
     * @param WC_Order_Item_Product $item Order item
     * @param string $cart_item_key Cart item key
     * @param array $values Cart item values
     * @param WC_Order $order Order object
     * @return void
     */
    public function save_loyalty_meta_to_order_item($item, $cart_item_key, $values, $order)
    {
        // Check if this is a loyalty reward
        if (isset($values['gift_from_loyalty_program']) && $values['gift_from_loyalty_program'] === 'yes') {
            // Save all loyalty-related meta data to order item
            $item->add_meta_data('gift_from_loyalty_program', 'yes', true);

            if (isset($values['unique_reward_id'])) {
                $item->add_meta_data('unique_reward_id', $values['unique_reward_id'], true);
            }

            if (isset($values['loyalty_user_id'])) {
                $item->add_meta_data('loyalty_user_id', $values['loyalty_user_id'], true);
            }

            if (isset($values['loyalty_reward_index'])) {
                $item->add_meta_data('loyalty_reward_index', $values['loyalty_reward_index'], true);
            }

            if (isset($values['loyalty_reward_price'])) {
                $item->add_meta_data('loyalty_reward_price', $values['loyalty_reward_price'], true);
            }

            // Log for debugging
            if (!class_exists('Loyalty_Program_Logger')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
            }

            Loyalty_Program_Logger::debug('Loyalty meta saved to order item', array(
                'order_id' => $order->get_id(),
                'product_id' => $item->get_product_id(),
                'unique_reward_id' => $values['unique_reward_id'] ?? 'N/A',
                'user_id' => $values['loyalty_user_id'] ?? 'N/A',
            ));
        }
    }

    /**
     * Mark reward as used when order is completed
     * 
     * @param int $order_id Order ID
     * @return void
     */
    public function mark_reward_as_used($order_id)
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            Loyalty_Program_Logger::error('Order not found', array('order_id' => $order_id));
            return;
        }

        Loyalty_Program_Logger::debug('Starting mark_reward_as_used', array(
            'order_id' => $order_id,
            'order_status' => $order->get_status(),
            'items_count' => count($order->get_items()),
        ));

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $item_meta = $item->get_meta_data();

            // Check if this is a loyalty reward
            $is_loyalty_gift = false;
            $unique_reward_id = '';
            $user_id = null;

            foreach ($item_meta as $meta) {
                if ($meta->key === 'gift_from_loyalty_program' && $meta->value === 'yes') {
                    $is_loyalty_gift = true;
                }
                if ($meta->key === 'unique_reward_id') {
                    $unique_reward_id = $meta->value;
                }
                if ($meta->key === 'loyalty_user_id') {
                    $user_id = intval($meta->value);
                }
            }

            Loyalty_Program_Logger::debug('Processing order item', array(
                'product_id' => $product_id,
                'is_loyalty_gift' => $is_loyalty_gift,
                'unique_reward_id' => $unique_reward_id,
                'user_id' => $user_id,
            ));

            if ($is_loyalty_gift && !empty($unique_reward_id) && $user_id) {
                // Get user's redeemed rewards
                $redeemed_rewards = get_user_meta($user_id, 'loyalty_program_redeemed_rewards', true);

                if (is_array($redeemed_rewards)) {
                    // Find the reward by unique_reward_id
                    foreach ($redeemed_rewards as $idx => $reward_item) {
                        if (isset($reward_item['unique_reward_id']) && $reward_item['unique_reward_id'] === $unique_reward_id) {
                            // Mark as used
                            $redeemed_rewards[$idx]['used'] = 'yes';
                            $redeemed_rewards[$idx]['order_id'] = $order_id;
                            $redeemed_rewards[$idx]['used_date'] = current_time('mysql');

                            update_user_meta($user_id, 'loyalty_program_redeemed_rewards', $redeemed_rewards);

                            // Log
                            Loyalty_Program_Logger::info('Reward marked as used', array(
                                'user_id' => $user_id,
                                'unique_reward_id' => $unique_reward_id,
                                'order_id' => $order_id,
                                'product_id' => $product_id,
                            ));

                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Prevent duplicate loyalty rewards in cart
     * 
     * @param bool $passed Validation status
     * @param int $product_id Product ID
     * @param int $quantity Quantity
     * @return bool
     */
    public function prevent_duplicate_loyalty_rewards($passed, $product_id, $quantity)
    {
        // Only check if this is being added via our AJAX handler
        if (!isset($_POST['action']) || $_POST['action'] !== 'loyalty_program_add_reward_to_cart') {
            return $passed;
        }

        $user_id = get_current_user_id();
        $reward_index = isset($_POST['reward_index']) ? intval($_POST['reward_index']) : -1;

        // Get the unique_reward_id from user's redeemed rewards
        $redeemed_rewards = get_user_meta($user_id, 'loyalty_program_redeemed_rewards', true);

        if (!is_array($redeemed_rewards) || !isset($redeemed_rewards[$reward_index])) {
            return $passed;
        }

        $unique_reward_id = isset($redeemed_rewards[$reward_index]['unique_reward_id']) ? $redeemed_rewards[$reward_index]['unique_reward_id'] : '';

        if (empty($unique_reward_id)) {
            return $passed;
        }

        // Check if this specific reward (by unique_reward_id) is already in cart
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (
                isset($cart_item['gift_from_loyalty_program']) &&
                $cart_item['gift_from_loyalty_program'] === 'yes' &&
                isset($cart_item['unique_reward_id']) &&
                $cart_item['unique_reward_id'] === $unique_reward_id
            ) {

                wc_add_notice(__('This loyalty reward is already in your cart.', 'loyalty-program'), 'error');
                return false;
            }
        }

        return $passed;
    }

    /**
     * Make each loyalty reward unique in cart (separate items)
     * 
     * @param array $cart_item_data Cart item data
     * @param int $product_id Product ID
     * @return array
     */
    public function make_loyalty_rewards_unique($cart_item_data, $product_id)
    {
        // If this is a loyalty reward, use unique_reward_id to prevent merging
        // Each reward gets its own unique_reward_id when redeemed
        // This ensures that even the same product appears as separate cart items
        if (
            isset($cart_item_data['gift_from_loyalty_program']) &&
            $cart_item_data['gift_from_loyalty_program'] === 'yes' &&
            isset($cart_item_data['unique_reward_id'])
        ) {
            // unique_reward_id already set from AJAX handler - it ensures uniqueness
        }

        return $cart_item_data;
    }

    /**
     * Prevent quantity input for loyalty rewards
     * 
     * @param string $product_quantity Quantity HTML
     * @param string $cart_item_key Cart item key
     * @param array $cart_item Cart item
     * @return string
     */
    public function loyalty_reward_quantity_input($product_quantity, $cart_item_key, $cart_item)
    {
        // If this is a loyalty reward, show quantity as text (not editable)
        if (isset($cart_item['gift_from_loyalty_program']) && $cart_item['gift_from_loyalty_program'] === 'yes') {
            return '<span class="loyalty-reward-qty">1</span>';
        }

        return $product_quantity;
    }

    /**
     * Prevent quantity changes for loyalty rewards
     * 
     * @param string $cart_item_key Cart item key
     * @param int $quantity New quantity
     * @param int $old_quantity Old quantity
     * @param object $cart Cart object
     * @return void
     */
    public function prevent_loyalty_reward_quantity_change($cart_item_key, $quantity, $old_quantity, $cart)
    {
        if (
            isset($cart->cart_contents[$cart_item_key]['gift_from_loyalty_program']) &&
            $cart->cart_contents[$cart_item_key]['gift_from_loyalty_program'] === 'yes'
        ) {

            // Always keep quantity at 1 for loyalty rewards
            $cart->cart_contents[$cart_item_key]['quantity'] = 1;
        }
    }

    /**
     * Add Flash Hunter fields to coupon edit page
     * 
     * @param int $coupon_id Coupon ID
     * @param WC_Coupon $coupon Coupon object
     * @return void
     */
    public function add_flash_hunter_fields($coupon_id, $coupon)
    {
        $is_flash_hunter = get_post_meta($coupon_id, '_is_flash_hunter', true);
        $flash_valid_from = get_post_meta($coupon_id, '_flash_hunter_valid_from', true);
        $flash_valid_to = get_post_meta($coupon_id, '_flash_hunter_valid_to', true);

        echo '<div class="options_group">';

        woocommerce_wp_checkbox(array(
            'id' => '_is_flash_hunter',
            'label' => __('Flash Hunter Coupon', 'loyalty-program'),
            'description' => __('Enable this to mark coupon as Flash Hunter promotion.', 'loyalty-program'),
            'value' => $is_flash_hunter === 'yes' ? 'yes' : 'no',
        ));

        echo '<div id="flash-hunter-dates" style="' . ($is_flash_hunter === 'yes' ? '' : 'display:none;') . '">';

        woocommerce_wp_text_input(array(
            'id' => '_flash_hunter_valid_from',
            'label' => __('Flash Hunter Valid From', 'loyalty-program'),
            'placeholder' => 'YYYY-MM-DD HH:MM',
            'description' => __('Start date and time for Flash Hunter validity (format: 2024-12-31 10:00).', 'loyalty-program'),
            'type' => 'datetime-local',
            'value' => $flash_valid_from,
            'custom_attributes' => array(
                'step' => '60',
            ),
        ));

        woocommerce_wp_text_input(array(
            'id' => '_flash_hunter_valid_to',
            'label' => __('Flash Hunter Valid To', 'loyalty-program'),
            'placeholder' => 'YYYY-MM-DD HH:MM',
            'description' => __('End date and time for Flash Hunter validity (format: 2024-12-31 23:59).', 'loyalty-program'),
            'type' => 'datetime-local',
            'value' => $flash_valid_to,
            'custom_attributes' => array(
                'step' => '60',
            ),
        ));

        echo '</div>';
        echo '</div>';

        // JavaScript to toggle date fields
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#_is_flash_hunter').on('change', function() {
        if ($(this).is(':checked')) {
            $('#flash-hunter-dates').slideDown();
        } else {
            $('#flash-hunter-dates').slideUp();
        }
    });
});
</script>
<?php
    }

    /**
     * Save Flash Hunter fields
     * 
     * @param int $coupon_id Coupon ID
     * @param WC_Coupon $coupon Coupon object
     * @return void
     */
    public function save_flash_hunter_fields($coupon_id, $coupon)
    {
        $is_flash_hunter = isset($_POST['_is_flash_hunter']) ? 'yes' : 'no';
        update_post_meta($coupon_id, '_is_flash_hunter', $is_flash_hunter);

        if (isset($_POST['_flash_hunter_valid_from'])) {
            $valid_from = sanitize_text_field($_POST['_flash_hunter_valid_from']);
            update_post_meta($coupon_id, '_flash_hunter_valid_from', $valid_from);
        }

        if (isset($_POST['_flash_hunter_valid_to'])) {
            $valid_to = sanitize_text_field($_POST['_flash_hunter_valid_to']);
            update_post_meta($coupon_id, '_flash_hunter_valid_to', $valid_to);
        }
    }

    /**
     * Validate personal coupon (prevent owner from using their own coupon)
     * 
     * @param bool $valid Whether the coupon is valid
     * @param WC_Coupon $coupon Coupon object
     * @return bool|WP_Error
     */
    public function validate_personal_coupon($valid, $coupon)
    {
        // If already invalid, return as is
        if (!$valid) {
            return $valid;
        }

        // Check if this is a personal loyalty coupon
        $coupon_owner_id = $coupon->get_meta('_loyalty_program_owner_id', true);

        if (empty($coupon_owner_id)) {
            return $valid; // Not a personal coupon, allow it
        }

        // Get current user
        $current_user_id = get_current_user_id();

        if (!$current_user_id) {
            return $valid; // Guest can use it (if they have the code)
        }

        // Check if user is trying to use their own coupon
        if ($current_user_id == $coupon_owner_id) {
            // User is trying to use their own personal coupon - block it!
            Loyalty_Program_Logger::info('User tried to use their own personal coupon (blocked)', array(
                'coupon_code' => $coupon->get_code(),
                'coupon_id' => $coupon->get_id(),
                'user_id' => $current_user_id,
                'coupon_owner_id' => $coupon_owner_id,
            ));

            return new WP_Error(
                'own_coupon_not_allowed',
                __('You cannot use your own personal coupon. Share it with friends to earn points!', 'loyalty-program')
            );
        }

        // Check if "disable personal coupons" option is enabled (global disable)
        $disabled = get_option('loyalty_program_disable_personal_coupons', 'no');
        if ($disabled === 'yes') {
            Loyalty_Program_Logger::info('Personal coupon blocked (disabled globally in settings)', array(
                'coupon_code' => $coupon->get_code(),
                'coupon_id' => $coupon->get_id(),
                'owner_id' => $coupon_owner_id,
                'attempted_by' => $current_user_id,
            ));

            return new WP_Error(
                'personal_coupon_disabled',
                __('Personal loyalty coupons are currently disabled.', 'loyalty-program')
            );
        }

        // Different user is using the coupon - allow it and owner will get points!
        Loyalty_Program_Logger::debug('Personal coupon allowed (different user)', array(
            'coupon_code' => $coupon->get_code(),
            'coupon_owner_id' => $coupon_owner_id,
            'using_user_id' => $current_user_id,
        ));

        return $valid;
    }

    /**
     * Validate personal coupon for cart (check if personal coupons are disabled)
     * 
     * @param bool $valid Whether the coupon is valid
     * @param WC_Coupon $coupon Coupon object
     * @return bool|WP_Error
     */
    public function validate_personal_coupon_for_cart($valid, $coupon)
    {
        // Use the same validation logic
        return $this->validate_personal_coupon($valid, $coupon);
    }

    /**
     * Remove own coupon after it's applied (fallback if validation doesn't catch it)
     * This is called AFTER a coupon is successfully applied to the cart
     * 
     * @param string $coupon_code Coupon code that was applied
     */
    public function remove_own_coupon_after_applied($coupon_code)
    {
        // Get current user
        $current_user_id = get_current_user_id();

        if (!$current_user_id) {
            return; // Guest users can use any coupon
        }

        // Get the coupon object
        $coupon = new WC_Coupon($coupon_code);

        if (!$coupon->get_id()) {
            return; // Coupon doesn't exist
        }

        // Check if this is a personal loyalty coupon
        $coupon_owner_id = $coupon->get_meta('_loyalty_program_owner_id', true);

        if (empty($coupon_owner_id)) {
            return; // Not a personal loyalty coupon
        }

        // Check if user is trying to use their own coupon
        if ($current_user_id == $coupon_owner_id) {
            // Remove the coupon from cart
            WC()->cart->remove_coupon($coupon_code);

            // Add notice to user
            wc_add_notice(
                __('You cannot use your own personal coupon. Share it with friends to earn points!', 'loyalty-program'),
                'error'
            );

            Loyalty_Program_Logger::info('Own coupon removed from cart (fallback)', array(
                'coupon_code' => $coupon_code,
                'user_id' => $current_user_id,
                'coupon_owner_id' => $coupon_owner_id,
            ));
        }
    }

    /**
     * Process Flash Hunter coupon and award points if valid
     * 
     * @param WC_Order $order Order object
     * @param string $coupon_code Coupon code
     * @param int $coupon_id Coupon ID
     * @return void
     */
    private function process_flash_hunter_coupon($order, $coupon_code, $coupon_id)
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        $order_id = $order->get_id();
        $user_id = $order->get_user_id();

        // Check if user is a member
        if (!$user_id || !Loyalty_Program_Points::is_member($user_id)) {
            Loyalty_Program_Logger::debug('Flash Hunter: User not a member', array(
                'order_id' => $order_id,
                'user_id' => $user_id,
            ));
            return;
        }

        // Check if Flash Hunter points already awarded for this coupon
        $flash_awarded = $order->get_meta('_loyalty_flash_hunter_awarded_' . $coupon_id, true);
        if ($flash_awarded === 'yes') {
            Loyalty_Program_Logger::debug('Flash Hunter points already awarded', array(
                'order_id' => $order_id,
                'coupon_code' => $coupon_code,
            ));
            return;
        }

        // Get Flash Hunter validity dates
        $valid_from = get_post_meta($coupon_id, '_flash_hunter_valid_from', true);
        $valid_to = get_post_meta($coupon_id, '_flash_hunter_valid_to', true);

        if (empty($valid_from) || empty($valid_to)) {
            Loyalty_Program_Logger::warning('Flash Hunter coupon missing validity dates', array(
                'coupon_code' => $coupon_code,
                'coupon_id' => $coupon_id,
            ));
            return;
        }

        // Get order date
        $order_date = $order->get_date_created();
        $order_timestamp = $order_date->getTimestamp();

        // Convert validity dates to timestamps
        $valid_from_timestamp = strtotime($valid_from);
        $valid_to_timestamp = strtotime($valid_to);

        Loyalty_Program_Logger::info('Flash Hunter coupon validation', array(
            'coupon_code' => $coupon_code,
            'order_id' => $order_id,
            'order_date' => $order_date->date('Y-m-d H:i:s'),
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
            'is_valid' => ($order_timestamp >= $valid_from_timestamp && $order_timestamp <= $valid_to_timestamp),
        ));

        // Check if order is within Flash Hunter validity period
        if ($order_timestamp >= $valid_from_timestamp && $order_timestamp <= $valid_to_timestamp) {
            // Award Flash Hunter points
            $points_flash_hunter = get_option('loyalty_program_points_flash_hunter', 20);

            if ($points_flash_hunter > 0) {
                $action_desc = sprintf(
                    __('Flash Hunter coupon "%s" used in order #%s', 'loyalty-program'),
                    $coupon_code,
                    $order->get_order_number()
                );

                Loyalty_Program_Points::add_points($user_id, $points_flash_hunter, $action_desc, array(
                    'type' => 'flash_hunter',
                    'order_id' => $order_id,
                    'coupon_code' => $coupon_code,
                    'coupon_id' => $coupon_id,
                ));

                // Add order note
                $order->add_order_note(
                    sprintf(
                        __('Flash Hunter! Customer used coupon "%s" within validity period. %d loyalty points awarded.', 'loyalty-program'),
                        $coupon_code,
                        $points_flash_hunter
                    )
                );

                // Mark as awarded to prevent duplicate points
                $order->update_meta_data('_loyalty_flash_hunter_awarded_' . $coupon_id, 'yes');
                $order->save();

                Loyalty_Program_Logger::info('Flash Hunter points awarded', array(
                    'user_id' => $user_id,
                    'coupon_code' => $coupon_code,
                    'order_id' => $order_id,
                    'points' => $points_flash_hunter,
                ));
            }
        } else {
            Loyalty_Program_Logger::debug('Flash Hunter coupon used outside validity period', array(
                'coupon_code' => $coupon_code,
                'order_id' => $order_id,
                'order_date' => $order_date->date('Y-m-d H:i:s'),
                'valid_from' => $valid_from,
                'valid_to' => $valid_to,
            ));
        }
    }

    /**
     * Add custom fields to WooCommerce account edit form
     * 
     * @return void
     */
    public function add_custom_account_fields()
    {
        // Check if any fields are enabled
        $enable_birth_date = get_option('loyalty_program_enable_birth_date', 'no');
        $enable_sms_consent = get_option('loyalty_program_enable_sms_consent', 'no');
        $enable_newsletter_consent = get_option('loyalty_program_enable_newsletter_consent', 'no');
        $enable_billing_phone = get_option('loyalty_program_enable_billing_phone', 'yes'); // Default 'yes' - telefon jest ważny

        if ($enable_birth_date === 'no' && $enable_sms_consent === 'no' && $enable_newsletter_consent === 'no' && $enable_billing_phone === 'no') {
            return;
        }

        $user_id = get_current_user_id();

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if user is a member of the loyalty program
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return;
        }

        echo '<div class="loyalty-program-account-fields">';
        echo '<h3>' . esc_html__('Additional Information', 'loyalty-program') . '</h3>';

        // Birth Date field
        if ($enable_birth_date === 'yes') {
            $birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);
        ?>
<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
    <label for="loyalty_program_birth_date">
        <?php esc_html_e('Birth Date', 'loyalty-program'); ?>
    </label>
    <input type="date" class="woocommerce-Input woocommerce-Input--text input-text" name="loyalty_program_birth_date"
        id="loyalty_program_birth_date" value="<?php echo esc_attr($birth_date); ?>"
        max="<?php echo date('Y-m-d'); ?>" />
</p>
<?php
        }

        // SMS Consent field
        if ($enable_sms_consent === 'yes') {
            $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true);
        ?>
<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
        <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
            name="loyalty_program_sms_consent" id="loyalty_program_sms_consent" value="yes"
            <?php checked($sms_consent, 'yes'); ?> />
        <span><?php esc_html_e('I consent to receiving SMS notifications', 'loyalty-program'); ?></span>
    </label>
</p>
<?php
        }

        // Newsletter Consent field
        if ($enable_newsletter_consent === 'yes') {
            $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true);
        ?>
<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
        <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
            name="loyalty_program_newsletter_consent" id="loyalty_program_newsletter_consent" value="yes"
            <?php checked($newsletter_consent, 'yes'); ?> />
        <span><?php esc_html_e('I consent to receiving newsletter emails', 'loyalty-program'); ?></span>
    </label>
</p>
<?php
        }

        // Billing Phone field
        if ($enable_billing_phone === 'yes') {
            $billing_phone = get_user_meta($user_id, 'billing_phone', true);
        ?>
<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
    <label for="billing_phone">
        <?php esc_html_e('Phone Number', 'loyalty-program'); ?>
        <span class="required">*</span>
    </label>
    <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_phone"
        id="billing_phone" value="<?php echo esc_attr($billing_phone); ?>"
        placeholder="<?php esc_attr_e('Enter your phone number', 'loyalty-program'); ?>" />
</p>
<?php
        }

        echo '</div>';
    }

    /**
     * Save custom account fields
     * 
     * @param int $user_id User ID
     * @return void
     */
    public function save_custom_account_fields($user_id)
    {
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Check if any fields are enabled
        $enable_birth_date = get_option('loyalty_program_enable_birth_date', 'no');
        $enable_sms_consent = get_option('loyalty_program_enable_sms_consent', 'no');
        $enable_newsletter_consent = get_option('loyalty_program_enable_newsletter_consent', 'no');
        $enable_billing_phone = get_option('loyalty_program_enable_billing_phone', 'yes');

        // Save Birth Date
        if ($enable_birth_date === 'yes' && isset($_POST['loyalty_program_birth_date'])) {
            $birth_date = sanitize_text_field($_POST['loyalty_program_birth_date']);

            // Validate date format
            if (!empty($birth_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
                // Check if birth date was empty before (first time setting)
                $existing_birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);
                $is_first_time = empty($existing_birth_date);

                update_user_meta($user_id, 'loyalty_program_birth_date', $birth_date);

                Loyalty_Program_Logger::info('User birth date updated', array(
                    'user_id' => $user_id,
                    'birth_date' => $birth_date,
                    'is_first_time' => $is_first_time,
                ));

                // Award points if this is the first time setting birth date
                if ($is_first_time) {
                    // Load Points class
                    if (!class_exists('Loyalty_Program_Points')) {
                        require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
                    }

                    // Check if user is a member
                    if (Loyalty_Program_Points::is_member($user_id)) {
                        // Check if points already awarded
                        $points_awarded = get_user_meta($user_id, 'loyalty_program_birthday_points_awarded', true);

                        if ($points_awarded !== 'yes') {
                            // Get birthday points value
                            $birthday_points = get_option('loyalty_program_points_birthday', 25);

                            if ($birthday_points > 0) {
                                // Award points
                                Loyalty_Program_Points::add_points(
                                    $user_id,
                                    $birthday_points,
                                    __('Birth date completed', 'loyalty-program'),
                                    array(
                                        'type' => 'birthday',
                                        'birth_date' => $birth_date,
                                    )
                                );

                                // Mark as awarded
                                update_user_meta($user_id, 'loyalty_program_birthday_points_awarded', 'yes');

                                Loyalty_Program_Logger::info('Birthday points awarded via account form', array(
                                    'user_id' => $user_id,
                                    'points' => $birthday_points,
                                    'birth_date' => $birth_date,
                                ));
                            }
                        }
                    }
                }
            } elseif (empty($birth_date)) {
                // Delete if empty
                delete_user_meta($user_id, 'loyalty_program_birth_date');
            }
        }

        // Save SMS Consent
        if ($enable_sms_consent === 'yes') {
            $sms_consent = isset($_POST['loyalty_program_sms_consent']) ? 'yes' : 'no';
            $previous_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true);

            update_user_meta($user_id, 'loyalty_program_sms_consent', $sms_consent);

            if ($previous_consent !== $sms_consent) {
                Loyalty_Program_Logger::info('User SMS consent updated', array(
                    'user_id' => $user_id,
                    'consent' => $sms_consent,
                    'previous' => $previous_consent,
                ));
            }
        }

        // Save Newsletter Consent
        if ($enable_newsletter_consent === 'yes') {
            $newsletter_consent = isset($_POST['loyalty_program_newsletter_consent']) ? 'yes' : 'no';
            $previous_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true);

            update_user_meta($user_id, 'loyalty_program_newsletter_consent', $newsletter_consent);

            if ($previous_consent !== $newsletter_consent) {
                Loyalty_Program_Logger::info('User newsletter consent updated', array(
                    'user_id' => $user_id,
                    'consent' => $newsletter_consent,
                    'previous' => $previous_consent,
                ));
            }
        }

        // Save Billing Phone
        if ($enable_billing_phone === 'yes' && isset($_POST['billing_phone'])) {
            $billing_phone = sanitize_text_field($_POST['billing_phone']);
            $previous_phone = get_user_meta($user_id, 'billing_phone', true);

            // Validate phone is not empty
            if (!empty($billing_phone)) {
                update_user_meta($user_id, 'billing_phone', $billing_phone);

                if ($previous_phone !== $billing_phone) {
                    Loyalty_Program_Logger::info('User billing phone updated', array(
                        'user_id' => $user_id,
                        'phone' => $billing_phone,
                        'previous' => $previous_phone,
                    ));
                }
            } elseif (empty($billing_phone) && !empty($previous_phone)) {
                // Delete if empty (user cleared the field)
                delete_user_meta($user_id, 'billing_phone');

                Loyalty_Program_Logger::info('User billing phone cleared', array(
                    'user_id' => $user_id,
                    'previous' => $previous_phone,
                ));
            }
        }

        // Check if user completed profile and award points (only once)
        $this->check_and_award_profile_completion_points($user_id);

        // Sync user data with SalesManago
        if (!class_exists('Loyalty_Program_SalesManago')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/integrations/class-loyalty-program-salesmanago.php';
        }

        if (Loyalty_Program_SalesManago::is_enabled()) {
            $user = get_userdata($user_id);
            if ($user) {
                $email = $user->user_email;
                $first_name = get_user_meta($user_id, 'first_name', true);
                $last_name = get_user_meta($user_id, 'last_name', true);
                $phone = get_user_meta($user_id, 'billing_phone', true);
                $birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);
                $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true) === 'yes';
                $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true) === 'yes';

                // Prepare contact data
                $contact_data = array(
                    'name' => $first_name,
                    'lastName' => $last_name,
                    'phone' => $phone,
                    'birthday' => $birth_date ? date('Y-m-d', strtotime($birth_date)) : null,
                    'streetAddress' => get_user_meta($user_id, 'billing_address_1', true),
                    'zipCode' => get_user_meta($user_id, 'billing_postcode', true),
                    'city' => get_user_meta($user_id, 'billing_city', true),
                    'country' => get_user_meta($user_id, 'billing_country', true),
                    'province' => get_user_meta($user_id, 'billing_state', true),
                );

                // Prepare consents
                $consents = array(
                    'sms' => $sms_consent,
                    'newsletter' => $newsletter_consent,
                );

                Loyalty_Program_Logger::info('🔄 Aktualizacja danych użytkownika - WooCommerce "Moje konto"', array(
                    'user_id' => $user_id,
                    'email' => $email,
                    'trigger' => 'WooCommerce account form save',
                ));

                // Sync with SalesManago
                $result = Loyalty_Program_SalesManago::upsert_contact(
                    $email,
                    $contact_data,
                    array('Program lojalnościowy'),
                    $consents
                );

                if ($result['success']) {
                    Loyalty_Program_Logger::info('✅ Dane użytkownika zsynchronizowane z SalesManago', array(
                        'user_id' => $user_id,
                        'email' => $email,
                        'contact_id' => isset($result['contactId']) ? $result['contactId'] : null,
                        'source' => 'WooCommerce account form',
                    ));
                } else {
                    Loyalty_Program_Logger::error('❌ Błąd synchronizacji z SalesManago', array(
                        'user_id' => $user_id,
                        'email' => $email,
                        'error' => $result['message'],
                        'source' => 'WooCommerce account form',
                    ));
                }
            }
        }
    }

    /**
     * Check for Supplementation Discipline achievement
     * Award points if user purchased same product 3 times within 3 months
     * 
     * @param int $user_id User ID
     * @param WC_Order $order Current order
     * @return void
     */
    private function check_supplementation_discipline($user_id, $order)
    {
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Get points value
        $discipline_points = get_option('loyalty_program_points_supplementation_discipline', 50);
        if ($discipline_points <= 0) {
            return; // Feature disabled
        }

        $order_id = $order->get_id();
        $current_time = current_time('timestamp');
        $three_months_ago = $current_time - (90 * DAY_IN_SECONDS); // 3 months = 90 days

        // Get detailed purchase history (product_id => array of purchase timestamps)
        $detailed_history = get_user_meta($user_id, 'loyalty_program_detailed_purchase_history', true);
        if (!is_array($detailed_history)) {
            $detailed_history = array();
        }

        // Fix structure - ensure we have product_id => array(timestamps) format
        $needs_fix = false;

        // Check if first level has sequential numeric keys (0, 1, 2) instead of product IDs
        if (isset($detailed_history[0]) && is_array($detailed_history[0])) {
            // Check if value at key 0 contains product arrays
            foreach ($detailed_history[0] as $inner_key => $inner_value) {
                if (is_array($inner_value) && isset($inner_value[0])) {
                    // This looks like product_id => array(timestamps) which is good
                    // But it's nested under key 0 which is bad
                    $needs_fix = true;
                    break;
                }
            }
        }

        if ($needs_fix) {
            Loyalty_Program_Logger::info('Detected bad structure, fixing...', array(
                'user_id' => $user_id,
                'old_structure' => $detailed_history
            ));

            // Flatten the structure
            $detailed_history = $detailed_history[0];
            update_user_meta($user_id, 'loyalty_program_detailed_purchase_history', $detailed_history);

            Loyalty_Program_Logger::info('Fixed purchase history structure', array(
                'user_id' => $user_id,
                'new_structure' => $detailed_history
            ));
        }

        // Get products from current order
        $items = $order->get_items();

        foreach ($items as $item) {
            $parent_product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();

            // Use variation ID if it's a variable product, otherwise use product ID
            // This ensures each variant is tracked separately
            $tracked_product_id = $variation_id > 0 ? $variation_id : $parent_product_id;
            $product_name = $item->get_name();

            // Check if discipline is enabled for THIS product/variation
            // For variations: check on variation itself
            // For simple products: check on product
            $discipline_enabled = get_post_meta($tracked_product_id, '_loyalty_discipline_enabled', true);
            if ($discipline_enabled !== 'yes') {
                continue; // Skip products without discipline enabled
            }

            // Initialize product history if doesn't exist (using tracked_product_id)
            if (!isset($detailed_history[$tracked_product_id])) {
                $detailed_history[$tracked_product_id] = array();
            }

            // Add current purchase to history (using tracked_product_id)
            $detailed_history[$tracked_product_id][] = $current_time;

            // Clean old purchases (older than 3 months) to keep data clean
            $detailed_history[$tracked_product_id] = array_filter($detailed_history[$tracked_product_id], function ($timestamp) use ($three_months_ago) {
                return $timestamp >= $three_months_ago;
            });

            // Re-index array after filter
            $detailed_history[$tracked_product_id] = array_values($detailed_history[$tracked_product_id]);

            // Count purchases in last 3 months
            $purchases_count = count($detailed_history[$tracked_product_id]);

            Loyalty_Program_Logger::debug('Checking supplementation discipline', array(
                'user_id' => $user_id,
                'parent_product_id' => $parent_product_id,
                'variation_id' => $variation_id,
                'tracked_product_id' => $tracked_product_id,
                'product_name' => $product_name,
                'purchases_in_3_months' => $purchases_count,
            ));

            // Award points if 3 or more purchases within 3 months (handles edge cases from old data)
            if ($purchases_count >= 3) {
                $action_desc = sprintf(
                    __('Supplementation Discipline: %s (3 purchases in 3 months)', 'loyalty-program'),
                    $product_name
                );

                Loyalty_Program_Points::add_points(
                    $user_id,
                    $discipline_points,
                    $action_desc,
                    array(
                        'type' => 'supplementation_discipline',
                        'parent_product_id' => $parent_product_id,
                        'variation_id' => $variation_id,
                        'tracked_product_id' => $tracked_product_id,
                        'order_id' => $order_id,
                        'purchases_count' => $purchases_count,
                    )
                );

                // RESET COUNTER - clear purchase history for this product to allow earning again
                $detailed_history[$tracked_product_id] = array();

                // Add order note
                $order->add_order_note(
                    sprintf(
                        __('Supplementation Discipline bonus: %d points for purchasing "%s" 3 times in 3 months! Counter reset.', 'loyalty-program'),
                        $discipline_points,
                        $product_name
                    )
                );

                Loyalty_Program_Logger::info('Supplementation Discipline points awarded - counter reset', array(
                    'user_id' => $user_id,
                    'parent_product_id' => $parent_product_id,
                    'variation_id' => $variation_id,
                    'tracked_product_id' => $tracked_product_id,
                    'product_name' => $product_name,
                    'order_id' => $order_id,
                    'points' => $discipline_points,
                    'counter_reset' => true,
                ));
            }
        }

        // Save updated history
        update_user_meta($user_id, 'loyalty_program_detailed_purchase_history', $detailed_history);
    }

    /**
     * Check if user has completed profile and award points (only once)
     * 
     * @param int $user_id User ID
     * @return void
     */
    private function check_and_award_profile_completion_points($user_id)
    {
        // Check if points already awarded
        $already_awarded = get_user_meta($user_id, 'loyalty_program_profile_complete_awarded', true);
        if ($already_awarded === 'yes') {
            return;
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if user is a loyalty program member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return;
        }

        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Get all required data
        $birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);
        $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true);
        $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true);
        $billing_phone = get_user_meta($user_id, 'billing_phone', true);

        Loyalty_Program_Logger::debug('Checking profile completion', array(
            'user_id' => $user_id,
            'birth_date' => !empty($birth_date) ? 'filled' : 'empty',
            'sms_consent' => $sms_consent,
            'newsletter_consent' => $newsletter_consent,
            'billing_phone' => !empty($billing_phone) ? 'filled' : 'empty',
        ));

        // Check if all required fields are filled
        $profile_complete = !empty($birth_date)
            && $sms_consent === 'yes'
            && $newsletter_consent === 'yes'
            && !empty($billing_phone);

        if ($profile_complete) {
            // Get points value for profile completion
            $points = get_option('loyalty_program_points_profile_complete', 75);

            if ($points > 0) {
                // Award points
                Loyalty_Program_Points::add_points(
                    $user_id,
                    $points,
                    __('Profile completed (Birth Date, SMS Consent, Newsletter Consent, Phone Number)', 'loyalty-program'),
                    array(
                        'type' => 'profile_complete',
                    )
                );

                // Mark as awarded to prevent duplicate points
                update_user_meta($user_id, 'loyalty_program_profile_complete_awarded', 'yes');

                Loyalty_Program_Logger::info('Profile completion points awarded', array(
                    'user_id' => $user_id,
                    'points' => $points,
                    'birth_date' => $birth_date,
                    'sms_consent' => $sms_consent,
                    'newsletter_consent' => $newsletter_consent,
                    'billing_phone' => !empty($billing_phone) ? 'yes' : 'no',
                ));
            }
        } else {
            Loyalty_Program_Logger::debug('Profile not complete yet', array(
                'user_id' => $user_id,
                'missing_fields' => array(
                    'birth_date' => empty($birth_date),
                    'sms_consent' => $sms_consent !== 'yes',
                    'newsletter_consent' => $newsletter_consent !== 'yes',
                    'billing_phone' => empty($billing_phone),
                ),
            ));
        }
    }

    /**
     * Convert order amount to base currency (PLN)
     * 
     * @param float $amount Amount to convert
     * @param string $currency Currency code (e.g., EUR, USD, PLN)
     * @return float Amount in base currency
     */
    private function convert_to_base_currency($amount, $currency)
    {
        // Get base currency (default WooCommerce currency, typically PLN)
        $base_currency = get_option('woocommerce_currency', 'PLN');

        // If same currency, no conversion needed
        if ($currency === $base_currency) {
            Loyalty_Program_Logger::debug('No currency conversion needed', array(
                'amount' => $amount,
                'currency' => $currency,
                'base_currency' => $base_currency,
            ));
            return $amount;
        }

        // Check if WooCommerce Multi Currency is active
        if (!class_exists('WOOMULTI_CURRENCY')) {
            Loyalty_Program_Logger::debug('WMC not active - no conversion', array(
                'amount' => $amount,
                'currency' => $currency,
            ));
            return $amount;
        }

        // Get WMC currency settings
        $wmc_params = get_option('woo_multi_currency_params', array());

        if (empty($wmc_params['currency']) || !is_array($wmc_params['currency'])) {
            Loyalty_Program_Logger::warning('WMC currencies not configured', array(
                'amount' => $amount,
                'currency' => $currency,
            ));
            return $amount;
        }

        // Find the currency in WMC settings
        $currencies = $wmc_params['currency'];
        $currency_rates = isset($wmc_params['currency_rate']) ? $wmc_params['currency_rate'] : array();

        $currency_index = array_search($currency, $currencies);

        if ($currency_index === false) {
            Loyalty_Program_Logger::warning('Currency not found in WMC settings', array(
                'amount' => $amount,
                'currency' => $currency,
                'available_currencies' => $currencies,
            ));
            return $amount;
        }

        // Get exchange rate
        $exchange_rate = isset($currency_rates[$currency_index]) ? floatval($currency_rates[$currency_index]) : 1;

        if ($exchange_rate <= 0) {
            Loyalty_Program_Logger::error('Invalid exchange rate', array(
                'amount' => $amount,
                'currency' => $currency,
                'exchange_rate' => $exchange_rate,
            ));
            return $amount;
        }

        // Convert to base currency
        // If rate is 0.2222222 (1 PLN = 0.2222222 EUR), then 1 EUR = 1/0.2222222 PLN
        // So: amount_in_pln = amount_in_eur / exchange_rate
        $converted_amount = $amount / $exchange_rate;

        Loyalty_Program_Logger::info('Currency conversion applied', array(
            'original_amount' => $amount,
            'original_currency' => $currency,
            'exchange_rate' => $exchange_rate,
            'converted_amount' => $converted_amount,
            'base_currency' => $base_currency,
            'calculation' => sprintf(
                '%s %s / %s = %s %s',
                number_format($amount, 2),
                $currency,
                number_format($exchange_rate, 7),
                number_format($converted_amount, 2),
                $base_currency
            ),
        ));

        return $converted_amount;
    }

    /**
     * Add Supplementation Discipline checkbox to product page
     * Displays under General tab, below price
     * 
     * @return void
     */
    public function add_discipline_checkbox()
    {
        global $post;

        echo '<div class="options_group">';

        woocommerce_wp_checkbox(array(
            'id' => '_loyalty_discipline_enabled',
            'label' => __('Supplementation Discipline', 'loyalty-program'),
            'description' => __('Enable discipline tracking for this product. Users who purchase this product 3 times within 3 months will receive bonus loyalty points.', 'loyalty-program'),
            'desc_tip' => true,
        ));

        echo '</div>';
    }

    /**
     * Save Supplementation Discipline checkbox value
     * 
     * @param int $post_id Product ID
     * @return void
     */
    public function save_discipline_checkbox($post_id)
    {
        $is_enabled = isset($_POST['_loyalty_discipline_enabled']) ? 'yes' : 'no';
        update_post_meta($post_id, '_loyalty_discipline_enabled', $is_enabled);
    }

    /**
     * Add Supplementation Discipline checkbox to product variation
     * Displays for each variation separately
     * 
     * @param int $loop Position in the loop
     * @param array $variation_data Variation data
     * @param WP_Post $variation Variation post object
     * @return void
     */
    public function add_discipline_checkbox_variation($loop, $variation_data, $variation)
    {
        $variation_id = $variation->ID;
        $is_enabled = get_post_meta($variation_id, '_loyalty_discipline_enabled', true);

        ?>
<div class="form-row form-row-full loyalty-discipline-variation-field">
    <label>
        <input type="checkbox" name="_loyalty_discipline_enabled[<?php echo esc_attr($loop); ?>]" value="yes"
            <?php checked($is_enabled, 'yes'); ?> />
        <?php _e('Enable Supplementation Discipline for this variation', 'loyalty-program'); ?>
    </label>
    <span class="woocommerce-help-tip"
        data-tip="<?php esc_attr_e('Users who purchase this specific variation 3 times within 3 months will receive bonus loyalty points.', 'loyalty-program'); ?>"></span>
</div>
<?php
    }

    /**
     * Save Supplementation Discipline checkbox value for variation
     * 
     * @param int $variation_id Variation ID
     * @param int $loop Position in the loop
     * @return void
     */
    public function save_discipline_checkbox_variation($variation_id, $loop)
    {
        $is_enabled = isset($_POST['_loyalty_discipline_enabled'][$loop]) ? 'yes' : 'no';
        update_post_meta($variation_id, '_loyalty_discipline_enabled', $is_enabled);
    }

    /**
     * Add custom account page to My Account menu
     * 
     * @param array $items Menu items
     * @return array Modified menu items
     */
    public function add_custom_account_page_to_menu($items)
    {
        $custom_page_id = get_option('loyalty_program_account_custom_page', '');

        if (empty($custom_page_id)) {
            return $items;
        }

        $page = get_post($custom_page_id);
        if (!$page) {
            return $items;
        }

        // Get page title
        $page_title = $page->post_title;

        // Remove logout from items
        $logout = isset($items['customer-logout']) ? $items['customer-logout'] : null;
        unset($items['customer-logout']);

        // Add custom page as second-to-last item
        $items['loyalty-custom-page'] = $page_title;

        // Add logout back as last item
        if ($logout) {
            $items['customer-logout'] = $logout;
        }

        return $items;
    }

    /**
     * Change custom account page URL to direct page link
     * 
     * @param string $url Endpoint URL
     * @param string $endpoint Endpoint name
     * @param string $value Endpoint value
     * @param string $permalink Permalink
     * @return string Modified URL
     */
    public function custom_account_page_url($url, $endpoint, $value, $permalink)
    {
        if ($endpoint === 'loyalty-custom-page') {
            $custom_page_id = get_option('loyalty_program_account_custom_page', '');

            if (!empty($custom_page_id)) {
                $page_url = get_permalink($custom_page_id);
                if ($page_url) {
                    return $page_url;
                }
            }
        }

        return $url;
    }
}