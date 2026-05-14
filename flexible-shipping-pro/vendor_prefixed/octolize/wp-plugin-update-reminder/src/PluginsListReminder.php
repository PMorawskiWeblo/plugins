<?php

namespace FSProVendor\Octolize\PluginUpdateReminder;

use FSProVendor\WPDesk\Notice\Notice;
use FSProVendor\WPDesk\PluginBuilder\Plugin\Hookable;
class PluginsListReminder implements Reminder, Hookable
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
        add_action('after_plugin_row_' . $this->reminder_data->get_plugin_file(), [$this, 'display_remainder_in_table_row'], 1, 3);
    }
    public function display_remainder_in_table_row($plugin_file, $plugin_data, $status)
    {
        if ($this->reminder_data->is_major_update()) {
            $this->display_reminder($this->reminder_data->get_major_reminder_content(\true), Notice::NOTICE_TYPE_ERROR);
        } elseif ($this->reminder_data->is_minor_update()) {
            $this->display_reminder($this->reminder_data->get_minor_reminder_content(\true), Notice::NOTICE_TYPE_WARNING);
        }
    }
    private function display_reminder(string $content, string $type): void
    {
        $id = basename($this->reminder_data->get_plugin_dir()) . '-oct-reminder';
        include __DIR__ . '/views/plugins-list-tr.php';
    }
}
