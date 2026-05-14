<?php



$cart_gifts_levels = get_option('cgm_cart_gifts_levels', array());
$cart_gifts_levels_count = count($cart_gifts_levels);
$cart_gifs_show = filter_var(get_option('cgm_show_cart_gifts', false), FILTER_VALIDATE_BOOLEAN);
$active_gifts_display = get_option('cgm_active_gifts_display', 'only_active_level');

$cart_value = 0;
if (class_exists('CustomCart')) {
    $cart_total = new CustomCart();
    $cart_value = $cart_total->get_cart_total_value(true);
} else {
    $cart = WC()->cart;
    $subtotal_with_tax = $cart->get_subtotal() + $cart->get_subtotal_tax();
    $cart_value = $subtotal_with_tax;
}
$cart_value = round($cart_value, 2);

$active_level_index = -1;

foreach ($cart_gifts_levels as $index => $level) {
    if ((float) $cart_value >= (float) $level['prog']) {

        $active_level_index = $index;
    }
}

if ($cart_gifs_show && count($cart_gifts_levels) > 0) {
    ?>
    <div class="gifts-templates">
        <div class="gifts-templates-inner">
            <div class="gifts-templates-title"><?= __('Select gifts', 'custom-cart-gifts-modal'); ?> 🎁</div>
            <?php include plugin_dir_path(__FILE__) . 'gifts-progress-bar.php'; ?>
        </div>
        <?php
        foreach ($cart_gifts_levels as $index => $level) {

            $is_level_active = false;
            $level_index = $index;

            if ($active_level_index == $index) {
                $is_level_active = true;
            } else if ($active_gifts_display == 'active_and_prev' && $active_level_index > $index) {
                $is_level_active = true;
            }
            ?>
            
            <div class="gifts-level gift-level-<?php echo $index; ?>">
                <div class="gifts-level-title <?php echo $is_level_active ? 'active' : 'inactive'; ?>"
                    data-level="<?php echo $index; ?>">
                    <span>
                        <?php
                        if (isset($level['nazwa']) && !empty($level['nazwa'])) {
                            echo $level['nazwa'];
                        } else {
                            echo __('Level ', 'custom-cart-gifts-modal') . ' ' . $index + 1;
                        }
                        ?>
                        <?php
                        if ($cart_value < $level['prog']) {
                            $remaining = $level['prog'] - $cart_value;
                            ?>
                            <span class="remaining-amount">(<?= __('Remaining: ', 'custom-cart-gifts-modal'); ?>
                                <?php echo wc_price($remaining); ?>)</span>
                            <?php
                        }
                        ?>
                        <?php
                        if ($is_level_active) {
                            ?>
                            <b style="font-weight: 300;" class="show-level-text">
                                <?= __(' - choose a gift:', 'custom-cart-gifts-modal'); ?>
                            </b>
                            <?php
                        }
                        ?>
                    </span>
                    <span class="show-level <?php echo $is_level_active ? 'active' : ''; ?>"><svg width="14" height="8"
                            viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path id="Vector 46" d="M1 1L7 7L13 1" stroke="#ffffff" />
                        </svg>
                    </span>
                </div>
                <div class="gifts-level-items <?php echo $is_level_active ? 'active show' : 'inactive'; ?>"
                    data-level="<?php echo $index; ?>">
                    <div class="gifts-level-items-splide splide">
                        <div class="splide__track">
                            <ul class="splide__list">
                                <?php
                                foreach ($level['products'] as $gift_product) {
                                    ?>
                                    <li class="splide__slide">
                                        <?php include plugin_dir_path(__FILE__) . 'gift-template.php'; ?>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>

        <div class="shipping-notice">
            <p><?php echo __('* Shipping costs are not included in gift thresholds calculation.', 'custom-cart-gifts-modal'); ?>
            </p>
        </div>
    </div>
    <?php
}
?>