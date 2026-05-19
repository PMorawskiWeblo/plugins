<?php

/**
 * Transform toolbar partial.
 *
 * @package WooProductPersonalizer
 */

defined('ABSPATH') || exit;
?>
<div class="wpp-transform-toolbar" hidden>
    <span class="wpp-toolbar-group wpp-toolbar-group--move">
        <button type="button" class="button wpp-btn-move-left" data-action="left"
            title="<?php esc_attr_e('Move left', 'woo-product-personalizer'); ?>">←</button>
        <button type="button" class="button wpp-btn-move-right" data-action="right"
            title="<?php esc_attr_e('Move right', 'woo-product-personalizer'); ?>">→</button>
        <button type="button" class="button wpp-btn-move-up" data-action="up"
            title="<?php esc_attr_e('Move up', 'woo-product-personalizer'); ?>">↑</button>
        <button type="button" class="button wpp-btn-move-down" data-action="down"
            title="<?php esc_attr_e('Move down', 'woo-product-personalizer'); ?>">↓</button>
    </span>
    <span class="wpp-toolbar-group wpp-toolbar-group--image">
        <button type="button" class="button wpp-btn-zoom-in" data-action="zoom-in"
            title="<?php esc_attr_e('Zoom in', 'woo-product-personalizer'); ?>">+</button>
        <button type="button" class="button wpp-btn-zoom-out" data-action="zoom-out"
            title="<?php esc_attr_e('Zoom out', 'woo-product-personalizer'); ?>">−</button>
        <button type="button" class="button wpp-btn-rotate"
            data-action="rotate"><?php esc_html_e('Rotate', 'woo-product-personalizer'); ?></button>
        <button type="button" class="button wpp-btn-flip-h"
            data-action="flip-h"><?php esc_html_e('Flip H', 'woo-product-personalizer'); ?></button>
        <button type="button" class="button wpp-btn-flip-v"
            data-action="flip-v"><?php esc_html_e('Flip V', 'woo-product-personalizer'); ?></button>
        <button type="button" class="button wpp-btn-autofit"
            data-action="autofit"><?php esc_html_e('Auto-fit', 'woo-product-personalizer'); ?></button>
        <button type="button" class="button wpp-btn-reset"
            data-action="reset"><?php esc_html_e('Reset', 'woo-product-personalizer'); ?></button>
    </span>
    <span class="wpp-toolbar-group wpp-toolbar-group--text-font" hidden="hidden">
        <button type="button" class="button wpp-btn-font-size-in" data-action="font-size-in"
            title="<?php esc_attr_e('Increase font size', 'woo-product-personalizer'); ?>">A+</button>
        <button type="button" class="button wpp-btn-font-size-out" data-action="font-size-out"
            title="<?php esc_attr_e('Decrease font size', 'woo-product-personalizer'); ?>">A−</button>
    </span>
    <span class="wpp-toolbar-group wpp-toolbar-group--text-font-family" hidden="hidden">
        <label for="wpp-font-family-select"><?php esc_html_e('Font family', 'woo-product-personalizer'); ?></label>
        <select id="wpp-font-family-select" class="wpp-font-family-select"></select>
    </span>
</div>