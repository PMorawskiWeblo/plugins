<?php

class CartAdmin
{

    private $plugin_name;
    private $version;

    public function __construct()
    {

        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_gifts_levels', array($this, 'save_gifts_levels'));
        add_action('admin_footer', array($this, 'add_templates'));

        if (isset($_POST['submit_cart_settings'])) {
            $this->save_cart_settings();
        }
        if (isset($_POST['submit_delivery_settings'])) {
            $this->save_delivery_settings();
        }
        if (isset($_POST['submit_free_shipping_settings'])) {
            $this->save_free_shipping_settings();
        }
        if (isset($_POST['submit_counter_settings'])) {
            $this->save_counter_settings();
        }
        if (isset($_POST['submit_cross_sell_page_settings'])) {
            $this->save_cross_sell_page_settings();
        }
    }

    public function enqueue_scripts()
    {

        if (!isset($_GET['page']) || $_GET['page'] !== 'cart-gifts-settings') {
            return;
        }
        $version = 2.2;

        wp_enqueue_style('custom-cart-gifts-modal-admin-style', plugin_dir_url(__FILE__) . '../assets/css/admin.min.css', array(), $version, 'all');
        wp_enqueue_script('custom-cart-gifts-modal-admin', plugin_dir_url(__FILE__) . '../assets/js/admin.js', array('jquery'), $version, true);
        wp_localize_script('custom-cart-gifts-modal-admin', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('save_gifts_levels')));


        wp_enqueue_style('select2', plugin_dir_url(__FILE__) . '../assets/css/select2.min.css', array(), $version, 'all');
        wp_enqueue_script('select2', plugin_dir_url(__FILE__) . '../assets/js/select2.min.js', array('jquery'), $version, true);
    }

    public function add_templates()
    {
        echo '<script type="text/template" id="product-template">';
        include plugin_dir_path(__FILE__) . '../templates/add-product-template.php';
        echo '</script>';

        echo '<script type="text/template" id="add-level-template">';
        include plugin_dir_path(__FILE__) . '../templates/add-level-template.php';
        echo '</script>';

        echo '<script type="text/template" id="add-cross-sell-product-template">';
        include plugin_dir_path(__FILE__) . '../templates/add-level-cross-sell-product-template.php';
        echo '</script>';
    }

    public function add_plugin_admin_menu()
    {
        add_menu_page(
            'Ustawienia prezentów w koszyku',
            'Ustawienia koszyka',
            'manage_options',
            'cart-gifts-settings',
            array($this, 'render_plugin_setup_page'),
            'dashicons-cart',
            30
        );
    }

    public function save_cart_settings()
    {

        if (!isset($_POST['submit_cart_settings'])) {
            return;
        }
        if (isset($_POST['enable_cart']) && !empty($_POST['enable_cart'])) {
            update_option('cgm_enable_cart', $_POST['enable_cart'] == 'on' ? 'on' : 'off');
        } else {
            update_option('cgm_enable_cart', 'off');
        }
        if (isset($_POST['show_attribute_name']) && !empty($_POST['show_attribute_name'])) {
            update_option('cgm_show_attribute_name_in_cart', $_POST['show_attribute_name'] == 'on' ? 'on' : 'off');
        } else {
            update_option('cgm_show_attribute_name_in_cart', 'off');
        }
        if (isset($_POST['show_attributes']) && !empty($_POST['show_attributes'])) {
            update_option('cgm_show_attributes_in_cart', $_POST['show_attributes']);
        }
        if (isset($_POST['show_mobile_coupon']) && !empty($_POST['show_mobile_coupon'])) {
            update_option('cgm_show_mobile_coupon', $_POST['show_mobile_coupon'] == 'on' ? 'on' : 'off');
        } else {
            update_option('cgm_show_mobile_coupon', 'off');
        }
        if (isset($_POST['notification_position']) && !empty($_POST['notification_position'])) {
            update_option('cgm_notification_position', $_POST['notification_position']);
        }
    }

    public function save_delivery_settings()
    {
        if (!isset($_POST['submit_delivery_settings'])) {
            return;
        }
        if (isset($_POST['show_delivery']) && !empty($_POST['show_delivery'])) {
            update_option('cgm_show_delivery_time', $_POST['show_delivery'] == 'on' ? 'on' : 'off');
        } else {
            update_option('cgm_show_delivery_time', 'off');
        }
    }

    public function save_free_shipping_settings()
    {
        if (!isset($_POST['submit_free_shipping_settings'])) {
            return;
        }
        if (isset($_POST['show_free_shipping']) && !empty($_POST['show_free_shipping'])) {
            update_option('cgm_show_free_shipping', $_POST['show_free_shipping'] == 'on' ? 'on' : 'off');
        } else {
            update_option('cgm_show_free_shipping', 'off');
        }
        if (isset($_POST['free_shipping_threshold']) && !empty($_POST['free_shipping_threshold'])) {
            update_option('cgm_free_shipping_threshold', $_POST['free_shipping_threshold']);
        }
    }

    public function save_counter_settings()
    {
        if (!isset($_POST['submit_counter_settings'])) {
            return;
        }
        if (isset($_POST['counter_class']) && !empty($_POST['counter_class'])) {
            update_option('cgm_counter_class', $_POST['counter_class']);
        }
    }

    public function save_cross_sell_page_settings()
    {
        if (!isset($_POST['submit_cross_sell_page_settings'])) {
            return;
        }

        if (isset($_POST['show_cross_sell_page']) && !empty($_POST['show_cross_sell_page'])) {
            update_option('cgm_show_cross_sell_page', $_POST['show_cross_sell_page'] == 'on' ? 'on' : 'off');
        } else {
            update_option('cgm_show_cross_sell_page', 'off');
        }

        if (isset($_POST['cross_sell_page_url']) && !empty($_POST['cross_sell_page_url'])) {
            update_option('cgm_cross_sell_page_url', $_POST['cross_sell_page_url']);
        }
    }

    public function render_plugin_setup_page()
    {

        if (!current_user_can('manage_options')) {
            return;
        }

        $show_gifts = get_option('cgm_show_cart_gifts', false);
        $is_progress_bar_visible = get_option('cgm_is_progress_bar_visible', false);
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'koszyk';

?>
        <div class="wrap">
            <h2 class="nav-tab-wrapper">
                <a href="?page=cart-gifts-settings&tab=koszyk"
                    class="nav-tab <?php echo $active_tab == 'koszyk' ? 'nav-tab-active' : ''; ?>">
                    Koszyk
                </a>
                <a href="?page=cart-gifts-settings&tab=modal"
                    class="nav-tab <?php echo $active_tab == 'modal' ? 'nav-tab-active' : ''; ?>">
                    Modal
                </a>
                <a href="?page=cart-gifts-settings&tab=gratisy"
                    class="nav-tab <?php echo $active_tab == 'gratisy' ? 'nav-tab-active' : ''; ?>">
                    Gratisy
                </a>
                <a href="?page=cart-gifts-settings&tab=cross-sell"
                    class="nav-tab <?php echo $active_tab == 'cross-sell' ? 'nav-tab-active' : ''; ?>">
                    Strona Cross-sell
                </a>
                <a href="?page=cart-gifts-settings&tab=darmowa-dostawa"
                    class="nav-tab <?php echo $active_tab == 'darmowa-dostawa' ? 'nav-tab-active' : ''; ?>">
                    Darmowa dostawa
                </a>
                <a href="?page=cart-gifts-settings&tab=delivery"
                    class="nav-tab <?php echo $active_tab == 'delivery' ? 'nav-tab-active' : ''; ?>">
                    Przewidywana dostawa
                </a>
                <a href="?page=cart-gifts-settings&tab=licznik"
                    class="nav-tab <?php echo $active_tab == 'licznik' ? 'nav-tab-active' : ''; ?>">
                    Licznik produktów
                </a>
            </h2>

            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'gratisy':
                ?>
                        <div class="gratisy-tab">
                            <h2>Ustawienia gratisów</h2>
                            <div class="show-gifts-container ">
                                <h3>Ustawienia ogólne</h3>
                                <label>
                                    <input type="checkbox" name="show_gifts" id="show_gifts"
                                        <?php checked($show_gifts === 'true' || $show_gifts === true || $show_gifts === '1' || $show_gifts === 1, true); ?>>
                                    Wyświetlaj gratisy
                                </label>
                                <br>
                                <br>
                                <label>
                                    <input type="checkbox" name="is_progress_bar_visible" id="is_progress_bar_visible"
                                        <?php checked($is_progress_bar_visible === 'true' || $is_progress_bar_visible === true || $is_progress_bar_visible === '1' || $is_progress_bar_visible === 1, true); ?>>
                                    Wyświetlaj pasek postępu
                                </label>
                                <br>
                                <br>
                                <label>
                                    Tytuł paska postępu:<br>
                                    <input type="text" disabled name="progress_bar_title" id="progress_bar_title" class="regular-text"
                                        value="<?php echo __('Match products and receive maximum benefits!', 'custom-cart-gifts-modal'); ?>">
                                </label>
                                <label style="display: block;" class="description">
                                    Treść komunikatu jest tłumaczona na język strony, więc jeśli chcesz zmienić język strony, musisz
                                    zmienić treść komunikatu w tłumaczeniach.
                                </label>
                                <br>
                                <br>
                                <label>
                                    Aktywne prezenty:<br>
                                    <select name="active_gifts_display" id="active_gifts_display" class="regular-text">
                                        <option value="only_active_level"
                                            <?php selected(get_option('cgm_active_gifts_display', 'only_active_level'), 'only_active_level'); ?>>
                                            Tylko aktywny poziom</option>
                                        <option value="active_and_prev"
                                            <?php selected(get_option('cgm_active_gifts_display', 'only_active_level'), 'active_and_prev'); ?>>
                                            Aktywny i niższe</option>
                                    </select>
                                </label>
                            </div>
                            <form method="post" action="">
                                <div id="levels-container">
                                    <?php
                                    $levels = get_option('cgm_cart_gifts_levels', array());
                                    foreach ($levels as $key => $level) {
                                        $key = $key + 1;
                                        include plugin_dir_path(__FILE__) . '../templates/add-level-template.php';
                                    }
                                    ?>
                                </div>
                                <input type="button" id="add_level" class="button button-primary" value="Dodaj poziom">

                                <p class="submit">
                                    <input type="button" name="submit_gifts_settings" id="submit_gifts_settings"
                                        class="button button-primary" value="Zapisz ustawienia gratisów">
                                </p>
                            </form>
                        </div>
                    <?php
                        break;

                    case 'darmowa-dostawa':
                    ?>
                        <div class="darmowa-dostawa-tab">
                            <h3>Ustawienia darmowej dostawy</h3>
                            <form method="post" action="">
                                <div class="control-wrap">
                                    <h3>Ogólne</h3>
                                    <?php
                                    $show_free_shipping = get_option('cgm_show_free_shipping', false) == 'on' ? true : false;
                                    $free_shipping_threshold = get_option('cgm_free_shipping_threshold', 0);
                                    $is_progress_bar_visible = get_option('cgm_is_progress_bar_visible', false);
                                    ?>
                                    <label>
                                        <input type="checkbox" name="show_free_shipping" id="show_free_shipping"
                                            <?php checked($show_free_shipping); ?>>
                                        Wyświetl informację o darmowej dostawie
                                    </label>
                                    <br>
                                    <br>
                                    <label>
                                        Próg darmowej dostawy:
                                        <input type="number" name="free_shipping_threshold" id="free_shipping_threshold" min="0"
                                            step="0.01" value="<?php echo esc_attr($free_shipping_threshold); ?>">
                                        zł
                                    </label>

                                </div>
                                <?php
                                $free_shipping_message = __('To get free shipping you need: {remaining}', 'custom-cart-gifts-modal');
                                $free_shipping_success_message = __('Congratulations! You get free delivery!', 'custom-cart-gifts-modal');
                                ?>
                                <div class="control-wrap">
                                    <h3>Komunikaty</h3>
                                    <h4>Komunikat przed osiągnięciem progu:</h4>
                                    <input type="text" name="free_shipping_message" id="free_shipping_message" disabled
                                        value="<?php echo esc_attr($free_shipping_message); ?>" style="width: 100%">

                                    <p class="description">Użyj {remaining} aby wstawić brakującą kwotę</p>
                                    <br>
                                    <h4> Komunikat po osiągnięciu progu:</h4>
                                    <input type="text" name="free_shipping_success_message" id="free_shipping_success_message" disabled
                                        value="<?php echo esc_attr($free_shipping_success_message); ?>" style="width: 100%">
                                </div>
                                <div class="control-wrap">
                                    <p>
                                        Uwaga. Ta funkcja wyświetla tylko informacje, logikę darmowej dostawy ustawiamy niezależnie w
                                        WooCommerce.<br>
                                        Ustawienia darmowej dostawy w WooCommerce znajdują się w ustawieniach WooCommerce -> Ustawienia
                                        -> Dostawa -> Darmowa dostawa<br>
                                        Treść komunikatów jest tłumaczona na język strony, więc jeśli chcesz zmienić język strony,
                                        musisz zmienić treść komunikatów w tłumaczeniach.
                                    </p>
                                </div>
                                <input type="submit" name="submit_free_shipping_settings" id="submit_free_shipping_settings"
                                    class="button button-primary" value="Zapisz ustawienia darmowej dostawy">
                            </form>
                        </div>
                    <?php
                        break;
                    case 'modal':
                    ?>
                        <div class="darmowa-dostawa-tab">
                            <form method="post" action="">
                                <h3>Ustawienia modala</h3>
                                <div class="control-wrap">
                                    <h3>Liczba produktów cross-sell</h3>
                                    <?php
                                    $crosssell_count = get_option('cgm_crosssell_products_count', 3);
                                    ?>

                                    <label>
                                        Ile produktów cross-sell wyświetlić?
                                        <input type="number" name="crosssell_count" id="crosssell_count" min="1" max="12"
                                            value="<?php echo esc_attr($crosssell_count); ?>">
                                    </label>
                                </div>
                                <input type="submit" name="submit_modal_settings" id="submit_modal_settings"
                                    class="button button-primary" value="Zapisz ustawienia modala">
                            </form>
                        </div>
                    <?php
                        break;
                    case 'koszyk':
                    ?>
                        <div class="koszyk-tab">
                            <h3>Ustawienia koszyka</h3>
                            <form method="post">
                                <div class="control-wrap">
                                    <h3>Ogólne</h3>
                                    <?php
                                    $enable_cart = get_option('cgm_enable_cart', false) == 'on' ? true : false;
                                    ?>
                                    <label>
                                        <input type="checkbox" name="enable_cart" id="enable_cart" <?php checked($enable_cart); ?>>
                                        Włącz koszyk
                                    </label>
                                </div>
                                <div class="control-wrap">
                                    <h3>Atrybuty produktów w koszyku</h3>
                                    <div class="control-wrap">
                                        <?php
                                        $show_attribute_name = get_option('cgm_show_attribute_name_in_cart', false) == 'on' ? true : false;
                                        ?>
                                        <h4>
                                            Ogólne
                                        </h4>
                                        <label>
                                            <input type="checkbox" name="show_attribute_name" id="show_attribute_name"
                                                <?php checked($show_attribute_name); ?>>
                                            Pokaż nazwę atrybutu
                                        </label>
                                    </div>
                                    <div class="control-wrap attributes-wrap">
                                        <h4>
                                            Wybierz atrybuty do wyświetlenia w koszyku
                                        </h4>
                                        <?php
                                        $attributes = wc_get_attribute_taxonomies();
                                        $show_attributes = get_option('cgm_show_attributes_in_cart', array());
                                        foreach ($attributes as $attribute) {
                                            $attribute_name = $attribute->attribute_name;
                                            $attribute_label = $attribute->attribute_label;
                                            $attribute_slug = wc_attribute_taxonomy_name($attribute_name);
                                            $is_checked = in_array($attribute_slug, (array)$show_attributes);
                                        ?>
                                            <div class="attribute-item">
                                                <label>
                                                    <input type="checkbox" name="show_attributes[]"
                                                        value="<?php echo esc_attr($attribute_slug); ?>" <?php checked($is_checked); ?>>
                                                    <?php echo esc_html($attribute_label); ?>
                                                </label>
                                            </div>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                    <h3>Pasek koszyka na mobilnej wersji</h3>
                                    <?php
                                    $show_mobile_coupon = get_option('cgm_show_mobile_coupon', false) == 'on' ? true : false;

                                    ?>
                                    <div class="control-wrap">
                                        <label>
                                            <input type="checkbox" name="show_mobile_coupon" id="show_mobile_coupon"
                                                <?php checked($show_mobile_coupon); ?>>
                                            Pokaż kupon w koszyku na mobilnej wersji
                                        </label>
                                    </div>
                                    <h3>
                                        Pozycja notyfikacji w koszyku
                                    </h3>
                                    <div class="control-wrap">
                                        <?php
                                        $notification_position = get_option('cgm_notification_position', 'standard');
                                        ?>
                                        <select name="notification_position" id="notification_position">
                                            <option value="standard" <?php selected($notification_position, 'standard'); ?>>Standardowa
                                            </option>
                                            <option value="fixed-top" <?php selected($notification_position, 'fixed-top'); ?>>
                                                Przyklejona do góry</option>
                                            <option value="fixed-bottom" <?php selected($notification_position, 'fixed-bottom'); ?>>
                                                Przyklejona do dołu</option>
                                        </select>
                                        <label>
                                            Wybierz pozycję notyfikacji w koszyku
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="button button-primary" name="submit_cart_settings"
                                    id="zapisz_ustawienia_koszyka">
                                    Zapisz ustawienia koszyka
                                </button>
                            </form>
                        </div>
                    <?php
                        break;

                    case 'delivery':
                    ?>
                        <div class="delivery-tab">
                            <h2>Przewidywana dostawa</h2>
                            <form method="post" action="">
                                <?php wp_nonce_field('save_delivery_settings', 'delivery_settings_nonce'); ?>
                                <div class="control-wrap">
                                    <h3>Ustawienia dostawy</h3>
                                    <?php

                                    $show_delivery = get_option('cgm_show_delivery_time', false) == 'on' ? true : false;
                                    $delivery_text = __('Expected delivery time', 'custom-cart-gifts-modal');

                                    ?>
                                    <label>
                                        <input type="checkbox" name="show_delivery" id="show_delivery"
                                            <?php checked($show_delivery); ?>>
                                        Pokaż przewidywany czas dostawy
                                    </label>
                                    <br><br>
                                    <label>
                                        Tekst dostawy:<br>
                                        <textarea disabled name="delivery_text" id="delivery_text" rows="3"
                                            style="width: 100%;"><?php echo esc_textarea($delivery_text); ?></textarea>
                                    </label>
                                    <p class="description">
                                        Treść komunikatu jest tłumaczona na język strony, więc jeśli chcesz zmienić język strony, musisz
                                        zmienić treść komunikatu w tłumaczeniach.
                                    </p>
                                </div>

                                <button type="submit" class="button button-primary" name="submit_delivery_settings">
                                    Zapisz ustawienia dostawy
                                </button>
                            </form>
                        </div>
                    <?php
                        break;

                    case 'licznik':
                    ?>
                        <div class="licznik-tab">
                            <h2>Licznik produktów</h2>
                            <div class="control-wrap">
                                <h3>Ustawienia licznika</h3>
                                <form method="post" action="">
                                    <label>
                                        Klasa elementu licznika:<br>
                                        <input type="text" name="counter_class" id="counter_class" class="regular-text"
                                            value="<?php echo esc_attr(get_option('cgm_counter_class', 'cart-counter')); ?>">
                                    </label>
                                    <p class="description">
                                        Wprowadź klasę elementu HTML, w którym będzie wyświetlana liczba produktów w koszyku
                                    </p>
                                    <br><br>
                                    <button type="submit" class="button button-primary" name="submit_counter_settings">
                                        Zapisz ustawienia licznika
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php
                        break;

                    case 'cross-sell':
                    ?>
                        <div class="cross-sell-tab">
                            <h2>Ustawienia strony cross-sell</h2>
                            <div class="control-wrap">
                                <h3>Ogólne</h3>
                                <form method="post" action="">
                                    <?php
                                    $show_cross_sell_page = get_option('cgm_show_cross_sell_page', false) == 'on' ? true : false;
                                    $cross_sell_page_url = get_option('cgm_cross_sell_page_url', '');
                                    $crossel_shortcode = get_option('cgm_crossel_page_shortcode', '');
                                    ?>
                                    <label>
                                        <input type="checkbox" name="show_cross_sell_page" id="show_cross_sell_page"
                                            <?php checked($show_cross_sell_page); ?>>
                                        Wyświetl sekcję cross-sell
                                    </label>
                                    <br><br>
                                    <label>
                                        Shortcode do wyświetlenia na stronie cross-sell:<br>
                                        <input type="text" name="crossel_shortcode" disabled id="crossel_shortcode" class="regular-text"
                                            value="<?php echo esc_attr($crossel_shortcode); ?>">
                                    </label>
                                    <br><br>
                                    <label>
                                        Strona cross-sell:<br>
                                        <?php

                                        $pages = get_pages();
                                        ?>
                                        <select name="cross_sell_page_url" id="cross_sell_page_url" class="regular-text">
                                            <option value="">Wybierz stronę</option>
                                            <?php foreach ($pages as $page): ?>
                                                <option value="<?php echo $page->ID; ?>"
                                                    <?php selected($cross_sell_page_url, $page->ID); ?>>
                                                    <?php echo $page->post_title; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php

                                        ?>

                                    </label>
                                    <br><br>
                                    <button type="submit" class="button button-primary" name="submit_cross_sell_page_settings">
                                        Zapisz ustawienia cross-sell
                                    </button>
                                </form>
                            </div>
                        </div>
                <?php
                        break;
                }
                ?>
            </div>
        </div>

<?php
    }

    public function save_gifts_levels()
    {

        if (!check_ajax_referer('save_gifts_levels', 'nonce', false)) {
            wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
            return;
        }

        $show_gifts = isset($_POST['show_gifts']) ? $_POST['show_gifts'] : false;
        $is_progress_bar_visible = isset($_POST['is_progress_bar_visible']) ? $_POST['is_progress_bar_visible'] : false;
        $levels_data = isset($_POST['levelsData']) ? $_POST['levelsData'] : array();
        $active_gifts_display = isset($_POST['active_gifts_display']) ? $_POST['active_gifts_display'] : 'only_active_level';
        $show_mobile_coupon = isset($_POST['show_mobile_coupon']) ? $_POST['show_mobile_coupon'] : false;

        update_option('cgm_show_cart_gifts', $show_gifts);
        update_option('cgm_is_progress_bar_visible', $is_progress_bar_visible);
        update_option('cgm_cart_gifts_levels', $levels_data);
        update_option('cgm_active_gifts_display', $active_gifts_display);
        update_option('cgm_show_mobile_coupon', $show_mobile_coupon);
        wp_send_json_success('Ustawienia zostały zapisane');
    }
}
