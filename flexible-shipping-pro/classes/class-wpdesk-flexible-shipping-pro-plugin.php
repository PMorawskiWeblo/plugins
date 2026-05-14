<?php
/**
 * Plugin.
 *
 * @package Flexible Shipping PRO
 */

use FSProVendor\Octolize\BetterDocs\Beacon\BeaconOptions;
use FSProVendor\Octolize\BetterDocs\Beacon\BeaconPro;
use FSProVendor\Octolize\Onboarding\PluginUpgrade\PluginUpgradeMessage;
use FSProVendor\Octolize\Onboarding\PluginUpgrade\PluginUpgradeOnboardingFactory;
use FSProVendor\Octolize\PluginUpdateReminder\RemindersFactory;
use FSProVendor\Octolize\Tracker\OptInNotice\ShouldDisplayNever;
use FSProVendor\Octolize\Tracker\TrackerInitializer;
use FSProVendor\Psr\Log\LoggerInterface;
use FSProVendor\Psr\Log\NullLogger;
use FSProVendor\WPDesk\FS\Compatibility\PluginCompatibility;
use FSProVendor\WPDesk\Notice\Notice;
use FSProVendor\WPDesk\PluginBuilder\Plugin\AbstractPlugin;
use FSProVendor\WPDesk\PluginBuilder\Plugin\ActivationAware;
use FSProVendor\WPDesk\PluginBuilder\Plugin\HookableCollection;
use FSProVendor\WPDesk\PluginBuilder\Plugin\HookableParent;
use FSProVendor\WPDesk\PluginBuilder\Plugin\TemplateLoad;
use WPDesk\FS\TableRate\FreeShipping\FreeShippingNotice;
use WPDesk\FS\TableRate\FreeShipping\FreeShippingNoticeData;
use WPDesk\FSPro\Blocks\FreeShipping\AllowedBlocks;
use WPDesk\FSPro\Blocks\FreeShipping\SessionVariables;
use WPDesk\FSPro\ShippingMethod\TrackerData;
use WPDesk\FSPro\TableRate\DefaultRulesSettings;
use WPDesk\FSPro\TableRate\FreeShipping\FreeShippingCompatibilityNotice;
use WPDesk\FSPro\TableRate\FreeShipping\FreeShippingDisplayOnSettings;
use WPDesk\FSPro\TableRate\FreeShipping\FreeShippingNoticeAllowed;
use WPDesk\FSPro\TableRate\FreeShipping\FreeShippingNoticeDisplayDecision;
use WPDesk\FSPro\TableRate\FreeShipping\FreeShippingQuantityNoticeGenerator;
use WPDesk\FSPro\TableRate\ImportExport\Conditions\ExportData;
use WPDesk\FSPro\TableRate\ImportExport\Conditions\ImportData;
use WPDesk\FSPro\TableRate\Rule\Condition\ProductCategory\AjaxHandler;
use WPDesk\FSPro\TableRate\Rule\Condition\ProductCategory\CategoriesOptions;
use WPDesk\FSPro\TableRate\Rule\PreconfiguredScenarios\PreconfiguredScenariosPro;
use WPDesk\FSPro\TableRate\Rule\RuleConditions;
use WPDesk\FSPro\TableRate\Rule\SpecialActions;
use WPDesk\FSPro\TableRate\RuleCost\RuleAdditionalCostHooks;
use WPDesk\FSPro\TableRate\RuleSettingsConverter;
use WPDesk\FSPro\TableRate\RulesTableSettings;
use WPDesk\FSPro\TableRate\ShippingMethod\CalculatedCost;
use WPDesk\FSPro\TableRate\ShippingMethod\CalculationFunction;
use WPDesk\FSPro\TableRate\ShippingMethod\FreeShippingCalculatorCallback;
use WPDesk\FSPro\TableRate\ShippingMethod\ShippingContentsFilter;
use WPDesk\FSPro\TableRate\WeightCalculationSettings;
use WPDesk\FSPro\ShippingMethod\MethodTimestamps;

/**
 * Plugin.
 */
class WPDesk_Flexible_Shipping_Pro_Plugin extends AbstractPlugin implements HookableCollection, ActivationAware {

	use HookableParent;
	use TemplateLoad;

	const HOOK_PRIORITY_AFTER_DEFAULT = 11;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $flexible_shipping_plugin_version = '0.0';

	/**
	 * @var bool
	 */
	private $is_plugin_licence_activated = false;

	/**
	 * WPDesk_Flexible_Shipping_Pro_Plugin constructor.
	 *
	 * @param FSProVendor\WPDesk_Plugin_Info $plugin_info Plugin info.
	 */
	public function __construct( FSProVendor\WPDesk_Plugin_Info $plugin_info ) {
		$this->plugin_info = $plugin_info;
		parent::__construct( $this->plugin_info );
		$this->init_logger();
		$this->init_tracker();
	}

	/**
	 * .
	 *
	 * @return void
	 */
	private function init_tracker() {
		$this->add_hookable( TrackerInitializer::create_from_plugin_info( $this->plugin_info, new ShouldDisplayNever() ) );
	}

	/**
	 * Initialize $this->logger
	 */
	private function init_logger() {
		$this->logger = new NullLogger();
	}

	/**
	 * Hooks.
	 */
	public function hooks() {
		parent::hooks();

		$plugin_compatibility = new PluginCompatibility();
		$plugin_compatibility->hooks();

		$this->add_hookable( new \WPDesk\FSPro\TableRate\Rule\Condition\Product\AjaxHandler() );
		$this->add_hookable( new \WPDesk\FSPro\TableRate\Rule\Condition\ProductTag\AjaxHandler() );

		add_action( 'flexible-shipping/core/initialized', [ $this, 'init_flexible_shipping_pro' ], self::HOOK_PRIORITY_AFTER_DEFAULT );

		add_action( 'flexible-shipping/core/initialized', [ $this, 'initialize_flexible_shipping_external_access' ] );

		add_action( 'init', [ $this, 'init_upgrade_onboarding' ] );

		$this->add_hookable( new RemindersFactory( $this->plugin_info->get_plugin_dir(), $this->plugin_info->get_plugin_file_name(), $this->plugin_info->get_plugin_name() ) );

		// Creation time.
		$this->add_hookable( new MethodTimestamps() );
		$this->add_hookable( new TrackerData() );

		$this->hooks_on_hookable_objects();
	}

	public function init_upgrade_onboarding() {
		$upgrade_onboarding = new PluginUpgradeOnboardingFactory(
			$this->plugin_info->get_plugin_name(),
			$this->plugin_info->get_version(),
			$this->plugin_info->get_plugin_file_name()
		);
		$upgrade_onboarding->add_upgrade_message(
			new PluginUpgradeMessage(
				'2.20.0',
				trailingslashit( $this->plugin_info->get_plugin_url() ) . 'vendor_prefixed/octolize/wp-onboarding/assets/images/icon-complex-solution.svg',
				__( 'New features in Flexible Shipping PRO!', 'flexible-shipping-pro' ),
				__( 'We’ve introduced a range of new conditions for calculating shipping costs, based on product field values and ranges, stock quantity, stock status, and calculated shipping cost. This allows you to create even more complex shipping scenarios, perfectly tailored to your store.', 'flexible-shipping-pro' ),
				'',
				''
			)
		);
		$upgrade_onboarding->add_upgrade_message(
			new PluginUpgradeMessage(
				'4.0.0',
				trailingslashit( $this->plugin_info->get_plugin_url() ) . 'vendor_prefixed/octolize/wp-onboarding/assets/images/icon-complex-solution.svg',
				__( 'Save time with AI', 'flexible-shipping-pro' ),
				sprintf(
					__( 'We’ve added an AI Assistant directly to Flexible Shipping plugin to make shipping configuration smarter and easier than ever. Just describe your shipping scenario in plain language — our AI will instantly generate a ready-to-use configuration for you. You can create brand new configurations or update your existing ones with just a few clicks.%1$sStart using the AI Assistant today and simplify your shipping setup!', 'flexible-shipping-pro' ),
					'<br /><br />'
				),
				'',
				''
			)
		);
		$upgrade_onboarding->create_onboarding();
	}

	/**
	 * Init base variables for plugin
	 */
	public function init_base_variables() {
		$this->plugin_url = $this->plugin_info->get_plugin_url();

		$this->plugin_path   = $this->plugin_info->get_plugin_dir();
		$this->template_path = $this->plugin_info->get_text_domain();

		$this->plugin_namespace   = $this->plugin_info->get_text_domain();
	}

	/**
	 * @param ExternalPluginAccess $external_plugin_access .
	 */
	public function initialize_flexible_shipping_external_access( $external_plugin_access ) {
		$this->flexible_shipping_plugin_version = $external_plugin_access->get_plugin_version();
		$this->logger = $external_plugin_access->get_logger();
		if ( version_compare( $this->flexible_shipping_plugin_version, '4.5', '<' ) ) {
			add_action(
				'admin_init',
				function () {
					new Notice(
						sprintf(
						// Translators: plugins url.
							__( 'A new Flexible Shipping version introducing the helpful rules table hints and tooltips is available. %1$sUpdate now%2$s', 'flexible-shipping-pro' ),
							'<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">',
							'</a>'
						)
					);
				}
			);
		}
	}

	/**
	 * .
	 */
	public function init_flexible_shipping_pro() {
		$fs = new WPDesk_Flexible_Shipping_Pro_FS_Hooks();

		$woocommerce_form_fields = new WPDesk_Flexible_Shipping_Pro_Woocommerce_Form_Field();
		$woocommerce_form_fields->hooks();

		if ( 'pl_PL' !== get_locale() ) {
			$beacon = new BeaconPro(
				( new BeaconOptions( [ 14 ] ) ),
				new WPDesk_Flexible_Shipping_Pro_Plugin_Beacon_Should_Show_Strategy(),
				$this->get_plugin_url() . 'vendor_prefixed/octolize/wp-betterdocs-beacon/assets/'
			);
			$beacon->hooks();
		}

		$this->init_calculated_shipping_costs();

		$rule_hooks = new RuleAdditionalCostHooks();
		$rule_hooks->hooks();

		if ( class_exists( 'WPDesk\FS\TableRate\Rule\Condition\AbstractCondition' ) ) {
			$categories_options = new CategoriesOptions();

			$rule_conditions = new RuleConditions( $categories_options );
			$rule_conditions->hooks();

			$rule_conditions = new AjaxHandler( $categories_options );
			$rule_conditions->hooks();
		}

		if ( class_exists( 'WPDesk\FS\TableRate\ImportExport\Conditions\AbstractExportData' ) ) {
			( new ExportData() )->hooks();
			( new ImportData() )->hooks();
		}

		$special_actions = new SpecialActions();
		$special_actions->hooks();

		$free_shipping_allowed = new FreeShippingNoticeAllowed();
		$free_shipping_allowed->hooks();

		$cart    = WC()->cart;
		$session = WC()->session;
		if ( defined( 'FLEXIBLE_SHIPPING_VERSION' ) && version_compare( FLEXIBLE_SHIPPING_VERSION, '4.24.0', '<' ) ) {
			if ( $cart instanceof WC_Cart && $session instanceof WC_Session ) {
				( new FreeShippingQuantityNoticeGenerator( $cart, $session, FreeShippingQuantityNoticeGenerator::FS_FREE_SHIPPING_NOTICE_NAME ) )->hooks();
			}
		} else {
			( new FreeShippingQuantityNoticeGenerator( null, null, FreeShippingQuantityNoticeGenerator::FS_FREE_SHIPPING_NOTICE_NAME ) )->hooks();
		}
		if ( class_exists( FreeShippingNoticeData::class ) && $cart instanceof WC_Cart && $session instanceof WC_Session ) {
			( new FreeShippingNotice( $cart, $session, FreeShippingQuantityNoticeGenerator::FS_FREE_SHIPPING_NOTICE_NAME ) )->hooks();
			( new FreeShippingNoticeDisplayDecision() )->hooks();
		}
		( new FreeShippingCompatibilityNotice() )->hooks();

		( new SessionVariables() )->hooks();
		( new AllowedBlocks() )->hooks();

		$rule_settings_hooks = new RuleSettingsConverter();
		$rule_settings_hooks->hooks();

		$default_rule_settings = new DefaultRulesSettings();
		$default_rule_settings->hooks();

		$rules_table_settings = new RulesTableSettings( $this->is_active() );
		$rules_table_settings->hooks();

		$shipping_contents = new ShippingContentsFilter();
		$shipping_contents->hooks();

		( new WeightCalculationSettings() )->hooks();

		$calculation_function = new CalculationFunction();
		$calculation_function->hooks();

		$calculated_cost = new CalculatedCost();
		$calculated_cost->hooks();

		$free_shipping_calculator = new FreeShippingCalculatorCallback();
		$free_shipping_calculator->hooks();

		$predefined_scenarios = new PreconfiguredScenariosPro();
		$predefined_scenarios->hooks();
	}

	private function init_calculated_shipping_costs() {
		add_filter(
			'flexible_shipping_pro/calculated_shipping_cost/available',
			function () {
				return version_compare( FLEXIBLE_SHIPPING_VERSION, '5.1.0', '>=' );
			}
		);
		add_action(
			'admin_notices',
			function () {
				if ( ! apply_filters( 'flexible_shipping_pro/calculated_shipping_cost/available', true ) ) {
					( new Notice(
						sprintf(
						// Translators: %1$s and %2$s are placeholders for the link to the Flexible Shipping plugin update.
							__( 'Flexible Shipping PRO: To use conditions and additional costs based on shipping costs, it is necessary to %1$supdate the Flexible Shipping plugin%2$s.', 'flexible-shipping-pro' ),
							'<a href="' . esc_url( admin_url( 'plugins.php#flexible-shipping-update' ) ) . '">',
							'</a>'
						),
						Notice::NOTICE_TYPE_WARNING
					) )->showNotice();
				}
			}
		);
	}

	/**
	 * .
	 *
	 * @param mixed $links .
	 *
	 * @return array
	 */
	public function links_filter( $links ) {
		$docs_link    = get_locale() === 'pl_PL' ? 'https://octol.io/fs-docs-pl' : 'https://octol.io/fs-docs';
		$support_link = get_locale() === 'pl_PL' ? 'https://octol.io/fs-pro-support-pl' : 'https://octol.io/fs-pro-support';

		$plugin_links = [
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=flexible_shipping_info' ) . '">' . __( 'Settings', 'flexible-shipping-pro' ) . '</a>',
			'<a target="_blank" href="' . $docs_link . '">' . __( 'Docs', 'flexible-shipping-pro' ) . '</a>',
			'<a target="_blank" href="' . $support_link . '">' . __( 'Support', 'flexible-shipping-pro' ) . '</a>',
		];

		return array_merge( $plugin_links, $links );
	}

	/**
	 * @return void
	 */
	public function set_active() {
		$this->is_plugin_licence_activated = true;
	}

	/**
	 * @return bool
	 */
	public function is_active() {
		return $this->is_plugin_licence_activated;
	}
}
