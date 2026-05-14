<?php
if (! class_exists('PPWC_Settings')) {
    class PPWC_Settings
    {
        private static $instance = null;

        public static function get_instance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct()
        {
            add_action('admin_menu', array($this, 'add_settings_page'));
            add_action('admin_init', array($this, 'register_settings'));
        }

        public function add_settings_page()
        {
            add_submenu_page(
                'woocommerce',
                'Product Premiere Settings',
                'Product Premiere',
                'manage_options',
                'ppwc-settings',
                array($this, 'render_settings_page')
            );
        }

        public function register_settings()
        {
            register_setting('ppwc_settings', 'ppwc_form_title');
            register_setting('ppwc_settings', 'ppwc_button_text');
            register_setting('ppwc_settings', 'ppwc_countdown_text');
            register_setting('ppwc_settings', 'ppwc_title_prefix');
            register_setting('ppwc_settings', 'ppwc_mailerlite_api_key');
            register_setting('ppwc_settings', 'ppwc_mailerlite_group_id');
            register_setting('ppwc_settings', 'ppwc_consents', array(
                'type' => 'array',
                'default' => array(
                    array(
                        'id' => 'privacy_policy',
                        'text' => 'Zapoznałam/em się z polityką prywatności oraz wyrażam zgodę na otrzymywanie drogą mailową informacji handlowych oraz marketingowych',
                        'required' => true
                    )
                ),
                'sanitize_callback' => array($this, 'sanitize_consents')
            ));
        }

        public function sanitize_consents($consents)
        {
            if (!is_array($consents)) {
                return array();
            }

            $sanitized = array();
            foreach ($consents as $consent) {
                if (!empty($consent['id']) && !empty($consent['text'])) {
                    $sanitized[] = array(
                        'id' => sanitize_text_field($consent['id']),
                        'text' => sanitize_textarea_field($consent['text']),
                        'required' => isset($consent['required']) ? (bool)$consent['required'] : false
                    );
                }
            }
            return $sanitized;
        }

        public function render_settings_page()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            $consents = get_option('ppwc_consents', array());
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
                    settings_fields('ppwc_settings');
                    do_settings_sections('ppwc_settings');
                    ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ppwc_title_prefix"><?php _e('Product Title Prefix', 'product-premiere'); ?></label>
                </th>
                <td>
                    <input type="text" id="ppwc_title_prefix" name="ppwc_title_prefix" class="regular-text"
                        value="<?php echo esc_attr(get_option('ppwc_title_prefix', 'WKRÓTCE!')); ?>">
                    <p class="description">
                        <?php _e('This text will be added before the product title if premiere date is set', 'product-premiere'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="ppwc_form_title"><?php _e('Form Title', 'product-premiere'); ?></label>
                </th>
                <td>
                    <input type="text" id="ppwc_form_title" name="ppwc_form_title" class="regular-text"
                        value="<?php echo esc_attr(get_option('ppwc_form_title', 'Otrzymaj powiadomienie jako pierwsza:')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="ppwc_countdown_text"><?php _e('Countdown Text', 'product-premiere'); ?></label>
                </th>
                <td>
                    <input type="text" id="ppwc_countdown_text" name="ppwc_countdown_text" class="regular-text"
                        value="<?php echo esc_attr(get_option('ppwc_countdown_text', 'PREMIERA ZA:')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="ppwc_button_text"><?php _e('Button Text', 'product-premiere'); ?></label>
                </th>
                <td>
                    <input type="text" id="ppwc_button_text" name="ppwc_button_text" class="regular-text"
                        value="<?php echo esc_attr(get_option('ppwc_button_text', 'POWIADOM MNIE O PREMIERZE')); ?>">
                </td>
            </tr>
        </table>




        <h2><?php _e('Consents', 'product-premiere'); ?></h2>
        <button type="button" class="button"
            id="add-consent"><?php _e('Add New Consent', 'product-premiere'); ?></button>

        <style>
        .ppwc-consents-table {
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 5px;
        }

        .ppwc-consents-table th,
        .ppwc-consents-table td {
            background-color: #fff;
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }
        </style>
        <table class="form-table ppwc-consents-table" style="background-color: #fff;">
            <thead>
                <tr>
                    <th><?php _e('Consent ID', 'product-premiere'); ?></th>
                    <th><?php _e('Consent Text', 'product-premiere'); ?></th>
                    <th><?php _e('Required', 'product-premiere'); ?></th>
                    <th><?php _e('Actions', 'product-premiere'); ?></th>
                </tr>
            </thead>
            <tbody id="ppwc-consents-container">
                <?php
                            if (!empty($consents)) :
                                foreach ($consents as $index => $consent) :
                                    $required = isset($consent['required']) ? $consent['required'] : false;
                            ?>
                <tr class="ppwc-consent-item">
                    <td>
                        <input type="text" name="ppwc_consents[<?php echo $index; ?>][id]"
                            value="<?php echo esc_attr($consent['id']); ?>"
                            placeholder="<?php esc_attr_e('Consent ID', 'product-premiere'); ?>" required>
                    </td>
                    <td>
                        <textarea name="ppwc_consents[<?php echo $index; ?>][text]" rows="3" cols="100"
                            required><?php echo esc_textarea($consent['text']); ?></textarea>
                    </td>
                    <td>
                        <input type="checkbox" name="ppwc_consents[<?php echo $index; ?>][required]" value="1"
                            <?php checked($required, true); ?>>
                    </td>
                    <td>
                        <button type="button"
                            class="button remove-consent"><?php _e('Remove', 'product-premiere'); ?></button>
                    </td>
                </tr>
                <?php
                                endforeach;
                            endif;
                            ?>
            </tbody>
        </table>


        <h2><?php _e('MailerLite Integration', 'product-premiere'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ppwc_mailerlite_api_key"><?php _e('MailerLite API Key', 'product-premiere'); ?></label>
                </th>
                <td>
                    <input type="text" id="ppwc_mailerlite_api_key" name="ppwc_mailerlite_api_key" class="regular-text"
                        value="<?php echo esc_attr(get_option('ppwc_mailerlite_api_key')); ?>">
                    <p class="description">
                        <?php _e('Enter your MailerLite API key from https://app.mailerlite.com/integrations/api/', 'product-premiere'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label
                        for="ppwc_mailerlite_group_id"><?php _e('MailerLite Group ID', 'product-premiere'); ?></label>
                </th>
                <td>
                    <input type="text" id="ppwc_mailerlite_group_id" name="ppwc_mailerlite_group_id"
                        class="regular-text" value="<?php echo esc_attr(get_option('ppwc_mailerlite_group_id')); ?>">
                    <p class="description">
                        <?php _e('Enter the ID of the group where subscribers should be added', 'product-premiere'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var consentTemplate = `
    <tr class="ppwc-consent-item">
        <td>
            <input type="text" name="ppwc_consents[{index}][id]" placeholder="Consent ID" required>
        </td>
        <td>
            <textarea name="ppwc_consents[{index}][text]" rows="3" cols="100" required></textarea>
        </td>
        <td>
            <input type="checkbox" name="ppwc_consents[{index}][required]" value="1">
        </td>
        <td>
            <button type="button" class="button remove-consent">Usuń</button>
        </td>
    </tr>
`;


    $('#add-consent').on('click', function() {
        var index = $('.ppwc-consent-item').length;
        var newConsent = consentTemplate.replace(/{index}/g, index);
        $('#ppwc-consents-container').append(newConsent);
    });

    $(document).on('click', '.remove-consent', function() {
        $(this).closest('.ppwc-consent-item').remove();
        // Aktualizacja indeksów
        $('.ppwc-consent-item').each(function(index) {
            $(this).find('input, textarea').each(function() {
                var name = $(this).attr('name');
                $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
            });
        });
    });
});
</script>
<?php
        }
    }
}