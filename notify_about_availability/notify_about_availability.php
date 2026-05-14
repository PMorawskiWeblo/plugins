<?php
/*
Plugin Name: Notify about availability
Description: This plugin allows users to get notified about product availability.
Version: 1.0
Author: Piotr Morawski - Weblo
Text Domain: notify-about-availability
*/



// Enqueue CSS and JS files
function notify_about_availability_enqueue_scripts()
{
    wp_enqueue_style('notify_about_availability_style', plugin_dir_url(__FILE__) . 'notify_about_availability.css');
    wp_enqueue_script('notify_about_availability_script', plugin_dir_url(__FILE__) . 'notify_about_availability.js', array('jquery'), false, true);
    wp_localize_script(
        'notify_about_availability_script',
        'admin_ajax_url',
        array(
            'ajax_url' => admin_url('admin-ajax.php')
        )
    );
    wp_localize_script(
        'notify_about_availability_script',
        'notify_availability_i18n',
        array(
            'email_required' => __('Email is required', 'notify-about-availability'),
            'error_occurred' => __('An error occurred. Please try again.', 'notify-about-availability')
        )
    );
}
add_action('wp_enqueue_scripts', 'notify_about_availability_enqueue_scripts');

// Shortcode function
function notify_about_availability_shortcode()
{
    $product = wc_get_product(get_the_ID());
    if (!$product) {
        return '';
    }

    $show_form = false;
    $is_variable = $product->is_type('variable');
    $variations_data = array();
    $default_variation_id = 0;

    if ($is_variable) {
        // Pobierz wszystkie warianty
        $all_variations = $product->get_children();

        // Sprawdź domyślny wariant (jeśli jest ustawiony)
        $default_attributes = $product->get_default_attributes();
        if (!empty($default_attributes)) {
            $default_variation_id = $product->get_matching_variation($default_attributes);
            if ($default_variation_id) {
                $default_variation = wc_get_product($default_variation_id);
                if ($default_variation && (!$default_variation->is_in_stock() && !$default_variation->is_on_backorder())) {
                    $show_form = true;
                }
            }
        }

        // Przygotuj dane o dostępności wszystkich wariantów
        foreach ($all_variations as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation) {
                $variations_data[$variation_id] = array(
                    'in_stock' => $variation->is_in_stock(),
                    'on_backorder' => $variation->is_on_backorder()
                );
            }
        }
    } else {
        // Dla produktów simple sprawdź dostępność
        if (!$product->is_in_stock() && !$product->is_on_backorder()) {
            $show_form = true;
        }
    }

    ob_start(); ?>

<div id="availability-info" class="availability-info w-100" style="<?php echo $show_form ? '' : 'display:none;'; ?>">
    <p>
        <?php _e("This product is currently unavailable. Leave your email and we'll notify you when it becomes available.", 'notify-about-availability'); ?>
    </p>
    <form method="post" action="">
        <div class="d-flex  w-100 flex-column"">
            <div class=" wrap_row" style="box-sizing:border-box;">

            <div class="wrap_col input_email_wrap">
                <input type="email" name="availability_email" id="availability_email" class="input_email"
                    placeholder="<?php _e('Email address', 'notify-about-availability'); ?>" required>
            </div>

            <div class="wrap_col notify_me_wrap">
                <input type="hidden" name="prod_id" value="<?php echo get_the_ID(); ?>">
                <input type="hidden" id="variation_id" value="<?php echo $default_variation_id; ?>">
                <a class="button btn btn-dark" id="notify_me">
                    <?php _e('Notify me', 'notify-about-availability'); ?>
                </a>
            </div>
        </div>
</div>
<div id="notification_response"></div>
</form>
</div>

<?php if ($is_variable): ?>
<script type="text/javascript">
var notifyAvailabilityData = <?php echo json_encode($variations_data); ?>;
var notifyDefaultVariationId = <?php echo $default_variation_id; ?>;
</script>
<?php endif; ?>

<?php
    return ob_get_clean();
}
add_shortcode('notify_about_availability', 'notify_about_availability_shortcode');


function custom_product_meta_box($post_type, $post)
{
    $notify_emails = array();
    $product = wc_get_product($post->ID);
    if ($product && $product->is_type('variable')) {
        $variations = $product->get_children();

        foreach ($variations as $variation_id) {
            $variation = wc_get_product($variation_id);
            $emails = get_post_meta($variation_id, '_notify_emails', true);
            if (is_array($emails))
                foreach ($emails as $email)
                    $notify_emails[] = $email['email'];
        }
    } else if ($product && $product->is_type('simple')) {
        $emails = get_post_meta($product->get_id(), '_notify_emails', true);
        if (is_array($emails))
            foreach ($emails as $email)
                $notify_emails[] = $email['email'];
    }
    add_meta_box(
        'custom_variation_meta_box',
        'Powiadomienia o dostępności (' . count($notify_emails) . ')',
        'display_custom_variation_meta_box',
        'product',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'custom_product_meta_box', 10, 2);

function display_custom_variation_meta_box($post)
{
    $product = wc_get_product($post->ID);
    $showed = false;
    $notify_emails = array();
    if ($product && $product->is_type('variable')) {
        $variations = $product->get_children();

        // Iterate through variations
        foreach ($variations as $variation_id) {
            $notify_emails = array();
            $variation = wc_get_product($variation_id);
            $notify_emails2 = get_post_meta($variation_id, '_notify_emails', true);

            if (is_array($notify_emails2))
                foreach ($notify_emails2 as $person)
                    $notify_emails[] = $person['email'] . ', ';


            if (!empty($notify_emails)) {
                $variation_name = wc_get_formatted_variation($variation, true);
                $variation_name = explode(',', esc_html($variation_name));
                unset($variation_name[0]);
                $nazwa = implode(',', $variation_name);
                $showed = true;
                echo '<p><strong>' . $nazwa . ' (' . count($notify_emails) . ') :</strong> ' . esc_html(implode(', ', $notify_emails)) . '</p>';
            }
        }
    } else if ($product && $product->is_type('simple')) {
        $notify_emails2 = get_post_meta($product->get_id(), '_notify_emails', true);

        if (is_array($notify_emails2))
            foreach ($notify_emails2 as $person)
                $notify_emails[] = $person['email'] . ', ';
        $showed = true;
        if (is_array($notify_emails))
            foreach ($notify_emails as $person)
                echo $person;
    }
    if (!$showed) {
        echo 'Brak oczekujących';
    }
}


function add_custom_product_admin_column($columns)
{
    $columns['oczekujacy'] = 'Oczekujący';
    return $columns;
}
add_filter('manage_edit-product_columns', 'add_custom_product_admin_column');

function display_custom_product_admin_column($column, $post_id)
{
    if ($column == 'oczekujacy') {
        $notify_emails = array();
        $product = wc_get_product($post_id);
        if ($product && $product->is_type('variable')) {
            $variations = $product->get_children();

            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);

                // Check if the variation has _notify_emails meta
                $emails = get_post_meta($variation_id, '_notify_emails', true);
                if (is_array($emails))
                    foreach ($emails as $email)
                        $notify_emails[] = $email;
            }
        } else if ($product && $product->is_type('simple')) {
            $emails = get_post_meta($product->get_id(), '_notify_emails', true);
            if (is_array($emails))
                foreach ($emails as $email)
                    $notify_emails[] = $email;
        }
        if (!empty($notify_emails))
            echo '<b>' . count($notify_emails) . '</b>';
        else
            echo count($notify_emails);
    }
}
add_action('manage_product_posts_custom_column', 'display_custom_product_admin_column', 10, 2);

// new



// Add options page to WordPress admin menu
function notify_about_availability_options_page()
{
    add_options_page(
        __('Powiadomienia o dostępności', 'notify_about_availability'),
        __('Powiadomienia o dostępności', 'notify_about_availability'),
        'manage_options',
        'notify-about-availability-options',
        'notify_about_availability_options_page_html'
    );
}
add_action('admin_menu', 'notify_about_availability_options_page');

// Register and initialize plugin settings
function notify_about_availability_register_settings()
{
    register_setting('notify_about_availability_options_group', 'notify_about_availability_options', 'notify_about_availability_options_sanitize');
}
add_action('admin_init', 'notify_about_availability_register_settings');

// Define HTML for options page
function notify_about_availability_options_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post" style="width: 380px;">
        <?php settings_fields('notify_about_availability_options_group'); ?>
        <?php $options = get_option('notify_about_availability_options'); ?>
        <div class="option">
            <h3><?php _e('Email title', 'notify_about_availability'); ?> <span class="required">*</span></h3>
            <p><?php _e('You can use <strong>[product_name]</strong> to substitute variables.', 'notify_about_availability'); ?>
            </p>
            <input type="text" name="notify_about_availability_options[email_title]" style="width: 100%;"
                value="<?php echo isset($options['email_title']) ? esc_attr($options['email_title']) : 'Your product - [product_name]'; ?>"
                required />
        </div>
        <div class="option">
            <h3><?php _e('E-mail text', 'notify_about_availability'); ?> <span class="required">*</span></h3>
            <p><?php _e('You can use <strong>[product_name]</strong>, <strong>[link]</strong> and <strong>[add_to_cart]</strong> to substitute variables.', 'notify_about_availability'); ?>
            </p>
            <textarea name="notify_about_availability_options[email_text]" rows="5" cols="50" style="width: 100%;"
                required><?php echo isset($options['email_text']) ? esc_textarea($options['email_text']) : '<h3>Hello!</h3><p>The product <strong><a href="[link]" target="_blank">[product_name]</a></strong> is available again in our offer</p>[add_to_cart]'; ?></textarea>
        </div>
        <div class="option">
            <h3><?php _e('Image', 'notify_about_availability'); ?></h3>
            <p><?php _e('You can use <strong>[product_img]</strong> as an image or your own link.', 'notify_about_availability'); ?>
            </p>
            <input type="text" name="notify_about_availability_options[image]" style="width: 100%;"
                value="<?php echo isset($options['image']) ? esc_attr($options['image']) : '[product_img]'; ?>" />
        </div>
        <?php submit_button(__('Save Changes', 'notify_about_availability')); ?>
    </form>
</div>
<?php
}

// Sanitize options
function notify_about_availability_options_sanitize($input)
{
    $sanitized_input = array();
    if (isset($input['email_title'])) {
        $sanitized_input['email_title'] = sanitize_text_field($input['email_title']);
    }
    if (isset($input['email_text'])) {
        $sanitized_input['email_text'] = wp_kses_post($input['email_text']);
    }
    if (isset($input['image'])) {
        $sanitized_input['image'] = sanitize_text_field($input['image']);
    }
    return $sanitized_input;
}

add_action('wp_head', function () {
    if (isset($_GET['test'])) {
        $emails = array(array("email" => "ceo@time4it.pl", "name" => "test"));
        send_availability_notification(153960, $emails);
    }
});

// Function to send emails
function send_availability_notification($product_id, $emails)
{
    $product = wc_get_product($product_id);
    $product_name = $product->get_name();
    $product_link = get_permalink($product_id);

    foreach ($emails as $person) {

        // Get email title and text from options

        $msg_wrapper = '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; padding: 20px; background:white; text-align: center; border: 1px solid #e0e0e0; border-radius: 5px;">';
        $msg_wrapper_end = '</div>';

        $options = get_option('notify_about_availability_options');
        $email_title = isset($options['email_title']) ? $options['email_title'] : '';
        $email_text = isset($options['email_text']) ? $options['email_text'] : '';
        $email_image = isset($options['image']) ? $options['image'] : '';
        $name = $person['name'];

        // Replace placeholders with actual values in email title
        $subject = str_replace('[product_name]', $product_name, $email_title);

        // Replace placeholders with actual values in email text
        $message = str_replace('[product_name]', $product_name, $email_text);
        $message = str_replace('[link]', $product_link, $message);
        $message = str_replace('[imie]', $name, $message);

        // Add to cart button
        $add_to_cart_button = '<p style="padding-top: 20px; "></p><a href="' . esc_url($product_link) . '" class="button" style="padding: 10px 20px; border-radius: 0px; border: 1.5px solid black; background: black; color: white; text-transform: uppercase; text-decoration: none; transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;">' . __('Przejdź do sklepu', 'notify_about_availability') . '</a></p>';
        $message = str_replace('[add_to_cart]', $add_to_cart_button, $message);

        $product_image = get_the_post_thumbnail_url($product_id, 'full');
        $product_image_link = get_permalink($product_id);
        $product_image = '<br><a href="' . esc_url($product_image_link) . '"><img style="max-width:300px" src="' . esc_url($product_image) . '" alt="' . esc_attr($product_name) . '"></a>';

        $message = str_replace('[product_img]', $product_image, $message);
        $message = $msg_wrapper . $message . $msg_wrapper_end;

        $headers = array('Content-Type: text/html; charset=UTF-8');


        wp_mail($person['email'], $subject, $message, $headers);
    }

    update_post_meta($product_id, '_notify_emails', '');
}


// Check product availability on update
add_action('woocommerce_product_set_stock', 'check_product_availability_on_update', 10, 1);
function check_product_availability_on_update($product)
{

    $product_id = $product->get_id();
    if ($product->is_type('variable')) {
        $variations = $product->get_available_variations();

        foreach ($variations as $variation) {
            $variation_id = $variation['variation_id'];
            $variation_product = wc_get_product($variation_id);
            if ($variation_product->is_in_stock()) {
                $emails = get_post_meta($variation_id, '_notify_emails', true);
                if ($emails != '' && is_array($emails)) {
                    send_availability_notification($variation_id, $emails);
                }
            }
        }
    } else if ($product->is_type('simple')) {
        if ($product && $product->is_in_stock()) {
            $emails = get_post_meta($product_id, '_notify_emails', true);
            if ($emails != '' && is_array($emails)) {
                send_availability_notification($product_id, $emails);
            }
        }
    }
}
add_action('wp_ajax_notify_me', 'custom_notify_me_callback');
add_action('wp_ajax_nopriv_notify_me', 'custom_notify_me_callback');
function custom_notify_me_callback()
{
    $product_id = intval($_POST['product_id']);
    $email = sanitize_email($_POST['email']);
    $name = sanitize_text_field($_POST['name']);


    if (!$email) {
        echo __('Email is required', 'notify_about_availability');
        wp_die();
    }

    // if (!$name) {
    //     echo __('Imię jest wymagane', 'notify_about_availability');
    //     wp_die();
    // }


    $emails = get_post_meta($product_id, '_notify_emails', true);
    if (!is_array($emails)) {
        $emails = array();
    }
    $person = array("name" => $email, "email" => $email);
    $emails[] = $person;

    update_post_meta($product_id, '_notify_emails', $emails);

    echo __("Thank you! We'll let you know when this product is back in stock.", 'notify_about_availability');
    wp_die();
}


// Add admin menu
add_action('admin_menu', 'notify_availability_admin_menu');

function notify_availability_admin_menu()
{
    add_menu_page(
        __('Zaległe powiadomienia', 'notify-about-availability'),
        __('Zaległe powiadomienia', 'notify-about-availability'),
        'manage_options',
        'notify-availability-settings',
        'notify_availability_settings_page'
    );
}

function notify_availability_settings_page()
{
?>
<div class="wrap">
    <h1><?php _e('Zaległe powiadomienia', 'notify-about-availability'); ?></h1>

    <form method="post" enctype="multipart/form-data">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="product_id"><?php _e('Wybierz produkt', 'notify-about-availability'); ?></label>
                </th>
                <td>
                    <select name="product_id" id="product_id" required>
                        <option value=""><?php _e('-- Wybierz produkt --', 'notify-about-availability'); ?></option>
                        <?php
                            $products = wc_get_products(array('limit' => -1));
                            foreach ($products as $product) {
                                echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
                            }
                            ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="csv_file"><?php _e('Plik CSV z mailami', 'notify-about-availability'); ?></label>
                </th>
                <td>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    <p class="description">
                        <?php _e('Plik CSV z jedną kolumną zawierającą adresy email (pierwszy wiersz zostanie pominięty)', 'notify-about-availability'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Importuj i wyślij powiadomienia', 'notify-about-availability')); ?>
        <?php wp_nonce_field('notify_availability_import', 'notify_availability_nonce'); ?>
    </form>
</div>
<?php

    if (isset($_POST['submit']) && check_admin_referer('notify_availability_import', 'notify_availability_nonce')) {
        if (!empty($_FILES['csv_file']['tmp_name']) && !empty($_POST['product_id'])) {
            $product_id = intval($_POST['product_id']);
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');

            // Skip first row
            fgetcsv($file);

            $emails = get_post_meta($product_id, '_notify_emails', true);
            if (!is_array($emails)) {
                $emails = array();
            }

            while (($data = fgetcsv($file)) !== FALSE) {
                if (isset($data[0]) && is_email($data[0])) {
                    $person = array(
                        "name" => __('brak', 'notify-about-availability'),
                        "email" => sanitize_email($data[0])
                    );
                    $emails[] = $person;
                }
            }

            fclose($file);

            update_post_meta($product_id, '_notify_emails', $emails);

            $product = wc_get_product($product_id);
            if ($product && $product->is_in_stock()) {
                send_availability_notification($product_id, $emails);
                echo '<div class="notice notice-success"><p>' . __('Powiadomienia zostały wysłane!', 'notify-about-availability') . '</p></div>';
            } else {
                echo '<div class="notice notice-warning"><p>' . __('Adresy email zostały zaimportowane, ale produkt nie jest dostępny.', 'notify-about-availability') . '</p></div>';
            }
        }
    }
}