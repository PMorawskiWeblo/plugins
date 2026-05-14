<?php

$is_progress_bar_visible = get_option('cgm_is_progress_bar_visible', false) === 'true' || get_option('cgm_is_progress_bar_visible', false) === true || get_option('cgm_is_progress_bar_visible', false) === '1' || get_option('cgm_is_progress_bar_visible', false) === 1;

$cart_gifts_levels = get_option('cgm_cart_gifts_levels', array());
$cart_gifts_levels_count = count($cart_gifts_levels);
$progress_bar_title = __('Match products and receive maximum benefits!', 'custom-cart-gifts-modal');

$custom_cart = new CustomCart();
$cart_value = $custom_cart->get_cart_total_value(true);

$cart_value = round($cart_value, 2);

$active_level = 0;

foreach ($cart_gifts_levels as $index => $level) {
    if ($cart_value >= $level['prog']) {
        $active_level = $index + 1;
    }
}

$remaining_to_next_level = 0;

if ($active_level < count($cart_gifts_levels)) {
    $next_level = $cart_gifts_levels[$active_level];
    $remaining_to_next_level = $next_level['prog'] - $cart_value;
} else {
    $remaining_to_next_level = 0; // Osiągnięto maksymalny poziom
}



if ($is_progress_bar_visible && count($cart_gifts_levels) > 0) {
?>
    <style>
        .gifts-progress-bar-inner {
            display: grid;
            width: 100%;
            grid-template-columns: 20px repeat(<?php echo $cart_gifts_levels_count - 1; ?>, 1fr);
        }

        @media (max-width: 576px) {
            .gifts-progress-bar-inner {
                grid-template-columns: 20px repeat(<?php echo $cart_gifts_levels_count - 1; ?>, 1fr);
            }
        }
    </style>

    <div class="gifts-progress-bar">


        <?php
        if ($progress_bar_title) {
        ?>
            <p class="gifts-progress-bar-title"><?php echo $progress_bar_title; ?></p>
        <?php
        }
        ?>
       
        <div class="gifts-progress-bar-inner">

            <?php
            foreach ($cart_gifts_levels as $index => $level) {

                $progress_min = $index === 0 ? 0 : $cart_gifts_levels[$index - 1]['prog'];

                $progress_max = $level['prog'];
                $gifts_count = $level['ilosc'];
                $products = $level['products'];

                $progress_percentage = 0;
                if ($cart_value >= $progress_min) {
                    if ($cart_value >= $progress_max) {
                        $progress_percentage = 100;
                    } else {
                        $range = $progress_max - $progress_min;
                        $value_in_range = $cart_value - $progress_min;
                        $progress_percentage = ($value_in_range / $range) * 100;
                    }
                }
                $progress_percentage_style = 'background-size: ' . $progress_percentage . '%; width: ' . $progress_percentage . '%;';
            ?>
                <?php
                $style = '<style>.section-' . $index . ' .progress-line {' . $progress_percentage_style . '}</style>';
                echo $style;
                ?>
                <div class="gift-progress-section section-<?php echo $index; ?> <?= $index == 0 ? 'first' : '' ?>">
                    <!-- <?php
                            if ($index === 0) {
                                echo '<div class="gift-progress-zero">0</div>';
                            }
                            ?> -->
                    <div class="gift-progress-section-title <?= $index == 0 ? 'first' : '' ?> <?= $index == $cart_gifts_levels_count - 1 ? 'last' : '' ?>"><?php
                                                                                                                                                            if (isset($level['nazwa']) && !empty($level['nazwa'])) {
                                                                                                                                                                echo $level['nazwa'];
                                                                                                                                                            } else {
                                                                                                                                                                echo __('Level ', 'custom-cart-gifts-modal') . ' ' . $index + 1;
                                                                                                                                                            }
                                                                                                                                                            ?></div>
                    <div class="progress-line"></div>
                    <div class="progress-line-track"></div>
                    <div class="gift-progress-max-value <?= $index == $cart_gifts_levels_count - 1 ? 'last' : '' ?> <?= $progress_percentage == 100 ? 'active' : 'inactive' ?>">
                        <?php
                        if ($progress_percentage == 100) {
                        ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z" />
                            </svg>
                        <?php
                        } else {
                        ?>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 8L8 16M8 8L16 16" stroke="#3A3533" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        <?php
                        }
                        ?>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
        <?php
        if ($remaining_to_next_level > 0) {
        ?>
            <p class="gifts-progress-bar-remaining">
                <?= __('Remaining to next level: ', 'custom-cart-gifts-modal'); ?> <span><?php echo wc_price($remaining_to_next_level); ?></span>
            </p>
        <?php
        }
        ?>
    </div>

<?php
}
?>