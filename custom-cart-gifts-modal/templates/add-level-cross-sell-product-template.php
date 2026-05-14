<?php

// Szablon używany do dynamicznego dodawania nowych produktów do listy gratisów
// Zawiera pola dla nazwy produktu, sloganu reklamowego, ceny oraz wyboru produktu z WooCommerce
// Używany przez JavaScript w panelu administracyjnym



// $product_id = isset($product['id']) ? $product['id'] : '';
// $product_discount_type = isset($product['discount_type'])  ? $product['discount_type'] : '';
// $product_discount_value = isset($product['discount_value']) ? $product['discount_value'] : '';


?>

<div class="cross-sell-product">

    <select name="product_id" class="regular-text product-id">
        <option value="">Wybierz produkt cross-sell</option>
        <?php
        $products = wc_get_products(array('status' => 'publish', 'limit' => -1));

        // Pobierz wszystkie warianty dla produktów zmiennych
        $variable_products = wc_get_products(array(
            'type' => 'variable',
            'status' => 'publish',
            'limit' => -1
        ));

        // Usuń produkty nadrzędne dla wariacji z głównej listy produktów
        $products = array_filter($products, function ($product) {
            return !$product->is_type('variable');
        });

        foreach ($variable_products as $variable_product) {
            $main_product_id = $variable_product->get_id();
            $variations = $variable_product->get_available_variations();
            foreach ($variations as $variation) {

                $variation_obj = wc_get_product($variation['variation_id']);

                if ($variation['variation_id'] == $main_product_id) {
                    continue;
                }


                $variation_name = $variable_product->get_name();

                $is_vaiariation = false;
                // Dodaj atrybuty do nazwy wariantu
                foreach ($variation['attributes'] as $attribute => $value) {
                    $taxonomy = str_replace('attribute_', '', $attribute);
                    $term = get_term_by('slug', $value, $taxonomy);
                    $variation_name .= ' - ' . ($term ? $term->name : $value);
                    $is_vaiariation = true;
                }

                if ($is_vaiariation) {
                    $products[] = $variation_obj;
                }
            }
        }

        // Posortuj alfabetycznie po nazwie
        usort($products, function ($a, $b) {
            return strcmp($a->get_name(), $b->get_name());
        });


        foreach ($products as $product) {
            echo '<option ' . ($product_id == $product->get_id() ? 'selected' : '') . ' value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
        }
        ?>
    </select>
    <select name="discount_type" class="regular-text discount-type">
        <option value="percent"
            <?php echo (isset($product_discount_type) && $product_discount_type == 'percent' ? 'selected' : ''); ?>>
            Zniżka procentowa</option>
        <option value="fixed"
            <?php echo (isset($product_discount_type) && $product_discount_type == 'fixed' ? 'selected' : ''); ?>>Zniżka
            kwotowa</option>
    </select>
    <input type="number" name="discount_value" placeholder="Wysokość zniżki" class="small-text product-discount" min="0"
        step="1" <?= isset($product_discount_type) && $product_discount_type == 'percent' ? 'max="100"' : '' ?>
        value="<?php echo esc_attr(isset($product_discount_value) ? $product_discount_value : ''); ?>">
    <button type="button" class="button remove-cross-sell-product">Usuń</button>

</div>