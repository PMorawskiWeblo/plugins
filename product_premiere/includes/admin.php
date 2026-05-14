<?php
if (! class_exists('PPWC_Admin')) {
    class PPWC_Admin
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
            add_action('woocommerce_product_options_general_product_data', array($this, 'add_premiere_date_field'));
            add_action('woocommerce_process_product_meta', array($this, 'save_premiere_date_field'));
        }

        public function add_premiere_date_field()
        {
            woocommerce_wp_text_input(array(
                'id' => '_ppwc_premiere_date',
                'label' => __('Premiere Date', 'product-premiere'),
                'type' => 'datetime-local',
                'custom_attributes' => array(
                    'step' => '1'
                )
            ));
            woocommerce_wp_textarea_input(array(
                'id' => '_ppwc_premiere_description',
                'label' => __('Premiere Description', 'product-premiere'),
                'rows' => 4,
                'cols' => 50,
            ));
            woocommerce_wp_checkbox(array(
                'id' => '_ppwc_premiere_enabled',
                'label' => __('Premiere Enabled', 'product-premiere'),
                'description' => __('Enable premiere for this product.', 'product-premiere'),
            ));
            woocommerce_wp_text_input(array(
                'id' => '_ppwc_mailerlite_group_id',
                'label' => __('Additional MailerLite Group ID', 'product-premiere'),
                'description' => __('Subscribers will be added to this group in addition to the default group from plugin settings.', 'product-premiere'),
                'desc_tip' => true,
            ));
        }

        public function save_premiere_date_field($post_id)
        {
            $premiere_date = isset($_POST['_ppwc_premiere_date']) ? sanitize_text_field($_POST['_ppwc_premiere_date']) : '';
            $premiere_description = isset($_POST['_ppwc_premiere_description']) ? sanitize_textarea_field($_POST['_ppwc_premiere_description']) : '';
            $premiere_enabled = isset($_POST['_ppwc_premiere_enabled']) ? sanitize_text_field($_POST['_ppwc_premiere_enabled']) : '';
            $mailerlite_group_id = isset($_POST['_ppwc_mailerlite_group_id']) ? sanitize_text_field($_POST['_ppwc_mailerlite_group_id']) : '';

            if (!empty($premiere_date)) {
                // Konwertuj format datetime-local na format MySQL
                $date = new DateTime($premiere_date);
                $premiere_date = $date->format('Y-m-d H:i:s');
            }
            if (!empty($premiere_description)) {
                update_post_meta($post_id, '_ppwc_premiere_description', $premiere_description);
            }
            if (!empty($premiere_enabled)) {
                update_post_meta($post_id, '_ppwc_premiere_enabled', $premiere_enabled);
            }
            update_post_meta($post_id, '_ppwc_premiere_date', $premiere_date);
            update_post_meta($post_id, '_ppwc_premiere_description', $premiere_description);
            update_post_meta($post_id, '_ppwc_premiere_enabled', $premiere_enabled);
            update_post_meta($post_id, '_ppwc_mailerlite_group_id', $mailerlite_group_id);
        }
    }
}
