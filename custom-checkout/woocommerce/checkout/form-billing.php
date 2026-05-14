<?php

/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-billing.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined('ABSPATH') || exit;
?>
<div class="woocommerce-billing-fields">


    <?php do_action('woocommerce_before_checkout_billing_form', $checkout); ?>

    <div class="woocommerce-billing-fields__field-wrapper woocommerce-billing-fields__field-wrapper_custom">
        <?php
        $fields   = $checkout->get_checkout_fields('billing');
        $settings = get_option('weblo_cc_settings', []);
        $company_field_id      = !empty($settings['company_field_id']) ? $settings['company_field_id'] : 'billing_company';
        $nip_field_id          = !empty($settings['nip_field_id']) ? $settings['nip_field_id'] : 'billing_nip';
        $vat_checkbox_rendered = false;

        foreach ($fields as $key => $field) {
            if (!$vat_checkbox_rendered && $key === $company_field_id) {
        ?>
        <p class="form-row form-row-wide weblo-vat-invoice-checkbox-wrapper">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                <input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
                    id="weblo_vat_invoice" type="checkbox" name="weblo_vat_invoice" value="1" />
                <span><?php esc_html_e('I want a vat invoice', 'weblo-custom-checkout'); ?></span>
            </label>
        </p>
        <?php
                $vat_checkbox_rendered = true;
            }

            woocommerce_form_field($key, $field, $checkout->get_value($key));
        }
        ?>
        <h4 class="weblo-billing-contact-wrapper-title weblo-billing-contact-wrapper-title_1">
            <?= __('Provide your details', 'weblo-custom-checkout') ?>
        </h4>
        <div class="weblo-billing-contact-wrapper">
            <h4 class="weblo-billing-contact-wrapper-title">
                <?= __('Billing address', 'weblo-custom-checkout') ?>
            </h4>
            <p class="weblo-billing-contact-wrapper-description">
                <?= __('Enter the billing address of the agreement with your payment method.', 'weblo-custom-checkout') ?>
            </p>
        </div>

    </div>

    <?php do_action('woocommerce_after_checkout_billing_form', $checkout); ?>
</div>

<?php if (! is_user_logged_in() && $checkout->is_registration_enabled()) : ?>
<div class="woocommerce-account-fields">
    <?php if (! $checkout->is_registration_required()) : ?>

    <p class="form-row form-row-wide create-account">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
            <input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount"
                <?php checked((true === $checkout->get_value('createaccount') || (true === apply_filters('woocommerce_create_account_default_checked', false))), true); ?>
                type="checkbox" name="createaccount" value="1" />
            <span><?php esc_html_e('Create an account?', 'woocommerce'); ?></span>
        </label>
    </p>

    <?php endif; ?>

    <?php do_action('woocommerce_before_checkout_registration_form', $checkout); ?>

    <?php if ($checkout->get_checkout_fields('account')) : ?>

    <div class="create-account">
        <?php foreach ($checkout->get_checkout_fields('account') as $key => $field) : ?>
        <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
        <?php endforeach; ?>
        <div class="clear"></div>
    </div>

    <?php endif; ?>

    <?php do_action('woocommerce_after_checkout_registration_form', $checkout); ?>
</div>
<?php endif; ?>