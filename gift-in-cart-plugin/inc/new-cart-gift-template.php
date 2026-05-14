<?php

$levels = get_gift_levels();
$sumakoszyka = get_gift_cart_total();
$products_count = get_eligible_products_count();

// $required_coupon = get_field('required_coupon', 'option');

// $required_coupon_code = get_field('required_coupon_code', 'option');

$required_coupon = false;

$required_coupon_code = false;

$allowed = true;

if ($required_coupon) {

    $coupons = WC()->cart->get_applied_coupons();

    if (!in_array(strtolower($required_coupon_code), $coupons)) {

        $allowed = false;
    }
}

if ($allowed) {



    // Filtruj poziomy, aby pokazać tylko te, które są włączone
    $filtered_levels = array();
    foreach ($levels as $key => $level) {
        $level_number = $key + 1; // Zakładając, że poziomy są numerowane od 1
        $level_settings = get_level_settings($level_number);

        if ($level_settings['enabled']) {
            $filtered_levels[$key] = $level;
        }
    }

    // Zastąp oryginalną tablicę poziomów przefiltrowaną
    $levels = $filtered_levels;

    // Jeśli nie ma żadnych aktywnych poziomów, przerwij wykonanie
    if (empty($levels)) {
        return;
    }

    $nextbrak = 0;
    $nextbrak_qty = 0;
    $maxprog = 0;
    $maxprog_qty = 0;

    foreach ($levels as $level) {
        if ($level['threshold_type'] == 'price' || $level['threshold_type'] == 'both') {
            if ($level['prog'] > $sumakoszyka && $nextbrak == 0) {
                $nextbrak = $level['prog'] - $sumakoszyka;
                $nextname = $level['nazwa'];
            }
            $maxprog = $level['prog'];
        }
        if ($level['threshold_type'] == 'quantity' || $level['threshold_type'] == 'both') {
            if ($level['qty_prog'] > $products_count && $nextbrak_qty == 0) {
                $nextbrak_qty = $level['qty_prog'] - $products_count;
                $nextname_qty = $level['nazwa'];
            }
            $maxprog_qty = $level['qty_prog'];
        }
    }

    $procent = 0;
    $last_achieved_level = -1;
    $gift_icon_bar = '
<svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21" fill="none">
  <path d="M10.5 3.79688C11.6724 6.87864 14.1215 9.32732 17.2031 10.5C14.1216 11.6725 11.6725 14.1216 10.5 17.2031C9.32732 14.1215 6.87864 11.6724 3.79688 10.5C6.87875 9.32746 9.32746 6.87875 10.5 3.79688Z" stroke="#6D6059" stroke-width="1.5"/>
</svg>';
    // Find last achieved level for progress bar calculation
    foreach ($levels as $key => $level) {
        $level_available = false;
        switch ($level['threshold_type']) {
            case 'price':
                $level_available = ($level['prog'] <= $sumakoszyka);
                break;
            case 'quantity':
                $level_available = ($level['qty_prog'] <= $products_count);
                break;
            case 'both':
                $level_available = ($level['prog'] <= $sumakoszyka && $level['qty_prog'] <= $products_count);
                break;
        }
        if ($level_available) {
            $last_achieved_level = $key;
        }
    }

    // Calculate progress only if at least first level is achieved
    if ($last_achieved_level >= 0) {
        if ($last_achieved_level < count($levels) - 1) {
            // Calculate progress to next level
            $next_level = $levels[$last_achieved_level + 1];
            $current_value = ($next_level['threshold_type'] == 'quantity') ? $products_count : $sumakoszyka;
            $next_threshold = ($next_level['threshold_type'] == 'quantity') ? $next_level['qty_prog'] : $next_level['prog'];
            $prev_threshold = ($next_level['threshold_type'] == 'quantity') ? $levels[$last_achieved_level]['qty_prog'] : $levels[$last_achieved_level]['prog'];

            $procent = ($current_value - $prev_threshold) / ($next_threshold - $prev_threshold) * 100;
            $procent = min(100, max(0, $procent));
        } else {
            $procent = 100;
        }
    }
    if (isset($maxprog_qty) && $maxprog_qty > 0) {
        $procent_qty = $products_count / $maxprog_qty * 100;
    } else {
        $procent_qty = 0;
    }
    if (count($levels) > 0) {
?>

<div class="gift-box" id="nagrody">
    <div class="gift-box-header">
        <h4 class="gift-box-title">
            <?php _e("GET YOUR GIFT(S)!", "gift-in-cart-plugin"); ?>
        </h4>

        <?php
                if ($nextbrak != 0)
                    echo '<div class="gift-level-missinginfo"> ' . $gift_icon_bar . ' <span class="gift-level-missinginfo-text">' . __('Missing', 'gift-in-cart-plugin') . ' ' . wc_price($nextbrak) . ' ' . __('for gift', 'gift-in-cart-plugin') . ' ' . $nextname . '.</span></div>';

                if (count($levels) > 1) {
                    echo '<div class="gift-level-progress-info">';
                    echo '<div class="gift-level-progress-bar background">';

                    // Oblicz szerokość jednego segmentu (między poziomami)
                    $segment_width = 100 / (count($levels) - 1);

                    // Dodaj czarne wypełnienie dla osiągniętych poziomów
                    for ($i = 0; $i < count($levels) - 1; $i++) {
                        $current_level_available = false;
                        $next_level_available = false;

                        // Sprawdź dostępność bieżącego poziomu
                        switch ($levels[$i]['threshold_type']) {
                            case 'price':
                                $current_level_available = ($levels[$i]['prog'] <= $sumakoszyka);
                                break;
                            case 'quantity':
                                $current_level_available = ($levels[$i]['qty_prog'] <= $products_count);
                                break;
                            case 'both':
                                $current_level_available = ($levels[$i]['prog'] <= $sumakoszyka && $levels[$i]['qty_prog'] <= $products_count);
                                break;
                        }

                        // Sprawdź dostępność następnego poziomu
                        switch ($levels[$i + 1]['threshold_type']) {
                            case 'price':
                                $next_level_available = ($levels[$i + 1]['prog'] <= $sumakoszyka);
                                break;
                            case 'quantity':
                                $next_level_available = ($levels[$i + 1]['qty_prog'] <= $products_count);
                                break;
                            case 'both':
                                $next_level_available = ($levels[$i + 1]['prog'] <= $sumakoszyka && $levels[$i + 1]['qty_prog'] <= $products_count);
                                break;
                        }

                        // Jeśli oba poziomy są osiągnięte, wypełnij segment na czarno
                        if ($current_level_available && $next_level_available) {
                            $left_position = $i * $segment_width;
                            echo '<div class="gift-level-progress-bar-filled" style="left: ' . $left_position . '%; width: ' . $segment_width . '%"></div>';
                        }
                        // Jeśli tylko pierwszy poziom jest osiągnięty, pokaż częściowy postęp
                        elseif ($current_level_available && !$next_level_available && $i == $last_achieved_level) {
                            $left_position = $i * $segment_width;
                            $partial_width = $segment_width * ($procent / 100);
                            echo '<div class="gift-level-progress-bar-filled" style="left: ' . $left_position . '%; width: ' . $partial_width . '%"></div>';
                        }
                    }

                    echo '</div>';

                    echo '<div class="gift-level-progress">';
                    $i = 0;

                    foreach ($levels as $key => $level) {
                        $level_available = true;

                        // Najpierw sprawdź czy poziom wymaga kodu rabatowego
                        $level_number = $i + 1; // Zakładając, że poziomy są numerowane od 1
                        $level_settings = get_level_settings($level_number);
                        if (!empty($level_settings['required_coupon']) && !WC()->cart->has_discount($level_settings['required_coupon'])) {
                            $level_available = false;
                        }
                        // Następnie sprawdź pozostałe warunki
                        switch ($level['threshold_type']) {
                            case 'price':
                                $level_available = ($level['prog'] <= $sumakoszyka);
                                break;
                            case 'quantity':
                                $level_available = ($level['qty_prog'] <= $products_count);
                                break;
                            case 'both':
                                $level_available = ($level['prog'] <= $sumakoszyka && $level['qty_prog'] <= $products_count);
                                break;
                        }
                        echo '<div class="gift-level-progress-col col-gift-' . $key . ' ' . ($level_available ? 'gift-level-progress-col-available' : 'gift-level-progress-col-unavailable') . '">';




                        if (!$level_available) {
                            echo '<div class="wrap_ico"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
  <circle cx="10" cy="10" r="9.5" stroke="#47170D"/>
</svg></div>';
                            echo ' ' . $level['nazwa'];
                        } else {
                            echo '<div class="wrap_ico">
                            <img src="' . plugin_dir_url(__FILE__) . '../img/check.svg" width="20" height="20"
        alt="check" />
    </div>';
                            echo '<span>' . $level['nazwa'] . '</span> ';
                            $max = $i;
                        }
                        echo '
</div>';
                        $i = $i + 1;
                    }

                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';

                $showlevel = get_gift_level_in_cart();
                $j = 0;

                foreach ($levels as $key => $level) {
                    $class = '';
                    $nextbrak = 0;
                    $nextbrak_qty = 0;
                    $available = true;

                    // Najpierw sprawdź czy poziom wymaga kodu rabatowego
                    $level_number = $j + 1; // Zakładając, że poziomy są numerowane od 1
                    $level_settings = get_level_settings($level_number);
                    $level_settings['required_coupon'] = false; //to usunac
                    if (!empty($level_settings['required_coupon']) && !WC()->cart->has_discount($level_settings['required_coupon'])) {
                        $available = false;
                    }


                    // Następnie sprawdź pozostałe warunki
                    switch ($level['threshold_type']) {
                        case 'price':
                            if ($level['prog'] > $sumakoszyka) {
                                $nextbrak = $level['prog'] - $sumakoszyka;
                                $available = false;
                            }
                            break;
                        case 'quantity':
                            if ($level['qty_prog'] > $products_count) {
                                $nextbrak_qty = $level['qty_prog'] - $products_count;
                                $available = false;
                            }
                            break;
                        case 'both':
                            if ($level['prog'] > $sumakoszyka || $level['qty_prog'] > $products_count) {
                                $nextbrak = $level['prog'] > $sumakoszyka ? $level['prog'] - $sumakoszyka : 0;
                                $nextbrak_qty = $level['qty_prog'] > $products_count ? $level['qty_prog'] - $products_count : 0;
                                $available = false;
                            }
                            break;
                    }

                    $class = $available ? 'giftlevel-available' : 'giftlevel-unavailable hide';

                    $show = false;
                    if ($showlevel != '') {
                        if (strtolower($showlevel) == strtolower($level['nazwa']))
                            $show = true;
                    } else {
                        if ($key == 0)
                            $show = true;
                    }
                ?>
        <div class="giftlevel_hr"></div>
        <div class="giftlevel <?php echo $class; ?> giftlevel-<?php echo strtolower($level['nazwa']); ?>">
            <?php if (count($levels) > 1) { ?>
            <div class="giftlevel-name">
                <div class="giftlevel-name-text">
                    <span class="giftlevel-name-text"><?php echo $level['nazwa']; ?></span>
                    <span class="giftlevel-name-text-sub"> -
                        <?php
                                        if (!$available) {
                                            $missing_text = array();
                                            if ($nextbrak > 0 && ($level['threshold_type'] == 'price' || $level['threshold_type'] == 'both')) {
                                                $missing_text[] = wc_price($nextbrak);
                                            }
                                            if ($nextbrak_qty > 0 && ($level['threshold_type'] == 'quantity' || $level['threshold_type'] == 'both')) {
                                                $missing_text[] = $nextbrak_qty . ' ' . __('products', 'gift-in-cart-plugin');
                                            }
                                            echo __('missing', 'gift-in-cart-plugin') . ' ' . implode(' ' . __('and', 'gift-in-cart-plugin') . ' ', $missing_text) . ' ' . __('for gift', 'gift-in-cart-plugin');
                                        } else {
                                            echo _e('choose gift', 'gift-in-cart-plugin');
                                        }
                                        ?>
                    </span>
                </div>
                <div class="arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="8" viewBox="0 0 14 8" fill="none">
                        <path d="M0.530273 0.53125L6.53027 6.53125L12.5303 0.53125" stroke="#47170D" stroke-width="1.5"
                            stroke-linejoin="round" />
                    </svg>
                </div>
            </div>
            <?php } ?>
            <div
                class="wrap-gift-lvl <?php echo $available ? 'wrap-gift-lvl-available' : 'wrap-gift-lvl-unavailable wrap-gift-lvl-hidden'; ?>">
                <div class="giftlevel-products">
                    <?php
                                if (!isset($level['produkty']) || !is_array($level['produkty'])) {
                                    $level['produkty'] = array();
                                }
                                foreach ($level['produkty'] as $key => $produkt) {
                                    $product = wc_get_product($produkt['produkt']);

                                    // Skip if product doesn't exist or is out of stock
                                    if (!$product || !$product->is_in_stock()) {
                                        continue;
                                    }
                                ?>

                    <div class="giftlevel-products-product w-100 d-flex <?php if ($available) echo 'add-gift'; ?> <?php if (isset($produkt['incart']) && $available) { ?> selected_gift <?php } ?>"
                        data-giftid="<?php echo $produkt['id']; ?>" data-action="add-to-cart">

                        <div class="giftlevel-products-product-image d-flex align-items-center">
                            <?php if (key_exists('influ', $produkt)) { ?>
                            <div class="giftlevel-products-product-image-influ">
                                <img src="<?php echo plugin_dir_url(__FILE__); ?>../img/crown.png">
                                <?php _e("From influencer", "gift-in-cart-plugin"); ?>
                            </div>
                            <?php } ?>
                            <?php echo (get_the_post_thumbnail($product->get_id(), 'medium')); ?>
                        </div>
                        <div class="giftlevel-products-product-wrap-content d-flex flex-column col">
                            <div class="giftlevel-products-product-name">
                                <?php echo ($produkt['nazwa']); ?>
                            </div>
                            <div class="giftlevel-products-product-slogan">
                                <?= $produkt['slogan']; ?>
                            </div>
                            <?php if (get_field('hide_price', 'option') != 1) : ?>
                            <div class="giftlevel-products-product-price">
                                <span class="new-price">
                                    <?php echo (wc_price(safe_wmc_get_price($produkt['cena']))); ?>
                                </span>
                                <span class="old-price">
                                    <?php echo (wc_price($product->get_price())); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="giftlevel-products-product-action">
                            <?php if (isset($produkt['incart'])) { ?>
                            <div data-action="remove-from-cart"
                                data-itemkey="<?php echo esc_attr($produkt['incart']); ?>"
                                class="gift-btn <?php if ($available) echo 'remove-gift'; ?>">
                                <img src="<?php echo plugin_dir_url(__FILE__) . '../img/gift_check.svg'; ?>" width="20"
                                    height="20" alt="check" />
                            </div>
                            <?php } else { ?>
                            <div class="gift-btn">
                                <img src="<?php echo plugin_dir_url(__FILE__) . '../img/gift_not.svg'; ?>" width="20"
                                    height="20" alt="not selected" />
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
    <?php }
}
    ?>