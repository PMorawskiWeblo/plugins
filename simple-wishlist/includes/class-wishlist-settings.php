<?php
class Wishlist_Settings
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_admin_menu()
    {
        add_options_page(
            'Ustawienia Wishlist',
            'Wishlist',
            'manage_options',
            'wishlist-settings',
            [$this, 'settings_page']
        );
    }

    public function register_settings()
    {
        register_setting('wishlist_options', 'wishlist_page_id');

        add_settings_section(
            'wishlist_main_section',
            'Główne ustawienia',
            null,
            'wishlist-settings'
        );

        add_settings_field(
            'wishlist_page_id',
            'Strona Wishlist',
            [$this, 'page_dropdown_callback'],
            'wishlist-settings',
            'wishlist_main_section'
        );
    }

    public function page_dropdown_callback()
    {
        $selected = get_option('wishlist_page_id');
        wp_dropdown_pages([
            'name' => 'wishlist_page_id',
            'selected' => $selected,
            'show_option_none' => 'Wybierz stronę',
        ]);
    }

    public function settings_page()
    {
?>
<div class="wrap">
    <h1>Ustawienia Wishlist</h1>
    <form method="post" action="options.php">
        <?php
                settings_fields('wishlist_options');
                do_settings_sections('wishlist-settings');
                submit_button();
                ?>
    </form>
</div>
<?php
    }
}