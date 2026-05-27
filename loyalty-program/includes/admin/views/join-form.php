<?php

/**
 * Admin Join Form View
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$logged_consent_text = get_option('loyalty_program_join_form_logged_consent_text', 'Wyrażam zgodę na dołączenie do programu lojalnościowego MyBestLife Club.');
$form_header = get_option('loyalty_program_join_form_header', 'Dołącz do programu lojalnościowego');
$form_description = get_option('loyalty_program_join_form_description', 'Zostań członkiem MyBestLife Club i zacznij zbierać punkty lojalnościowe!');
$form_points_info = get_option('loyalty_program_join_form_points_info', 'Za dołączenie do programu otrzymasz 100 punktów lojalnościowych.');

// SMS consent
$sms_consent_enabled = get_option('loyalty_program_join_form_sms_consent_enabled', 'yes');
$sms_consent_text = get_option('loyalty_program_join_form_sms_consent_text', 'Wyrażam zgodę na otrzymywanie wiadomości SMS.');
$sms_consent_required = get_option('loyalty_program_join_form_sms_consent_required', 'no');

// Newsletter consent
$newsletter_consent_enabled = get_option('loyalty_program_join_form_newsletter_consent_enabled', 'yes');
$newsletter_consent_text = get_option('loyalty_program_join_form_newsletter_consent_text', 'Wyrażam zgodę na otrzymywanie newslettera.');
$newsletter_consent_required = get_option('loyalty_program_join_form_newsletter_consent_required', 'no');

// Terms consent (Regulamin)
$terms_consent_enabled = get_option('loyalty_program_join_form_terms_consent_enabled', 'yes');
$terms_consent_text = get_option('loyalty_program_join_form_terms_consent_text', 'Akceptuję <a href="#">regulamin</a>.');
$terms_consent_required = get_option('loyalty_program_join_form_terms_consent_required', 'yes');

// Auto newsletter for logged in users
$logged_auto_newsletter = get_option('loyalty_program_join_form_logged_auto_newsletter', 'no');

// Custom consents
$custom_consents = get_option('loyalty_program_join_form_custom_consents', array());
if (!is_array($custom_consents)) {
    $custom_consents = array();
}

settings_errors('loyalty_program_join_form');
?>

<div class="wrap loyalty-program-join-form">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="" id="join-form-settings-form">
        <?php wp_nonce_field('loyalty_program_join_form', 'loyalty_program_join_form_nonce'); ?>

        <h2 class="title"><?php _e('For Logged In Users', 'loyalty-program'); ?></h2>
        <p class="description"><?php _e('Settings for users who are logged in but haven\'t joined the program yet.', 'loyalty-program'); ?></p>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="join_form_logged_consent_text">
                            <?php _e('Consent Text', 'loyalty-program'); ?> <span class="required">*</span>
                        </label>
                    </th>
                    <td>
                        <textarea name="join_form_logged_consent_text" id="join_form_logged_consent_text" rows="3" class="large-text" required><?php echo esc_textarea($logged_consent_text); ?></textarea>
                        <p class="description">
                            <?php _e('This consent is required. Users must check this box to join the program. HTML is allowed.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Auto Newsletter', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="join_form_logged_auto_newsletter" value="yes" 
                                <?php checked($logged_auto_newsletter, 'yes'); ?>>
                            <?php _e('Automatically check newsletter consent when user joins', 'loyalty-program'); ?>
                        </label>
                        <p class="description">
                            <?php _e('If enabled, when a logged-in user joins the program, their newsletter consent will be automatically set to "yes".', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2 class="title" style="margin-top: 30px; border-top: 1px solid #dcdcde; padding-top: 20px;">
            <?php _e('For Not Logged In Users', 'loyalty-program'); ?>
        </h2>
        <p class="description"><?php _e('Settings for the registration form shown to users who are not logged in.', 'loyalty-program'); ?></p>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="join_form_header">
                            <?php _e('Form Header', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="join_form_header" id="join_form_header" 
                            value="<?php echo esc_attr($form_header); ?>" class="regular-text">
                        <p class="description">
                            <?php _e('Header text displayed at the top of the registration form.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="join_form_description">
                            <?php _e('Form Description', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea name="join_form_description" id="join_form_description" rows="3" class="large-text"><?php echo esc_textarea($form_description); ?></textarea>
                        <p class="description">
                            <?php _e('Description text displayed below the header.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="join_form_points_info">
                            <?php _e('Points Information', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea name="join_form_points_info" id="join_form_points_info" rows="2" class="large-text"><?php echo esc_textarea($form_points_info); ?></textarea>
                        <p class="description">
                            <?php _e('Information about points awarded for joining the program.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h3 class="title" style="margin-top: 30px;">
            <?php _e('Consents', 'loyalty-program'); ?>
        </h3>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <?php _e('Newsletter Consent', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="join_form_newsletter_consent_enabled" value="yes" 
                                <?php checked($newsletter_consent_enabled, 'yes'); ?>>
                            <?php _e('Enable Newsletter consent', 'loyalty-program'); ?>
                        </label>
                        <div style="margin-top: 10px;">
                            <textarea name="join_form_newsletter_consent_text" rows="2" 
                                class="large-text" placeholder="<?php esc_attr_e('Newsletter consent text (HTML allowed)', 'loyalty-program'); ?>"><?php echo esc_textarea($newsletter_consent_text); ?></textarea>
                        </div>
                        <div style="margin-top: 10px;">
                            <label>
                                <input type="checkbox" name="join_form_newsletter_consent_required" value="yes" 
                                    <?php checked($newsletter_consent_required, 'yes'); ?>>
                                <?php _e('Required', 'loyalty-program'); ?>
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('SMS Consent', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="join_form_sms_consent_enabled" value="yes" 
                                <?php checked($sms_consent_enabled, 'yes'); ?>>
                            <?php _e('Enable SMS consent', 'loyalty-program'); ?>
                        </label>
                        <div style="margin-top: 10px;">
                            <textarea name="join_form_sms_consent_text" rows="2" 
                                class="large-text" placeholder="<?php esc_attr_e('SMS consent text (HTML allowed)', 'loyalty-program'); ?>"><?php echo esc_textarea($sms_consent_text); ?></textarea>
                        </div>
                        <div style="margin-top: 10px;">
                            <label>
                                <input type="checkbox" name="join_form_sms_consent_required" value="yes" 
                                    <?php checked($sms_consent_required, 'yes'); ?>>
                                <?php _e('Required', 'loyalty-program'); ?>
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Terms Consent (Regulamin)', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="join_form_terms_consent_enabled" value="yes" 
                                <?php checked($terms_consent_enabled, 'yes'); ?>>
                            <?php _e('Enable Terms consent', 'loyalty-program'); ?>
                        </label>
                        <div style="margin-top: 10px;">
                            <textarea name="join_form_terms_consent_text" rows="2" 
                                class="large-text" placeholder="<?php esc_attr_e('Terms consent text (HTML allowed)', 'loyalty-program'); ?>"><?php echo esc_textarea($terms_consent_text); ?></textarea>
                        </div>
                        <div style="margin-top: 10px;">
                            <label>
                                <input type="checkbox" name="join_form_terms_consent_required" value="yes" 
                                    <?php checked($terms_consent_required, 'yes'); ?>>
                                <?php _e('Required', 'loyalty-program'); ?>
                            </label>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <h3 class="title" style="margin-top: 30px;">
            <?php _e('Custom Consents', 'loyalty-program'); ?>
        </h3>
        <p class="description"><?php _e('Add additional custom consents to the registration form.', 'loyalty-program'); ?></p>

        <div id="custom-consents-container">
            <?php if (!empty($custom_consents)) : ?>
                <?php foreach ($custom_consents as $index => $consent) : ?>
                    <div class="custom-consent-row" style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                        <div style="display: flex; gap: 10px; align-items: flex-start;">
                            <div style="flex: 1;">
                                <textarea name="custom_consents[<?php echo esc_attr($index); ?>][text]" rows="2" 
                                    class="large-text" placeholder="<?php esc_attr_e('Consent text (HTML allowed)', 'loyalty-program'); ?>" required><?php echo esc_textarea($consent['text']); ?></textarea>
                            </div>
                            <div>
                                <label>
                                    <input type="checkbox" name="custom_consents[<?php echo esc_attr($index); ?>][required]" value="yes" 
                                        <?php checked($consent['required'], 'yes'); ?>>
                                    <?php _e('Required', 'loyalty-program'); ?>
                                </label>
                            </div>
                            <div>
                                <button type="button" class="button remove-consent-btn"><?php _e('Remove', 'loyalty-program'); ?></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button type="button" id="add-custom-consent-btn" class="button" style="margin-top: 10px;">
            <?php _e('+ Add Custom Consent', 'loyalty-program'); ?>
        </button>

        <p class="submit">
            <input type="submit" name="loyalty_program_join_form_save" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'loyalty-program'); ?>">
        </p>
    </form>
</div>

<script>
(function($) {
    'use strict';
    
    $(document).ready(function() {
        var consentIndex = <?php echo absint(count($custom_consents)); ?>;
        
        $('#add-custom-consent-btn').on('click', function(e) {
            e.preventDefault();
            var html = '<div class="custom-consent-row" style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">' +
                '<div style="display: flex; gap: 10px; align-items: flex-start;">' +
                '<div style="flex: 1;">' +
                '<textarea name="custom_consents[' + consentIndex + '][text]" rows="2" ' +
                'class="large-text" placeholder="<?php echo esc_js(__('Consent text (HTML allowed)', 'loyalty-program')); ?>" required></textarea>' +
                '</div>' +
                '<div>' +
                '<label>' +
                '<input type="checkbox" name="custom_consents[' + consentIndex + '][required]" value="yes">' +
                '<?php echo esc_js(__('Required', 'loyalty-program')); ?>' +
                '</label>' +
                '</div>' +
                '<div>' +
                '<button type="button" class="button remove-consent-btn"><?php echo esc_js(__('Remove', 'loyalty-program')); ?></button>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            $('#custom-consents-container').append(html);
            consentIndex++;
        });
        
        $(document).on('click', '.remove-consent-btn', function(e) {
            e.preventDefault();
            $(this).closest('.custom-consent-row').remove();
        });
    });
})(jQuery);
</script>

<style>
.required {
    color: #d63638;
}
</style>

