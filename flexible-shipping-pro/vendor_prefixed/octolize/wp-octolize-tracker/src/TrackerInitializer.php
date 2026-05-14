<?php

namespace FSProVendor\Octolize\Tracker;

use FSProVendor\Octolize\Tracker\DeactivationTracker\OctolizeReasonsFactory;
use FSProVendor\Octolize\Tracker\OptInNotice\OptInNotice;
use FSProVendor\Octolize\Tracker\OptInNotice\ShouldDisplay;
use FSProVendor\Octolize\Tracker\OptInNotice\ShouldDisplayAlways;
use FSProVendor\Octolize\Tracker\OptInNotice\ShouldDisplayAndConditions;
use FSProVendor\Octolize\Tracker\OptInNotice\ShouldDisplayGetParameterPresent;
use FSProVendor\Octolize\Tracker\OptInNotice\ShouldDisplayGetParameterValue;
use FSProVendor\Octolize\Tracker\OptInNotice\ShouldDisplayOrConditions;
use FSProVendor\Octolize\Tracker\OptInNotice\ShouldDisplayShippingMethodInstanceSettings;
use FSProVendor\WPDesk\PluginBuilder\Plugin\HookableCollection;
use FSProVendor\WPDesk\PluginBuilder\Plugin\HookableParent;
use FSProVendor\WPDesk\Tracker\Deactivation\PluginData;
use FSProVendor\WPDesk\Tracker\Deactivation\ReasonsFactory;
use FSProVendor\WPDesk\Tracker\Deactivation\TrackerFactory;
use FSProVendor\WPDesk\Tracker\PluginActionLinks;
/**
 * Can create complete tracker.
 */
class TrackerInitializer implements HookableCollection
{
    use HookableParent;
    /**
     * @var string
     */
    private $plugin_file;
    /**
     * @var string
     */
    private $plugin_slug;
    /**
     * @var string
     */
    private $plugin_name;
    /**
     * @var string
     */
    private $shop_url;
    /**
     * @var ShouldDisplay
     */
    private $should_display;
    /**
     * @var ReasonsFactory
     */
    private $reasons_factory;
    /**
     * @param string $plugin_file Plugin file.
     * @param string $plugin_slug Plugin slug.
     * @param string $plugin_name Plugin name.
     * @param string $shop_url Shop URL.
     * @param ShouldDisplay $should_display Should display.
     * @param ReasonsFactory|null $reasons_factory Reasons factory.
     */
    public function __construct(string $plugin_file, string $plugin_slug, string $plugin_name, string $shop_url, ShouldDisplay $should_display, ReasonsFactory $reasons_factory = null)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = $plugin_slug;
        $this->plugin_name = $plugin_name;
        $this->shop_url = $shop_url;
        $this->should_display = $should_display;
        $this->reasons_factory = $reasons_factory ?? new OctolizeReasonsFactory();
    }
    /**
     * Hooks.
     *
     * @return void
     */
    public function hooks()
    {
        $this->add_hookable(new SenderRegistrator($this->plugin_slug));
        $this->add_hookable(TrackerFactory::createCustomTracker(new PluginData($this->plugin_slug, $this->plugin_file, $this->plugin_name), null, null, null, $this->reasons_factory));
        $tracker_consent = new \FSProVendor\WPDesk_Tracker_Persistence_Consent();
        if (!$tracker_consent->is_active()) {
            $this->add_hookable(new OptInNotice($this->plugin_slug, $this->shop_url, $this->should_display));
        }
        $this->hooks_on_hookable_objects();
        // By returning empty array, we opt out from notice provided by wp-wpdesk-tracker.
        add_filter('wpdesk_tracker_notice_screens', fn() => []);
        add_action('plugins_loaded', [$this, 'init_tracker']);
    }
    /**
     * Creates Tracker.
     * All data will be sent to https://data.octolize.org
     *
     * @return void
     */
    public function init_tracker()
    {
        $tracker = apply_filters('wpdesk_tracker_instance', null);
    }
    /**
     * Creates tracker initializer from plugin info.
     *
     * @param \WPDesk_Plugin_Info $plugin_info .
     * @param ShouldDisplay       $should_display .
     * @param ReasonsFactory|null $reasons_factory .
     *
     * @return TrackerInitializer
     */
    public static function create_from_plugin_info(\FSProVendor\WPDesk_Plugin_Info $plugin_info, $should_display, ReasonsFactory $reasons_factory = null)
    {
        $shops = $plugin_info->get_plugin_shops();
        $shop_url = $shops[get_locale()] ?? $shops['default'] ?? 'https://octolize.com';
        return new self($plugin_info->get_plugin_file_name(), $plugin_info->get_plugin_slug(), $plugin_info->get_plugin_name(), $shop_url, $should_display ?? new ShouldDisplayAlways(), $reasons_factory);
    }
    /**
     * Creates tracker initializer from plugin info for shipping method.
     *
     * @param \WPDesk_Plugin_Info $plugin_info .
     * @param string              $shipping_method_id .
     * @param ReasonsFactory|null $reasons_factory .
     *
     * @return TrackerInitializer
     */
    public static function create_from_plugin_info_for_shipping_method(\FSProVendor\WPDesk_Plugin_Info $plugin_info, string $shipping_method_id, ReasonsFactory $reasons_factory = null)
    {
        $shops = $plugin_info->get_plugin_shops();
        $shop_url = $shops[get_locale()] ?? $shops['default'] ?? 'https://octolize.com';
        $should_display = new ShouldDisplayOrConditions();
        $should_display_and_conditions = new ShouldDisplayAndConditions();
        $should_display_and_conditions->add_should_diaplay_condition(new ShouldDisplayGetParameterValue('page', 'wc-settings'));
        $should_display_and_conditions->add_should_diaplay_condition(new ShouldDisplayGetParameterValue('tab', 'shipping'));
        $should_display_and_conditions->add_should_diaplay_condition(new ShouldDisplayGetParameterValue('section', $shipping_method_id));
        $should_display->add_should_diaplay_condition($should_display_and_conditions);
        $should_display_and_conditions = new ShouldDisplayAndConditions();
        $should_display_and_conditions->add_should_diaplay_condition(new ShouldDisplayGetParameterValue('page', 'wc-settings'));
        $should_display_and_conditions->add_should_diaplay_condition(new ShouldDisplayGetParameterValue('tab', 'shipping'));
        $should_display_and_conditions->add_should_diaplay_condition(new ShouldDisplayGetParameterPresent('instance_id'));
        $should_display_and_conditions->add_should_diaplay_condition(new ShouldDisplayShippingMethodInstanceSettings($shipping_method_id));
        $should_display->add_should_diaplay_condition($should_display_and_conditions);
        return new self($plugin_info->get_plugin_file_name(), $plugin_info->get_plugin_slug(), $plugin_info->get_plugin_name(), $shop_url, $should_display, $reasons_factory);
    }
}
