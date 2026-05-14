<?php

namespace FSProVendor\Octolize\PluginUpdateReminder;

use WooCommerce;
class ReminderData
{
    private static array $plugin_data = [];
    private string $plugin_dir;
    private string $plugin_name;
    private string $plugin_file;
    private WooCommerce $woocommerce;
    public function __construct(string $plugin_dir, string $plugin_file, string $plugin_name, WooCommerce $woocommerce)
    {
        $this->plugin_dir = $plugin_dir;
        $this->plugin_file = $plugin_file;
        $this->plugin_name = $plugin_name;
        $this->woocommerce = $woocommerce;
    }
    public function get_plugin_dir(): string
    {
        return $this->plugin_dir;
    }
    public function get_plugin_name(): string
    {
        return $this->get_plugin_data()['Name'] ? __($this->get_plugin_data()['Name'], $this->get_plugin_data()['TextDomain'] ?? '') : $this->plugin_name;
    }
    private function get_plugins_page_url(): string
    {
        return admin_url('plugins.php' . '#' . basename($this->plugin_dir) . '-oct-reminder');
    }
    public function get_plugin_file(): string
    {
        return $this->plugin_file;
    }
    protected function get_plugin_file_path(): string
    {
        return trailingslashit($this->plugin_dir) . basename($this->plugin_file);
    }
    public function is_minor_update(): bool
    {
        if ($this->is_empty_version()) {
            return \false;
        }
        if ($this->major_versions_difference() === 0 && $this->minor_versions_difference() < 3 && $this->minor_versions_difference() > 0) {
            return \true;
        }
        return \false;
    }
    public function is_major_update(): bool
    {
        if ($this->is_empty_version()) {
            return \false;
        }
        if ($this->major_versions_difference() > 0) {
            return \true;
        }
        if ($this->major_versions_difference() === 0 && $this->minor_versions_difference() >= 3) {
            return \true;
        }
        return \false;
    }
    public function get_major_reminder_content($no_link = \false): string
    {
        return sprintf(__('You\'re using an outdated version of %1$s plugin, which may cause serious compatibility issues with WooCommerce. %2$sUpdate immediately%3$s to avoid potential disruptions.', 'flexible-shipping-pro'), '<strong>' . $this->get_plugin_name() . '</strong>', $no_link ? '<strong>' : '<a href="' . esc_url($this->get_plugins_page_url()) . '">', $no_link ? '</strong>' : '</a>');
    }
    public function get_minor_reminder_content($no_link = \false): string
    {
        return sprintf(__('You\'re using an older version of %1$s plugin that hasn\'t been tested with the latest WooCommerce versions. Please %2$supdate to the latest version%3$s to ensure smooth compatibility.', 'flexible-shipping-pro'), '<strong>' . $this->get_plugin_name() . '</strong>', $no_link ? '<strong>' : '<a href="' . esc_url($this->get_plugins_page_url()) . '">', $no_link ? '</strong>' : '</a>');
    }
    private function major_versions_difference(): int
    {
        return (int) $this->get_major_version($this->get_woocommerce_version()) - (int) $this->get_major_version($this->get_plugin_wc_tested_up_version());
    }
    private function minor_versions_difference(): int
    {
        return (int) $this->get_minor_version($this->get_woocommerce_version()) - (int) $this->get_minor_version($this->get_plugin_wc_tested_up_version());
    }
    private function is_empty_version(): bool
    {
        return empty($this->get_plugin_wc_tested_up_version()) || empty($this->get_woocommerce_version());
    }
    public function get_plugin_wc_tested_up_version(): string
    {
        $plugin_data = $this->get_plugin_data();
        return $plugin_data['WC tested up to'] ?? '';
    }
    private function get_plugin_data(): array
    {
        if (empty(self::$plugin_data)) {
            if (!function_exists('get_plugin_data')) {
                require_once \ABSPATH . 'wp-admin/includes/plugin.php';
            }
            self::$plugin_data = get_plugin_data($this->get_plugin_file_path());
        }
        return self::$plugin_data;
    }
    public function get_woocommerce_version(): string
    {
        return $this->woocommerce->version;
    }
    private function get_major_version(string $version): string
    {
        return explode('.', $version)[0] ?? '';
    }
    private function get_minor_version(string $version): string
    {
        return explode('.', $version)[1] ?? '';
    }
}
