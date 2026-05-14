<?php

namespace FSProVendor\Octolize\PluginUpdateReminder;

use FSProVendor\WPDesk\PluginBuilder\Plugin\Hookable;
class SiteHealthReminder implements Reminder, Hookable
{
    private ReminderData $reminder_data;
    public function create_reminder(ReminderData $reminder_data): void
    {
        $this->reminder_data = $reminder_data;
        $this->hooks();
    }
    /**
     * @inheritDoc
     */
    public function hooks()
    {
        add_filter('site_status_tests', [$this, 'add_site_status_test']);
    }
    public function add_site_status_test($tests): array
    {
        if ($this->reminder_data->is_major_update() || $this->reminder_data->is_minor_update()) {
            $tests['direct'][$this->reminder_data->get_plugin_name()] = ['label' => basename($this->reminder_data->get_plugin_dir()), 'test' => [$this, 'do_site_health_test']];
        }
        return $tests;
    }
    public function do_site_health_test(): array
    {
        if ($this->reminder_data->is_major_update()) {
            return $this->get_results($this->reminder_data->get_major_reminder_content(), 'critical', 'blue');
        } elseif ($this->reminder_data->is_minor_update()) {
            return $this->get_results($this->reminder_data->get_minor_reminder_content(), 'recommended', 'blue');
        }
        return [];
    }
    private function get_results($description, $status, $color): array
    {
        $results = array('label' => __('You should update plugin', 'flexible-shipping-pro'), 'status' => $status, 'badge' => array('label' => __('Compatibility', 'flexible-shipping-pro'), 'color' => $color), 'description' => $description, 'actions' => '', 'test' => basename($this->reminder_data->get_plugin_dir()));
        return $results;
    }
}
