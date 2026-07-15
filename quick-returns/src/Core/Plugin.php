<?php

namespace Weblo\QuickReturns\Core;

use Weblo\QuickReturns\Admin\MetaBoxes;
use Weblo\QuickReturns\Admin\SettingsPage;
use Weblo\QuickReturns\Frontend\AjaxHandler;
use Weblo\QuickReturns\Frontend\Shortcodes;
use Weblo\QuickReturns\Infrastructure\PostType\ReturnRequestPostType;

final class Plugin {

	private static ?self $instance = null;

	private bool $booted = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		load_plugin_textdomain( 'quick-returns', false, dirname( plugin_basename( QUICK_RETURNS_FILE ) ) . '/languages' );

		( new ReturnRequestPostType() )->register_hooks();
		( new Assets() )->register_hooks();
		( new Shortcodes() )->register_hooks();
		( new AjaxHandler() )->register_hooks();

		if ( is_admin() ) {
			( new SettingsPage() )->register_hooks();
			( new MetaBoxes() )->register_hooks();
		}
	}
}
