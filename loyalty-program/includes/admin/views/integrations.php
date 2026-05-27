<?php

/**
 * Admin Integrations View
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get SalesManago settings
$salesmanago_enabled = get_option('loyalty_program_salesmanago_enabled', 'no');
$salesmanago_client_id = get_option('loyalty_program_salesmanago_client_id', '');
$salesmanago_sha = get_option('loyalty_program_salesmanago_sha', '');
$salesmanago_api_key = get_option('loyalty_program_salesmanago_api_key', '');
$salesmanago_owner = get_option('loyalty_program_salesmanago_owner', '');

// Check if WooCommerce Multi Currency is active
$is_wmc_active = class_exists('WOOMULTI_CURRENCY');

// Get WooCommerce Multi Currency settings
$wmc_currencies = array();
if ($is_wmc_active) {
    $wmc_params = get_option('woo_multi_currency_params', array());

    if (!empty($wmc_params['currency']) && is_array($wmc_params['currency'])) {
        $currencies = $wmc_params['currency'];
        $currency_rates = isset($wmc_params['currency_rate']) ? $wmc_params['currency_rate'] : array();
        $currency_hidden = isset($wmc_params['currency_hidden']) ? $wmc_params['currency_hidden'] : array();
        $wmc_default = isset($wmc_params['currency_default']) ? $wmc_params['currency_default'] : get_option('woocommerce_currency');

        // Build currencies list from individual arrays
        foreach ($currencies as $key => $currency_code) {
            if (!empty($currency_code)) {
                // Skip hidden currencies
                if (isset($currency_hidden[$key]) && $currency_hidden[$key] == 1) {
                    continue;
                }

                $wmc_currencies[$currency_code] = array(
                    'code' => $currency_code,
                    'rate' => isset($currency_rates[$key]) ? $currency_rates[$key] : 1,
                    'is_default' => ($currency_code === $wmc_default)
                );
            }
        }
    }
}

// Debug
if (!class_exists('Loyalty_Program_Logger')) {
    require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
}
Loyalty_Program_Logger::debug('Loading integrations page', array(
    'salesmanago_enabled' => $salesmanago_enabled,
    'salesmanago_client_id' => $salesmanago_client_id,
));

settings_errors('loyalty_program_integrations');
?>

<div class="wrap loyalty-program-integrations">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <p class="description">
        <?php _e('Configure third-party integrations to extend your loyalty program functionality.', 'loyalty-program'); ?>
    </p>

    <form method="post" action="">
        <?php wp_nonce_field('loyalty_program_integrations', 'loyalty_program_integrations_nonce'); ?>

        <!-- SalesManago Integration -->
        <div class="loyalty-integration-section">
            <div class="loyalty-integration-header">
                <div>
                    <h2>
                        <span class="dashicons dashicons-admin-plugins" style="color: #2271b1;"></span>
                        SalesManago
                    </h2>
                    <p class="description" style="margin: 5px 0 0 0;">
                        <?php _e('Marketing automation platform integration for syncing user data and consents.', 'loyalty-program'); ?>
                    </p>
                </div>
                <label class="loyalty-toggle">
                    <input type="checkbox" name="salesmanago_enabled" value="yes"
                        <?php checked($salesmanago_enabled, 'yes'); ?>
                        onchange="toggleIntegrationFields('salesmanago', this.checked)">
                    <span class="loyalty-toggle-slider"></span>
                </label>
            </div>

            <div id="salesmanago-fields"
                class="loyalty-integration-content <?php echo $salesmanago_enabled === 'no' ? 'loyalty-integration-disabled' : ''; ?>">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="salesmanago_client_id">
                                    <?php _e('Client ID', 'loyalty-program'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="salesmanago_client_id" id="salesmanago_client_id"
                                    value="<?php echo esc_attr($salesmanago_client_id); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Your SalesManago Client ID', 'loyalty-program'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="salesmanago_sha">
                                    <?php _e('SHA', 'loyalty-program'); ?>
                                </label>
                            </th>
                            <td>
                                <div style="position: relative; display: inline-block; width: 100%;">
                                    <input type="password" name="salesmanago_sha" id="salesmanago_sha"
                                        value="<?php echo esc_attr($salesmanago_sha); ?>" class="regular-text"
                                        autocomplete="off">
                                    <span class="dashicons dashicons-visibility toggle-password-visibility"
                                        data-target="salesmanago_sha"
                                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888;"
                                        title="<?php _e('Show/Hide', 'loyalty-program'); ?>"></span>
                                </div>
                                <p class="description">
                                    <?php _e('Your SalesManago SHA key', 'loyalty-program'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="salesmanago_api_key">
                                    <?php _e('API Key', 'loyalty-program'); ?>
                                </label>
                            </th>
                            <td>
                                <div style="position: relative; display: inline-block; width: 100%;">
                                    <input type="password" name="salesmanago_api_key" id="salesmanago_api_key"
                                        value="<?php echo esc_attr($salesmanago_api_key); ?>" class="regular-text"
                                        autocomplete="off">
                                    <span class="dashicons dashicons-visibility toggle-password-visibility"
                                        data-target="salesmanago_api_key"
                                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888;"
                                        title="<?php _e('Show/Hide', 'loyalty-program'); ?>"></span>
                                </div>
                                <p class="description">
                                    <?php _e('Your SalesManago API Key', 'loyalty-program'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="salesmanago_owner">
                                    <?php _e('Owner Email', 'loyalty-program'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="email" name="salesmanago_owner" id="salesmanago_owner"
                                    value="<?php echo esc_attr($salesmanago_owner); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('SalesManago account owner email address', 'loyalty-program'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"></th>
                            <td>
                                <button type="button" id="test-salesmanago-connection" class="button button-secondary">
                                    <span class="dashicons dashicons-cloud"></span>
                                    <?php _e('Test Connection', 'loyalty-program'); ?>
                                </button>
                                <div id="salesmanago-test-result"></div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"></th>
                            <td>
                                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #dcdcde;">
                                <h4 style="margin: 0 0 15px 0;">
                                    <?php _e('Verify Email in SalesManago', 'loyalty-program'); ?></h4>
                                <p style="margin: 0 0 10px 0; color: #646970;">
                                    <?php _e('Check if a specific email address exists in your SalesManago account.', 'loyalty-program'); ?>
                                </p>
                                <div style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px;">
                                    <input type="email" id="verify-email-input" class="regular-text"
                                        placeholder="<?php esc_attr_e('Enter email address...', 'loyalty-program'); ?>"
                                        style="max-width: 350px;">
                                    <button type="button" id="verify-email-salesmanago" class="button button-secondary">
                                        <span class="dashicons dashicons-search"></span>
                                        <?php _e('Verify Email', 'loyalty-program'); ?>
                                    </button>
                                </div>
                                <div id="verify-email-result"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="background: #f0f6fc; border-left: 4px solid #72aee6; padding: 15px; margin-top: 20px;">
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Integration Features:', 'loyalty-program'); ?></strong>
                    </p>
                    <ul style="margin: 5px 0 0 20px;">
                        <li><?php _e('Automatic sync of user consents (SMS and Newsletter)', 'loyalty-program'); ?></li>
                        <li><?php _e('User profile data synchronization', 'loyalty-program'); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- WooCommerce Multi Currency -->
        <div class="loyalty-integration-section">
            <div class="loyalty-integration-header">
                <div>
                    <h2>
                        <span class="dashicons dashicons-money-alt" style="color: #2271b1;"></span>
                        WooCommerce Multi Currency
                    </h2>
                    <p class="description" style="margin: 5px 0 0 0;">
                        <?php _e('Multi-currency support for your WooCommerce store.', 'loyalty-program'); ?>
                    </p>
                </div>
                <div>
                    <?php if ($is_wmc_active) : ?>
                        <span class="dashicons dashicons-yes-alt" style="color: #00a32a; font-size: 24px;"></span>
                        <span style="color: #00a32a; font-weight: 600;"><?php _e('Active', 'loyalty-program'); ?></span>
                    <?php else : ?>
                        <span class="dashicons dashicons-warning" style="color: #d63638; font-size: 24px;"></span>
                        <span style="color: #d63638; font-weight: 600;"><?php _e('Inactive', 'loyalty-program'); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="loyalty-integration-content">
                <?php if (!$is_wmc_active) : ?>
                    <div style="background: #fcf8e3; border-left: 4px solid #f0ad4e; padding: 12px; margin-bottom: 15px;">
                        <strong style="color: #8a6d3b;">⚠️ <?php _e('Plugin Not Detected', 'loyalty-program'); ?></strong>
                        <p style="margin: 5px 0 0 0; color: #8a6d3b;">
                            <?php _e('The WooCommerce Multi Currency plugin is not active. Please install and activate it to use multi-currency features.', 'loyalty-program'); ?>
                        </p>
                    </div>
                <?php else : ?>
                    <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin-bottom: 15px;">
                        <p style="margin: 0 0 5px 0;">
                            <strong><?php _e('Available Currencies', 'loyalty-program'); ?></strong>
                        </p>
                        <p class="description" style="margin: 0;">
                            <?php _e('The following currencies are configured in WooCommerce Multi Currency:', 'loyalty-program'); ?>
                        </p>
                    </div>

                    <?php if (!empty($wmc_currencies)) : ?>
                        <table class="widefat striped" style="margin-top: 15px;">
                            <thead>
                                <tr>
                                    <th style="width: 80px;"><strong><?php _e('Default', 'loyalty-program'); ?></strong></th>
                                    <th style="width: 120px;"><strong><?php _e('Currency Code', 'loyalty-program'); ?></strong></th>
                                    <th><strong><?php _e('Currency Name', 'loyalty-program'); ?></strong></th>
                                    <th style="width: 120px;"><strong><?php _e('Exchange Rate', 'loyalty-program'); ?></strong></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($wmc_currencies as $currency_code => $currency_data) :
                                    if (is_array($currency_data)) :
                                        // Get currency name from WooCommerce if available
                                        $currency_name = $currency_code;
                                        $currency_symbol = '';

                                        if (function_exists('get_woocommerce_currencies')) {
                                            $wc_currencies = get_woocommerce_currencies();
                                            if (isset($wc_currencies[strtoupper($currency_code)])) {
                                                $currency_name = $wc_currencies[strtoupper($currency_code)];
                                            }
                                        }

                                        if (function_exists('get_woocommerce_currency_symbol')) {
                                            $currency_symbol = get_woocommerce_currency_symbol(strtoupper($currency_code));
                                        }

                                        $rate = isset($currency_data['rate']) ? $currency_data['rate'] : 1;
                                        $is_default = isset($currency_data['is_default']) ? $currency_data['is_default'] : false;
                                ?>
                                        <tr>
                                            <td style="text-align: center;">
                                                <?php if ($is_default) : ?>
                                                    <span class="dashicons dashicons-yes-alt" style="color: #00a32a;" title="<?php esc_attr_e('Default Currency', 'loyalty-program'); ?>"></span>
                                                <?php else : ?>
                                                    <span style="color: #dcdcde;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo esc_html(strtoupper($currency_code)); ?></strong>
                                                <?php if ($currency_symbol) : ?>
                                                    <span style="color: #757575;">(<?php echo esc_html($currency_symbol); ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html($currency_name); ?></td>
                                            <td><code><?php echo esc_html(number_format((float)$rate, 7)); ?></code></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p style="padding: 15px; background: #f6f7f7; border-left: 4px solid #72aee6; margin: 15px 0;">
                            <?php _e('No currencies configured yet. Please configure currencies in WooCommerce Multi Currency settings.', 'loyalty-program'); ?>
                        </p>
                    <?php endif; ?>

                    <div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #72aee6;">
                        <p style="margin: 0 0 5px 0;">
                            <strong><?php _e('Note:', 'loyalty-program'); ?></strong>
                        </p>
                        <p style="margin: 0;" class="description">
                            <?php _e('The loyalty program automatically uses the active currency selected by the customer. Points are calculated based on the order total in the selected currency.', 'loyalty-program'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php submit_button(__('Save Integration Settings', 'loyalty-program')); ?>
    </form>

</div>

<style>
    .loyalty-program-integrations {
        max-width: 1200px;
    }

    .loyalty-integration-section {
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .loyalty-integration-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #dcdcde;
    }

    .loyalty-integration-header h2 {
        margin: 0;
        font-size: 18px;
    }

    .loyalty-toggle {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }

    .loyalty-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .loyalty-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }

    .loyalty-toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    .loyalty-toggle input:checked+.loyalty-toggle-slider {
        background-color: #2271b1;
    }

    .loyalty-toggle input:checked+.loyalty-toggle-slider:before {
        transform: translateX(26px);
    }

    .loyalty-integration-content {
        margin-top: 20px;
    }

    .loyalty-integration-content .form-table th {
        width: 200px;
        padding-left: 0;
    }

    .loyalty-integration-disabled {
        opacity: 0.5;
    }

    .loyalty-integration-disabled input[readonly],
    .loyalty-integration-disabled button:disabled {
        cursor: not-allowed;
        background-color: #f0f0f1;
    }

    .loyalty-test-connection {
        margin-top: 10px;
    }

    .loyalty-test-result {
        margin-top: 10px;
        padding: 10px;
        border-radius: 4px;
    }

    .loyalty-test-result.success {
        background: #d5f4e6;
        border-left: 4px solid #00a32a;
    }

    .loyalty-test-result.error {
        background: #fcf0f1;
        border-left: 4px solid #d63638;
    }

    .toggle-password-visibility {
        transition: color 0.2s ease;
    }

    .toggle-password-visibility:hover {
        color: #2271b1 !important;
    }

    @keyframes rotation {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Toggle integration fields
        window.toggleIntegrationFields = function(integration, enabled) {
            console.log('Toggle ' + integration + ':', enabled);

            const $fields = $('#' + integration + '-fields');
            const $inputs = $fields.find(
                'input[type="text"], input[type="email"], input[type="password"], select, textarea');
            const $buttons = $fields.find('button');

            console.log('Found fields:', $fields.length, 'inputs:', $inputs.length, 'buttons:', $buttons
                .length);

            if (enabled) {
                $fields.removeClass('loyalty-integration-disabled');
                $inputs.removeAttr('readonly');
                $buttons.prop('disabled', false);
                console.log('Enabled - classes after:', $fields.attr('class'));
            } else {
                $fields.addClass('loyalty-integration-disabled');
                $inputs.attr('readonly', 'readonly');
                $buttons.prop('disabled', true);
                console.log('Disabled - classes after:', $fields.attr('class'));
            }
        };

        // Toggle password visibility
        $('.toggle-password-visibility').on('click', function() {
            const targetId = $(this).data('target');
            const $input = $('#' + targetId);
            const currentType = $input.attr('type');

            if (currentType === 'password') {
                $input.attr('type', 'text');
                $(this).removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $input.attr('type', 'password');
                $(this).removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });

        // Initialize field states on page load
        const $checkbox = $('input[name="salesmanago_enabled"]');
        console.log('Checkbox element:', $checkbox.length);
        console.log('Checkbox checked attribute:', $checkbox.attr('checked'));
        console.log('Checkbox is(:checked):', $checkbox.is(':checked'));
        console.log('PHP value: <?php echo $salesmanago_enabled; ?>');

        const salesManagoEnabled = $checkbox.is(':checked');
        toggleIntegrationFields('salesmanago', salesManagoEnabled);

        // Test SalesManago connection
        $('#test-salesmanago-connection').on('click', function() {
            const $button = $(this);
            const $result = $('#salesmanago-test-result');

            $button.prop('disabled', true).html(
                '<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> <?php esc_attr_e('Testing...', 'loyalty-program'); ?>'
            );
            $result.html('');

            $.post(ajaxurl, {
                action: 'loyalty_program_test_salesmanago',
                nonce: '<?php echo wp_create_nonce('loyalty_program_test_salesmanago'); ?>',
                client_id: $('#salesmanago_client_id').val(),
                sha: $('#salesmanago_sha').val(),
                api_key: $('#salesmanago_api_key').val(),
                owner: $('#salesmanago_owner').val()
            }, function(response) {
                if (response.success) {
                    $result.html(
                        '<div class="loyalty-test-result success"><span class="dashicons dashicons-yes-alt"></span> ' +
                        response.data.message + '</div>');
                } else {
                    $result.html(
                        '<div class="loyalty-test-result error"><span class="dashicons dashicons-dismiss"></span> ' +
                        response.data.message + '</div>');
                }
            }).fail(function() {
                $result.html(
                    '<div class="loyalty-test-result error"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Connection error. Please try again.', 'loyalty-program'); ?></div>'
                );
            }).always(function() {
                $button.prop('disabled', false).html(
                    '<span class="dashicons dashicons-cloud"></span> <?php esc_attr_e('Test Connection', 'loyalty-program'); ?>'
                );
            });
        });

        // Verify email in SalesManago
        $('#verify-email-salesmanago').on('click', function() {
            const $button = $(this);
            const $result = $('#verify-email-result');
            const $input = $('#verify-email-input');
            const email = $input.val().trim();

            if (!email) {
                $result.html(
                    '<div class="loyalty-test-result error"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Please enter an email address.', 'loyalty-program'); ?></div>'
                );
                return;
            }

            // Simple email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                $result.html(
                    '<div class="loyalty-test-result error"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Please enter a valid email address.', 'loyalty-program'); ?></div>'
                );
                return;
            }

            $button.prop('disabled', true).html(
                '<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> <?php esc_attr_e('Verifying...', 'loyalty-program'); ?>'
            );
            $result.html('');

            $.post(ajaxurl, {
                action: 'loyalty_program_verify_email_salesmanago',
                nonce: '<?php echo wp_create_nonce('loyalty_program_verify_email_salesmanago'); ?>',
                email: email
            }, function(response) {
                if (response.success) {
                    let html = '<div class="loyalty-test-result success">';
                    html +=
                        '<h4 style="margin: 0 0 10px 0;"><span class="dashicons dashicons-yes-alt"></span> ' +
                        response.data.message + '</h4>';

                    if (response.data.contact) {
                        const contact = response.data.contact;
                        html +=
                            '<div style="background: #fff; padding: 20px; border: 1px solid #c3e6cb; border-radius: 4px; margin-top: 10px;">';

                        // Contact Details header
                        html +=
                            '<h3 style="margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb; font-size: 16px;"><?php esc_html_e('Contact Details:', 'loyalty-program'); ?></h3>';

                        html +=
                            '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">';

                        // Full Name (combine name and lastName if available)
                        let fullName = '';
                        if (contact.name) fullName += contact.name;
                        if (contact.lastName) fullName += (fullName ? ' ' : '') + contact.lastName;
                        if (fullName) {
                            html +=
                                '<p style="margin: 0;"><strong><?php esc_html_e('Full Name:', 'loyalty-program'); ?></strong><br>' +
                                fullName + '</p>';
                        }

                        if (contact.email) {
                            html += '<p style="margin: 0;"><strong>Email:</strong><br>' + contact
                                .email + '</p>';
                        }

                        if (contact.phone) {
                            html +=
                                '<p style="margin: 0;"><strong><?php esc_html_e('Phone:', 'loyalty-program'); ?></strong><br>' +
                                contact.phone + '</p>';
                        }

                        // Birthday from separate fields
                        let birthday = '';
                        if (contact.birthdayYear && contact.birthdayMonth && contact.birthdayDay) {
                            birthday = contact.birthdayYear + '-' + String(contact.birthdayMonth)
                                .padStart(2, '0') + '-' + String(contact.birthdayDay).padStart(2,
                                    '0');
                        } else if (contact.birthday) {
                            birthday = contact.birthday;
                        }
                        if (birthday) {
                            html +=
                                '<p style="margin: 0;"><strong><?php esc_html_e('Birthday:', 'loyalty-program'); ?></strong><br>' +
                                birthday + '</p>';
                        }

                        html += '</div>';

                        // Address
                        if (contact.address && (contact.address.streetAddress || contact.address
                                .city)) {
                            html +=
                                '<h4 style="margin: 20px 0 10px 0; font-size: 14px;"><?php esc_html_e('Address:', 'loyalty-program'); ?></h4>';
                            html +=
                                '<div style="background: #f9fafb; padding: 12px; border-radius: 4px; margin-bottom: 15px;">';

                            if (contact.address.streetAddress) {
                                html += '<p style="margin: 0;">' + contact.address.streetAddress +
                                    '</p>';
                            }

                            let cityLine = '';
                            if (contact.address.zipCode) cityLine += contact.address.zipCode + ' ';
                            if (contact.address.city) cityLine += contact.address.city;
                            if (cityLine) html += '<p style="margin: 5px 0 0 0;">' + cityLine +
                                '</p>';

                            if (contact.address.province) {
                                html += '<p style="margin: 5px 0 0 0;">' + contact.address
                                    .province + '</p>';
                            }
                            if (contact.address.country) {
                                html += '<p style="margin: 5px 0 0 0;">' + contact.address.country +
                                    '</p>';
                            }

                            html += '</div>';
                        }

                        // Consents
                        html +=
                            '<h4 style="margin: 20px 0 10px 0; font-size: 14px;"><?php esc_html_e('Consents:', 'loyalty-program'); ?></h4>';
                        html +=
                            '<div style="background: #f9fafb; padding: 12px; border-radius: 4px;">';

                        // Newsletter Consent: optedOut === false means ENABLED
                        let newsletterConsent;
                        if (contact.optedOut === false) {
                            newsletterConsent =
                                '<span style="color: #00a32a; font-weight: 600;">✓ Newsletter</span>';
                        } else {
                            newsletterConsent =
                                '<span style="color: #d63638; font-weight: 600;">✗ Newsletter</span>';
                        }

                        // SMS Consent: optedOutPhone === false means ENABLED
                        let smsConsent;
                        let smsMismatch = false;
                        if (contact.optedOutPhone === false) {
                            smsConsent =
                                '<span style="color: #00a32a; font-weight: 600;">✓ SMS</span>';
                        } else {
                            smsConsent =
                                '<span style="color: #d63638; font-weight: 600;">✗ SMS</span>';
                        }

                        // Check if WordPress consents are available and compare
                        if (contact._wp_consents) {
                            const wpSms = contact._wp_consents.sms;
                            const smSms = contact.optedOutPhone === false;
                            
                            if (wpSms !== smSms) {
                                smsMismatch = true;
                                html += '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin-bottom: 10px; border-radius: 4px;">';
                                html += '<p style="margin: 0 0 5px 0;"><strong><?php esc_html_e('⚠️ Mismatch detected:', 'loyalty-program'); ?></strong></p>';
                                html += '<p style="margin: 0 0 5px 0;"><?php esc_html_e('WordPress:', 'loyalty-program'); ?> ' + (wpSms ? '<span style="color: #00a32a;">✓ SMS Aktywny</span>' : '<span style="color: #d63638;">✗ SMS Nieaktywny</span>') + '</p>';
                                html += '<p style="margin: 0;"><?php esc_html_e('SalesManago:', 'loyalty-program'); ?> ' + (smSms ? '<span style="color: #00a32a;">✓ SMS Aktywny</span>' : '<span style="color: #d63638;">✗ SMS Nieaktywny</span>') + '</p>';
                                html += '</div>';
                            }
                        }

                        html += '<p style="margin: 0 0 8px 0;"><strong><?php esc_html_e('SalesManago:', 'loyalty-program'); ?></strong> ' + newsletterConsent + '</p>';
                        html += '<p style="margin: 0;"><strong><?php esc_html_e('SalesManago:', 'loyalty-program'); ?></strong> ' + smsConsent + '</p>';
                        
                        // Show WordPress consents if available
                        if (contact._wp_consents) {
                            html += '<hr style="margin: 10px 0; border: 0; border-top: 1px solid #dcdcde;">';
                            html += '<p style="margin: 0 0 8px 0;"><strong><?php esc_html_e('WordPress:', 'loyalty-program'); ?></strong> ' + 
                                (contact._wp_consents.newsletter ? '<span style="color: #00a32a; font-weight: 600;">✓ Newsletter</span>' : '<span style="color: #d63638; font-weight: 600;">✗ Newsletter</span>') + '</p>';
                            html += '<p style="margin: 0;"><strong><?php esc_html_e('WordPress:', 'loyalty-program'); ?></strong> ' + 
                                (contact._wp_consents.sms ? '<span style="color: #00a32a; font-weight: 600;">✓ SMS</span>' : '<span style="color: #d63638; font-weight: 600;">✗ SMS</span>') + '</p>';
                        }
                        
                        html += '</div>';
                        
                        // Add sync button if mismatch detected
                        if (smsMismatch && contact._wp_consents) {
                            html += '<div style="margin-top: 15px;">';
                            html += '<button type="button" class="button button-secondary sync-consents-btn" data-email="' + email + '" style="margin-right: 10px;">';
                            html += '<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> ';
                            html += '<?php esc_html_e('Synchronize Consents with SalesManago', 'loyalty-program'); ?>';
                            html += '</button>';
                            html += '</div>';
                        }

                        html += '</div>';
                    }

                    html += '</div>';
                    $result.html(html);
                } else {
                    $result.html(
                        '<div class="loyalty-test-result error"><span class="dashicons dashicons-dismiss"></span> ' +
                        response.data.message + '</div>');
                }
            }).fail(function() {
                $result.html(
                    '<div class="loyalty-test-result error"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Connection error. Please try again.', 'loyalty-program'); ?></div>'
                );
            }).always(function() {
                $button.prop('disabled', false).html(
                    '<span class="dashicons dashicons-search"></span> <?php esc_attr_e('Verify Email', 'loyalty-program'); ?>'
                );
            });
        });

        // Allow Enter key to trigger verification
        $('#verify-email-input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#verify-email-salesmanago').click();
            }
        });

        // Sync consents button handler (delegated event for dynamically added buttons)
        $(document).on('click', '.sync-consents-btn', function() {
            const $button = $(this);
            const email = $button.data('email');
            const $result = $('#verify-email-result');

            if (!email) {
                return;
            }

            $button.prop('disabled', true).html(
                '<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> <?php esc_attr_e('Synchronizing...', 'loyalty-program'); ?>'
            );

            $.post(ajaxurl, {
                action: 'loyalty_program_sync_consents_salesmanago',
                nonce: '<?php echo wp_create_nonce('loyalty_program_sync_consents_salesmanago'); ?>',
                email: email
            }, function(response) {
                if (response.success) {
                    $result.html(
                        '<div class="loyalty-test-result success"><span class="dashicons dashicons-yes-alt"></span> ' +
                        response.data.message + '</div>'
                    );
                    // Trigger verification again to refresh data
                    setTimeout(function() {
                        $('#verify-email-input').val(email);
                        $('#verify-email-salesmanago').click();
                    }, 1000);
                } else {
                    $result.html(
                        '<div class="loyalty-test-result error"><span class="dashicons dashicons-dismiss"></span> ' +
                        response.data.message + '</div>'
                    );
                }
            }).fail(function() {
                $result.html(
                    '<div class="loyalty-test-result error"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Connection error. Please try again.', 'loyalty-program'); ?></div>'
                );
            }).always(function() {
                $button.prop('disabled', false).html(
                    '<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> <?php esc_attr_e('Synchronize Consents with SalesManago', 'loyalty-program'); ?>'
                );
            });
        });
    });
</script>