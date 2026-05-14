<?php

$product_id = isset($gift_product['id']) ? $gift_product['id'] : '';
$product_name = isset($gift_product['nazwa']) ? $gift_product['nazwa'] : '';
$product_price = isset($gift_product['cena']) ? $gift_product['cena'] : '';
$product_slogan = isset($gift_product['slogan']) ? $gift_product['slogan'] : '';

$regular_price = 0;
$thumbnail = '';
$is_in_stock = false;

if ($product_id) {
    $product = wc_get_product($product_id);
    if ($product) {
        $regular_price = $product->get_regular_price();
        $thumbnail = $product->get_image('full');
        $is_in_stock = $product->is_in_stock();
        $product_name = $product->get_name();
    }
}

$is_in_cart = false;
$any_gift_selected = false;

foreach (WC()->cart->get_cart() as $cart_item) {
    if (isset($cart_item['is_gift']) && $cart_item['is_gift'] == true && $cart_item['level_index'] == $level_index) {
        if ($cart_item['product_id'] == $product_id) {
            $is_in_cart = true;
        }
        $any_gift_selected = true;
        break;
    }
}

if ($product) {
    $classes = array();
    
    if ($is_in_cart) {
        $classes[] = 'choosen';
    } else if ($any_gift_selected) {
        $classes[] = 'shade';
    }
    
    if (!$is_in_stock || !$is_level_active) {
        $classes[] = 'inactive';
    } else {
        $classes[] = 'active';
    }
    
    if ($is_in_stock) {
        $classes[] = ($is_level_active && !$is_in_cart) ? 'add-gift-to-cart' : 'remove-gift-from-cart';
    }

    $class_string = implode(' ', $classes);
?>
    <div
        data-level="<?php echo $level_index; ?>"
        data-is-gift="true"
        data-product-id="<?php echo $product_id; ?>"
        class="gift-view <?php echo $class_string; ?>">

        <?php if (!$is_in_stock) { ?>
            <div class="innactive-overlay">
                <div class="innactive-overlay-text">
                    <?= __('Out of stock', 'custom-cart-gifts-modal'); ?>
                </div>
            </div>
        <?php } ?>
        <div class="gift-image"><?php echo $thumbnail; ?></div>
        <div class="gift-info">
            <div class="left">
                <div class="gift-name"><?php echo $product_name; ?></div>
                <?php if ($product_slogan) { ?>
                    <div class="gift-slogan"><?php echo $product_slogan; ?></div>
                <?php } ?>
                <div class="gift-price"><del><?php echo wc_price($regular_price); ?></del> <?php echo wc_price($product_price); ?></div>

            </div>
            <div class="right">
                <?php if ($is_level_active && $is_in_stock) {
                    if (!$is_in_cart) {
                ?>
                        <div class="gift-add-to-cart">
                            <button data-level="<?php echo $level_index; ?>" data-product-id="<?php echo $product_id; ?>" data-is-gift="true" class="button add-gift-to-cart-btn"><?= __('+ Add', 'custom-cart-gifts-modal') ?></button>
                        </div>
                    <?php
                    } else {
                    ?>
                        <div class="gift-remove-from-cart">
                            <button data-level="<?php echo $level_index; ?>" data-product-id="<?php echo $product_id; ?>" data-is-gift="true" class="button remove-gift-from-cart-btn"><?= __('- Remove', 'custom-cart-gifts-modal') ?></button>
                        </div>
                    <?php
                    }
                    ?>
                <?php } ?>
            </div>
        </div>
    </div>
<?php
}
?>