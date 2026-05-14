<?php

/**
 * Shipping Method Class
 *
 * @package Weblo_Pickup_Point
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Weblo Pick-up Point Shipping Method
 */
class Weblo_Pickup_Point_Shipping_Method extends WC_Shipping_Method
{

	/**
	 * Constructor
	 *
	 * @param int $instance_id Instance ID
	 */
	public function __construct($instance_id = 0)
	{
		$this->id                 = 'weblo_pickup_point';
		$this->instance_id        = absint($instance_id);
		$this->method_title       = __('Pick-up point', 'weblo-pickup-point');
		$this->method_description = __('Let customers pick up their order at a selected location.', 'weblo-pickup-point');
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->init();
	}

	/**
	 * Initialize the shipping method
	 */
	public function init()
	{
		$this->init_form_fields();
		$this->init_settings();

		// Load settings
		$this->title  = $this->get_option('title', __('Pick-up point', 'weblo-pickup-point'));
		$this->enabled = $this->get_option('enabled', 'yes');

		// Save settings - try multiple hooks
		add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));

		// Also hook into general shipping update (for instance settings)
		if ($this->instance_id > 0) {
			add_action('woocommerce_update_options_shipping_' . $this->id . '_instance_' . $this->instance_id, array($this, 'process_admin_options'));
		}

		// Filter POST data before processing (to update pickup_points from JavaScript)
		add_filter('woocommerce_shipping_' . $this->id . '_instance_settings_values', array($this, 'filter_instance_settings'), 10, 2);

		// Enqueue admin scripts
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

		// Filter shipping method label and cost to update dynamically
		add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'update_shipping_method_label'), 10, 2);
		add_filter('woocommerce_package_rates', array($this, 'update_shipping_rate_cost'), 10, 2);

		// Also filter rates after they're calculated to ensure they're updated
		add_filter('woocommerce_shipping_packages', array($this, 'update_shipping_packages'), 10, 1);
	}

	/**
	 * Filter instance settings to update pickup_points from JavaScript
	 *
	 * @param array $settings Settings array.
	 * @param int   $instance_id Instance ID.
	 * @return array
	 */
	public function filter_instance_settings($settings, $instance_id)
	{
		if ($instance_id != $this->instance_id) {
			return $settings;
		}

		if (isset($_POST['data'][$this->get_field_key('pickup_points')])) {
			$settings['pickup_points'] = sanitize_text_field(wp_unslash($_POST['data'][$this->get_field_key('pickup_points')]));
		}

		return $settings;
	}

	/**
	 * Initialize form fields
	 */
	public function init_form_fields()
	{
		$this->instance_form_fields = array(
			'title' => array(
				'title'       => __('Method title', 'weblo-pickup-point'),
				'type'        => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'weblo-pickup-point'),
				'default'     => __('Pick-up point', 'weblo-pickup-point'),
				'desc_tip'    => true,
			),
			'pickup_points' => array(
				'title'       => __('Pick-up points', 'weblo-pickup-point'),
				'type'        => 'pickup_points_table',
				'default'     => '',
			),
			'show_in_order_confirmation' => array(
				'title'       => __('Show in order confirmation', 'weblo-pickup-point'),
				'type'        => 'checkbox',
				'label'       => __('Add pickup point information in order confirmation', 'weblo-pickup-point'),
				'description' => __('If checked, pickup point location will be displayed in order confirmation page and emails. You can also use the shortcode [weblo_pickup_point] to display pickup point information anywhere on the order confirmation page.', 'weblo-pickup-point'),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Get pickup points from settings
	 *
	 * @return array Array of pickup points with name and price
	 */
	public function get_pickup_points()
	{
		$pickup_points_raw = $this->get_option('pickup_points', '');
		$pickup_points     = array();

		if (empty($pickup_points_raw)) {
			return $pickup_points;
		}

		if (is_string($pickup_points_raw)) {
			$decoded = json_decode($pickup_points_raw, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				return $decoded;
			}
		}

		if (is_array($pickup_points_raw)) {
			return $pickup_points_raw;
		}

		// Legacy format: parse textarea format (for backward compatibility)
		if (is_string($pickup_points_raw)) {
			$lines = explode("\n", $pickup_points_raw);
			foreach ($lines as $line) {
				$line = trim($line);
				if (empty($line)) {
					continue;
				}

				$parts = explode('|', $line);
				if (count($parts) >= 2) {
					$name  = trim($parts[0]);
					$price = floatval(trim($parts[1]));
					if (! empty($name)) {
						$pickup_points[] = array(
							'name'  => $name,
							'price' => $price,
						);
					}
				}
			}
		}

		return $pickup_points;
	}

	/**
	 * Generate pickup points table HTML
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 * @return string
	 */
	public function generate_pickup_points_table_html($key, $data)
	{
		$field_key = $this->get_field_key($key);
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args($data, $defaults);

		$pickup_points = $this->get_pickup_points();
		$pickup_points_json = wp_json_encode($pickup_points);

		ob_start();
?>
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?></label>
    </th>
    <td class="forminp">
        <fieldset>
            <input type="hidden" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>"
                value="<?php echo esc_attr($pickup_points_json); ?>" />
            <div class="weblo-pickup-points-table-wrapper">
                <table class="weblo-pickup-points-table widefat">
                    <thead>
                        <tr>
                            <th class="sort-handle-column" style="width: 30px;">
                                <?php esc_html_e('Order', 'weblo-pickup-point'); ?></th>
                            <th><?php esc_html_e('Name', 'weblo-pickup-point'); ?></th>
                            <th style="width: 150px;"><?php esc_html_e('Price', 'weblo-pickup-point'); ?></th>
                            <th class="actions-column" style="width: 80px;">
                                <?php esc_html_e('Actions', 'weblo-pickup-point'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="weblo-pickup-points-tbody">
                        <?php if (! empty($pickup_points)) : ?>
                        <?php foreach ($pickup_points as $index => $point) : ?>
                        <tr class="weblo-pickup-point-row" data-index="<?php echo esc_attr($index); ?>">
                            <td class="sort-handle">
                                <span class="dashicons dashicons-menu-alt"></span>
                            </td>
                            <td>
                                <input type="text" class="weblo-point-name"
                                    value="<?php echo esc_attr($point['name']); ?>"
                                    placeholder="<?php esc_attr_e('Point name', 'weblo-pickup-point'); ?>" />
                            </td>
                            <td>
                                <input type="number" class="weblo-point-price" step="0.01" min="0"
                                    value="<?php echo esc_attr($point['price']); ?>" placeholder="0.00" />
                            </td>
                            <td>
                                <button type="button"
                                    class="button weblo-remove-point"><?php esc_html_e('Remove', 'weblo-pickup-point'); ?></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else : ?>
                        <tr class="weblo-no-points">
                            <td colspan="4" style="text-align: center; padding: 20px;">
                                <?php esc_html_e('No pickup points added yet.', 'weblo-pickup-point'); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <p>
                    <button type="button"
                        class="button button-primary weblo-add-point"><?php esc_html_e('Add Pick-up Point', 'weblo-pickup-point'); ?></button>
                </p>
            </div>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                'use strict';

                function initWebloPickupPointsTable() {
                    var $wrapper = $('.weblo-pickup-points-table-wrapper');
                    if ($wrapper.length === 0 || $wrapper.data('weblo-initialized')) {
                        return;
                    }

                    $wrapper.data('weblo-initialized', true);
                    // Find hidden input - it's BEFORE the wrapper div, inside fieldset
                    var $fieldset = $wrapper.closest('fieldset');
                    var $hiddenInput = $fieldset.find('input[type="hidden"]').first();

                    // If not found, try other selectors
                    if ($hiddenInput.length === 0) {
                        $hiddenInput = $wrapper.closest('td').find('input[type="hidden"]').first();
                    }
                    if ($hiddenInput.length === 0) {
                        $hiddenInput = $wrapper.siblings('input[type="hidden"]').first();
                    }
                    if ($hiddenInput.length === 0) {
                        // Last resort: find by ID pattern
                        var fieldKeyPattern = 'woocommerce_weblo_pickup_point_pickup_points';
                        $hiddenInput = $('input[type="hidden"][name*="' + fieldKeyPattern + '"]');
                    }

                    var $tbody = $wrapper.find('.weblo-pickup-points-tbody');
                    var $addButton = $wrapper.find('.weblo-add-point');


                    // Initialize sortable
                    if ($tbody.length > 0 && !$tbody.hasClass('ui-sortable')) {
                        $tbody.sortable({
                            handle: '.sort-handle',
                            axis: 'y',
                            update: function() {
                                updateHiddenInput();
                            }
                        });
                    }

                    // Add new point
                    $addButton.off('click.weblo').on('click.weblo', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        addNewRow();
                        return false;
                    });

                    // Remove point
                    $tbody.off('click', '.weblo-remove-point').on('click', '.weblo-remove-point', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $(this).closest('tr').remove();
                        updateHiddenInput();
                        checkEmptyState();
                        return false;
                    });

                    // Update on input - with immediate update
                    $tbody.off('input', '.weblo-point-name, .weblo-point-price').on('input',
                        '.weblo-point-name, .weblo-point-price',
                        function() {
                            updateHiddenInput();
                            // Also trigger change event to ensure WooCommerce sees it
                            $hiddenInput.trigger('change');
                        });

                    // Use MutationObserver to watch for DOM changes
                    if (typeof MutationObserver !== 'undefined') {
                        var observer = new MutationObserver(function(mutations) {
                            updateHiddenInput();
                            $hiddenInput.trigger('change');
                        });

                        observer.observe($tbody[0], {
                            childList: true,
                            subtree: true,
                            characterData: true
                        });
                    }

                    function addNewRow() {
                        var index = $tbody.find('tr.weblo-pickup-point-row').length;
                        var newRow = '<tr class="weblo-pickup-point-row" data-index="' + index + '">' +
                            '<td class="sort-handle"><span class="dashicons dashicons-menu-alt"></span></td>' +
                            '<td><input type="text" class="weblo-point-name" value="" placeholder="<?php echo esc_js(__('Point name', 'weblo-pickup-point')); ?>" /></td>' +
                            '<td><input type="number" class="weblo-point-price" step="0.01" min="0" value="0" placeholder="0.00" /></td>' +
                            '<td><button type="button" class="button weblo-remove-point"><?php echo esc_js(__('Remove', 'weblo-pickup-point')); ?></button></td>' +
                            '</tr>';

                        $tbody.find('.weblo-no-points').remove();
                        $tbody.append(newRow);

                        if ($tbody.hasClass('ui-sortable')) {
                            $tbody.sortable('destroy');
                        }
                        $tbody.sortable({
                            handle: '.sort-handle',
                            axis: 'y',
                            update: function() {
                                updateHiddenInput();
                            }
                        });

                        updateHiddenInput();
                    }

                    function updateHiddenInput() {
                        // Re-find hidden input in case DOM changed
                        var $fieldset = $wrapper.closest('fieldset');
                        var $currentHiddenInput = $fieldset.find('input[type="hidden"]').first();

                        if ($currentHiddenInput.length === 0) {
                            $currentHiddenInput = $wrapper.closest('td').find('input[type="hidden"]').first();
                        }
                        if ($currentHiddenInput.length === 0) {
                            $currentHiddenInput = $wrapper.siblings('input[type="hidden"]').first();
                        }
                        if ($currentHiddenInput.length === 0) {
                            $currentHiddenInput = $('input[type="hidden"][name*="pickup_points"]');
                        }

                        if ($currentHiddenInput.length === 0) {
                            return;
                        }

                        var points = [];
                        $tbody.find('tr.weblo-pickup-point-row').each(function() {
                            var $row = $(this);
                            var name = $row.find('.weblo-point-name').val().trim();
                            var price = parseFloat($row.find('.weblo-point-price').val()) || 0;
                            if (name) {
                                points.push({
                                    name: name,
                                    price: price
                                });
                            }
                        });
                        var jsonValue = JSON.stringify(points);
                        $currentHiddenInput.val(jsonValue);

                    }

                    function checkEmptyState() {
                        if ($tbody.find('tr.weblo-pickup-point-row').length === 0) {
                            $tbody.append(
                                '<tr class="weblo-no-points"><td colspan="4" style="text-align: center; padding: 20px;"><?php echo esc_js(__('No pickup points added yet.', 'weblo-pickup-point')); ?></td></tr>'
                            );
                        }
                    }

                    updateHiddenInput();
                }

                // Initialize immediately
                initWebloPickupPointsTable();

                // Reinitialize when modal loads
                $(document).on('wc_backbone_modal_loaded', function() {
                    setTimeout(function() {
                        $('.weblo-pickup-points-table-wrapper').removeData('weblo-initialized');
                        initWebloPickupPointsTable();
                    }, 200);
                });

                // Function to update hidden input
                function forceUpdateHiddenInput() {
                    $('.weblo-pickup-points-table-wrapper').each(function() {
                        var $wrapper = $(this);
                        // Find hidden input - try multiple strategies
                        var $fieldset = $wrapper.closest('fieldset');
                        var $hiddenInput = $fieldset.find('input[type="hidden"]').first();

                        if ($hiddenInput.length === 0) {
                            $hiddenInput = $wrapper.closest('td').find('input[type="hidden"]').first();
                        }
                        if ($hiddenInput.length === 0) {
                            $hiddenInput = $wrapper.siblings('input[type="hidden"]').first();
                        }
                        if ($hiddenInput.length === 0) {
                            // Last resort: find by name pattern
                            $hiddenInput = $('input[type="hidden"][name*="pickup_points"]');
                        }

                        var $tbody = $wrapper.find('.weblo-pickup-points-tbody');
                        var points = [];

                        $tbody.find('tr.weblo-pickup-point-row').each(function() {
                            var $row = $(this);
                            var name = $row.find('.weblo-point-name').val().trim();
                            var price = parseFloat($row.find('.weblo-point-price').val()) || 0;
                            if (name) {
                                points.push({
                                    name: name,
                                    price: price
                                });
                            }
                        });

                        var jsonValue = JSON.stringify(points);
                        if ($hiddenInput.length > 0) {
                            $hiddenInput.val(jsonValue);
                        }
                    });
                }

                // Update hidden input continuously (every 200ms) when modal is open - more aggressive
                var updateInterval = setInterval(function() {
                    if ($('.weblo-pickup-points-table-wrapper').length > 0) {
                        forceUpdateHiddenInput();
                        // Also trigger change event
                        $('.weblo-pickup-points-table-wrapper input[type="hidden"]').trigger('change');
                    }
                }, 200);

                // Stop interval when modal closes
                $(document).on('wc_backbone_modal_removed', function() {
                    clearInterval(updateInterval);
                });

                // CRITICAL: Update hidden input BEFORE WooCommerce collects form data
                // Hook into WooCommerce's form serialization
                var originalSerialize = $.fn.serialize;
                $.fn.serialize = function() {
                    if ($(this).find('.weblo-pickup-points-table-wrapper').length > 0) {
                        forceUpdateHiddenInput();
                    }
                    return originalSerialize.apply(this, arguments);
                };

                // Also intercept serializeArray
                var originalSerializeArray = $.fn.serializeArray;
                $.fn.serializeArray = function() {
                    if ($(this).find('.weblo-pickup-points-table-wrapper').length > 0) {
                        forceUpdateHiddenInput();
                    }
                    return originalSerializeArray.apply(this, arguments);
                };

                // Update before WooCommerce modal saves
                $(document).on('wc_backbone_modal_before_save', function(e, target, data) {
                    forceUpdateHiddenInput();
                    // Also update data object directly if possible
                    if (data && typeof data === 'object') {
                        var $hiddenInput = $('.weblo-pickup-points-table-wrapper input[type="hidden"]');
                        if ($hiddenInput.length > 0) {
                            var fieldName = $hiddenInput.attr('name');
                            var fieldValue = $hiddenInput.val();
                            if (fieldName && fieldValue) {
                                data[fieldName] = fieldValue;
                            }
                        }
                    }
                });

                // Also try on form submit
                $(document).on('submit', 'form', function(e) {
                    if ($(this).find('.weblo-pickup-points-table-wrapper').length > 0) {
                        forceUpdateHiddenInput();
                    }
                });

                // Also try on button click (multiple selectors) - with longer delay
                $(document).on('click',
                    'button[type="submit"], .button-primary, .wc-shipping-zone-method-save, button.wc-shipping-zone-method-save',
                    function(e) {
                        if ($(this).closest('form').find('.weblo-pickup-points-table-wrapper').length > 0 ||
                            $('.weblo-pickup-points-table-wrapper').length > 0) {
                            // Update multiple times with delays
                            forceUpdateHiddenInput();
                            setTimeout(function() {
                                forceUpdateHiddenInput();
                            }, 100);
                            setTimeout(function() {
                                forceUpdateHiddenInput();
                            }, 300);
                            setTimeout(function() {
                                forceUpdateHiddenInput();
                            }, 500);
                        }
                    });
            });
            </script>
        </fieldset>
    </td>
</tr>
<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_admin_scripts($hook)
	{
		// Load on WooCommerce settings page or any admin page (for modals)
		$is_wc_settings = ('woocommerce_page_wc-settings' === $hook);
		$is_admin = is_admin();

		if (! $is_admin) {
			return;
		}

		// If on WC settings page, check if it's shipping tab
		if ($is_wc_settings && (! isset($_GET['tab']) || 'shipping' !== $_GET['tab'])) {
			return;
		}

		wp_enqueue_script('jquery-ui-sortable');

		wp_enqueue_script(
			'weblo-pickup-point-admin',
			WEBLO_PICKUP_POINT_PLUGIN_URL . 'assets/js/admin.js',
			array('jquery', 'jquery-ui-sortable'),
			WEBLO_PICKUP_POINT_VERSION,
			false // Load in header so it's available when modal loads
		);

		wp_localize_script(
			'weblo-pickup-point-admin',
			'webloPickupPointAdmin',
			array(
				'pointNamePlaceholder' => __('Point name', 'weblo-pickup-point'),
				'removeButton'         => __('Remove', 'weblo-pickup-point'),
				'noPointsMessage'      => __('No pickup points added yet.', 'weblo-pickup-point'),
			)
		);

		wp_enqueue_style(
			'weblo-pickup-point-admin',
			WEBLO_PICKUP_POINT_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WEBLO_PICKUP_POINT_VERSION
		);
	}

	/**
	 * Process admin options and save pickup points
	 */
	public function process_admin_options()
	{
		parent::process_admin_options();

		$field_key = $this->get_field_key('pickup_points');
		$post_data = isset($_POST['data']) ? $_POST['data'] : $_POST;

		if (isset($post_data[$field_key])) {
			$pickup_points_json = sanitize_text_field(wp_unslash($post_data[$field_key]));
			$pickup_points = json_decode($pickup_points_json, true);

			if (json_last_error() === JSON_ERROR_NONE && is_array($pickup_points)) {
				$sanitized_points = array();
				foreach ($pickup_points as $point) {
					if (isset($point['name']) && ! empty(trim($point['name']))) {
						$sanitized_points[] = array(
							'name'  => sanitize_text_field($point['name']),
							'price' => floatval($point['price']),
						);
					}
				}

				$this->update_option('pickup_points', wp_json_encode($sanitized_points));
			}
		}
	}

	/**
	 * Calculate shipping
	 *
	 * @param array $package Package data
	 */
	public function calculate_shipping($package = array())
	{
		$pickup_points = $this->get_pickup_points();

		if (empty($pickup_points)) {
			return;
		}

		// Get selected pickup point from session
		$selected_point = WC()->session->get('weblo_selected_pickup_point');

		// If no point selected, set cost to 0 temporarily
		$cost = 0;

		if (! empty($selected_point)) {
			// Find the selected point
			foreach ($pickup_points as $point) {
				if ($point['name'] === $selected_point) {
					$cost = floatval($point['price']);
					break;
				}
			}
		}

		// Add shipping rate
		// Cost is already inclusive of tax (brutto), so we set taxes to empty array
		// to prevent WooCommerce from adding tax again
		// Label is just the title, without pickup point name
		$this->add_rate(array(
			'id'    => $this->get_rate_id(),
			'label' => $this->title,
			'cost'  => $cost,
			'taxes' => array(), // Empty array means cost is already inclusive of tax
		));
	}

	/**
	 * Get rate ID
	 *
	 * @param string $suffix Optional suffix.
	 * @return string
	 */
	public function get_rate_id($suffix = '')
	{
		$rate_id = $this->id . ':' . $this->instance_id;
		if (! empty($suffix)) {
			$rate_id .= ':' . $suffix;
		}
		return $rate_id;
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
		$pickup_points = $this->get_pickup_points();

		if (empty($selected_point) || empty($pickup_points)) {
			return $rates;
		}

		// Find the selected point
		$selected_cost = 0;
		foreach ($pickup_points as $point) {
			if ($point['name'] === $selected_point) {
				$selected_cost = floatval($point['price']);
				break;
			}
		}

		// Update cost and label for our shipping method rates
		foreach ($rates as $rate_id => $rate) {
			if (strpos($rate_id, $this->id) !== false) {
				// Force update cost - set it directly on the rate object
				$rates[$rate_id]->cost = $selected_cost;
				$rates[$rate_id]->label = $this->title;
				$rates[$rate_id]->taxes = array();

				if (method_exists($rates[$rate_id], 'set_cost')) {
					$rates[$rate_id]->set_cost($selected_cost);
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
		$pickup_points = $this->get_pickup_points();

		if (empty($selected_point) || empty($pickup_points)) {
			return $packages;
		}

		// Find the selected point
		$selected_cost = 0;
		foreach ($pickup_points as $point) {
			if ($point['name'] === $selected_point) {
				$selected_cost = floatval($point['price']);
				break;
			}
		}

		foreach ($packages as $package_key => $package) {
			if (isset($package['rates']) && is_array($package['rates'])) {
				foreach ($package['rates'] as $rate_id => $rate) {
					if (strpos($rate_id, $this->id) !== false) {
						$packages[$package_key]['rates'][$rate_id]->cost = $selected_cost;
						$packages[$package_key]['rates'][$rate_id]->label = $this->title;
						$packages[$package_key]['rates'][$rate_id]->taxes = array();

						if (method_exists($packages[$package_key]['rates'][$rate_id], 'set_cost')) {
							$packages[$package_key]['rates'][$rate_id]->set_cost($selected_cost);
						}
					}
				}
			}
		}

		return $packages;
	}
}