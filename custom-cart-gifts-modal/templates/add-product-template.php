<?php

// Szablon używany do dynamicznego dodawania nowych produktów do listy gratisów
// Zawiera pola dla nazwy produktu, sloganu reklamowego, ceny oraz wyboru produktu z WooCommerce
// Używany przez JavaScript w panelu administracyjnym

$product_id = isset($product_id) && !empty($product_id) ? $product_id : '';
$product_name = isset($product_name) && !empty($product_name) ? $product_name : '';
$product_slogan = isset($product_slogan) && !empty($product_slogan) ? $product_slogan : '';
$product_cena = isset($product_cena) && !empty($product_cena) ? $product_cena : '';

?>

<div class="product-item">
    <input type="text" name="product_nazwa" placeholder="Nazwa produktu (opcjonalna)" class="product-name regular-text" value="<?php echo esc_attr($product_name); ?>">
    <input type="text" name="product_slogan" placeholder="Slogan (opcjonalny)" class="product-slogan regular-text" value="<?php echo esc_attr($product_slogan); ?>">
    <input type="number" name="product_cena" placeholder="Cena" class="small-text product-price" min="0" step="0.01" value="<?php echo esc_attr($product_cena); ?>">
    <select name="product_id" class="regular-text product-id">
        <option value="">Wybierz product</option>
        <?php
        $products = wc_get_products(array('status' => 'publish', 'limit' => -1));
        foreach ($products as $product) {
            if ($product->is_type('variable')) {
                $variations = $product->get_available_variations();
                foreach ($variations as $variation) {
                    $variation_obj = wc_get_product($variation['variation_id']);
                    $variation_name = $product->get_name();
                    foreach ($variation['attributes'] as $attribute => $value) {
                        $taxonomy = str_replace('attribute_', '', $attribute);
                        $term = get_term_by('slug', $value, $taxonomy);
                        $variation_name .= ' - ' . ($term ? $term->name : $value);
                    }
                    echo '<option ' . ($product_id == $variation['variation_id'] ? 'selected' : '') . ' value="' . esc_attr($variation['variation_id']) . '">' . esc_html($variation_name) . '</option>';
                }
            } else {
                echo '<option ' . ($product_id == $product->get_id() ? 'selected' : '') . ' value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
            }
        }
        ?>
    </select>
    <button type="button" class="button remove-product">Usuń</button>
</div>