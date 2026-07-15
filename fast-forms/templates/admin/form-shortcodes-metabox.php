<?php
/**
 * Sidebar shortcodes metabox on form edit screen.
 *
 * @package FastForms
 *
 * @var \WP_Post $post Edytowany formularz.
 */

use Weblo\FastForms\FormBuilder\FormSettingsStorage;
use Weblo\FastForms\Frontend\ShortcodeAttributes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$form_settings     = FormSettingsStorage::get_all( $post->ID );
$form_opts         = $form_settings['form'] ?? array();
$form_id           = (int) $post->ID;
$id_suffix         = '-metabox';
$compact           = true;
$shortcode_inline  = ShortcodeAttributes::build( $form_id, 'inline', $form_opts );
$shortcode_button  = ShortcodeAttributes::build( $form_id, 'button', $form_opts );
$shortcode_trigger = ShortcodeAttributes::build( $form_id, 'trigger', $form_opts );

include FF_PLUGIN_DIR . 'templates/admin/partials/form-shortcodes.php';
