<?php

$level_title = isset($level['nazwa']) && !empty($level['nazwa']) ? $level['nazwa'] : '';
$level_prog = isset($level['prog']) && !empty($level['prog']) ? (float)$level['prog'] : 0.00;
$level_products = isset($level['products']) && !empty($level['products']) ? $level['products'] : [];
$level_cross_sell_products = isset($level['crossSellProducts']) && !empty($level['crossSellProducts']) ? $level['crossSellProducts'] : [];
$level_id = isset($level['id']) && !empty($level['id']) ? $level['id'] : '';
$key = isset($key) && !empty($key) ? $key : 'Nowy poziom';
?>

<div class="gift-level" >
    <h2 class="level-title"><?php echo esc_html($key); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="gratis_nazwa">Nazwa</label>
            </th>
            <td>
                <input type="text" id="gratis_nazwa" name="gratis_nazwa" placeholder="Nazwa poziomu (opcjonalna)" class="regular-text level-title" value="<?php echo esc_attr($level_title); ?>">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="gratis_prog">Próg</label>
            </th>
            <td>
                <input type="number" id="gratis_prog" name="gratis_prog" class="regular-text level-prog" min="0" step="0.01" value="<?= esc_attr($level_prog); ?>">
                <p class="description">Wartość koszyka od której gratisy będą dostępne</p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="gratis_ilosc">Ile prezentów można wybrać</label>
            </th>
            <td>
                <input type="number" id="gratis_ilosc" name="gratis_ilosc" class="regular-text level-gifts-count" min="1" step="1" value="<?php echo isset($level['ilosc']) ? esc_attr($level['ilosc']) : 1; ?>">
                <p class="description">Liczba prezentów jaką klient może wybrać z tego poziomu</p>
            </td>
        </tr>
    </table>
    <div class="producty-template">
        <h4>Prezenty</h4>
        <div class="product-container">
            <?php
            foreach ($level_products as $product) {

                $product_id = isset($product['id']) && !empty($product['id']) ? $product['id'] : '';
                $product_name = isset($product['nazwa']) && !empty($product['nazwa']) ? $product['nazwa'] : '';
                $product_slogan = isset($product['slogan']) && !empty($product['slogan']) ? $product['slogan'] : '';
                $product_cena = isset($product['cena']) && !empty($product['cena']) ? $product['cena'] : '';
                $product_ilosc = isset($product['ilosc']) && !empty($product['ilosc']) ? $product['ilosc'] : 1;
               
                include plugin_dir_path(__FILE__) . '../templates/add-product-template.php';
            }
            ?>
        </div>
        <button type="button" id="add-product-to-gift-list" class="button add-product">Dodaj produkt</button>
    </div>
    <div class="cross-sell-products producty-template">
        <h4>Produkty cross-sell</h4>
        <div class="cross-sell-products-container">
            <?php
            foreach ($level_cross_sell_products as $product) {
                
                $product_id = isset($product['id']) && !empty($product['id']) ? $product['id'] : '';
                $product_discount_type = isset($product['discount_type']) && !empty($product['discount_type']) ? $product['discount_type'] : '';
                $product_discount_value = isset($product['discount_value']) && !empty($product['discount_value']) ? $product['discount_value'] : '';

                
                include plugin_dir_path(__FILE__) . '../templates/add-level-cross-sell-product-template.php';
            }
            ?>
        </div>
        <button type="button" id="add-cross-sell-product-to-gift-list" class="button add-cross-sell-product">Dodaj produkt cross-sell</button>
    </div>
    <div class="btns-wrap-right">
        <button type="button" id="remove-gift-level" class="button remove-gift-level">Usuń poziom</button>
    </div>

</div>