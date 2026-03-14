<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.1' );

/**
 * Theme setup — menus, post thumbnails, WooCommerce support.
 */
function lt_theme_setup() {
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus( [
		'primary' => __( 'Primary Navigation', 'hello-elementor-child' ),
	] );
}
add_action( 'after_setup_theme', 'lt_theme_setup' );

/**
 * Load child theme scripts & styles. 
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {
	// Depend on parent stylesheet only when Elementor has registered it.
	$parent = 'hello-elementor-theme-style';
	$deps   = wp_style_is( $parent, 'registered' ) ? [ $parent ] : [];

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		$deps,
		HELLO_ELEMENTOR_CHILD_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

/**
 * Inject mobile CSS in wp_footer (last in body) so it beats all Elementor
 * inline <style> blocks, including loop template styles injected mid-body.
 */
function lt_mobile_override_css() {
	?>
<style id="lt-mobile-override">
@media (max-width: 768px) {

  /* 1-column product loop grid — exclude Swiper carousels */
  .elementor-loop-container.elementor-grid:not(.swiper),
  .elementor-grid:not(.swiper):not(.swiper-wrapper) {
    grid-template-columns: 1fr !important;
    gap: 3px !important;
  }

  /* Related Items: convert carousel to 2-column grid on mobile */
  .elementor-widget-loop-carousel {
    width: 100% !important;
  }
  .elementor-widget-loop-carousel .swiper {
    overflow: visible !important;
  }
  /* Kill Swiper's inline transform + force grid layout */
  .elementor-widget-loop-carousel .swiper-wrapper {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 3px !important;
    transform: none !important;
    width: 100% !important;
    flex-wrap: unset !important;
  }
  .elementor-widget-loop-carousel .swiper-slide {
    width: auto !important;
    margin: 0 !important;
    height: auto !important;
  }
  /* Hide nav arrows + pagination dots */
  .elementor-widget-loop-carousel .swiper-button-next,
  .elementor-widget-loop-carousel .swiper-button-prev,
  .elementor-widget-loop-carousel .swiper-pagination {
    display: none !important;
  }
  /* Show max 4 items (2 rows of 2) */
  .elementor-widget-loop-carousel .swiper-slide:nth-child(n+5) {
    display: none !important;
  }

  /* Outer loop item wrapper — reset spacing */
  .e-loop-item {
    margin: 0 0 3px 0 !important;
    padding: 0 !important;
  }

  /* Inner card container (template 388: elementor-element-6fca236) */
  .elementor-388 .elementor-element-6fca236,
  .e-loop-item > .elementor-section,
  .e-loop-item > .e-con {
    border: none !important;
    border-radius: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    box-shadow: none !important;
    background: #1a1a1a !important;
    min-height: 0 !important;
  }

  /* Image widget container — no padding/margin */
  .elementor-388 .elementor-element-fb7af2c,
  .elementor-388 .elementor-element-22d8d43 > .elementor-widget-container {
    margin: 0 !important;
    padding: 0 !important;
  }

  /* Product image — full width, square-ish */
  .elementor-388 .elementor-element-22d8d43 img,
  .e-loop-item img {
    width: 100% !important;
    height: 56vw !important;
    object-fit: cover !important;
    display: block !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    aspect-ratio: unset !important;
    max-width: 100% !important;
  }

  /* Title widget — dark band */
  .elementor-388 .elementor-element-380c5e5,
  .elementor-388 .elementor-element-44edb66 {
    margin: 0 !important;
    padding: 0 !important;
  }

  .elementor-388 .elementor-element-44edb66 > .elementor-widget-container {
    background: #1a1a1a !important;
    padding: 12px 14px 4px !important;
    margin: 0 !important;
  }

  .elementor-388 .elementor-element-44edb66 .elementor-heading-title {
    color: #fff !important;
    font-size: 0.95rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.03em !important;
    line-height: 1.2 !important;
  }

  /* Price widget — dark band */
  .elementor-388 .elementor-element-7c9373c > .elementor-widget-container {
    background: #1a1a1a !important;
    padding: 2px 14px 12px !important;
    margin: 0 !important;
  }

  .elementor-388 .elementor-element-7c9373c .price,
  .elementor-388 .elementor-element-7c9373c .price * {
    color: #bbb !important;
    font-size: 0.85rem !important;
  }

  /* Hide any remaining container sections that add space */
  .elementor-388 .elementor-element-67d5363,
  .elementor-388 .elementor-element-d58129c,
  .elementor-388 .elementor-element-50bc04a {
    margin: 0 !important;
    padding: 0 !important;
  }

  /* =====================================================
     SINGLE PRODUCT PAGE (page template 2539)
     ===================================================== */

  /* Stack gallery + info vertically */
  .elementor-2539 .elementor-element-997a9e4,
  .elementor-2539 .elementor-element-6b8c3c2 {
    --flex-direction: column !important;
    flex-direction: column !important;
    flex-wrap: nowrap !important;
  }

  /* Each panel full width */
  .elementor-2539 .elementor-element-997a9e4 > .e-con,
  .elementor-2539 .elementor-element-6b8c3c2 > .e-con,
  .elementor-2539 .elementor-element-2a7f293 {
    width: 100% !important;
    --width: 100% !important;
    max-width: 100% !important;
  }

  /* Remove card shadow/radius on mobile */
  .elementor-2539 .elementor-element-2a7f293 {
    box-shadow: none !important;
    --border-radius: 0 !important;
    border-radius: 0 !important;
    --padding-top: 12px !important;
    --padding-bottom: 12px !important;
    --padding-left: 12px !important;
    --padding-right: 12px !important;
  }

  /* Product gallery: full width, square */
  .woocommerce-product-gallery {
    width: 100% !important;
    float: none !important;
    margin-bottom: 0 !important;
  }

  .woocommerce-product-gallery .woocommerce-product-gallery__wrapper img {
    width: 100% !important;
    height: auto !important;
    display: block !important;
  }

  /* Thumbnail strip: hide on mobile to save space */
  .woocommerce-product-gallery .flex-control-thumbs {
    display: none !important;
  }

  /* Product title */
  .single-product .product_title,
  .woocommerce div.product .product_title {
    font-size: 1.5rem !important;
    line-height: 1.2 !important;
    margin: 0 0 8px !important;
  }

  /* Price */
  .woocommerce div.product p.price,
  .woocommerce div.product span.price {
    font-size: 1.3rem !important;
    margin-bottom: 16px !important;
  }

  /* Qty + Add to cart row: stack vertically */
  .woocommerce div.product form.cart {
    display: flex !important;
    flex-direction: column !important;
    gap: 10px !important;
  }

  /* Add to cart button: full width, tall */
  .woocommerce div.product form.cart .single_add_to_cart_button {
    width: 100% !important;
    padding: 16px !important;
    font-size: 1.1rem !important;
    text-align: center !important;
    margin: 0 !important;
  }

  /* Quantity input */
  .woocommerce div.product form.cart .qty {
    width: 70px !important;
    font-size: 1rem !important;
    padding: 8px !important;
  }

  /* Product summary: full width */
  .woocommerce div.product div.summary {
    float: none !important;
    width: 100% !important;
    padding: 0 !important;
  }

  /* Tabs: stack nicely */
  .woocommerce-tabs .wc-tabs {
    display: flex !important;
    flex-wrap: wrap !important;
    border-bottom: 2px solid #e6e6e6 !important;
    padding: 0 !important;
    margin: 0 0 16px !important;
  }

  .woocommerce-tabs .wc-tabs li {
    margin: 0 !important;
    border-radius: 0 !important;
  }

  .woocommerce-tabs .wc-tabs li a {
    padding: 10px 14px !important;
    font-size: 0.85rem !important;
  }

  /* Related Items carousel: full-width single slide, styled cards */
  .elementor-widget-loop-carousel {
    width: 100% !important;
  }

  /* Carousel pagination dots */
  .elementor-widget-loop-carousel .swiper-pagination {
    position: relative !important;
    margin-top: 8px !important;
  }

  /* Related section heading */
  .woocommerce .related h2,
  .woocommerce .upsells h2 {
    font-size: 1.2rem !important;
    margin: 24px 0 12px !important;
    padding: 0 4px !important;
  }

  /* ── WooCommerce Blocks: Cart + Checkout layout ─────────────────────
     hello-elementor theme sets .alignwide{margin-inline:-80px} which
     pushes the checkout/cart block 80px off the left edge on mobile.
     Gutenberg also injects flex-wrap:nowrap; inline on columns.
     These !important rules in wp_footer override everything last. */
  .wp-block-woocommerce-cart.alignwide,
  .wp-block-woocommerce-checkout.alignwide {
    margin-inline: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
  }
  .wp-block-columns.is-layout-flex,
  [class*="wp-container-core-columns"] {
    flex-direction: column !important;
    flex-wrap: wrap !important;
    gap: 0 !important;
    padding: 0 !important;
  }
  .wp-block-column,
  .wp-block-column.is-layout-flow {
    flex-basis: 100% !important;
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
  }
  .wp-block-woocommerce-cart,
  .wp-block-woocommerce-checkout,
  .wp-block-woocommerce-filled-cart-block,
  .wp-block-woocommerce-cart-items-block,
  .wp-block-woocommerce-cart-totals-block,
  .wp-block-woocommerce-checkout-fields-block,
  .wp-block-woocommerce-checkout-totals-block {
    width: 100% !important;
    max-width: 100% !important;
    overflow-x: hidden !important;
    box-sizing: border-box !important;
  }
  .page-content {
    overflow-x: hidden !important;
    box-sizing: border-box !important;
    padding-left: 12px !important;
    padding-right: 12px !important;
  }
}
</style>
<script>
(function(){
  if(window.innerWidth>768)return;
  function killCarousel(){
    document.querySelectorAll('.elementor-widget-loop-carousel .swiper').forEach(function(el){
      if(el.swiper){el.swiper.destroy(true,true);}
      var w=el.querySelector('.swiper-wrapper');
      if(w){w.removeAttribute('style');}
      el.querySelectorAll('.swiper-slide').forEach(function(s){s.removeAttribute('style');});
    });
  }
  window.addEventListener('load',function(){setTimeout(killCarousel,200);});
})();
</script>
	<?php
}
add_action( 'wp_footer', 'lt_mobile_override_css', 9999 );

