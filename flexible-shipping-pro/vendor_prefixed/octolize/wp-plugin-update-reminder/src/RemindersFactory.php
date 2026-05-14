<?php

namespace FSProVendor\Octolize\PluginUpdateReminder;

use FSProVendor\WPDesk\PluginBuilder\Plugin\Hookable;
class RemindersFactory implements Hookable
{
    public const REMINDERS = [PluginsListReminder::class, SiteHealthReminder::class, NoticeReminder::class];
    private string $plugin_dir;
    private string $plugin_file;
    private string $plugin_name;
    private array $reminders = [];
    public function __construct(string $plugin_dir, string $plugin_file, string $plugin_name, array $reminders = RemindersFactory::REMINDERS)
    {
        $this->plugin_dir = $plugin_dir;
        $this->plugin_file = $plugin_file;
        $this->plugin_name = $plugin_name;
        $this->reminders = $reminders;
    }
    public function hooks()
    {
        add_action('admin_init', [$this, 'create_reminders']);
    }
    public function create_reminders()
    {
        if (!function_exists('WC')) {
            return;
        }
        $reminder_data = new ReminderData($this->plugin_dir, $this->plugin_file, $this->plugin_name, WC());
        foreach ($this->reminders as $reminder) {
            $reminder = new $reminder();
            if ($reminder instanceof Reminder) {
                $reminder->create_reminder($reminder_data);
            }
        }
    }
}
