<?php

/**
 * Frontend Handler Class
 *
 * @package Weblo_Pickup_Point
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Weblo Pick-up Point Frontend Handler
 */
class Weblo_Pickup_Point_Frontend
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Enqueue scripts and styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

		// Display pickup point selector on checkout
		// Use multiple hooks to ensure selector is always rendered
		add_action('woocommerce_review_order_after_shipping', array($this, 'display_pickup_point_selector'));
		add_action('woocommerce_checkout_after_order_review', array($this, 'display_pickup_point_selector'));
		add_action('woocommerce_review_order_before_payment', array($this, 'display_pickup_point_selector'));

		// Save selected pickup point to session
		add_action('woocommerce_checkout_update_order_review', array($this, 'save_pickup_point_to_session'));

		// Clear shipping cache after updating pickup point to force recalculation
		add_action('woocommerce_checkout_update_order_review', array($this, 'clear_shipping_cache'), 20);

		// AJAX handler to update pickup point in session
		add_action('wp_ajax_weblo_update_pickup_point', array($this, 'ajax_update_pickup_point'));
		add_action('wp_ajax_nopriv_weblo_update_pickup_point', array($this, 'ajax_update_pickup_point'));

		// Validate pickup point selection
		add_action('woocommerce_checkout_process', array($this, 'validate_pickup_point_selection'));

		// Save pickup point to order meta
		add_action('woocommerce_checkout_create_order', array($this, 'save_pickup_point_to_order'), 10, 2);

		// Display pickup point in order details (after shipping method in customer details)
		// Try multiple hooks to ensure it displays in the right place
		add_action('woocommerce_order_details_after_shipping_address', array($this, 'display_pickup_point_in_order_details'));
		add_action('woocommerce_order_details_after_order_table_items', array($this, 'display_pickup_point_in_order_details'));
		add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'display_pickup_point_in_admin'));

		// Display pickup point in emails (after shipping method)
		add_action('woocommerce_email_order_details', array($this, 'display_pickup_point_in_email'), 10, 4);

		// Filter shipping method label and cost to update dynamically (global for all instances)
		add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'update_shipping_method_label'), 10, 2);
		add_filter('woocommerce_package_rates', array($this, 'update_shipping_rate_cost'), 10, 2);
		add_filter('woocommerce_shipping_packages', array($this, 'update_shipping_packages'), 10, 1);

		// Register shortcode for pickup point display
		add_shortcode('weblo_pickup_point', array($this, 'shortcode_pickup_point'));
	}

	/**
	 * AJAX handler to update pickup point in session
	 */
	public function ajax_update_pickup_point()
	{
		// Verify nonce if available
		if (isset($_POST['security'])) {
			if (!wp_verify_nonce($_POST['security'], 'update-order-review')) {
				wp_send_json_error(array('message' => __('Security check failed', 'weblo-pickup-point')));
				return;
			}
		}

		if (!WC()->session) {
			wp_send_json_error(array('message' => __('Session not available', 'weblo-pickup-point')));
			return;
		}

		$pickup_point = isset($_POST['pickup_point']) ? sanitize_text_field($_POST['pickup_point']) : '';

		if (!empty($pickup_point)) {
			WC()->session->set('weblo_selected_pickup_point', $pickup_point);
		} else {
			WC()->session->__unset('weblo_selected_pickup_point');
		}

		// Clear shipping cache to force recalculation
		if (WC()->cart) {
			WC()->session->__unset('shipping_for_package');
			WC()->cart->calculate_shipping();
		}

		wp_send_json_success(array('pickup_point' => $pickup_point));
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts()
	{
		if (! is_checkout()) {
			return;
		}

		wp_enqueue_script(
			'weblo-pickup-point-frontend',
			WEBLO_PICKUP_POINT_PLUGIN_URL . 'assets/js/frontend.js',
			array('jquery', 'wc-checkout'),
			WEBLO_PICKUP_POINT_VERSION,
			true
		);

		wp_enqueue_style(
			'weblo-pickup-point-frontend',
			WEBLO_PICKUP_POINT_PLUGIN_URL . 'assets/css/style.css',
			array(),
			WEBLO_PICKUP_POINT_VERSION
		);

		$pickup_points = $this->get_pickup_points();
		$pickup_points_json = array();
		if (!empty($pickup_points)) {
			foreach ($pickup_points as $point) {
				$pickup_points_json[] = array(
					'name' => $point['name'],
					'price' => $point['price'],
				);
			}
		}

		wp_localize_script(
			'weblo-pickup-point-frontend',
			'webloPickupPoint',
			array(
				'shippingMethodId' => 'weblo_pickup_point',
				'pickupPoints' => $pickup_points_json,
				'ajaxUrl' => admin_url('admin-ajax.php'),
			)
		);
	}

	/**
	 * Get available shipping methods instances
	 *
	 * @return array
	 */
	private function get_shipping_method_instances()
	{
		$instances = array();

		if (! WC()->shipping()) {
			return $instances;
		}

		$shipping_zones = WC_Shipping_Zones::get_zones();
		$worldwide_zone = new WC_Shipping_Zone(0);

		// Get instances from all zones
		foreach ($shipping_zones as $zone) {
			$zone_obj = new WC_Shipping_Zone($zone['zone_id']);
			$methods = $zone_obj->get_shipping_methods(true);
			foreach ($methods as $method) {
				if ($method->id === 'weblo_pickup_point') {
					$instances[] = $method;
				}
			}
		}

		// Check worldwide zone
		$methods = $worldwide_zone->get_shipping_methods(true);
		foreach ($methods as $method) {
			if ($method->id === 'weblo_pickup_point') {
				$instances[] = $method;
			}
		}

		return $instances;
	}

	/**
	 * Get pickup points from active shipping method
	 *
	 * @return array
	 */
	private function get_pickup_points()
	{
		$instances = $this->get_shipping_method_instances();
		$all_points = array();

		foreach ($instances as $instance) {
			$points = $instance->get_pickup_points();
			$all_points = array_merge($all_points, $points);
		}

		return $all_points;
	}

	/**
	 * Check if pickup point method is selected
	 *
	 * @return bool
	 */
	private function is_pickup_point_method_selected()
	{
		if (! WC()->session) {
			return false;
		}

		$chosen_methods = WC()->session->get('chosen_shipping_methods', array());
		if (empty($chosen_methods)) {
			return false;
		}

		foreach ($chosen_methods as $method) {
			if (strpos($method, 'weblo_pickup_point') === 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Display pickup point selector on checkout
	 */
	public function display_pickup_point_selector()
	{
		// Prevent duplicate rendering - check if selector already exists
		static $rendered = false;
		if ($rendered) {
			return;
		}

		$pickup_points = $this->get_pickup_points();
		if (empty($pickup_points)) {
			return;
		}

		$selected_point = WC()->session->get('weblo_selected_pickup_point', '');

		// If no point selected, select first one by default
		if (empty($selected_point) && ! empty($pickup_points)) {
			$selected_point = $pickup_points[0]['name'];
			WC()->session->set('weblo_selected_pickup_point', $selected_point);
		}

		// Always render the selector, JavaScript will control visibility
		// Hide by default if method is not selected, JavaScript will show it when method is selected
		$is_method_selected = $this->is_pickup_point_method_selected();
		$display_style = $is_method_selected ? '' : ' style="display:none;"';

		$rendered = true;
?>
<div class="weblo-pickup-point-selector" <?php echo $display_style; ?>>

    <label>
        <?php esc_html_e('Select a pick-up point', 'weblo-pickup-point'); ?>
    </label>
    <ul class="weblo-pickup-point-radio-list" <?php echo $display_style; ?>>
        <?php foreach ($pickup_points as $index => $point) : ?>
        <li>
            <label class="weblo-pickup-point-radio-label">
                <input type="radio" name="weblo_pickup_point" value="<?php echo esc_attr($point['name']); ?>"
                    class="weblo-pickup-point-radio" <?php checked($selected_point, $point['name']); ?> />
                <span class="weblo-pickup-point-name"><?php echo esc_html($point['name']); ?></span>
                <span class="weblo-pickup-point-price"><?php echo wc_price($point['price']); ?></span>
            </label>
        </li>
        <?php endforeach; ?>
    </ul>

</div>
<?php
	}

	/**
	 * Save pickup point to session
	 *
	 * @param string $post_data POST data
	 */
	public function save_pickup_point_to_session($post_data)
	{
		if (! WC()->session) {
			return;
		}

		parse_str($post_data, $data);

		if (isset($data['weblo_pickup_point']) && ! empty($data['weblo_pickup_point'])) {
			WC()->session->set('weblo_selected_pickup_point', sanitize_text_field($data['weblo_pickup_point']));
		} else {
			WC()->session->__unset('weblo_selected_pickup_point');
		}
	}

	/**
	 * Clear shipping cache to force recalculation
	 *
	 * @param string $post_data POST data
	 */
	public function clear_shipping_cache($post_data)
	{
		if (! WC()->session || ! WC()->cart) {
			return;
		}

		// Clear all shipping-related cache
		WC()->session->__unset('shipping_for_package');
		WC()->session->__unset('chosen_shipping_methods');

		// Clear cart shipping cache
		if (method_exists(WC()->cart, 'get_shipping_packages')) {
			$packages = WC()->cart->get_shipping_packages();
			foreach ($packages as $key => $package) {
				WC()->session->__unset('shipping_for_package_' . $key);
			}
		}

		// Force recalculation of shipping
		WC()->cart->calculate_shipping();
	}

	/**
	 * Validate pickup point selection
	 */
	public function validate_pickup_point_selection()
	{
		if (! $this->is_pickup_point_method_selected()) {
			return;
		}

		$selected_point = WC()->session->get('weblo_selected_pickup_point');

		if (empty($selected_point)) {
			wc_add_notice(
				__('Please select a pick-up point to continue.', 'weblo-pickup-point'),
				'error'
			);
		}
	}

	/**
	 * Save pickup point to order meta
	 *
	 * @param WC_Order $order Order object
	 * @param array    $data  Checkout data
	 */
	public function save_pickup_point_to_order($order, $data)
	{
		if (! WC()->session) {
			return;
		}

		$selected_point = WC()->session->get('weblo_selected_pickup_point');
		if (! empty($selected_point)) {
			// Get price for the selected point
			$pickup_points = $this->get_pickup_points();
			$point_price = 0;
			foreach ($pickup_points as $point) {
				if ($point['name'] === $selected_point) {
					$point_price = $point['price'];
					break;
				}
			}

			$order->update_meta_data('_weblo_pickup_point_location', $selected_point);
			$order->update_meta_data('_weblo_pickup_point_price', $point_price);
			$order->save();
		}
	}

	/**
	 * Display pickup point in order details
	 *
	 * @param WC_Order $order Order object
	 */
	public function display_pickup_point_in_order_details($order)
	{
		static $rendered = false;
		if ($rendered) {
			return;
		}

		$location = $order->get_meta('_weblo_pickup_point_location');
		if (empty($location)) {
			return;
		}

		// Check if shipping method is our pickup point method
		$shipping_methods = $order->get_shipping_methods();
		$is_our_method = false;
		$instance_id = 0;

		foreach ($shipping_methods as $shipping_method) {
			if (strpos($shipping_method->get_method_id(), 'weblo_pickup_point') !== false) {
				$is_our_method = true;
				// Extract instance ID from instance_id or method_id
				$instance_id = $shipping_method->get_instance_id();
				if (empty($instance_id)) {
					// Try to extract from method_id (format: weblo_pickup_point:49)
					$method_id = $shipping_method->get_method_id();
					if (strpos($method_id, ':') !== false) {
						$parts = explode(':', $method_id);
						$instance_id = isset($parts[1]) ? (int) $parts[1] : 0;
					}
				}
				break;
			}
		}

		if (! $is_our_method) {
			return;
		}

		// Check if option to show in order confirmation is enabled
		if (! empty($instance_id)) {
			$shipping_method_instance = new Weblo_Pickup_Point_Shipping_Method($instance_id);
			$show_in_confirmation = $shipping_method_instance->get_option('show_in_order_confirmation', 'yes');

			if ($show_in_confirmation !== 'yes') {
				return;
			}
		}

		$rendered = true;
	?>
<div class="woocommerce-order-pickup-point">
    <p>
        <strong><?php esc_html_e('Location:', 'weblo-pickup-point'); ?></strong>
        <?php echo esc_html($location); ?>
    </p>
</div>
<?php
	}

	/**
	 * Display pickup point in admin order details
	 *
	 * @param WC_Order $order Order object
	 */
	public function display_pickup_point_in_admin($order)
	{
		$location = $order->get_meta('_weblo_pickup_point_location');
		if (empty($location)) {
			return;
		}

		$price = $order->get_meta('_weblo_pickup_point_price');

	?>
<div class="weblo-pickup-point-info">
    <h3><?php esc_html_e('Pick-up point', 'weblo-pickup-point'); ?></h3>
    <p>
        <strong><?php esc_html_e('Location:', 'weblo-pickup-point'); ?></strong>
        <?php echo esc_html($location); ?>
    </p>
    <?php if ($price > 0) : ?>
    <p>
        <strong><?php esc_html_e('Shipping cost:', 'weblo-pickup-point'); ?></strong>
        <?php echo wc_price($price); ?>
    </p>
    <?php endif; ?>
</div>
<?php
	}

	/**
	 * Display pickup point in email
	 *
	 * @param WC_Order $order         Order object
	 * @param bool     $sent_to_admin Whether email is sent to admin
	 * @param bool     $plain_text    Whether email is plain text
	 * @param object   $email         Email object
	 */
	public function display_pickup_point_in_email($order, $sent_to_admin, $plain_text, $email)
	{
		$location = $order->get_meta('_weblo_pickup_point_location');
		if (empty($location)) {
			return;
		}

		// Check if shipping method is our pickup point method
		$shipping_methods = $order->get_shipping_methods();
		$is_our_method = false;
		$instance_id = 0;

		foreach ($shipping_methods as $shipping_method) {
			if (strpos($shipping_method->get_method_id(), 'weblo_pickup_point') !== false) {
				$is_our_method = true;
				// Extract instance ID from instance_id or method_id
				$instance_id = $shipping_method->get_instance_id();
				if (empty($instance_id)) {
					// Try to extract from method_id (format: weblo_pickup_point:49)
					$method_id = $shipping_method->get_method_id();
					if (strpos($method_id, ':') !== false) {
						$parts = explode(':', $method_id);
						$instance_id = isset($parts[1]) ? (int) $parts[1] : 0;
					}
				}
				break;
			}
		}

		if (! $is_our_method) {
			return;
		}

		// Check if option to show in order confirmation is enabled
		if (! empty($instance_id)) {
			$shipping_method_instance = new Weblo_Pickup_Point_Shipping_Method($instance_id);
			$show_in_confirmation = $shipping_method_instance->get_option('show_in_order_confirmation', 'yes');

			if ($show_in_confirmation !== 'yes') {
				return;
			}
		}

		if ($plain_text) {
			echo "\n" . __('Location:', 'weblo-pickup-point') . ' ' . esc_html($location) . "\n";
		} else {
		?>
<div class="woocommerce-order-pickup-point">
    <p>
        <strong><?php esc_html_e('Location:', 'weblo-pickup-point'); ?></strong>
        <?php echo esc_html($location); ?>
    </p>
</div>
<?php
		}
	}

	/**
	 * Update shipping method label dynamically
	 *
	 * @param string $label Shipping method label
	 * @param WC_Shipping_Rate $method Shipping method object
	 * @return string
	 */
	public function update_shipping_method_label($label, $method)
	{
		// Don't modify the label - let WooCommerce handle it
		// JavaScript will update the price dynamically
		return $label;
	}

	/**
	 * Update shipping rate cost dynamically
	 *
	 * @param array $rates Shipping rates
	 * @param string $package Package key
	 * @return array
	 */
	public function update_shipping_rate_cost($rates, $package)
	{
		if (! WC()->session) {
			return $rates;
		}

		$selected_point = WC()->session->get('weblo_selected_pickup_point');
		if (empty($selected_point)) {
			return $rates;
		}

		foreach ($rates as $rate_id => $rate) {
			if (strpos($rate_id, 'weblo_pickup_point') !== false) {
				preg_match('/weblo_pickup_point:(\d+)/', $rate_id, $matches);
				$instance_id = isset($matches[1]) ? (int) $matches[1] : 0;

				$temp_instance = new Weblo_Pickup_Point_Shipping_Method($instance_id);
				$pickup_points = $temp_instance->get_pickup_points();

				if (! empty($pickup_points)) {
					$selected_cost = 0;
					$selected_label = $temp_instance->get_option('title', __('Pick-up point', 'weblo-pickup-point'));

					foreach ($pickup_points as $point) {
						if ($point['name'] === $selected_point) {
							$selected_cost = floatval($point['price']);
							break;
						}
					}

					// Force update cost - set it directly on the rate object
					$rates[$rate_id]->cost = $selected_cost;
					$rates[$rate_id]->label = $selected_label; // Just the title, without pickup point name
					// Cost is already inclusive of tax (brutto), so set taxes to empty array
					$rates[$rate_id]->taxes = array();

					// Also update the rate's meta data to ensure WooCommerce recognizes the change
					if (method_exists($rates[$rate_id], 'set_cost')) {
						$rates[$rate_id]->set_cost($selected_cost);
					}
				}
			}
		}

		return $rates;
	}

	/**
	 * Update shipping packages to ensure rates are updated
	 *
	 * @param array $packages Shipping packages
	 * @return array
	 */
	public function update_shipping_packages($packages)
	{
		if (! WC()->session) {
			return $packages;
		}

		$selected_point = WC()->session->get('weblo_selected_pickup_point');
		if (empty($selected_point)) {
			return $packages;
		}

		// Update rates in all packages
		foreach ($packages as $package_key => $package) {
			if (isset($package['rates']) && is_array($package['rates'])) {
				foreach ($package['rates'] as $rate_id => $rate) {
					if (strpos($rate_id, 'weblo_pickup_point') !== false) {
						// Get instance ID from rate ID
						preg_match('/weblo_pickup_point:(\d+)/', $rate_id, $matches);
						$instance_id = isset($matches[1]) ? (int) $matches[1] : 0;

						// Create temporary instance to get pickup points
						$temp_instance = new Weblo_Pickup_Point_Shipping_Method($instance_id);
						$pickup_points = $temp_instance->get_pickup_points();

						if (! empty($pickup_points)) {
							$selected_cost = 0;
							$selected_label = $temp_instance->get_option('title', __('Pick-up point', 'weblo-pickup-point'));

							foreach ($pickup_points as $point) {
								if ($point['name'] === $selected_point) {
									$selected_cost = floatval($point['price']);
									break;
								}
							}

							$packages[$package_key]['rates'][$rate_id]->cost = $selected_cost;
							$packages[$package_key]['rates'][$rate_id]->label = $selected_label;
							$packages[$package_key]['rates'][$rate_id]->taxes = array();

							if (method_exists($packages[$package_key]['rates'][$rate_id], 'set_cost')) {
								$packages[$package_key]['rates'][$rate_id]->set_cost($selected_cost);
							}
						}
					}
				}
			}
		}

		return $packages;
	}

	/**
	 * Shortcode to display pickup point information
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function shortcode_pickup_point($atts)
	{
		$atts = shortcode_atts(array(
			'order_id' => 0,
		), $atts, 'weblo_pickup_point');

		$order_id = ! empty($atts['order_id']) ? intval($atts['order_id']) : 0;

		if (empty($order_id) && isset($_GET['order-received'])) {
			$order_id = intval($_GET['order-received']);
		}

		if (empty($order_id) && isset($_GET['key'])) {
			$order_id = wc_get_order_id_by_order_key($_GET['key']);
		}

		if (empty($order_id)) {
			global $wp;
			if (isset($wp->query_vars['view-order']) && ! empty($wp->query_vars['view-order'])) {
				$order_id = absint($wp->query_vars['view-order']);
			}
		}

		if (empty($order_id) && function_exists('get_query_var')) {
			$order_id = absint(get_query_var('view-order', 0));
		}

		if (empty($order_id) && is_account_page()) {
			global $wp;
			if (isset($wp->query_vars['view-order']) && ! empty($wp->query_vars['view-order'])) {
				$order_id = absint($wp->query_vars['view-order']);
			}
		}

		if (empty($order_id) && is_account_page()) {
			$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
			if (preg_match('/view-order\/(\d+)/', $request_uri, $matches)) {
				$order_id = absint($matches[1]);
			}
		}

		if (empty($order_id)) {
			return '';
		}

		$order = wc_get_order($order_id);
		if (! $order) {
			return '';
		}

		$location = $order->get_meta('_weblo_pickup_point_location');
		if (empty($location)) {
			return '';
		}

		// Check if shipping method is our pickup point method
		$shipping_methods = $order->get_shipping_methods();
		$is_our_method = false;
		$instance_id = 0;

		foreach ($shipping_methods as $shipping_method) {
			if (strpos($shipping_method->get_method_id(), 'weblo_pickup_point') !== false) {
				$is_our_method = true;
				// Extract instance ID from instance_id or method_id
				$instance_id = $shipping_method->get_instance_id();
				if (empty($instance_id)) {
					// Try to extract from method_id (format: weblo_pickup_point:49)
					$method_id = $shipping_method->get_method_id();
					if (strpos($method_id, ':') !== false) {
						$parts = explode(':', $method_id);
						$instance_id = isset($parts[1]) ? (int) $parts[1] : 0;
					}
				}
				break;
			}
		}

		if (! $is_our_method) {
			return '';
		}

		ob_start();
		?>
<div class="woocommerce-order-pickup-point">
    <p>
        <strong><?php esc_html_e('Location:', 'weblo-pickup-point'); ?></strong>
        <?php echo esc_html($location); ?>
    </p>
</div>
<?php
		return ob_get_clean();
	}
}