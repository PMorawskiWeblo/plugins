<?php 
$levels = get_gift_levels();
$sumakoszyka = get_gift_cart_total();

$nextbrak = 0;
foreach ($levels as $key => $level) {
    if ($level['prog'] > $sumakoszyka && $nextbrak == 0) {
        $nextbrak = $level['prog'] - $sumakoszyka;
        $nextname = $level['nazwa'];
        $nextnamebrak = $key;
        break;
    }
    $maxprog = $level['prog'];
}

if($key==0){

?>

<div class="gift-level-missing-counter">
    <img src="<?php echo plugin_dir_url(__FILE__) . '../img/gift.png';?>">
    Dodaj <b><?php echo $nextbrak;?></b> produkt(y) więcej, aby otrzymać nagrodę. <a href="#nagrody">Zobacz nagrody</a>

</div>
<?php } else if(!is_gift_in_cart()){?>

    <?php } ?>