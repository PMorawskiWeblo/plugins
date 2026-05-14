<?php
/**
 * Main plugin coordinator.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-requirements.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-hpos.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-i18n.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-plugin-links.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-admin-menu.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-settings.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-developer-settings.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-admin.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-debug-logger.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-widget-renderer.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-conversation-manager.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-chat-controller.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-rest.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-frontend.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/database/class-schema.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/database/class-migrations.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-index-builder.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-index-manager.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-index-worker.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-retriever.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-provider-manager.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-quota-manager.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-semantic-store.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/providers/interface-provider.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/providers/class-openai-provider.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/providers/class-openrouter-provider.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/providers/class-custom-provider.php';

class StoreGuide_AI_Plugin {
	/**
	 * Hook loader.
	 *
	 * @var StoreGuide_AI_Loader
	 */
	private $loader;

	/**
	 * Requirements service.
	 *
	 * @var StoreGuide_AI_Requirements
	 */
	private $requirements;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->loader       = new StoreGuide_AI_Loader();
		$this->requirements = new StoreGuide_AI_Requirements();
		$this->register_common_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	private function register_common_hooks() {
		$i18n        = new StoreGuide_AI_I18n();
		$hpos        = new StoreGuide_AI_HPOS();
		$links       = new StoreGuide_AI_Plugin_Links();
		$menu        = new StoreGuide_AI_Admin_Menu();
		$settings    = new StoreGuide_AI_Settings();
		$dev         = new StoreGuide_AI_Developer_Settings();
		$admin       = new StoreGuide_AI_Admin( $menu, $settings, $dev );
		$renderer    = new StoreGuide_AI_Widget_Renderer();
		$frontend    = new StoreGuide_AI_Frontend( $renderer );
		$session     = new StoreGuide_AI_Conversation_Manager();
		$retriever   = new StoreGuide_AI_Retriever();
		$providers   = new StoreGuide_AI_Provider_Manager();
		$quotas      = new StoreGuide_AI_Quota_Manager();
		$controller  = new StoreGuide_AI_Chat_Controller( $session, $retriever, $providers, $quotas );
		$rest        = new StoreGuide_AI_REST( $controller );
		$indexer     = new StoreGuide_AI_Index_Manager( new StoreGuide_AI_Index_Builder() );
		$index_worker = new StoreGuide_AI_Index_Worker( new StoreGuide_AI_Index_Builder() );
		$semantic    = new StoreGuide_AI_Semantic_Store();
		$migrations  = new StoreGuide_AI_Migrations( new StoreGuide_AI_Schema() );

		$this->loader->add_action( 'init', $i18n, 'load_textdomain' );
		$this->loader->add_action( 'init', $migrations, 'maybe_migrate' );
		$this->loader->add_action( 'before_woocommerce_init', $hpos, 'declare_compatibility' );
		$this->loader->add_filter( 'plugin_action_links_' . STOREGUIDE_AI_PLUGIN_BASENAME, $links, 'add_settings_link' );
		$this->loader->add_action( 'admin_notices', $this, 'render_requirement_notice' );
		$admin->register( $this->loader );
		$frontend->register( $this->loader );
		$indexer->register( $this->loader );
		$index_worker->register( $this->loader );
		$semantic->register( $this->loader );
		$this->loader->add_action( 'rest_api_init', $rest, 'register_routes' );
	}

	/**
	 * Display requirement notice.
	 *
	 * @return void
	 */
	public function render_requirement_notice() {
		$error = $this->requirements->validate();
		if ( true === $error ) {
			return;
		}

		if ( is_wp_error( $error ) ) {
			$message = $this->requirements->get_error_message( $error->get_error_code() );
			echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Run plugin.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}
}
