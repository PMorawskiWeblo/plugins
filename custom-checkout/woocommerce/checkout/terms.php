<?php

/**
 * Checkout terms and conditions area.
 *
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined('ABSPATH') || exit;

// Check if custom checkout is enabled and we have custom consents
$settings = get_option('weblo_cc_settings', []);
$use_custom_consents = !empty($settings['enable_custom_checkout']) &&
    !empty($settings['consents']) &&
    is_array($settings['consents']) &&
    count(array_filter($settings['consents'], function ($c) {
        return !empty($c['enabled']);
    })) > 0;

if ($use_custom_consents) {
    // Use custom consents
    do_action('woocommerce_checkout_before_terms_and_conditions');
?>
<div class="woocommerce-terms-and-conditions-wrapper weblo-custom-consents-wrapper">
    <?php
        $consents = array_filter($settings['consents'], function ($c) {
            return !empty($c['enabled']);
        });
        usort($consents, function ($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });

        foreach ($consents as $consent) {
            $consent_id = !empty($consent['id']) ? sanitize_key($consent['id']) : 'weblo_consent_' . md5($consent['text'] ?? '');
            $field_name = 'weblo_consent_' . $consent_id;
            $is_required = !empty($consent['required']);
            $is_checked = isset($_POST[$field_name]) || apply_filters('woocommerce_terms_is_checked_default', false);
        ?>
    <p class="form-row form_row_checkbox <?php echo $is_required ? 'validate-required' : ''; ?>">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
            <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
                name="<?php echo esc_attr($field_name); ?>" id="<?php echo esc_attr($consent_id); ?>" value="1"
                <?php checked($is_checked); ?> />
            <span class="woocommerce-terms-and-conditions-checkbox-text">
                <?php echo wp_kses_post($consent['text'] ?? ''); ?>
            </span>
            <?php if ($is_required) : ?>
            &nbsp;<abbr class="required" title="<?php esc_attr_e('required', 'woocommerce'); ?>">*</abbr>
            <?php endif; ?>
        </label>
        <?php if ($is_required) : ?>
        <input type="hidden" name="<?php echo esc_attr($field_name . '_field'); ?>" value="1" />
        <?php endif; ?>
    </p>
    <?php
        }
        ?>
</div>
<?php
    do_action('woocommerce_checkout_after_terms_and_conditions');
} elseif (apply_filters('woocommerce_checkout_show_terms', true) && function_exists('wc_terms_and_conditions_checkbox_enabled')) {
    // Use default WooCommerce terms
    do_action('woocommerce_checkout_before_terms_and_conditions');
?>
<div class="woocommerce-terms-and-conditions-wrapper">
    <?php
        /**
         * Terms and conditions hook used to inject content.
         *
         * @since 3.4.0.
         * @hooked wc_checkout_privacy_policy_text() Shows custom privacy policy text. Priority 20.
         * @hooked wc_terms_and_conditions_page_content() Shows t&c page content. Priority 30.
         */
        do_action('woocommerce_checkout_terms_and_conditions');
        ?>

    <?php if (wc_terms_and_conditions_checkbox_enabled()) : ?>
    <p class="form-row validate-required">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
            <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
                name="terms" <?php checked(apply_filters('woocommerce_terms_is_checked_default', isset($_POST['terms'])), true); // WPCS: input var ok, csrf ok. 
                                        ?> id="terms" />
            <span
                class="woocommerce-terms-and-conditions-checkbox-text"><?php wc_terms_and_conditions_checkbox_text(); ?></span>&nbsp;<abbr
                class="required" title="<?php esc_attr_e('required', 'woocommerce'); ?>">*</abbr>
        </label>
        <input type="hidden" name="terms-field" value="1" />
    </p>
    <?php endif; ?>
</div>
<?php
    do_action('woocommerce_checkout_after_terms_and_conditions');
}