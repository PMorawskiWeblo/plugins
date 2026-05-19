<?php
/**
 * Plugin action links.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class PluginActionLinks
 */
class PluginActionLinks {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter(
			'plugin_action_links_' . plugin_basename( WPP_PLUGIN_FILE ),
			array( $this, 'add_settings_link' )
		);
	}

	/**
	 * Add settings link.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$url = admin_url( 'admin.php?page=wpp-settings' );
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( $url ),
				esc_html__( 'Settings', 'woo-product-personalizer' )
			)
		);
		return $links;
	}
}
