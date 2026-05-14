<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Weblo_Custom_Checkout
{
    private const OPTION_KEY = 'weblo_cc_settings';
    private const VERSION    = '1.0.0';

    private Weblo_CC_Logger $logger;

    public function __construct()
    {
        $this->logger = new Weblo_CC_Logger($this->get_log_file_path());

        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('woocommerce_before_checkout_form', [$this, 'maybe_adjust_checkout_hooks'], 1);
        add_filter('woocommerce_locate_template', [$this, 'maybe_override_template'], 10, 3);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('woocommerce_before_checkout_form', [$this, 'maybe_enqueue_scripts_fallback'], 5);
        add_action('woocommerce_before_checkout_form', [$this, 'maybe_hide_coupons'], 1);
        add_action('woocommerce_before_checkout_form', [$this, 'maybe_hide_login'], 1);
        add_filter('woocommerce_checkout_fields', [$this, 'add_vat_invoice_fields'], 999);
        add_action('woocommerce_checkout_process', [$this, 'validate_nip_field']);
        add_action('woocommerce_checkout_process', [$this, 'validate_consents']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'weblo-custom-checkout',
            false,
            dirname(plugin_basename(WEBLO_CC_FILE)) . '/languages'
        );
    }

    public function register_settings_page(): void
    {
        add_menu_page(
            __('Checkout Settings', 'weblo-custom-checkout'),
            __('Checkout Settings', 'weblo-custom-checkout'),
            'manage_options',
            'weblo-cc-settings',
            [$this, 'render_settings_page'],
            'dashicons-cart',
            99
        );
    }

    public function register_settings(): void
    {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default'           => $this->default_settings(),
        ]);

        add_settings_section(
            'weblo_cc_general',
            __('General', 'weblo-custom-checkout'),
            [$this, 'render_general_description'],
            'weblo-cc-settings'
        );

        add_settings_field(
            'enable_custom_checkout',
            __('Enable custom checkout', 'weblo-custom-checkout'),
            [$this, 'render_enable_field'],
            'weblo-cc-settings',
            'weblo_cc_general'
        );

        add_settings_field(
            'checkout_status_info',
            __('Current status', 'weblo-custom-checkout'),
            [$this, 'render_status_field'],
            'weblo-cc-settings',
            'weblo_cc_general'
        );

        add_settings_field(
            'enable_coupons',
            __('Enable coupons in checkout', 'weblo-custom-checkout'),
            [$this, 'render_coupons_field'],
            'weblo-cc-settings',
            'weblo_cc_general'
        );

        add_settings_field(
            'enable_login',
            __('Enable login in checkout', 'weblo-custom-checkout'),
            [$this, 'render_login_field'],
            'weblo-cc-settings',
            'weblo_cc_general'
        );

        add_settings_section(
            'weblo_cc_developer',
            __('Developer', 'weblo-custom-checkout'),
            '__return_null',
            'weblo-cc-settings'
        );

        add_settings_field(
            'developer_mode',
            __('Developer mode (random asset versions)', 'weblo-custom-checkout'),
            [$this, 'render_developer_field'],
            'weblo-cc-settings',
            'weblo_cc_developer'
        );

        add_settings_field(
            'asset_version',
            __('Asset version (used when developer mode is off)', 'weblo-custom-checkout'),
            [$this, 'render_version_field'],
            'weblo-cc-settings',
            'weblo_cc_developer'
        );

        add_settings_field(
            'enable_debug_log',
            __('Enable debug log', 'weblo-custom-checkout'),
            [$this, 'render_debug_log_field'],
            'weblo-cc-settings',
            'weblo_cc_developer'
        );

        add_settings_field(
            'company_field_id',
            __('Company name field ID', 'weblo-custom-checkout'),
            [$this, 'render_company_field_id'],
            'weblo-cc-settings',
            'weblo_cc_general'
        );

        add_settings_field(
            'nip_field_id',
            __('Tax Identification Number (NIP) field ID', 'weblo-custom-checkout'),
            [$this, 'render_nip_field_id'],
            'weblo-cc-settings',
            'weblo_cc_general'
        );

        add_settings_section(
            'weblo_cc_consents',
            __('Consents', 'weblo-custom-checkout'),
            [$this, 'render_consents_description'],
            'weblo-cc-settings'
        );

        add_settings_field(
            'consents_manager',
            __('Manage consents', 'weblo-custom-checkout'),
            [$this, 'render_consents_manager'],
            'weblo-cc-settings',
            'weblo_cc_consents'
        );
    }

    public function sanitize_settings(array $settings): array
    {
        $defaults = $this->default_settings();

        $settings['enable_custom_checkout'] = !empty($settings['enable_custom_checkout']);
        $settings['enable_coupons']        = !empty($settings['enable_coupons']);
        $settings['enable_login']          = !empty($settings['enable_login']);
        $settings['developer_mode']        = !empty($settings['developer_mode']);
        $settings['enable_debug_log']      = !empty($settings['enable_debug_log']);
        $settings['asset_version']         = isset($settings['asset_version']) && $settings['asset_version'] !== ''
            ? sanitize_text_field($settings['asset_version'])
            : $defaults['asset_version'];
        $settings['company_field_id']      = isset($settings['company_field_id']) && $settings['company_field_id'] !== ''
            ? sanitize_text_field($settings['company_field_id'])
            : $defaults['company_field_id'];
        $settings['nip_field_id']          = isset($settings['nip_field_id']) && $settings['nip_field_id'] !== ''
            ? sanitize_text_field($settings['nip_field_id'])
            : $defaults['nip_field_id'];

        // Sanitize consents
        if (isset($settings['consents']) && is_array($settings['consents'])) {
            $sanitized_consents = [];
            foreach ($settings['consents'] as $consent) {
                if (!is_array($consent)) {
                    continue;
                }
                $sanitized_consents[] = [
                    'id'          => isset($consent['id']) ? sanitize_text_field($consent['id']) : '',
                    'enabled'     => !empty($consent['enabled']),
                    'required'    => !empty($consent['required']),
                    'text'        => isset($consent['text']) ? wp_kses_post($consent['text']) : '',
                    'order'       => isset($consent['order']) ? absint($consent['order']) : 0,
                ];
            }
            // Sort by order
            usort($sanitized_consents, function ($a, $b) {
                return $a['order'] <=> $b['order'];
            });
            $settings['consents'] = $sanitized_consents;
        } else {
            $settings['consents'] = $defaults['consents'];
        }

        return wp_parse_args($settings, $defaults);
    }

    public function render_settings_page(): void
    {
?>
        <div class="wrap">
            <h1><?php esc_html_e('Checkout Settings', 'weblo-custom-checkout'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_KEY);
                do_settings_sections('weblo-cc-settings');
                submit_button(__('Save changes', 'weblo-custom-checkout'));
                ?>
            </form>
        </div>
    <?php
    }

    public function render_enable_field(): void
    {
        $settings = $this->get_settings();
    ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[enable_custom_checkout]" value="1"
                <?php checked($settings['enable_custom_checkout']); ?>>
            <?php esc_html_e('Replace the default checkout with a custom experience.', 'weblo-custom-checkout'); ?>
        </label>
    <?php
    }

    public function render_status_field(): void
    {
        $settings  = $this->get_settings();
        $is_enabled = !empty($settings['enable_custom_checkout']);
        $label     = $is_enabled
            ? __('Custom checkout is enabled.', 'weblo-custom-checkout')
            : __('Custom checkout is disabled.', 'weblo-custom-checkout');
    ?>
        <p><strong><?php echo esc_html($label); ?></strong></p>
    <?php
    }

    public function render_coupons_field(): void
    {
        $settings = $this->get_settings();
    ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[enable_coupons]" value="1"
                <?php checked($settings['enable_coupons']); ?>>
            <?php esc_html_e('Show coupon form in checkout.', 'weblo-custom-checkout'); ?>
        </label>
    <?php
    }

    public function render_login_field(): void
    {
        $settings = $this->get_settings();
    ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[enable_login]" value="1"
                <?php checked($settings['enable_login']); ?>>
            <?php esc_html_e('Show login form in checkout.', 'weblo-custom-checkout'); ?>
        </label>
    <?php
    }

    public function render_developer_field(): void
    {
        $settings = $this->get_settings();
    ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[developer_mode]" value="1"
                <?php checked($settings['developer_mode']); ?>>
            <?php esc_html_e('When enabled, CSS/JS versions will be randomized.', 'weblo-custom-checkout'); ?>
        </label>
    <?php
    }

    public function render_version_field(): void
    {
        $settings = $this->get_settings();
    ?>
        <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[asset_version]"
            value="<?php echo esc_attr($settings['asset_version']); ?>" class="regular-text"
            placeholder="<?php echo esc_attr(self::VERSION); ?>">
        <p class="description">
            <?php esc_html_e('Used only when developer mode is disabled. Leave empty to use plugin version.', 'weblo-custom-checkout'); ?>
        </p>
    <?php
    }

    public function render_debug_log_field(): void
    {
        $settings = $this->get_settings();
    ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[enable_debug_log]" value="1"
                <?php checked($settings['enable_debug_log']); ?>>
            <?php esc_html_e('Write debug log to the plugin folder (max 5MB, auto-truncate).', 'weblo-custom-checkout'); ?>
        </label>
        <p class="description">
            <?php echo esc_html($this->get_log_file_path()); ?>
        </p>
    <?php
    }

    public function render_company_field_id(): void
    {
        $settings = $this->get_settings();
    ?>
        <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[company_field_id]"
            value="<?php echo esc_attr($settings['company_field_id']); ?>" class="regular-text" placeholder="billing_company">
        <p class="description">
            <?php esc_html_e('Field ID for Company name (used when VAT invoice checkbox is checked).', 'weblo-custom-checkout'); ?>
        </p>
    <?php
    }

    public function render_nip_field_id(): void
    {
        $settings = $this->get_settings();
    ?>
        <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[nip_field_id]"
            value="<?php echo esc_attr($settings['nip_field_id']); ?>" class="regular-text" placeholder="billing_nip">
        <p class="description">
            <?php esc_html_e('Field ID for Tax Identification Number (NIP) - must be 10 digits.', 'weblo-custom-checkout'); ?>
        </p>
    <?php
    }

    public function render_consents_description(): void
    {
        echo '<p>' . esc_html__('Manage consents displayed in the checkout. You can add, edit, reorder, enable/disable, and set custom IDs for each consent.', 'weblo-custom-checkout') . '</p>';
    }

    public function render_consents_manager(): void
    {
        $settings = $this->get_settings();
        $consents = isset($settings['consents']) && is_array($settings['consents']) ? $settings['consents'] : [];
    ?>
        <div id="weblo-consents-manager">
            <div id="weblo-consents-list">
                <?php if (empty($consents)) : ?>
                    <p class="weblo-no-consents">
                        <?php esc_html_e('No consents added yet. Click "Add Consent" to create one.', 'weblo-custom-checkout'); ?>
                    </p>
                <?php else : ?>
                    <?php foreach ($consents as $index => $consent) : ?>
                        <div class="weblo-consent-item" data-index="<?php echo esc_attr($index); ?>">
                            <div class="weblo-consent-handle">
                                <span class="dashicons dashicons-menu-alt"></span>
                            </div>
                            <div class="weblo-consent-content">
                                <div class="weblo-consent-header">
                                    <label>
                                        <input type="checkbox"
                                            name="<?php echo esc_attr(self::OPTION_KEY); ?>[consents][<?php echo esc_attr($index); ?>][enabled]"
                                            value="1" <?php checked(!empty($consent['enabled'])); ?>>
                                        <?php esc_html_e('Enabled', 'weblo-custom-checkout'); ?>
                                    </label>
                                    <label>
                                        <input type="checkbox"
                                            name="<?php echo esc_attr(self::OPTION_KEY); ?>[consents][<?php echo esc_attr($index); ?>][required]"
                                            value="1" <?php checked(!empty($consent['required'])); ?>>
                                        <?php esc_html_e('Required', 'weblo-custom-checkout'); ?>
                                    </label>
                                </div>
                                <div class="weblo-consent-fields">
                                    <p>
                                        <label>
                                            <?php esc_html_e('Custom ID:', 'weblo-custom-checkout'); ?>
                                            <input type="text"
                                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[consents][<?php echo esc_attr($index); ?>][id]"
                                                value="<?php echo esc_attr($consent['id'] ?? ''); ?>" class="regular-text"
                                                placeholder="consent_<?php echo esc_attr($index + 1); ?>">
                                        </label>
                                    </p>
                                    <p>
                                        <label>
                                            <?php esc_html_e('Consent text:', 'weblo-custom-checkout'); ?>
                                            <?php
                                            $editor_id = 'weblo_consent_text_' . (int) $index;
                                            $editor_settings = [
                                                'textarea_name' => self::OPTION_KEY . '[consents][' . (int) $index . '][text]',
                                                'textarea_rows' => 3,
                                                'media_buttons' => false,
                                                'teeny'         => true,
                                                'tinymce'       => true,
                                                'quicktags'     => true,
                                            ];
                                            wp_editor($consent['text'] ?? '', $editor_id, $editor_settings);
                                            ?>
                                        </label>
                                    </p>
                                    <input type="hidden"
                                        name="<?php echo esc_attr(self::OPTION_KEY); ?>[consents][<?php echo esc_attr($index); ?>][order]"
                                        value="<?php echo esc_attr($consent['order'] ?? $index); ?>" class="weblo-consent-order">
                                </div>
                            </div>
                            <div class="weblo-consent-actions">
                                <button type="button"
                                    class="button weblo-remove-consent"><?php esc_html_e('Remove', 'weblo-custom-checkout'); ?></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="button button-secondary"
                id="weblo-add-consent"><?php esc_html_e('Add Consent', 'weblo-custom-checkout'); ?></button>
        </div>

        <style>
            #weblo-consents-manager {
                margin-top: 10px;
            }

            .weblo-consent-item {
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 10px;
                background: #fff;
                display: flex;
                align-items: flex-start;
                gap: 10px;
            }

            .weblo-consent-handle {
                cursor: move;
                color: #999;
                padding-top: 5px;
            }

            .weblo-consent-content {
                flex: 1;
            }

            .weblo-consent-header {
                margin-bottom: 10px;
            }

            .weblo-consent-header label {
                margin-right: 15px;
            }

            .weblo-consent-fields p {
                margin-bottom: 10px;
            }

            .weblo-consent-fields label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
            }

            .weblo-consent-actions {
                padding-top: 5px;
            }

            .weblo-no-consents {
                padding: 20px;
                background: #f9f9f9;
                border: 1px dashed #ddd;
                text-align: center;
                color: #666;
            }
        </style>

        <script>
            jQuery(function($) {
                var consentIndex = <?php echo json_encode(count($consents)); ?>;
                var optionKey = <?php echo json_encode(self::OPTION_KEY); ?>;
                var enabledText = <?php echo json_encode(__('Enabled', 'weblo-custom-checkout')); ?>;
                var requiredText = <?php echo json_encode(__('Required', 'weblo-custom-checkout')); ?>;
                var customIdText = <?php echo json_encode(__('Custom ID:', 'weblo-custom-checkout')); ?>;
                var consentTextLabel = <?php echo json_encode(__('Consent text:', 'weblo-custom-checkout')); ?>;
                var removeText = <?php echo json_encode(__('Remove', 'weblo-custom-checkout')); ?>;
                var noConsentsText =
                    <?php echo json_encode(__('No consents added yet. Click "Add Consent" to create one.', 'weblo-custom-checkout')); ?>;

                function getConsentTemplate(index) {
                    return '<div class="weblo-consent-item" data-index="' + index + '">' +
                        '<div class="weblo-consent-handle"><span class="dashicons dashicons-menu-alt"></span></div>' +
                        '<div class="weblo-consent-content">' +
                        '<div class="weblo-consent-header">' +
                        '<label><input type="checkbox" name="' + optionKey + '[consents][' + index +
                        '][enabled]" value="1" checked> ' + enabledText + '</label> ' +
                        '<label><input type="checkbox" name="' + optionKey + '[consents][' + index +
                        '][required]" value="1"> ' + requiredText + '</label>' +
                        '</div>' +
                        '<div class="weblo-consent-fields">' +
                        '<p><label>' + customIdText + ' <input type="text" name="' + optionKey + '[consents][' + index +
                        '][id]" value="" class="regular-text" placeholder="consent_' + (index + 1) + '"></label></p>' +
                        '<p><label>' + consentTextLabel + ' <textarea name="' + optionKey + '[consents][' + index +
                        '][text]" rows="3" class="large-text"></textarea></label></p>' +
                        '<input type="hidden" name="' + optionKey + '[consents][' + index + '][order]" value="' + index +
                        '" class="weblo-consent-order">' +
                        '</div>' +
                        '</div>' +
                        '<div class="weblo-consent-actions">' +
                        '<button type="button" class="button weblo-remove-consent">' + removeText + '</button>' +
                        '</div>' +
                        '</div>';
                }

                $('#weblo-add-consent').on('click', function() {
                    if ($('.weblo-no-consents').length) {
                        $('.weblo-no-consents').remove();
                    }
                    $('#weblo-consents-list').append(getConsentTemplate(consentIndex));
                    consentIndex++;
                    updateConsentIndices();
                });

                $(document).on('click', '.weblo-remove-consent', function() {
                    $(this).closest('.weblo-consent-item').remove();
                    updateConsentIndices();
                    if ($('.weblo-consent-item').length === 0) {
                        $('#weblo-consents-list').html('<p class="weblo-no-consents">' + noConsentsText + '</p>');
                    }
                });

                function updateConsentIndices() {
                    $('.weblo-consent-item').each(function(index) {
                        $(this).attr('data-index', index);
                        $(this).find('input, textarea').each(function() {
                            var name = $(this).attr('name');
                            if (name) {
                                name = name.replace(/\[consents\]\[\d+\]/, '[consents][' + index + ']');
                                $(this).attr('name', name);
                            }
                        });
                        $(this).find('.weblo-consent-order').val(index);
                    });
                }

                // Sortable
                if ($.fn.sortable) {
                    $('#weblo-consents-list').sortable({
                        handle: '.weblo-consent-handle',
                        axis: 'y',
                        update: function() {
                            updateConsentIndices();
                        }
                    });
                }
            });
        </script>
<?php
    }

    public function get_asset_version(): string
    {
        $settings = $this->get_settings();

        if (!empty($settings['developer_mode'])) {
            return (string) microtime(true);
        }

        return $settings['asset_version'] ?: self::VERSION;
    }

    public function maybe_override_template(string $template, string $template_name, string $template_path): string
    {
        $settings = $this->get_settings();
        if (empty($settings['enable_custom_checkout'])) {
            return $template;
        }

        $overridable = [
            'checkout/form-checkout.php',
            'checkout/form-pay.php',
            'checkout/form-billing.php',
            'checkout/form-shipping.php',
            'checkout/review-order.php',
            'cart/cart-shipping.php',
            'checkout/payment.php',
            'checkout/terms.php',
            'global/form-login.php',
            'checkout/payment-method.php',
        ];

        // Normalize input path.
        $template_name = ltrim(str_replace('\\', '/', $template_name), '/');

        if (!in_array($template_name, $overridable, true)) {
            return $template;
        }

        $plugin_template = $this->get_plugin_template_path($template_name);
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return $template;
    }

    public function enqueue_styles(): void
    {
        $settings = $this->get_settings();
        if (empty($settings['enable_custom_checkout'])) {
            return;
        }

        if (!is_checkout()) {
            return;
        }

        $css_file = 'assets/css/custom_checkout.min.css';
        $css_path = WEBLO_CC_PATH . $css_file;
        $css_url  = WEBLO_CC_URL . $css_file;

        if (!file_exists($css_path)) {
            return;
        }

        wp_enqueue_style(
            'weblo-custom-checkout',
            $css_url,
            [],
            $this->get_asset_version()
        );

        // Add inline CSS to hide coupons if disabled.
        if (empty($settings['enable_coupons'])) {
            $inline_css = '.woocommerce-form-coupon-toggle { display: none !important; }';
            wp_add_inline_style('weblo-custom-checkout', $inline_css);
        }

        // Note: VAT invoice fields will be hidden by JS on page load
        // CSS is not needed as JS handles the visibility
    }

    public function enqueue_scripts(): void
    {
        $settings = $this->get_settings();

        if (empty($settings['enable_custom_checkout'])) {
            return;
        }

        if (!is_checkout()) {
            return;
        }

        $js_file = 'assets/js/checkout.js';
        $js_path = WEBLO_CC_PATH . $js_file;
        $js_url  = WEBLO_CC_URL . $js_file;

        if (!file_exists($js_path)) {
            return;
        }

        $version = $this->get_asset_version();

        wp_enqueue_script(
            'weblo-custom-checkout',
            $js_url,
            ['jquery'],
            $version,
            true
        );

        // Przekazanie tekstów do tłumaczenia do JS
        $i18n = [
            'field_required'      => __('This field is required.', 'weblo-custom-checkout'),
            'phone_min_length'    => __('Phone number must contain at least 9 digits.', 'weblo-custom-checkout'),
            'postcode_format'     => __('Postcode must be in format XX-XXX or XXXXX.', 'weblo-custom-checkout'),
            'nip_exact_digits'    => __('Tax Identification Number must be exactly 10 digits.', 'weblo-custom-checkout'),
            'street_label'        => __('st.', 'weblo-custom-checkout'),
        ];
        wp_localize_script('weblo-custom-checkout', 'WebloCheckoutI18n', $i18n);
    }

    public function maybe_enqueue_scripts_fallback(): void
    {
        $settings = $this->get_settings();
        if (empty($settings['enable_custom_checkout'])) {
            return;
        }

        if (wp_script_is('weblo-custom-checkout', 'enqueued') || wp_script_is('weblo-custom-checkout', 'done')) {
            return;
        }

        $js_file = 'assets/js/checkout.js';
        $js_path = WEBLO_CC_PATH . $js_file;
        $js_url  = WEBLO_CC_URL . $js_file;

        if (!file_exists($js_path)) {
            return;
        }

        $version = $this->get_asset_version();

        wp_enqueue_script(
            'weblo-custom-checkout',
            $js_url,
            ['jquery'],
            $version,
            true
        );
    }

    public function maybe_adjust_checkout_hooks(): void
    {
        $settings = $this->get_settings();
        if (empty($settings['enable_custom_checkout'])) {
            return;
        }

        // Remove default payment section from order review to avoid duplication with custom block.
        remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);

        // Remove default shipping section from order review to avoid duplication with custom shipping block.
        remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_shipping', 20);

        // Remove shipping totals row from the order review table (we render shipping in a custom block).
        remove_action('woocommerce_review_order_before_shipping', 'woocommerce_cart_totals_shipping_html', 10);
        remove_action('woocommerce_review_order_after_shipping', 'woocommerce_cart_totals_shipping_html', 10);
    }

    public function maybe_hide_coupons(): void
    {
        $settings = $this->get_settings();
        if (empty($settings['enable_custom_checkout'])) {
            return;
        }

        if (!empty($settings['enable_coupons'])) {
            return;
        }

        // Remove coupon form from checkout.
        remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
        remove_action('woocommerce_checkout_before_customer_details', 'woocommerce_checkout_coupon_form', 10);
    }

    public function maybe_hide_login(): void
    {
        $settings = $this->get_settings();
        if (empty($settings['enable_custom_checkout'])) {
            return;
        }

        if (!empty($settings['enable_login'])) {
            return;
        }

        // Remove login form from checkout.
        remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
        remove_action('woocommerce_checkout_before_customer_details', 'woocommerce_checkout_login_form', 10);
    }

    public function add_vat_invoice_fields(array $fields): array
    {
        $settings = $this->get_settings();

        if (empty($settings['enable_custom_checkout'])) {
            return $fields;
        }

        $company_field_id = $settings['company_field_id'] ?: 'billing_company';
        $nip_field_id     = $settings['nip_field_id'] ?: 'billing_nip';

        if (!isset($fields['billing'])) {
            $fields['billing'] = [];
        }
        if (!isset($fields['billing'])) {
            $fields['billing'] = [];
        }

        if (isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['priority'] = 10;
            $fields['billing']['billing_email']['label'] = __('Email address', 'weblo-custom-checkout');
            $fields['billing']['billing_email']['placeholder'] = __('Email address', 'weblo-custom-checkout');
        }
        if (isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['priority'] = 20;
            $fields['billing']['billing_phone']['label'] = __('Phone number', 'weblo-custom-checkout');
            $fields['billing']['billing_phone']['placeholder'] = __('Phone number', 'weblo-custom-checkout');
        }
        if (isset($fields['billing']['billing_first_name'])) {
            $fields['billing']['billing_first_name']['priority'] = 30;
            $fields['billing']['billing_first_name']['label'] = __('First name', 'weblo-custom-checkout');
            $fields['billing']['billing_first_name']['placeholder'] = __('First name', 'weblo-custom-checkout');
        }
        if (isset($fields['billing']['billing_last_name'])) {
            $fields['billing']['billing_last_name']['priority'] = 31;
            $fields['billing']['billing_last_name']['label'] = __('Last name', 'weblo-custom-checkout');
            $fields['billing']['billing_last_name']['placeholder'] = __('Last name', 'weblo-custom-checkout');
        }
        if (isset($fields['billing']['billing_address_1'])) {
            $fields['billing']['billing_address_1']['priority'] = 40;
            $fields['billing']['billing_address_1']['label'] = __('Street and number', 'weblo-custom-checkout');
            $fields['billing']['billing_address_1']['placeholder'] = __('Street and number', 'weblo-custom-checkout');
        }
        if (isset($fields['billing']['billing_address_2'])) {
            $fields['billing']['billing_address_2']['priority'] = 41;
            $fields['billing']['billing_address_2']['label'] = __('Apartment, suite, unit (optional)', 'weblo-custom-checkout');
            $fields['billing']['billing_address_2']['placeholder'] = __('Apartment, suite, unit (optional)', 'weblo-custom-checkout');
        }
        if (isset($fields['billing']['billing_postcode'])) {
            $fields['billing']['billing_postcode']['priority'] = 60;
            $fields['billing']['billing_postcode']['label'] = __('Postcode', 'weblo-custom-checkout');
            $fields['billing']['billing_postcode']['placeholder'] = __('Postcode', 'weblo-custom-checkout');
        }
        if (isset($fields['billing']['billing_city'])) {
            $fields['billing']['billing_city']['priority'] = 70;
            $fields['billing']['billing_city']['label'] = __('City', 'weblo-custom-checkout');
            $fields['billing']['billing_city']['placeholder'] = __('City', 'weblo-custom-checkout');
        }
        if (isset($fields['billing']['billing_country'])) {
            $fields['billing']['billing_country']['priority'] = 80;
            $fields['billing']['billing_country']['label'] = __('Country / Region', 'weblo-custom-checkout');
        }
        if (isset($fields['billing']['billing_state'])) {
            $fields['billing']['billing_state']['priority'] = 71;
            $fields['billing']['billing_state']['label'] = __('State / County', 'weblo-custom-checkout');
        }

        if (!isset($fields['billing'][$company_field_id])) {
            $fields['billing'][$company_field_id] = [
                'label'       => __('Company name', 'weblo-custom-checkout'),
                'placeholder' => __('Company name', 'weblo-custom-checkout'),
                'required'    => false,
                'class'       => ['form-row-wide', 'weblo-vat-invoice-field', 'company_field'],
                'priority'    => 999,
                'type'        => 'text',
            ];
        } else {
            if (!is_array($fields['billing'][$company_field_id]['class'])) {
                $fields['billing'][$company_field_id]['class'] = [$fields['billing'][$company_field_id]['class']];
            }
            if (!in_array('weblo-vat-invoice-field', $fields['billing'][$company_field_id]['class'], true)) {
                $fields['billing'][$company_field_id]['class'][] = 'weblo-vat-invoice-field';
            }
            if (!in_array('company_field', $fields['billing'][$company_field_id]['class'], true)) {
                $fields['billing'][$company_field_id]['class'][] = 'company_field';
            }
            $fields['billing'][$company_field_id]['label'] = __('Company name', 'weblo-custom-checkout');
            $fields['billing'][$company_field_id]['placeholder'] = __('Company name', 'weblo-custom-checkout');
            $fields['billing'][$company_field_id]['required'] = false;
            $fields['billing'][$company_field_id]['priority'] = 999;
        }

        if (!isset($fields['billing'][$nip_field_id])) {
            $fields['billing'][$nip_field_id] = [
                'label'       => __('Tax Identification Number', 'weblo-custom-checkout'),
                'placeholder' => __('Tax Identification Number', 'weblo-custom-checkout'),
                'required'    => false,
                'class'       => ['form-row-wide', 'weblo-vat-invoice-field', 'nip_field'],
                'priority'    => 1000,
                'type'        => 'text',
                'custom_attributes' => [
                    'maxlength' => '10',
                    'pattern'  => '[0-9]{10}',
                ],
            ];
        } else {
            if (!is_array($fields['billing'][$nip_field_id]['class'])) {
                $fields['billing'][$nip_field_id]['class'] = [$fields['billing'][$nip_field_id]['class']];
            }
            if (!in_array('weblo-vat-invoice-field', $fields['billing'][$nip_field_id]['class'], true)) {
                $fields['billing'][$nip_field_id]['class'][] = 'weblo-vat-invoice-field';
            }
            if (!in_array('nip_field', $fields['billing'][$nip_field_id]['class'], true)) {
                $fields['billing'][$nip_field_id]['class'][] = 'nip_field';
            }
            $fields['billing'][$nip_field_id]['label'] = __('Tax Identification Number', 'weblo-custom-checkout');
            $fields['billing'][$nip_field_id]['placeholder'] = __('Tax Identification Number', 'weblo-custom-checkout');
            $fields['billing'][$nip_field_id]['required'] = false;
            $fields['billing'][$nip_field_id]['priority'] = 1000;
            if (!isset($fields['billing'][$nip_field_id]['custom_attributes'])) {
                $fields['billing'][$nip_field_id]['custom_attributes'] = [];
            }
            $fields['billing'][$nip_field_id]['custom_attributes']['maxlength'] = '10';
            $fields['billing'][$nip_field_id]['custom_attributes']['pattern']   = '[0-9]{10}';
        }

        if (isset($fields['shipping']) && is_array($fields['shipping'])) {
            if (isset($fields['shipping']['shipping_first_name'])) {
                $fields['shipping']['shipping_first_name']['label'] = __('First name', 'weblo-custom-checkout');
                $fields['shipping']['shipping_first_name']['placeholder'] = __('First name', 'weblo-custom-checkout');
            }
            if (isset($fields['shipping']['shipping_last_name'])) {
                $fields['shipping']['shipping_last_name']['label'] = __('Last name', 'weblo-custom-checkout');
                $fields['shipping']['shipping_last_name']['placeholder'] = __('Last name', 'weblo-custom-checkout');
            }
            if (isset($fields['shipping']['shipping_company'])) {
                $fields['shipping']['shipping_company']['label'] = __('Company name', 'weblo-custom-checkout');
                $fields['shipping']['shipping_company']['placeholder'] = __('Company name', 'weblo-custom-checkout');
            }
            if (isset($fields['shipping']['shipping_address_1'])) {
                $fields['shipping']['shipping_address_1']['label'] = __('Street and number', 'weblo-custom-checkout');
                $fields['shipping']['shipping_address_1']['placeholder'] = __('Street and number', 'weblo-custom-checkout');
            }
            if (isset($fields['shipping']['shipping_address_2'])) {
                $fields['shipping']['shipping_address_2']['label'] = __('Apartment, suite, unit (optional)', 'weblo-custom-checkout');
                $fields['shipping']['shipping_address_2']['placeholder'] = __('Apartment, suite, unit (optional)', 'weblo-custom-checkout');
            }
            if (isset($fields['shipping']['shipping_postcode'])) {
                $fields['shipping']['shipping_postcode']['label'] = __('Postcode', 'weblo-custom-checkout');
                $fields['shipping']['shipping_postcode']['placeholder'] = __('Postcode', 'weblo-custom-checkout');
            }
            if (isset($fields['shipping']['shipping_city'])) {
                $fields['shipping']['shipping_city']['label'] = __('City', 'weblo-custom-checkout');
                $fields['shipping']['shipping_city']['placeholder'] = __('City', 'weblo-custom-checkout');
            }
            if (isset($fields['shipping']['shipping_state'])) {
                $fields['shipping']['shipping_state']['label'] = __('State / County', 'weblo-custom-checkout');
            }
            if (isset($fields['shipping']['shipping_country'])) {
                $fields['shipping']['shipping_country']['label'] = __('Country / Region', 'weblo-custom-checkout');
            }
            if (isset($fields['shipping']['shipping_phone'])) {
                $fields['shipping']['shipping_phone']['label'] = __('Phone number', 'weblo-custom-checkout');
                $fields['shipping']['shipping_phone']['placeholder'] = __('Phone number', 'weblo-custom-checkout');
            }
        }

        if (isset($fields['order']['order_comments'])) {
            $fields['order']['order_comments']['label'] = __('Order notes', 'weblo-custom-checkout');
            $fields['order']['order_comments']['placeholder'] = __('Order notes', 'weblo-custom-checkout');
        }

        if (isset($fields['account']) && is_array($fields['account'])) {
            if (isset($fields['account']['account_username'])) {
                $fields['account']['account_username']['label'] = __('Account username', 'weblo-custom-checkout');
                $fields['account']['account_username']['placeholder'] = __('Account username', 'weblo-custom-checkout');
            }
            if (isset($fields['account']['account_password'])) {
                $fields['account']['account_password']['label'] = __('Account password', 'weblo-custom-checkout');
                $fields['account']['account_password']['placeholder'] = __('Account password', 'weblo-custom-checkout');
            }
            if (isset($fields['account']['account_password-2'])) {
                $fields['account']['account_password-2']['label'] = __('Confirm password', 'weblo-custom-checkout');
                $fields['account']['account_password-2']['placeholder'] = __('Confirm password', 'weblo-custom-checkout');
            }
        }

        return $fields;
    }

    public function validate_nip_field(): void
    {
        $settings = $this->get_settings();
        if (empty($settings['enable_custom_checkout'])) {
            return;
        }

        if (empty($_POST['weblo_vat_invoice'])) {
            return;
        }

        $nip_field_id = $settings['nip_field_id'] ?: 'billing_nip';
        $company_field_id = $settings['company_field_id'] ?: 'billing_company';

        // Validate Company name
        if (empty($_POST[$company_field_id])) {
            wc_add_notice(__('Company name is required when requesting a VAT invoice.', 'weblo-custom-checkout'), 'error');
        }

        // Validate NIP
        $nip = isset($_POST[$nip_field_id]) ? sanitize_text_field($_POST[$nip_field_id]) : '';
        if (empty($nip)) {
            wc_add_notice(__('Tax Identification Number (NIP) is required when requesting a VAT invoice.', 'weblo-custom-checkout'), 'error');
        } elseif (!preg_match('/^[0-9]{10}$/', $nip)) {
            wc_add_notice(__('Tax Identification Number (NIP) must be exactly 10 digits.', 'weblo-custom-checkout'), 'error');
        }
    }

    public function validate_consents(): void
    {
        $settings = $this->get_settings();
        if (empty($settings['enable_custom_checkout'])) {
            return;
        }

        $consents = isset($settings['consents']) && is_array($settings['consents']) ? $settings['consents'] : [];

        foreach ($consents as $consent) {
            if (empty($consent['enabled']) || empty($consent['required'])) {
                continue;
            }

            $consent_id = !empty($consent['id']) ? sanitize_key($consent['id']) : 'weblo_consent_' . md5($consent['text'] ?? '');
            $field_name = 'weblo_consent_' . $consent_id;

            if (empty($_POST[$field_name])) {
                $error_message = !empty($consent['text'])
                    ? sprintf(__('You must accept: %s', 'weblo-custom-checkout'), wp_strip_all_tags($consent['text']))
                    : __('You must accept all required consents.', 'weblo-custom-checkout');
                wc_add_notice($error_message, 'error');
            }
        }
    }

    public function enqueue_admin_scripts(string $hook): void
    {
        if ($hook !== 'toplevel_page_weblo-cc-settings') {
            return;
        }

        wp_enqueue_script('jquery-ui-sortable');
    }

    public function debug(string $message): void
    {
        $settings = $this->get_settings();
        if (empty($settings['enable_debug_log'])) {
            return;
        }

        $this->logger->log($message);
    }

    private function get_settings(): array
    {
        $saved = get_option(self::OPTION_KEY, []);
        return wp_parse_args((array) $saved, $this->default_settings());
    }

    private function default_settings(): array
    {
        return [
            'enable_custom_checkout' => false,
            'enable_coupons'         => true,
            'enable_login'           => true,
            'developer_mode'         => false,
            'enable_debug_log'       => false,
            'asset_version'          => self::VERSION,
            'company_field_id'       => 'billing_company',
            'nip_field_id'           => 'billing_nip',
            'consents'               => [],
        ];
    }

    public function render_general_description(): void
    {
        echo '<p>' . esc_html__('Control global checkout settings.', 'weblo-custom-checkout') . '</p>';
    }

    private function get_log_file_path(): string
    {
        return WEBLO_CC_PATH . 'logs/debug.log';
    }

    private function get_plugin_template_path(string $template_name): string
    {
        return WEBLO_CC_PATH . 'woocommerce/' . $template_name;
    }
}
