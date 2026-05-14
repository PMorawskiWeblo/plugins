<div id="cross-sell-modal" class="cross-sell-modal">
    <div class="cross-sell-modal-content">
        <div class="cross-sell-modal-header">
            <div class="cross-sell-modal-add-notification"></div><button class="cross-sell-modal-close">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 8L8 16M8 8L16 16" stroke="#3A3533" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </button>
        </div>
        <div class="cross-sell-modal-body">
            <h2><?= __('Check out also:', 'custom-cart-gifts-modal') ?></h2>
            <div class="cross-sell-modal-products">
                <section class="splide cross-sell-modal-products-splide">
                    <div class="splide__track">
                        <ul class="splide__list">


                        </ul>
                    </div>
                </section>
            </div>
        </div>
        <div class="cross-sell-modal-footer">
            <div
                class="cross-sell-modal-continue-shopping btn btn_dark_outline"><?= __('Continue shopping', 'custom-cart-gifts-modal') ?></div>
            <a href="<?php echo wc_get_cart_url(); ?>"
                class="cross-sell-modal-go-to-cart btn btn_dark"><?= __('Go to cart', 'custom-cart-gifts-modal') ?></a>
        </div>
    </div>
</div>