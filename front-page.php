<?php
/**
 * Front Page (Homepage) — Etsy-style layout.
 *
 * To activate: WP Admin → Settings → Reading → "A static page" →
 * set Front page to any static page (e.g. "Home"). WordPress will
 * automatically use this template for that page.
 */

defined( 'ABSPATH' ) || exit;

get_header();

// ── Recently listed products (8) ─────────────────────────────────────────────
$featured_query = new WP_Query( [
	'post_type'           => 'product',
	'posts_per_page'      => 8,
	'orderby'             => 'date',
	'order'               => 'DESC',
	'post_status'         => 'publish',
	'ignore_sticky_posts' => true,
	'tax_query'           => [ [
		'taxonomy' => 'product_visibility',
		'field'    => 'name',
		'terms'    => 'exclude-from-catalog',
		'operator' => 'NOT IN',
	] ],
] );

// ── Approved vendors (up to 12) ──────────────────────────────────────────────
$vendors = [];
if ( function_exists( 'dokan_get_vendors' ) ) {
	$vendors = dokan_get_vendors( [ 'number' => 12, 'status' => 'approved' ] );
}

$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
$sell_url = function_exists( 'dokan_get_navigation_url' )
	? dokan_get_navigation_url( 'dashboard' )
	: wp_registration_url();
?>

<!-- ═══════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════ -->
<section class="lt-hero">
	<div class="lt-hero__inner">

		<p class="lt-hero__eyebrow">The Guitar Gear Marketplace</p>

		<h1 class="lt-hero__title">Find Your Next<br>Piece of Tone</h1>

		<form class="lt-hero__search" role="search" method="get" action="<?php echo esc_url( $shop_url ); ?>">
			<input type="hidden" name="post_type" value="product">
			<input class="lt-hero__input"
			       type="search"
			       name="s"
			       placeholder="Search guitars, amps, pedals, effects…"
			       autocomplete="off">
			<button class="lt-hero__btn" type="submit">Search</button>
		</form>

	</div>
</section>

<!-- ═══════════════════════════════════════════════════════
     SHOPS / VENDORS
════════════════════════════════════════════════════════ -->
<?php if ( ! empty( $vendors ) ) : ?>
<section class="lt-home-section lt-home-cats-section">
	<div class="lt-home-section__inner">

		<div class="lt-home-section__head">
			<h2 class="lt-home-section__title">Shop by Seller</h2>
		</div>

		<div class="lt-cat-tiles lt-vendor-circles">
			<?php foreach ( $vendors as $vendor ) :
				$store_info = dokan_get_store_info( $vendor->ID );
				$store_name = $store_info['store_name'] ?? $vendor->display_name;
				$store_url  = dokan_get_store_url( $vendor->ID );
				$logo_id    = $store_info['gravatar'] ?? 0;
				$logo_url   = $logo_id
					? wp_get_attachment_image_url( $logo_id, 'medium' )
					: get_avatar_url( $vendor->ID, [ 'size' => 150 ] );
			?>
				<a class="lt-cat-tile" href="<?php echo esc_url( $store_url ); ?>">
					<span class="lt-cat-tile__img-wrap">
						<img src="<?php echo esc_url( $logo_url ); ?>"
						     alt="<?php echo esc_attr( $store_name ); ?>"
						     loading="lazy">
					</span>
					<span class="lt-cat-tile__name"><?php echo esc_html( $store_name ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

	</div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     RECENTLY LISTED
════════════════════════════════════════════════════════ -->
<?php if ( $featured_query->have_posts() ) : ?>
<section class="lt-home-section lt-home-products-section">
	<div class="lt-home-section__inner">

		<div class="lt-home-section__head">
			<h2 class="lt-home-section__title">Recently Listed</h2>
			<a class="lt-home-section__all" href="<?php echo esc_url( $shop_url ); ?>">See all listings →</a>
		</div>

		<div class="lt-product-grid lt-home-product-grid">
			<?php while ( $featured_query->have_posts() ) :
				$featured_query->the_post();
				$product = wc_get_product( get_the_ID() );
				if ( ! $product ) continue;

				$img_url    = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_single' ) ?: wc_placeholder_img_src();
				$title      = $product->get_name();
				$link       = get_permalink();
				$on_sale    = $product->is_on_sale();
				$price_html = $product->get_price_html();
				$vendor_id  = (int) get_post_field( 'post_author', get_the_ID() );
				$store_name = '';
				if ( function_exists( 'dokan_get_store_info' ) && $vendor_id ) {
					$info       = dokan_get_store_info( $vendor_id );
					$store_name = $info['store_name'] ?? '';
				}
			?>
				<article class="lt-product-card">
					<a href="<?php echo esc_url( $link ); ?>" class="lt-product-card__image-wrap" tabindex="-1" aria-hidden="true">
						<img src="<?php echo esc_url( $img_url ); ?>"
						     alt="<?php echo esc_attr( $title ); ?>"
						     loading="lazy">
						<?php if ( $on_sale ) : ?>
							<span class="lt-product-card__badge">Sale</span>
						<?php endif; ?>
					</a>
					<div class="lt-product-card__body">
						<?php if ( $store_name ) : ?>
							<p class="lt-product-card__shop"><?php echo esc_html( $store_name ); ?></p>
						<?php endif; ?>
						<a href="<?php echo esc_url( $link ); ?>" class="lt-product-card__title">
							<?php echo esc_html( $title ); ?>
						</a>
						<p class="lt-product-card__price"><?php echo wp_kses_post( $price_html ); ?></p>
					</div>
				</article>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>

	</div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     SELL CTA BANNER
════════════════════════════════════════════════════════ -->
<section class="lt-sell-banner">
	<div class="lt-sell-banner__inner">
		<h2 class="lt-sell-banner__title">Have Gear to Sell?</h2>
		<p class="lt-sell-banner__text">
			Join independent sellers on Loothtool and reach guitar players everywhere.
		</p>
		<a class="lt-sell-banner__btn" href="<?php echo esc_url( $sell_url ); ?>">Open Your Shop</a>
	</div>
</section>

<?php get_footer(); ?>
