<?php
$samples_on = get_field('samples_on', 'option');
$plus_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
  <path d="M0 7H14M7 0V14" stroke="#47170D" stroke-width="1.5" stroke-linejoin="round"/>
</svg>';
if ($samples_on && have_rows('samples', 'option')) : ?>
    <div class="samples-box" id="samples">
        <div class="samples-box-header">
            <h4 class="samples-box-title"><?= __('Select samples for your order', 'gift-in-cart-plugin') ?> </h4>
            <p class="samples-box-description">
                <?= __('Choose a sample set and add it to your cart with one click.', 'gift-in-cart-plugin') ?> </p>
        </div>
        <div class="samples-box-body">
            <?php while (have_rows('samples', 'option')) : the_row();
                $sample_name = get_sub_field('sample_name');
                $sample_slogan = get_sub_field('sample_slogan');
                $sample_price = get_sub_field('sample_price');
                $sample_product = get_sub_field('sample_product');
            ?>

                <?php if ($sample_product) : ?>
                    <?php
                    if (is_object($sample_product)) {
                        $product_id = $sample_product->ID;
                    } elseif (is_array($sample_product)) {
                        $product_id = isset($sample_product['ID']) ? $sample_product['ID'] : (isset($sample_product['id']) ? $sample_product['id'] : $sample_product);
                    } else {
                        $product_id = intval($sample_product);
                    }

                    $product = wc_get_product($product_id);
                    if (!$product) {
                        continue;
                    }
                    ?>
                    <div class="sample-item" data-sample-product-id="<?php echo esc_attr($product_id); ?>"
                        data-sample-name="<?php echo esc_attr($sample_name); ?>"
                        data-sample-price="<?php echo esc_attr($sample_price); ?>"
                        data-sample-slogan="<?php echo esc_attr($sample_slogan); ?>">
                        <div class="sample-content d-flex">
                            <div class="sample_content_left">
                                <img src="<?php echo get_the_post_thumbnail_url($product_id); ?>"
                                    alt="<?php echo esc_attr($sample_name); ?>">
                            </div>
                            <div class="sample_content_right d-flex flex-row">
                                <div class="sample_content_right_text d-flex flex-column">
                                    <h5 class="sample-item-name"><?php echo esc_html($sample_name); ?></h5>
                                    <?php if ($sample_slogan) : ?>
                                        <p class="sample-item-slogan"><?php echo esc_html($sample_slogan); ?></p>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="add-sample-to-cart" data-action="add-sample"
                                    data-product-id="<?php echo esc_attr($product_id); ?>"
                                    data-sample-name="<?php echo esc_attr($sample_name); ?>"
                                    data-sample-price="<?php echo esc_attr($sample_price); ?>"
                                    data-sample-slogan="<?php echo esc_attr($sample_slogan); ?>">

                                    <span class="plus-icon"><?php echo $plus_icon; ?></span>
                                    <div class="wrap_btn_text d-flex flex-column">
                                        <span class="sample-button-text"><?= __('Add', 'gift-in-cart-plugin') ?></span>
                                        <span class="sample-button-price"><?= __('for', 'gift-in-cart-plugin') ?>
                                            <?php
                                            // Formatuj cenę - jeśli nie ma części dziesiętnej, wyświetl bez .00
                                            $formatted_price = wc_price($sample_price);
                                            // Usuń .00 z końca jeśli cena jest całkowita
                                            if (is_numeric($sample_price) && (float)$sample_price == (int)$sample_price) {
                                                $formatted_price = str_replace('.00', '', $formatted_price);
                                            }
                                            echo $formatted_price;
                                            ?></span>
                                    </div>
                                </button>
                            </div>

                        </div>

                    </div>
                <?php endif; ?>
            <?php endwhile; ?>
        </div>
    </div>
<?php endif; ?>