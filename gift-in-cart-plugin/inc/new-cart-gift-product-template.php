<?php

$levels = get_gift_levels();
$sumakoszyka = get_gift_cart_total();
$products_count = get_eligible_products_count();

$nextbrak = 0;
$nextbrak_qty = 0;

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

$procent = isset($maxprog) ? ($sumakoszyka / $maxprog * 100) : 0;
$procent_qty = isset($maxprog_qty) ? ($products_count / $maxprog_qty * 100) : 0;

if ($procent < 100 || $procent_qty < 100) {
?>
    <div class="container">
        <div class="gift-box-product">
            <div class="row">
                <div class="col-left col-lg-4">
                    <?php if ($nextbrak != 0 && ($level['threshold_type'] == 'price' || $level['threshold_type'] == 'both')) { ?>
                        <div class="gift-level-missinginfo">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>../img/gift.png">
                            <?php echo __('Missing', 'gift-in-cart-plugin') . ' ' . wc_price($nextbrak) . ' ' . __('for gift', 'gift-in-cart-plugin') . ' ' . $nextname; ?>
                        </div>
                    <?php } ?>
                    <?php if ($nextbrak_qty != 0 && ($level['threshold_type'] == 'quantity' || $level['threshold_type'] == 'both')) { ?>
                        <div class="gift-level-missinginfo">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>../img/gift.png">
                            <?php echo __('Missing', 'gift-in-cart-plugin') . ' ' . $nextbrak_qty . ' ' . __('products for gift', 'gift-in-cart-plugin') . ' ' . $nextname_qty; ?>
                        </div>
                    <?php } ?>
                </div>
                <div class="col-middle col-lg-7">
                    <div class="gift-level-progress-info">
                        <?php if (isset($maxprog) && ($level['threshold_type'] == 'price' || $level['threshold_type'] == 'both')) { ?>
                            <div class="gift-level-progress-label"><?php _e('Price progress', 'gift-in-cart-plugin'); ?></div>
                            <div class="gift-level-progress-bar">
                                <div class="gift-level-progress-bar-filled" style="width:<?php echo $procent; ?>%"></div>
                            </div>
                        <?php } ?>
                        
                        <?php if (isset($maxprog_qty) && ($level['threshold_type'] == 'quantity' || $level['threshold_type'] == 'both')) { ?>
                            <div class="gift-level-progress-label"><?php _e('Quantity progress', 'gift-in-cart-plugin'); ?></div>
                            <div class="gift-level-progress-bar">
                                <div class="gift-level-progress-bar-filled" style="width:<?php echo $procent_qty; ?>%"></div>
                            </div>
                        <?php } ?>

                        <div class="gift-level-progress">
                            <div class="col"></div>
                            <?php foreach ($levels as $level) { 
                                $level_available = true;
                                
                                // Check threshold conditions
                                switch($level['threshold_type']) {
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
                            ?>
                                <div class="col">
                                    <?php if (!$level_available) { ?>
                                        <img alt="" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBpZD0iRWxsaXBzZSAxNiIgY3g9IjgiIGN5PSI4IiByPSI4IiBmaWxsPSIjRTY4RkIyIi8+Cjwvc3ZnPgo=" />
                                        <span><?php echo $level['nazwa']; ?></span>
                                        <?php if ($level['threshold_type'] == 'price' || $level['threshold_type'] == 'both') { ?>
                                            <div class="requirement"><?php echo wc_price($level['prog']); ?></div>
                                        <?php } ?>
                                        <?php if ($level['threshold_type'] == 'quantity' || $level['threshold_type'] == 'both') { ?>
                                            <div class="requirement"><?php echo $level['qty_prog'] . ' ' . __('products', 'gift-in-cart-plugin'); ?></div>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <img alt="" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgaWQ9IkFwcHJvdmVkIiBjbGlwLXBhdGg9InVybCgjY2xpcDBfMzVfMTM2NSkiPgo8cGF0aCBpZD0iVmVjdG9yIiBkPSJNOC4wMDA4NyAxNi4wMDE3QzEyLjQxOTYgMTYuMDAxNyAxNi4wMDE3IDEyLjQxOTYgMTYuMDAxNyA4LjAwMDg3QzE2LjAwMTcgMy41ODIxMSAxMi40MTk2IDAgOC4wMDA4NyAwQzMuNTgyMTEgMCAwIDMuNTgyMTEgMCA4LjAwMDg3QzAgMTIuNDE5NiAzLjU4MjExIDE2LjAwMTcgOC4wMDA4NyAxNi4wMDE3WiIgZmlsbD0iI0IwMkU2NiIvPgo8cGF0aCBpZD0iVmVjdG9yXzIiIGQ9Ik01IDcuOTI1MzdMNy41ODkyMyAxMC4wMTQ5TDExLjI2ODcgNSIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLW1pdGVybGltaXQ9IjEwIi8+CjwvZz4KPGRlZnM+CjxjbGlwUGF0aCBpZD0iY2xpcDBfMzVfMTM2NSI+CjxyZWN0IHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0id2hpdGUiLz4KPC9jbGlwUGF0aD4KPC9kZWZzPgo8L3N2Zz4K" />
                                        <span><b><?php echo $level['nazwa']; ?></b></span>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="col-right col-lg-1 d-lg-flex justify-content-lg-end">
                    <div id="close-gift-box-product">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M1 1L13 13" stroke="black" />
                            <path d="M13 1L0.999999 13" stroke="black" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>