<?php
/**
 * Plugin uninstall handler.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

delete_option('ffmm_settings');
