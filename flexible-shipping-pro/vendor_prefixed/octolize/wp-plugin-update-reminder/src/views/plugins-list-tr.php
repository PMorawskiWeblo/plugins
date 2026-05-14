<?php

namespace FSProVendor;

/**
 * Plugin update reminder notice.
 *
 * @package Octolize\PluginUpdateReminder
 *
 * @var string $id
 * @var string $content
 * @var string $type
 */
?>
<tr class="plugin-update-tr active" id="<?php 
echo \esc_attr($id);
?>">
	<td colspan="10" class="plugin-update colspanchange" style="box-shadow: none;">
		<div class="oct-plugin-update-reminder-notice notice inline notice-alt notice-<?php 
echo \esc_html($type);
?>">
			<p><?php 
echo \wp_kses_post($content);
?></p>
		</div>
	</td>
</tr>
<?php 
