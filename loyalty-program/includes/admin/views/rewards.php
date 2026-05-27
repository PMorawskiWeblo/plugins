<?php

/**
 * Admin Rewards View
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get saved rewards - separate for products and coupons
$product_rewards = get_option('loyalty_program_product_rewards', array());
$coupon_rewards = get_option('loyalty_program_coupon_rewards', array());

// Check if WooCommerce is active
$wc_active = class_exists('WooCommerce');

settings_errors('loyalty_program_rewards');
?>

<div class="wrap loyalty-program-rewards">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <p class="description">
        <?php _e('Configure rewards that customers can redeem with their loyalty points.', 'loyalty-program'); ?>
    </p>

    <?php if (!$wc_active) : ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('WooCommerce not detected!', 'loyalty-program'); ?></strong>
            <?php _e('WooCommerce must be installed and activated to use rewards.', 'loyalty-program'); ?>
        </p>
    </div>
    <?php endif; ?>

    <form method="post" action="" id="rewards-form">
        <?php wp_nonce_field('loyalty_program_rewards', 'loyalty_program_rewards_nonce'); ?>

        <!-- Product Rewards Section -->
        <div class="loyalty-rewards-section" style="margin-bottom: 40px;">
            <div class="loyalty-rewards-header">
                <h2><?php _e('Product Rewards', 'loyalty-program'); ?></h2>
                <button type="button" id="add-product-reward-row" class="button button-secondary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Product Reward', 'loyalty-program'); ?>
                </button>
            </div>

            <div class="loyalty-rewards-table-wrapper">
                <table class="wp-list-table widefat fixed striped loyalty-rewards-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><?php _e('#', 'loyalty-program'); ?></th>
                            <th style="width: 40px;"></th>
                            <th style="width: 180px;"><?php _e('Reward Name', 'loyalty-program'); ?></th>
                            <th style="width: 300px;"><?php _e('Description', 'loyalty-program'); ?></th>
                            <th style="width: 200px;"><?php _e('Product', 'loyalty-program'); ?></th>
                            <th style="width: 120px;"><?php _e('Points Required', 'loyalty-program'); ?></th>
                            <th style="width: 120px;"><?php _e('Price', 'loyalty-program'); ?></th>
                            <th style="width: 150px;"><?php _e('Actions', 'loyalty-program'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="product-rewards-tbody">
                        <?php if (!empty($product_rewards)) : ?>
                        <?php foreach ($product_rewards as $index => $reward) : ?>
                        <?php
                                global $loyalty_program_menu;
                                if (!isset($loyalty_program_menu)) {
                                    $loyalty_program_menu = new Loyalty_Program_Admin_Menu();
                                }
                                echo $loyalty_program_menu->render_product_reward_row($index, $reward, $wc_active);
                                ?>
                        <?php endforeach; ?>
                        <?php else : ?>
                        <tr class="no-rewards-row">
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <span class="dashicons dashicons-cart" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php _e('No product rewards configured yet. Click "Add Product Reward" to create your first reward.', 'loyalty-program'); ?></p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Coupon Rewards Section -->
        <div class="loyalty-rewards-section" style="margin-bottom: 40px;">
            <div class="loyalty-rewards-header">
                <h2><?php _e('Coupon Rewards', 'loyalty-program'); ?></h2>
                <button type="button" id="add-coupon-reward-row" class="button button-secondary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Coupon Reward', 'loyalty-program'); ?>
                </button>
            </div>

            <div class="loyalty-rewards-table-wrapper">
                <table class="wp-list-table widefat fixed striped loyalty-rewards-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><?php _e('#', 'loyalty-program'); ?></th>
                            <th style="width: 40px;"></th>
                            <th style="width: 150px;"><?php _e('Coupon Type', 'loyalty-program'); ?></th>
                            <th style="width: 180px;"><?php _e('Coupon Name', 'loyalty-program'); ?></th>
                            <th style="width: 300px;"><?php _e('Description', 'loyalty-program'); ?></th>
                            <th style="width: 120px;"><?php _e('Image', 'loyalty-program'); ?></th>
                            <th style="width: 120px;"><?php _e('Points Required', 'loyalty-program'); ?></th>
                            <th style="width: 150px;"><?php _e('Min Order Amount', 'loyalty-program'); ?></th>
                            <th style="width: 150px;"><?php _e('Actions', 'loyalty-program'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="coupon-rewards-tbody">
                        <?php if (!empty($coupon_rewards)) : ?>
                        <?php foreach ($coupon_rewards as $index => $reward) : ?>
                        <?php
                                global $loyalty_program_menu;
                                if (!isset($loyalty_program_menu)) {
                                    $loyalty_program_menu = new Loyalty_Program_Admin_Menu();
                                }
                                echo $loyalty_program_menu->render_coupon_reward_row($index, $reward, $wc_active);
                                ?>
                        <?php endforeach; ?>
                        <?php else : ?>
                        <tr class="no-rewards-row">
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <span class="dashicons dashicons-tickets-alt" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php _e('No coupon rewards configured yet. Click "Add Coupon Reward" to create your first coupon reward.', 'loyalty-program'); ?></p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Coupon Settings Section -->
            <div class="loyalty-coupon-settings-section" style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #dcdcde;">
                <h3 style="margin-bottom: 20px;"><?php _e('Coupon Settings', 'loyalty-program'); ?></h3>
                <p class="description" style="margin-bottom: 20px;">
                    <?php _e('Configure default settings for coupon rewards. These settings will be used when generating coupons.', 'loyalty-program'); ?>
                </p>

                <?php
                // Get coupon settings
                $coupon_apply_to = get_option('loyalty_program_coupon_apply_to', 'cart');
                $coupon_individual_use = get_option('loyalty_program_coupon_individual_use', 'no');
                $coupon_excluded_products = get_option('loyalty_program_coupon_excluded_products', array());
                $coupon_excluded_categories = get_option('loyalty_program_coupon_excluded_categories', array());
                ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="coupon_apply_to"><?php _e('Apply to', 'loyalty-program'); ?></label>
                            </th>
                            <td>
                                <select name="coupon_apply_to" id="coupon_apply_to" style="width: 300px;">
                                    <option value="cart" <?php selected($coupon_apply_to, 'cart'); ?>><?php _e('Cart value', 'loyalty-program'); ?></option>
                                    <option value="products" <?php selected($coupon_apply_to, 'products'); ?>><?php _e('Selected products only', 'loyalty-program'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Choose whether the coupon applies to the entire cart value or only to selected products.', 'loyalty-program'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="coupon_individual_use"><?php _e('Individual use', 'loyalty-program'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="coupon_individual_use" id="coupon_individual_use" value="yes" <?php checked($coupon_individual_use, 'yes'); ?>>
                                    <?php _e('Check this box if the coupon cannot be used in conjunction with other coupons.', 'loyalty-program'); ?>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="coupon_excluded_products"><?php _e('Excluded products', 'loyalty-program'); ?></label>
                            </th>
                            <td>
                                <select name="coupon_excluded_products[]" id="coupon_excluded_products" class="coupon-excluded-products-select2" multiple="multiple" style="width: 100%;">
                                    <?php
                                    if (!empty($coupon_excluded_products) && $wc_active) {
                                        foreach ($coupon_excluded_products as $product_id) {
                                            $product = wc_get_product($product_id);
                                            if ($product) {
                                                echo '<option value="' . esc_attr($product_id) . '" selected>' . esc_html($product->get_name()) . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                                <p class="description">
                                    <?php _e('Products that the coupon cannot be used on.', 'loyalty-program'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="coupon_excluded_categories"><?php _e('Excluded categories', 'loyalty-program'); ?></label>
                            </th>
                            <td>
                                <select name="coupon_excluded_categories[]" id="coupon_excluded_categories" class="coupon-excluded-categories-select2" multiple="multiple" style="width: 100%;">
                                    <?php
                                    if (!empty($coupon_excluded_categories) && $wc_active) {
                                        $categories = get_terms(array(
                                            'taxonomy' => 'product_cat',
                                            'hide_empty' => false,
                                            'include' => $coupon_excluded_categories,
                                        ));
                                        foreach ($categories as $category) {
                                            echo '<option value="' . esc_attr($category->term_id) . '" selected>' . esc_html($category->name) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <p class="description">
                                    <?php _e('Product categories that the coupon cannot be used on.', 'loyalty-program'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="submit">
            <div class="save-changes-notice" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-bottom: 15px;">
                <span class="dashicons dashicons-info" style="color: #dba617; vertical-align: middle;"></span>
                <strong>Ważne:</strong>
                Po zmianach należy kliknąć przycisk „Zapisz zmiany", aby zapisać je na stałe.
            </div>
            <button type="submit" name="loyalty_program_rewards_save" class="button button-primary button-large">
                <span class="dashicons dashicons-saved" style="margin-top: 3px;"></span>
                <?php _e('Save Rewards', 'loyalty-program'); ?>
            </button>
        </div>
    </form>
</div>

<!-- Product Reward Row Template -->
<script type="text/template" id="product-reward-row-template">
    <?php
    global $loyalty_program_menu;
    if (!isset($loyalty_program_menu)) {
        $loyalty_program_menu = new Loyalty_Program_Admin_Menu();
    }
    echo $loyalty_program_menu->render_product_reward_row('{{INDEX}}', array(
        'name' => '',
        'product_id' => 0,
        'points' => 100,
        'price' => 0.01,
        'enabled' => 'yes',
    ), $wc_active);
    ?>
</script>

<!-- Coupon Reward Row Template -->
<script type="text/template" id="coupon-reward-row-template">
    <?php
    global $loyalty_program_menu;
    if (!isset($loyalty_program_menu)) {
        $loyalty_program_menu = new Loyalty_Program_Admin_Menu();
    }
    echo $loyalty_program_menu->render_coupon_reward_row('{{INDEX}}', array(
        'type' => 'fixed_cart',
        'name' => '',
        'description' => '',
        'points' => 1000,
        'discount_value' => 10,
        'min_order_amount' => 0,
        'enabled' => 'yes',
    ), $wc_active);
    ?>
</script>

<style>
.loyalty-rewards-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    margin: 20px 0;
    border-radius: 4px;
}

.loyalty-rewards-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #dcdcde;
}

.loyalty-rewards-header h2 {
    margin: 0;
}

.loyalty-rewards-table-wrapper {
    overflow-x: auto;
    margin-bottom: 20px;
}

.loyalty-rewards-table tbody tr {
    cursor: move;
}

.loyalty-rewards-table tbody tr.ui-sortable-helper {
    background: #f0f6fc;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.loyalty-rewards-table tbody tr.disabled {
    opacity: 0.5;
    background: #f6f7f7;
}

.loyalty-rewards-table .drag-handle {
    cursor: move;
    color: #646970;
    text-align: center;
}

.loyalty-rewards-table .drag-handle:hover {
    color: #2271b1;
}

.loyalty-rewards-table input[type="text"],
.loyalty-rewards-table input[type="number"],
.loyalty-rewards-table select,
.loyalty-rewards-table textarea {
    width: 100%;
}

.loyalty-rewards-table .row-number {
    text-align: center;
    font-weight: 600;
    color: #646970;
}

.loyalty-rewards-table .toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 22px;
    margin-right: 10px;
}

.loyalty-rewards-table .toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.loyalty-rewards-table .toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #c3c4c7;
    transition: 0.4s;
    border-radius: 22px;
}

.loyalty-rewards-table .toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

.loyalty-rewards-table .toggle-switch input:checked+.toggle-slider {
    background-color: #2271b1;
}

.loyalty-rewards-table .toggle-switch input:checked+.toggle-slider:before {
    transform: translateX(22px);
}

.delete-reward-btn {
    color: #d63638;
}

.delete-reward-btn:hover {
    color: #b32d2e;
}

/* Select2 Styles */
.loyalty-program-rewards .select2-container {
    min-width: 205px !important;
    z-index: 999 !important;
}

.loyalty-program-rewards .select2-container .select2-selection--single {
    height: 36px !important;
    padding: 4px 8px !important;
    border: 1px solid #8c8f94 !important;
    border-radius: 4px !important;
    background: #fff !important;
}

.loyalty-program-rewards .select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 28px !important;
    padding-left: 8px !important;
    color: #2c3338 !important;
}

.loyalty-program-rewards .select2-container .select2-selection--single .select2-selection__arrow {
    height: 34px !important;
    right: 4px !important;
}

.loyalty-program-rewards .select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #646970 !important;
}

.loyalty-program-rewards .select2-dropdown {
    border: 1px solid #8c8f94 !important;
    border-radius: 4px !important;
    background: #fff !important;
    z-index: 999999 !important;
}

.loyalty-program-rewards .select2-results__option {
    padding: 8px 12px !important;
}

.loyalty-program-rewards .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #2271b1 !important;
    color: #fff !important;
}

.loyalty-program-rewards .select2-container--focus .select2-selection--single {
    border-color: #2271b1 !important;
    box-shadow: 0 0 0 1px #2271b1 !important;
}

.coupon-discount-value-wrapper {
    display: flex;
    align-items: center;
    gap: 5px;
}

.coupon-discount-value-wrapper input {
    flex: 1;
}

.coupon-discount-value-wrapper span {
    white-space: nowrap;
    color: #646970;
}
</style>

<script>
jQuery(document).ready(function($) {
    // SweetAlert2 Helper
    var SwalConfig = {
        alert: function(message, title, icon) {
            return Swal.fire({
                title: title || '',
                text: message,
                icon: icon || 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#b02e66',
                color: '#000000',
                buttonsStyling: true
            });
        },
        confirm: function(message, title, icon) {
            return Swal.fire({
                title: title || '',
                text: message,
                icon: icon || 'question',
                showCancelButton: true,
                confirmButtonText: 'Tak',
                cancelButtonText: 'Anuluj',
                confirmButtonColor: '#b02e66',
                cancelButtonColor: '#aac096',
                color: '#000000',
                buttonsStyling: true
            });
        },
        error: function(message, title) {
            return this.alert(message, title || 'Błąd', 'error');
        }
    };
    
    var productRowIndex = <?php echo count($product_rewards); ?>;
    var couponRowIndex = <?php echo count($coupon_rewards); ?>;

    // Make tables sortable
    $('#product-rewards-tbody, #coupon-rewards-tbody').sortable({
        handle: '.drag-handle',
        placeholder: 'ui-state-highlight',
        update: function(event, ui) {
            updateRowNumbers($(this));
        }
    });

    // Delete product reward row
    $(document).on('click', '.delete-product-reward-btn', function() {
        var $btn = $(this);
        SwalConfig.confirm('<?php esc_attr_e('Are you sure you want to delete this reward?', 'loyalty-program'); ?>').then(function(result) {
            if (result.isConfirmed) {
                $btn.closest('tr').remove();
                updateRowNumbers($('#product-rewards-tbody'));

                if ($('#product-rewards-tbody tr').length === 0) {
                    $('#product-rewards-tbody').html(
                        '<tr class="no-rewards-row"><td colspan="8" style="text-align: center; padding: 40px;"><span class="dashicons dashicons-cart" style="font-size: 48px; opacity: 0.3;"></span><p><?php _e('No product rewards configured yet. Click "Add Product Reward" to create your first reward.', 'loyalty-program'); ?></p></td></tr>'
                    );
                }
            }
        });
    });

    // Delete coupon reward row
    $(document).on('click', '.delete-coupon-reward-btn', function() {
        var $btn = $(this);
        SwalConfig.confirm('<?php esc_attr_e('Are you sure you want to delete this coupon reward?', 'loyalty-program'); ?>').then(function(result) {
            if (result.isConfirmed) {
                $btn.closest('tr').remove();
                updateRowNumbers($('#coupon-rewards-tbody'));

                if ($('#coupon-rewards-tbody tr').length === 0) {
                    $('#coupon-rewards-tbody').html(
                        '<tr class="no-rewards-row"><td colspan="9" style="text-align: center; padding: 40px;"><span class="dashicons dashicons-tickets-alt" style="font-size: 48px; opacity: 0.3;"></span><p><?php _e('No coupon rewards configured yet. Click "Add Coupon Reward" to create your first coupon reward.', 'loyalty-program'); ?></p></td></tr>'
                    );
                }
            }
        });
    });

    // Toggle enabled/disabled styling
    $(document).on('change', '.reward-enabled-toggle', function() {
        var $row = $(this).closest('tr');
        if ($(this).is(':checked')) {
            $row.removeClass('disabled');
        } else {
            $row.addClass('disabled');
        }
    });

    // Handle coupon type change - show/hide discount value field
    $(document).on('change', '.coupon-type-select', function() {
        var $row = $(this).closest('tr');
        var couponType = $(this).val();
        var $discountWrapper = $row.find('.coupon-discount-value-wrapper');
        var $discountInput = $discountWrapper.find('input');
        var $discountLabel = $discountWrapper.find('span');
        
        if (couponType === 'free_shipping') {
            $discountWrapper.hide();
            $discountInput.val(0);
        } else {
            $discountWrapper.show();
            if (couponType === 'percent') {
                $discountLabel.text('%');
                $discountInput.attr('max', '100');
            } else {
                $discountLabel.text('PLN');
                $discountInput.removeAttr('max');
            }
        }
    });

    // Initialize coupon type on page load
    $('.coupon-type-select').each(function() {
        $(this).trigger('change');
    });

    // Update row numbers
    function updateRowNumbers($tbody) {
        $tbody.find('tr:not(.no-rewards-row)').each(function(index) {
            $(this).find('.row-number').text(index + 1);
        });
    }

    // Form validation
    $('#rewards-form').on('submit', function(e) {
        var hasErrors = false;
        var errorMessages = [];

        // Validate product rewards
        $('#product-rewards-tbody tr:not(.no-rewards-row)').each(function(index) {
            var rowNum = index + 1;
            var name = $(this).find('.reward-name').val().trim();
            var points = $(this).find('.reward-points').val();
            var price = $(this).find('.reward-price').val();
            var productId = $(this).find('.reward-product-select2').val();

            if (!name) {
                errorMessages.push('<?php esc_html_e('Product Reward Row', 'loyalty-program'); ?> ' + rowNum + ': <?php esc_html_e('Reward name is required', 'loyalty-program'); ?>');
                hasErrors = true;
            }

            if (!productId || productId === '' || productId === '0') {
                errorMessages.push('<?php esc_html_e('Product Reward Row', 'loyalty-program'); ?> ' + rowNum + ': <?php esc_html_e('Product is required', 'loyalty-program'); ?>');
                hasErrors = true;
            }

            if (!points || points <= 0) {
                errorMessages.push('<?php esc_html_e('Product Reward Row', 'loyalty-program'); ?> ' + rowNum + ': <?php esc_html_e('Points must be greater than 0', 'loyalty-program'); ?>');
                hasErrors = true;
            }

            if (!price || price < 0) {
                errorMessages.push('<?php esc_html_e('Product Reward Row', 'loyalty-program'); ?> ' + rowNum + ': <?php esc_html_e('Price must be 0 or greater', 'loyalty-program'); ?>');
                hasErrors = true;
            }
        });

        // Validate coupon rewards
        $('#coupon-rewards-tbody tr:not(.no-rewards-row)').each(function(index) {
            var rowNum = index + 1;
            var name = $(this).find('.coupon-name').val().trim();
            var points = $(this).find('.coupon-points').val();
            var couponType = $(this).find('.coupon-type-select').val();
            var discountValue = $(this).find('.coupon-discount-value').val();

            if (!name) {
                errorMessages.push('<?php esc_html_e('Coupon Reward Row', 'loyalty-program'); ?> ' + rowNum + ': <?php esc_html_e('Coupon name is required', 'loyalty-program'); ?>');
                hasErrors = true;
            }

            if (!points || points <= 0) {
                errorMessages.push('<?php esc_html_e('Coupon Reward Row', 'loyalty-program'); ?> ' + rowNum + ': <?php esc_html_e('Points must be greater than 0', 'loyalty-program'); ?>');
                hasErrors = true;
            }

            if (couponType !== 'free_shipping') {
                if (!discountValue || discountValue <= 0) {
                    errorMessages.push('<?php esc_html_e('Coupon Reward Row', 'loyalty-program'); ?> ' + rowNum + ': <?php esc_html_e('Discount value must be greater than 0', 'loyalty-program'); ?>');
                    hasErrors = true;
                }
                if (couponType === 'percent' && parseFloat(discountValue) > 100) {
                    errorMessages.push('<?php esc_html_e('Coupon Reward Row', 'loyalty-program'); ?> ' + rowNum + ': <?php esc_html_e('Percentage discount cannot exceed 100%', 'loyalty-program'); ?>');
                    hasErrors = true;
                }
            }
        });

        if (hasErrors) {
            e.preventDefault();
            SwalConfig.error(errorMessages.join('\n'));
            return false;
        }
    });

    // Initialize Select2 for product search
    function initSelect2($element) {
        $element.select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 300,
                type: 'GET',
                data: function(params) {
                    return {
                        action: 'loyalty_program_search_products',
                        _ajax_nonce: '<?php echo wp_create_nonce('loyalty_program_search_products'); ?>',
                        search: params.term || ''
                    };
                },
                processResults: function(data) {
                    if (data.success) {
                        return {
                            results: data.data.results
                        };
                    }
                    return {
                        results: []
                    };
                },
                cache: true
            },
            minimumInputLength: 3,
            placeholder: '<?php esc_attr_e('Type at least 3 characters to search...', 'loyalty-program'); ?>',
            allowClear: true,
            width: '100%',
            dropdownAutoWidth: true,
            language: {
                inputTooShort: function() {
                    return '<?php esc_html_e('Please enter 3 or more characters', 'loyalty-program'); ?>';
                },
                searching: function() {
                    return '<?php esc_html_e('Searching...', 'loyalty-program'); ?>';
                },
                noResults: function() {
                    return '<?php esc_html_e('No products found', 'loyalty-program'); ?>';
                },
                errorLoading: function() {
                    return '<?php esc_html_e('Error loading results. Please try again.', 'loyalty-program'); ?>';
                }
            }
        });
    }

    // Initialize existing Select2 elements
    $('.reward-product-select2').each(function() {
        initSelect2($(this));
    });

    // Add new product reward row
    $('#add-product-reward-row').on('click', function() {
        $('.no-rewards-row', '#product-rewards-tbody').remove();

        var template = $('#product-reward-row-template').html();
        var newRow = template.replace(/\{\{INDEX\}\}/g, productRowIndex);

        $('#product-rewards-tbody').append(newRow);

        // Initialize Select2 on the new row
        var $newSelect = $('#product-rewards-tbody tr:last-child').find('.reward-product-select2');
        initSelect2($newSelect);

        productRowIndex++;
        updateRowNumbers($('#product-rewards-tbody'));
    });

    // Add new coupon reward row
    $('#add-coupon-reward-row').on('click', function() {
        $('.no-rewards-row', '#coupon-rewards-tbody').remove();

        var template = $('#coupon-reward-row-template').html();
        var newRow = template.replace(/\{\{INDEX\}\}/g, couponRowIndex);

        $('#coupon-rewards-tbody').append(newRow);

        // Initialize coupon type
        $('#coupon-rewards-tbody tr:last-child').find('.coupon-type-select').trigger('change');

        couponRowIndex++;
        updateRowNumbers($('#coupon-rewards-tbody'));
    });

    // Initialize row numbers on load
    updateRowNumbers($('#product-rewards-tbody'));
    updateRowNumbers($('#coupon-rewards-tbody'));

    // WordPress Media Uploader for coupon images
    // Create a new frame for each button click to avoid conflicts
    $(document).on('click', '.upload-coupon-image-btn', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $wrapper = $button.closest('.coupon-image-upload-wrapper');
        var $preview = $wrapper.find('.coupon-image-preview');
        var $input = $wrapper.find('.coupon-image-id');
        
        // Create a new media frame for each button click
        var couponImageFrame = wp.media({
            title: '<?php esc_attr_e('Select Coupon Image', 'loyalty-program'); ?>',
            button: {
                text: '<?php esc_attr_e('Use this image', 'loyalty-program'); ?>'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When an image is selected, run a callback
        couponImageFrame.on('select', function() {
            var attachment = couponImageFrame.state().get('selection').first().toJSON();
            
            // Update preview
            $preview.html(
                '<img src="' + attachment.sizes.thumbnail.url + '" style="max-width: 100px; max-height: 100px; display: block; margin-bottom: 5px;">' +
                '<button type="button" class="button remove-coupon-image-btn" style="display: block;"><?php esc_attr_e('Remove Image', 'loyalty-program'); ?></button>'
            );
            
            // Update hidden input
            $input.val(attachment.id);
            
            // Close the frame after selection
            couponImageFrame.close();
        });
        
        // Open the media frame
        couponImageFrame.open();
    });
    
    // Remove coupon image
    $(document).on('click', '.remove-coupon-image-btn', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $wrapper = $button.closest('.coupon-image-upload-wrapper');
        var $preview = $wrapper.find('.coupon-image-preview');
        var $input = $wrapper.find('.coupon-image-id');
        
        $preview.html(
            '<div style="width: 100px; height: 100px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; margin-bottom: 5px;">' +
            '<span class="dashicons dashicons-format-image" style="font-size: 32px; color: #ccc;"></span>' +
            '</div>' +
            '<button type="button" class="button upload-coupon-image-btn"><?php esc_attr_e('Upload Image', 'loyalty-program'); ?></button>'
        );
        $input.val(0);
    });

    // Initialize Select2 for excluded products
    if ($('#coupon_excluded_products').length) {
        $('#coupon_excluded_products').select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 300,
                type: 'GET',
                data: function(params) {
                    return {
                        action: 'loyalty_program_search_products',
                        _ajax_nonce: '<?php echo wp_create_nonce('loyalty_program_search_products'); ?>',
                        search: params.term || ''
                    };
                },
                processResults: function(data) {
                    if (data.success) {
                        return {
                            results: data.data.results
                        };
                    }
                    return {
                        results: []
                    };
                },
                cache: true
            },
            minimumInputLength: 3,
            placeholder: '<?php esc_attr_e('Type at least 3 characters to search...', 'loyalty-program'); ?>',
            allowClear: true,
            width: '100%'
        });
    }

    // Initialize Select2 for excluded categories
    if ($('#coupon_excluded_categories').length) {
        $('#coupon_excluded_categories').select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 300,
                type: 'GET',
                data: function(params) {
                    return {
                        action: 'loyalty_program_search_categories',
                        _ajax_nonce: '<?php echo wp_create_nonce('loyalty_program_search_categories'); ?>',
                        search: params.term || ''
                    };
                },
                processResults: function(data) {
                    if (data.success) {
                        return {
                            results: data.data.results
                        };
                    }
                    return {
                        results: []
                    };
                },
                cache: true
            },
            minimumInputLength: 2,
            placeholder: '<?php esc_attr_e('Type at least 2 characters to search...', 'loyalty-program'); ?>',
            allowClear: true,
            width: '100%'
        });
    }
});
</script>

<?php
// Enqueue Select2 from CDN
wp_enqueue_script(
    'select2-cdn',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    array('jquery'),
    '4.1.0',
    true
);

wp_enqueue_style(
    'select2-cdn',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    array(),
    '4.1.0'
);
?>
