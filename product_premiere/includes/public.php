<?php
if (! class_exists('PPWC_Public')) {
    class PPWC_Public
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
            add_shortcode('ppwc_premiere_form', array($this, 'ppwc_render_premiere_form'));
            add_action('wp_enqueue_scripts', array($this, 'ppwc_enqueue_scripts'));
            add_action('wp_ajax_ppwc_save_premiere_signup', array($this, 'ppwc_save_premiere_signup'));
            add_action('wp_ajax_nopriv_ppwc_save_premiere_signup', array($this, 'ppwc_save_premiere_signup'));

            add_filter('the_title', array($this, 'ppwc_modify_product_title'), 10, 2);

            // Dodanie klasy do body
            add_filter('body_class', array($this, 'add_premiere_body_class'));

            // Ukryj przycisk "Dodaj do koszyka" i pokaż status "Niedostępny"
            add_filter('woocommerce_is_purchasable', array($this, 'ppwc_is_purchasable'), 10, 2);
            add_filter('woocommerce_get_availability', array($this, 'ppwc_get_availability'), 10, 2);
            add_filter('woocommerce_post_class', [$this, 'add_premiere_product_class'], 10, 2);
        }

        public function ppwc_modify_product_title($title, $post_id)
        {
            // Sprawdź, czy jesteśmy na stronie produktu i czy to główny tytuł produktu
            if (is_product() && get_the_ID() == $post_id) {
                $prefix_enabled = get_option('ppwc_prefix_enabled', 1); // 1 = włączone
                $prefix = get_option('ppwc_title_prefix', 'WKRÓTCE!');
                $premiere_enabled = get_post_meta($post_id, '_ppwc_premiere_enabled', true);

                if ($prefix_enabled && $premiere_enabled === 'yes' && !empty($prefix)) {
                    // Unikaj podwójnego dodania prefiksu
                    if (strpos($title, $prefix) !== 0) {
                        $title = $prefix . ' ' . $title;
                    }
                }
            }
            return $title;
        }

        private function is_premiere_product($product)
        {
            if (!$product) return false;
            $premiere_date = get_post_meta($product->get_id(), '_ppwc_premiere_date', true);
            if (empty($premiere_date)) return false;

            // Sprawdź czy data premiery jeszcze nie minęła
            $current_time = current_time('timestamp');
            $premiere_timestamp = strtotime($premiere_date);

            return $premiere_timestamp > $current_time;
        }

        private function has_premiere_passed($product)
        {
            if (!$product) return false;
            $premiere_date = get_post_meta($product->get_id(), '_ppwc_premiere_date', true);
            if (empty($premiere_date)) return false;

            $current_time = current_time('timestamp');
            $premiere_timestamp = strtotime($premiere_date);

            return $premiere_timestamp <= $current_time;
        }

        public function add_premiere_body_class($classes)
        {
            if (is_product()) {
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $premiere_date = get_post_meta($product->get_id(), '_ppwc_premiere_date', true);
                    if (!empty($premiere_date)) {
                        if (!$this->has_premiere_passed($product)) {
                            $classes[] = 'ppwc-countdown-active';
                        }
                    }
                }
            }
            return $classes;
        }

        public function add_premiere_product_class($classes, $product)
        {
            if ($product && !$this->has_premiere_passed($product) && $this->is_premiere_product($product)) {
                $classes[] = 'ppwc-product-countdown-active';
            }
            return $classes;
        }

        public function ppwc_enqueue_scripts()
        {
            $ajax_url = admin_url('admin-ajax.php');
            error_log('[PPWC] AJAX URL: ' . $ajax_url);

            wp_enqueue_style(
                'ppwc-premiere-form',
                PPWC_PLUGIN_URL . 'assets/css/premiere-form.css',
                array(),
                PPWC_PLUGIN_VERSION
            );

            wp_enqueue_script(
                'ppwc-premiere-form',
                PPWC_PLUGIN_URL . 'assets/js/premiere-form.js',
                array('jquery'),
                PPWC_PLUGIN_VERSION,
                true
            );

            wp_localize_script('ppwc-premiere-form', 'ppwcPremiereAjax', array(
                'ajaxUrl' => $ajax_url,
                'nonce' => wp_create_nonce('ppwc_premiere_nonce'),
                'successMsg' => __('Thank you! You will be notified about the premiere.', 'product-premiere'),
                'errorMsg' => __('Please fill in all required fields.', 'product-premiere'),
            ));
        }

        public function ppwc_render_premiere_form($atts)
        {

            global $product;

            if (!$product) {
                if (is_singular('product')) {
                    $product = wc_get_product(get_the_ID());
                }
                if (!$product) {
                    error_log('[PPWC] No product found for premiere form.');
                    return '<!-- PPWC: No product found -->';
                }
            }

            $consents = get_option('ppwc_consents', array());
            $premiere_date = get_post_meta($product->get_id(), '_ppwc_premiere_date', true);
            $countdown_text = get_option('ppwc_countdown_text', 'PREMIERA ZA:');

            if (empty($premiere_date) || $this->has_premiere_passed($product)) {
                return '';
            }



            ob_start();
?>
<div class="ppwc-premiere-form">
    <?php


                if ($premiere_date) : ?>
    <div class="ppwc-premiere-form-container">
        <?php
                        $premiere_description = get_post_meta($product->get_id(), '_ppwc_premiere_description', true);
                        if (!empty($premiere_description)) : ?>
        <div class="ppwc-premiere-description">
            <?php echo wp_kses_post($premiere_description); ?>
        </div>
        <?php endif; ?>
        <div class="ppwc-countdown-wrapper">
            <h3><?php echo esc_html($countdown_text); ?></h3>
            <div class="ppwc-countdown" data-premiere-date="<?php echo esc_attr($premiere_date); ?>">
                <div class="ppwc-countdown-item">
                    <span class="ppwc-days ppwc-countdown-number">00</span>
                    <span class="ppwc-label"><?php _e('days', 'product-premiere'); ?></span>
                </div>
                <div class="ppwc-countdown-item">
                    <span class="ppwc-hours ppwc-countdown-number">00</span>
                    <span class="ppwc-label"><?php _e('hours', 'product-premiere'); ?></span>
                </div>
                <div class="ppwc-countdown-item">
                    <span class="ppwc-minutes ppwc-countdown-number">00</span>
                    <span class="ppwc-label"><?php _e('minutes', 'product-premiere'); ?></span>
                </div>
                <div class="ppwc-countdown-item">
                    <span class="ppwc-seconds ppwc-countdown-number">00</span>
                    <span class="ppwc-label"><?php _e('seconds', 'product-premiere'); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="ppwc-premiere-form-container">
            <h3><?php echo esc_html(get_option('ppwc_form_title', __('Notify me about the premiere', 'product-premiere'))); ?>
            </h3>
            <form id="ppwc-premiere-signup-form" data-product="<?php echo esc_attr($product->get_id()); ?>">
                <div class="ppwc-premiere-form-row">
                    <div class="ppwc-premiere-form-group">
                        <input type="text" id="ppwc_name" name="ppwc_name"
                            placeholder="<?php _e('Your name', 'product-premiere'); ?>" required>
                    </div>
                    <div class="ppwc-premiere-form-group">
                        <input type="email" id="ppwc_email" name="ppwc_email"
                            placeholder="<?php _e('Your email', 'product-premiere'); ?>" required>
                    </div>
                </div>
                <div class="ppwc-premiere-form-consent">
                    <?php foreach ($consents as $index => $consent) : ?>

                    <div class="ppwc-premiere-form-group">
                        <label>
                            <input type="checkbox" name="ppwc_consent[<?php echo esc_attr($consent['id']); ?>]"
                                value="1"
                                <?php echo (isset($consent['required']) && $consent['required']) ? 'required' : ''; ?>>
                            <div class="ppwc-premiere-form-consent-text"><?php echo esc_html($consent['text']); ?></div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="ppwc-premiere-form-group">
                    <button type="submit"
                        class="ppwc-premiere-form-button btn btn_dark"><?php echo esc_html(get_option('ppwc_button_text', __('Notify me about the premiere', 'product-premiere'))); ?></button>
                </div>
                <div class="ppwc-premiere-message" style="display:none;"></div>
            </form>
        </div>
    </div>

</div>
<?php
            return ob_get_clean();
        }

        /**
         * Klasa do obsługi integracji z MailerLite
         */
        private function add_to_mailerlite($email, $name, $product_id)
        {
            error_log('Adding to MailerLite: ' . $email . ' ' . $name . ' ' . $product_id);
            $api_key = get_option('ppwc_mailerlite_api_key');
            if (empty($api_key)) {
                error_log('MailerLite API key not set');
                return false;
            }

            // Pobierz ID grupy z ustawień wtyczki
            $default_group_id = get_option('ppwc_mailerlite_group_id');

            // Pobierz dodatkowe ID grupy z ustawień produktu
            $product_group_id = get_post_meta($product_id, '_ppwc_mailerlite_group_id', true);

            $product = wc_get_product($product_id);
            $product_name = $product ? $product->get_name() : '';

            // Przygotuj dane dla API v5
            $subscriber_data = array(
                'email' => $email,
                'fields' => array(
                    'name' => $name,
                    'product_premiere' => $product_name,
                    'signup_date' => current_time('mysql')
                )
            );

            // Przygotuj tablicę grup
            $groups = array();
            if (!empty($default_group_id)) {
                $groups[] = $default_group_id;
            }
            if (!empty($product_group_id)) {
                $groups[] = $product_group_id;
            }

            // Dodaj grupy do danych subskrybenta
            if (!empty($groups)) {
                $subscriber_data['groups'] = $groups;
            }

            error_log('MailerLite subscriber data: ' . print_r($subscriber_data, true));

            // Używamy nowego endpointu API v5
            $response = wp_remote_post('https://connect.mailerlite.com/api/subscribers', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ),
                'body' => json_encode($subscriber_data),
                'timeout' => 15
            ));

            if (is_wp_error($response)) {
                error_log('MailerLite API Error: ' . $response->get_error_message());
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body);

            error_log('MailerLite API Response: ' . $body);

            // Sprawdź kod odpowiedzi (200 lub 201 oznaczają sukces)
            if ($response_code !== 200 && $response_code !== 201) {
                error_log('MailerLite API Error: ' . $body);
                return false;
            }

            return true;
        }

        public function ppwc_save_premiere_signup()
        {
            // Weryfikacja nonce
            if (!isset($_POST['nonce'])) {
                wp_send_json_error('Security token is missing');
                return;
            }

            if (!wp_verify_nonce($_POST['nonce'], 'ppwc_premiere_nonce')) {
                wp_send_json_error('Security check failed');
                return;
            }

            // Sprawdzenie obecności wymaganych pól
            if (!isset($_POST['product_id'], $_POST['name'], $_POST['email'])) {
                wp_send_json_error('Please fill in all required fields');
                return;
            }

            $product_id = intval($_POST['product_id']);
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $consents = isset($_POST['consent']) ? $_POST['consent'] : array();

            // Sprawdzenie poprawności danych
            if (!$product_id || empty($name) || !is_email($email)) {
                wp_send_json_error('Please provide valid data');
                return;
            }

            // Sprawdzenie wymaganych zgód
            $required_consents = array_filter(get_option('ppwc_consents', array()), function ($consent) {
                return !empty($consent['required']);
            });

            foreach ($required_consents as $consent) {
                if (empty($consents[$consent['id']])) {
                    wp_send_json_error('Please accept all required consents');
                    return;
                }
            }

            // Sprawdzenie czy produkt istnieje
            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error('Invalid product');
                return;
            }

            $signups = get_post_meta($product_id, '_ppwc_premiere_signups', true);
            if (!is_array($signups)) $signups = array();

            // Sprawdzenie czy email już istnieje
            foreach ($signups as $signup) {
                if ($signup['email'] === $email) {
                    wp_send_json_error('You are already signed up for this premiere notification');
                    return;
                }
            }

            $signups[] = array(
                'name' => $name,
                'email' => $email,
                'consents' => $consents,
                'date' => current_time('mysql'),
            );

            $updated = update_post_meta($product_id, '_ppwc_premiere_signups', $signups);
            error_log('--Signups updated--: ' . $updated);
            // Dodaj email do listy mailingowej jako prosty array
            $mail_list = get_post_meta($product_id, 'ppwc_mail_list', true);
            if (!is_array($mail_list)) $mail_list = array();

            if (!in_array($email, $mail_list)) {
                $mail_list[] = $email;
                update_post_meta($product_id, 'ppwc_mail_list', $mail_list);
                error_log('--Mail list updated--: ' . $mail_list);
            }

            // Dodaj subskrybenta do MailerLite
            $mailerlite_success = $this->add_to_mailerlite($email, $name, $product_id);
            error_log('--MailerLite success--: ' . $mailerlite_success);
            if ($updated) {
                $message = __('Thank you! You will be notified about the premiere.', 'product-premiere');
                if (!$mailerlite_success) {
                    error_log('Failed to add subscriber to MailerLite');
                }
                wp_send_json_success($message);
            } else {
                wp_send_json_error(__('Failed to save your data. Please try again.', 'product-premiere'));
            }
        }


        public function ppwc_is_purchasable($is_purchasable, $product)
        {
            if ($this->is_premiere_product($product)) {
                return false;
            }
            return $is_purchasable;
        }

        public function ppwc_get_availability($availability, $product)
        {
            if ($this->is_premiere_product($product)) {
                $premiere_date = get_post_meta($product->get_id(), '_ppwc_premiere_date', true);
                if (!empty($premiere_date)) {
                    $current_time = current_time('timestamp');
                    $premiere_timestamp = strtotime($premiere_date);

                    if ($premiere_timestamp > $current_time) {
                        $availability['availability'] = sprintf(
                            __('Produkt będzie dostępny od %s', 'product-premiere'),
                            date_i18n(get_option('date_format'), $premiere_timestamp)
                        );
                        $availability['class'] = 'out-of-stock premiere-pending';
                    }
                }
            }
            return $availability;
        }

        /**
         * Czyści metadane premiery dla produktu
         * 
         * @param int|WC_Product $product_id ID produktu lub obiekt produktu
         * @return bool True jeśli wyczyszczono metadane, false w przeciwnym razie
         */
        public function cleanup_premiere_data($product_id)
        {
            if ($product_id instanceof WC_Product) {
                $product_id = $product_id->get_id();
            }

            $product = wc_get_product($product_id);
            if (!$product) {
                return false;
            }

            // Sprawdź czy premiera minęła
            $premiere_date = get_post_meta($product_id, '_ppwc_premiere_date', true);
            if (empty($premiere_date)) {
                return false;
            }

            $current_time = current_time('timestamp');
            $premiere_timestamp = strtotime($premiere_date);

            if ($premiere_timestamp > $current_time) {
                return false; // Premiera jeszcze nie minęła
            }

            // Usuń wszystkie metadane związane z premierą
            $deleted = true;
            $deleted &= delete_post_meta($product_id, '_ppwc_premiere_signups');
            $deleted &= delete_post_meta($product_id, 'ppwc_mail_list');

            // Możemy też wyczyścić datę premiery i inne powiązane meta
            $deleted &= delete_post_meta($product_id, '_ppwc_premiere_date');
            $deleted &= delete_post_meta($product_id, '_ppwc_premiere_description');
            $deleted &= delete_post_meta($product_id, '_ppwc_premiere_enabled');

            return $deleted;
        }

        /**
         * Czyści metadane premiery dla wszystkich produktów z przeterminowaną premierą
         * 
         * @return array Lista ID produktów, dla których wyczyszczono metadane
         */
        public function cleanup_all_expired_premieres()
        {
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_ppwc_premiere_date',
                        'value' => '',
                        'compare' => '!='
                    )
                )
            );

            $products = get_posts($args);
            $cleaned_products = array();

            foreach ($products as $product) {
                if ($this->cleanup_premiere_data($product->ID)) {
                    $cleaned_products[] = $product->ID;
                }
            }

            return $cleaned_products;
        }
    }
}