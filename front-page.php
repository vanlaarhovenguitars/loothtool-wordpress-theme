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

// ── Approved vendors (up to 20) ──────────────────────────────────────────────
// dokan()->vendor->all() returns Vendor objects with ->id and ->get_shop_info()
$vendors_raw = function_exists( 'dokan' )
	? dokan()->vendor->all( [
		'number' => 20,
		'status' => 'approved',
		'role'   => 'seller',
		'meta_query' => [ [
			'key'     => '_lt_vendor_hidden',
			'compare' => 'NOT EXISTS',
		] ],
	] )
	: [];

// Place VL Guitar Repair (ID 6) and Looth Prints (ID 126) at the visual center
// so the center-zoom carousel effect naturally highlights them on load.
$featured_ids = [ 6, 126 ];
$featured     = [];
$rest         = [];
foreach ( $vendors_raw as $v ) {
	$pos = array_search( $v->get_id(), $featured_ids, true );
	if ( $pos !== false ) {
		$featured[ $pos ] = $v;
	} else {
		$rest[] = $v;
	}
}
ksort( $featured );
$half    = (int) ceil( count( $rest ) / 2 );
$vendors = array_merge(
	array_slice( $rest, 0, $half ),
	array_values( $featured ),
	array_slice( $rest, $half )
);

$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
$sell_url = function_exists( 'dokan_get_navigation_url' )
	? dokan_get_navigation_url( 'dashboard' )
	: wp_registration_url();
?>

<!-- ═══════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════ -->
<?php
$hero_bg      = get_option( 'lt_hero_bg_color', '' );
$hero_color   = get_option( 'lt_hero_text_color', '' );
$hero_style   = '';
if ( $hero_bg )    $hero_style .= 'background:' . esc_attr( $hero_bg ) . ';';
if ( $hero_color ) $hero_style .= 'color:' . esc_attr( $hero_color ) . ';';
?>
<section class="lt-hero"<?php echo $hero_style ? ' style="' . $hero_style . '"' : ''; ?>>
	<div class="lt-hero__inner">

		<p class="lt-hero__eyebrow"><?php echo esc_html( get_option( 'lt_hero_eyebrow', 'The Guitar Tool Marketplace' ) ); ?></p>

		<h1 class="lt-hero__title"><?php echo wp_kses_post( get_option( 'lt_hero_headline', 'Find Your Next<br>Favorite Tool' ) ); ?></h1>

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
     RECENTLY LISTED + SHOPS STRIP
════════════════════════════════════════════════════════ -->
<?php if ( $featured_query->have_posts() || ! empty( $vendors ) ) : ?>
<section class="lt-home-section lt-home-products-section">
	<div class="lt-home-section__inner">

		<?php if ( ! empty( $vendors ) ) : ?>
		<!-- Shops horizontal strip -->
		<div class="lt-shops-strip-wrap">
			<h2 class="lt-shops-strip-label">Shops</h2>
			<div class="lt-shops-strip-outer">
				<button class="lt-shops-arrow lt-shops-arrow--prev" aria-label="Previous shops">&#8592;</button>
				<div class="lt-shops-strip" id="lt-shops-strip">
					<?php foreach ( $vendors as $vendor ) :
						$vid        = $vendor->get_id();
						$store_info = $vendor->get_shop_info();
						$store_name = ! empty( $store_info['store_name'] ) ? $store_info['store_name'] : $vendor->data->display_name;
						$store_url  = dokan_get_store_url( $vid );
						$logo_id    = $store_info['gravatar'] ?? 0;
						$logo_url   = $logo_id
							? wp_get_attachment_image_url( $logo_id, 'medium' )
							: get_avatar_url( $vid, [ 'size' => 150 ] );
					?>
						<a class="lt-shop-chip" href="<?php echo esc_url( $store_url ); ?>">
							<span class="lt-shop-chip__img">
								<img src="<?php echo esc_url( $logo_url ); ?>"
								     alt="<?php echo esc_attr( $store_name ); ?>"
								     loading="lazy">
							</span>
							<span class="lt-shop-chip__name"><?php echo esc_html( $store_name ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
				<button class="lt-shops-arrow lt-shops-arrow--next" aria-label="Next shops">&#8594;</button>
			</div>
		</div>
		<?php endif; ?>

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
<?php endif; // products or vendors ?>
<?php if ( ! $featured_query->have_posts() ) : wp_reset_postdata(); endif; ?>

<!-- ═══════════════════════════════════════════════════════
     SELL CTA BANNER
════════════════════════════════════════════════════════ -->
<section class="lt-sell-banner">
	<div class="lt-sell-banner__inner">
		<h2 class="lt-sell-banner__title">Have Tools to Sell?</h2>
		<p class="lt-sell-banner__text">
			Join independent sellers on Loothtool and reach luthiers worldwide.
		</p>
		<?php
		// TODO PRODUCTION: Update this email to the live contact address before going live.
		// See MASTERPROMPT.md Production Migration Checklist.
		$contact_email = 'vanlaarhovenguitars@gmail.com';
		$mailto = 'mailto:' . $contact_email . '?subject=' . rawurlencode( 'I want to open a shop on Loothtool' );
		?>
		<a class="lt-sell-banner__btn" href="<?php echo esc_attr( $mailto ); ?>">Open Your Shop</a>
	</div>
</section>

<?php get_footer(); ?>
