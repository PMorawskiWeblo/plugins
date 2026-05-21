<?php

/**
 * Layout builder metabox template.
 *
 * @package WooProductPersonalizer
 * @var string $config_json JSON config.
 */

defined('ABSPATH') || exit;
?>
<div class="wpp-layout-builder" data-config="<?php echo esc_attr($config_json); ?>">
    <p class="description">
        <?php esc_html_e('Configure canvas, image slots, and text fields. Use the visual builder or edit JSON directly.', 'woo-product-personalizer'); ?>
    </p>

    <div class="wpp-layout-builder__toolbar">
        <button type="button" class="button wpp-add-image-slot"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image-icon lucide-image">
                <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                <circle cx="9" cy="9" r="2" />
                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
            </svg> <?php esc_html_e('Add image slot', 'woo-product-personalizer'); ?></button>
        <button type="button" class="button wpp-add-text-field"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-type-icon lucide-type">
                <path d="M12 4v16" />
                <path d="M4 7V5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2" />
                <path d="M9 20h6" />
            </svg> <?php esc_html_e('Add text field', 'woo-product-personalizer'); ?></button>
        <button type="button" class="button wpp-toggle-all-cards" aria-pressed="false">
            <span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
            <span class="wpp-toggle-all-cards__label"><?php esc_html_e('Collapse all', 'woo-product-personalizer'); ?></span>
        </button>
    </div>

    <div class="wpp-layout-builder__workspace">
        <div class="wpp-layout-builder__editor">
            <div class="wpp-layout-builder__canvas-settings wpp-canvas-panel">
                <div class="wpp-canvas-panel__header">
                    <div class="wpp-canvas-panel__heading">
                        <span class="wpp-canvas-panel__icon dashicons dashicons-art" aria-hidden="true"></span>
                        <h4 class="wpp-canvas-panel__title"><?php esc_html_e('Canvas', 'woo-product-personalizer'); ?></h4>
                    </div>
                    <span class="wpp-canvas-panel__badge"><?php esc_html_e('Template size', 'woo-product-personalizer'); ?></span>
                </div>
                <div class="wpp-canvas-panel__body wpp-canvas-panel__body--split">
                    <div class="wpp-canvas-panel__col wpp-canvas-panel__col--meta">
                        <div class="wpp-canvas-field">
                            <label class="wpp-canvas-field__label" for="wpp-personalization-mode"><?php esc_html_e('Personalization layout', 'woo-product-personalizer'); ?></label>
                            <select id="wpp-personalization-mode" class="wpp-canvas-field__input wpp-personalization-mode">
                                <option value="layout_1"><?php esc_html_e('Layout personalization 1', 'woo-product-personalizer'); ?></option>
                                <option value="layout_2"><?php esc_html_e('Layout personalization 2 (crop after upload)', 'woo-product-personalizer'); ?></option>
                            </select>
                            <p class="wpp-canvas-media__hint"><?php esc_html_e('Mode 2 opens a crop window after the customer uploads a photo (zoom, cancel, select).', 'woo-product-personalizer'); ?></p>
                        </div>
                        <div class="wpp-canvas-field wpp-canvas-field--checkbox">
                            <label class="wpp-crop-mask-shape-row">
                                <input type="checkbox" class="wpp-crop-mask-shape" value="1" checked="checked" />
                                <?php esc_html_e('Crop area follows slot mask shape', 'woo-product-personalizer'); ?>
                            </label>
                            <p class="wpp-canvas-media__hint"><?php esc_html_e('In layout 2, the crop frame matches the mask image (requires a mask on the slot). Enabled by default.', 'woo-product-personalizer'); ?></p>
                        </div>
                        <span class="wpp-canvas-media__label"><?php esc_html_e('Background image', 'woo-product-personalizer'); ?></span>
                        <p class="wpp-canvas-media__hint"><?php esc_html_e('Base template shown behind photos (e.g. letter outlines).', 'woo-product-personalizer'); ?></p>
                        <div class="wpp-canvas-panel__grid">
                            <div class="wpp-canvas-field">
                                <label class="wpp-canvas-field__label" for="wpp-canvas-width"><?php esc_html_e('Width', 'woo-product-personalizer'); ?></label>
                                <input id="wpp-canvas-width" type="number" class="wpp-canvas-field__input wpp-canvas-width" min="100" step="1" />
                            </div>
                            <div class="wpp-canvas-field">
                                <label class="wpp-canvas-field__label" for="wpp-canvas-height"><?php esc_html_e('Height', 'woo-product-personalizer'); ?></label>
                                <input id="wpp-canvas-height" type="number" class="wpp-canvas-field__input wpp-canvas-height" min="100" step="1" />
                            </div>
                        </div>
                        <div class="wpp-canvas-field">
                            <label class="wpp-canvas-field__label" for="wpp-canvas-background"><?php esc_html_e('Image URL', 'woo-product-personalizer'); ?></label>
                            <input id="wpp-canvas-background" type="text" class="wpp-canvas-field__input wpp-canvas-background" placeholder="https://…" />
                        </div>
                    </div>
                    <div class="wpp-canvas-panel__col wpp-canvas-panel__col--media">
                        <div class="wpp-media-picker" data-target="background">
                            <div class="wpp-media-picker__toolbar">
                                <button type="button" class="wpp-builder-card__icon wpp-select-media" data-target="background"
                                    aria-label="<?php esc_attr_e('Select image', 'woo-product-personalizer'); ?>"
                                    title="<?php esc_attr_e('Select image', 'woo-product-personalizer'); ?>">
                                    <span class="dashicons dashicons-format-image" aria-hidden="true"></span>
                                </button>
                                <button type="button" class="wpp-builder-card__icon wpp-builder-card__icon--danger wpp-remove-media" data-target="background"
                                    aria-label="<?php esc_attr_e('Remove image', 'woo-product-personalizer'); ?>"
                                    title="<?php esc_attr_e('Remove image', 'woo-product-personalizer'); ?>">
                                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                </button>
                            </div>
                            <button type="button" class="wpp-media-picker__preview wpp-open-media wpp-media-preview wpp-bg-preview" data-target="background"
                                aria-label="<?php esc_attr_e('Open media library', 'woo-product-personalizer'); ?>"
                                data-empty-label="<?php esc_attr_e('Click to select background image', 'woo-product-personalizer'); ?>"></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wpp-layout-builder__slots wpp-slots-stack"></div>
            <div class="wpp-layout-builder__text-fields wpp-text-fields-stack"></div>
        </div>

        <aside class="wpp-layout-builder__live-preview">
            <div class="wpp-live-preview__header">
                <h4><?php esc_html_e('Live preview', 'woo-product-personalizer'); ?></h4>
                <button type="button" class="button wpp-preview-zoom-toggle" aria-pressed="false"
                    aria-label="<?php esc_attr_e('Zoom preview to 100%', 'woo-product-personalizer'); ?>"
                    title="<?php esc_attr_e('Zoom to 100%', 'woo-product-personalizer'); ?>">
                    <span class="dashicons dashicons-search" aria-hidden="true"></span>
                </button>
            </div>
            <p class="description wpp-live-preview__hint">
                <?php esc_html_e('Drag to move. Use corner handles to resize width and height. Click an element to edit its settings.', 'woo-product-personalizer'); ?>
            </p>
            <div id="wpp-admin-canvas-container" class="wpp-admin-canvas-container"></div>
        </aside>
    </div>

    <div class="wpp-layout-json-panel wpp-builder-card is-collapsed">
        <div class="wpp-builder-card__header">
            <button type="button" class="wpp-builder-card__toggle wpp-json-panel__toggle" aria-expanded="false"
                aria-label="<?php esc_attr_e('Toggle JSON configuration', 'woo-product-personalizer'); ?>">
                <span class="wpp-builder-card__title"><?php esc_html_e('JSON configuration', 'woo-product-personalizer'); ?></span>
                <span class="dashicons dashicons-arrow-up-alt2 wpp-builder-card__arrow" aria-hidden="true"></span>
            </button>
            <div class="wpp-builder-card__actions">
                <button type="button" class="wpp-builder-card__icon wpp-json-copy"
                    aria-label="<?php esc_attr_e('Copy JSON to clipboard', 'woo-product-personalizer'); ?>"
                    title="<?php esc_attr_e('Copy JSON', 'woo-product-personalizer'); ?>">
                    <span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
                </button>
            </div>
        </div>
        <div class="wpp-builder-card__body">
            <textarea name="wpp_layout_config" class="large-text code wpp-layout-json"
                rows="16"><?php echo esc_textarea($config_json); ?></textarea>
        </div>
    </div>
</div>