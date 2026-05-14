<?php

namespace FSProVendor\Octolize\PluginUpdateReminder;

use FSProVendor\WPDesk\Notice\Notice;
use FSProVendor\WPDesk\Notice\PermanentDismissibleNotice;
use FSProVendor\WPDesk\PluginBuilder\Plugin\Hookable;
class NoticeReminder implements Reminder, Hookable
{
    private ReminderData $reminder_data;
    public function create_reminder(ReminderData $reminder_data): void
    {
        $this->reminder_data = $reminder_data;
        $this->hooks();
    }
    public function hooks()
    {
        add_action('admin_notices', [$this, 'display_notice']);
    }
    public function display_notice()
    {
        if ($this->should_display_notice()) {
            if ($this->reminder_data->is_major_update()) {
                new PermanentDismissibleNotice($this->reminder_data->get_major_reminder_content(), $this->get_notice_name(), Notice::NOTICE_TYPE_ERROR);
            } elseif ($this->reminder_data->is_minor_update()) {
                new PermanentDismissibleNotice($this->reminder_data->get_minor_reminder_content(), $this->get_notice_name(), Notice::NOTICE_TYPE_WARNING);
            }
        }
    }
    private function get_notice_name(): string
    {
        return 'oct-notice-reminder-' . basename($this->reminder_data->get_plugin_dir()) . $this->reminder_data->get_woocommerce_version() . $this->reminder_data->get_plugin_wc_tested_up_version();
    }
    private function should_display_notice(): bool
    {
        $current_screen = get_current_screen();
        return $current_screen->id !== 'site-health';
    }
}
