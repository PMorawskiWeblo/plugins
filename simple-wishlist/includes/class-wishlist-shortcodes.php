<?php
class Wishlist_Shortcodes
{
    private $wishlist;

    public function __construct($wishlist)
    {
        $this->wishlist = $wishlist;
        add_shortcode('wishlist_button', [$this, 'wishlist_button_shortcode']);
        add_shortcode('wishlist_counter', [$this, 'wishlist_counter_shortcode']);
        add_shortcode('wishlist_products', [$this, 'wishlist_products_shortcode']);
    }



    public function wishlist_button_shortcode($atts)
    {
        if (is_user_logged_in()) {
            // Spróbuj pobrać ID produktu z globalnego kontekstu WooCommerce
            $product_id = get_the_ID();
            global $product;
            if (empty($product_id) && $product && $product->get_id()) {
                $product_id = $product->get_id();
            }
            if (empty($product_id)) {
                return '';
            }
            $is_in_wishlist = $this->wishlist->is_product_in_wishlist($product_id);
            $active_class = $is_in_wishlist ? 'active' : '';

            $svg_active = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M2 9.49955C2.00002 8.38675 2.33759 7.30014 2.96813 6.38322C3.59867 5.4663 4.49252 4.76221 5.53161 4.36395C6.5707 3.96569 7.70616 3.892 8.78801 4.1526C9.86987 4.4132 10.8472 4.99583 11.591 5.82355C11.6434 5.87957 11.7067 5.92422 11.7771 5.95475C11.8474 5.98528 11.9233 6.00104 12 6.00104C12.0767 6.00104 12.1526 5.98528 12.2229 5.95475C12.2933 5.92422 12.3566 5.87957 12.409 5.82355C13.1504 4.99045 14.128 4.40292 15.2116 4.13915C16.2952 3.87539 17.4335 3.9479 18.4749 4.34704C19.5163 4.74617 20.4114 5.453 21.0411 6.37345C21.6708 7.2939 22.0053 8.38431 22 9.49955C22 11.7896 20.5 13.4996 19 14.9996L13.508 20.3126C13.3217 20.5266 13.0919 20.6985 12.834 20.8169C12.5762 20.9352 12.296 20.9974 12.0123 20.9992C11.7285 21.001 11.4476 20.9424 11.1883 20.8273C10.9289 20.7122 10.697 20.5432 10.508 20.3316L5 14.9996C3.5 13.4996 2 11.7996 2 9.49955Z" fill="#A2693C"/></svg>';
            $svg_inactive = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 9.50004C2.00002 8.38724 2.33759 7.30062 2.96813 6.3837C3.59867 5.46678 4.49252 4.7627 5.53161 4.36444C6.5707 3.96618 7.70616 3.89248 8.78801 4.15308C9.86987 4.41368 10.8472 4.99632 11.591 5.82404C11.6434 5.88005 11.7067 5.92471 11.7771 5.95524C11.8474 5.98577 11.9233 6.00152 12 6.00152C12.0767 6.00152 12.1526 5.98577 12.2229 5.95524C12.2933 5.92471 12.3566 5.88005 12.409 5.82404C13.1504 4.99094 14.128 4.40341 15.2116 4.13964C16.2952 3.87588 17.4335 3.94839 18.4749 4.34752C19.5163 4.74666 20.4114 5.45349 21.0411 6.37394C21.6708 7.29439 22.0053 8.3848 22 9.50004C22 11.79 20.5 13.5 19 15L13.508 20.313C13.3217 20.527 13.0919 20.699 12.834 20.8173C12.5762 20.9357 12.296 20.9979 12.0123 20.9997C11.7285 21.0015 11.4476 20.9429 11.1883 20.8278C10.9289 20.7127 10.697 20.5437 10.508 20.332L5 15C3.5 13.5 2 11.8 2 9.50004Z" stroke="#6D6059" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

            return sprintf(
                '<button class="wishlist-button %s" data-product-id="%d">
                    %s
                </button>',
                $active_class,
                $product_id,
                $is_in_wishlist ? $svg_active : $svg_inactive
            );
        }
    }

    public function wishlist_counter_shortcode()
    {
        // Sprawdzenie czy użytkownik jest zalogowany
        if (!is_user_logged_in()) {
            $wishlist_url = wc_get_page_permalink('myaccount');
        } else {
            $wishlist_page_id = get_option('wishlist_page_id');
            $wishlist_url = $wishlist_page_id ? get_permalink($wishlist_page_id) : '#';
        }

        $count = count($this->wishlist->get_wishlist_items());

        // Przygotowanie spana z licznikiem tylko jeśli count > 0
        $count_html = $count > 0 ? sprintf('<span class="count">%d</span>', $count) : '';

        return sprintf(
            '<a href="%s" class="wishlist-counter single_icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="18" viewBox="0 0 20 18" fill="none">
                    <path d="M9.99954 3.39898C10.1505 3.20798 12.2295 0.654981 14.9335 1.11198C15.7719 1.31991 16.5481 1.72633 17.1965 2.29699C17.845 2.86764 18.3467 3.58582 18.6595 4.39098C19.9785 7.60998 17.3865 11.621 12.2795 16.133C11.6353 16.692 10.811 16.9998 9.95804 16.9998C9.10507 16.9998 8.28075 16.692 7.63654 16.133C2.54854 11.633 0.027538 7.67798 1.34654 4.45998C1.75154 3.47198 2.98054 1.40698 5.06954 1.05398C7.77254 0.598981 9.85254 3.20398 9.99954 3.39898Z" stroke="#181B39" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                %s
            </a>',
            esc_url($wishlist_url),
            $count_html
        );
    }



    public function wishlist_products_shortcode()
    {
        $products = $this->wishlist->get_wishlist_items();
        $output = '<div class="wishlist-products-wrapper">';
        if (empty($products)) {
            $output .= '<p>' . __('Your wish list is empty', 'simple-wishlist') . '</p></div>';
            return $output;
        }

        $output .= '<div class="wishlist-products">';
        $valid_products_count = 0;

        // Zapisz oryginalną globalną zmienną $product jeśli istnieje
        global $product, $post;
        $original_product = $product;
        $original_post = $post;

        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);
            // get_wishlist_items() już filtruje, ale dodatkowa kontrola na wszelki wypadek
            if (!$product) continue;

            // Sprawdź czy produkt jest opublikowany
            if (get_post_status($product_id) !== 'publish') {
                continue;
            }

            $valid_products_count++;

            // Ustaw globalne $product i $post dla szablonu WooCommerce
            $GLOBALS['product'] = $product;
            $post = get_post($product_id);
            setup_postdata($post);

            // Użyj output buffering do przechwycenia outputu z template part
            ob_start();
            wc_get_template_part('content', 'product');
            $product_template = ob_get_clean();



            $output .= '<div class="wishlist-product">' . $product_template . '</div>';
        }

        // Przywróć oryginalne globalne zmienne
        $GLOBALS['product'] = $original_product;
        $post = $original_post;
        wp_reset_postdata();

        $output .= '</div>';

        // Jeśli wszystkie produkty były nieprawidłowe
        if ($valid_products_count === 0) {
            $output .= '<p>' . __('Your wish list is empty', 'simple-wishlist') . '</p></div></div>';
            return $output;
        }

        // Dodanie przycisku "Dodaj wszystkie do koszyka"
        if ($valid_products_count > 0) {
            $output .= sprintf(
                '<div class="wishlist-bulk-actions">
                    <button class="add-all-to-cart btn btn-primary">%s</button>
                </div>',
                __('Add all to cart', 'simple-wishlist')
            );
        }
        $output .= '</div>';
        return $output;
    }
}
