<?php
/**
 * Shop / product archive template.
 * 3-column layout: categories sidebar | product grid | shops sidebar.
 */

defined( 'ABSPATH' ) || exit;

// Suppress WooCommerce's default category-thumbnail output — we handle layout ourselves.
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_product_subcategories', 10 );

get_header();

// ── Left sidebar: product categories ─────────────────────────────────────────
$product_cats = get_terms( [
	'taxonomy'   => 'product_cat',
	'hide_empty' => true,
	'parent'     => 0,
	'orderby'    => 'name',
] );

// ── Right sidebar: Dokan vendor logos ─────────────────────────────────────────
$vendors = function_exists( 'dokan' )
	? dokan()->vendor->all( [
		'number' => 20,
		'status' => 'approved',
		'meta_query' => [ [
			'key'     => '_lt_vendor_hidden',
			'compare' => 'NOT EXISTS',
		] ],
	] )
	: [];
?>

<div class="lt-shop-layout">

	<!-- Left: brand + category nav -->
	<aside class="lt-shop-sidebar lt-shop-sidebar--left">
		<p class="lt-shop-brand">Loothtool<br>.com</p>
		<div class="lt-shop-brand-line"></div>

		<?php /* categories hidden — not ready yet */ ?>
	</aside>

	<!-- Center: product grid -->
	<main class="lt-shop-main" id="main">

		<div class="lt-shop-toolbar">
			<span class="lt-shop-result-count"><?php woocommerce_result_count(); ?></span>
			<?php woocommerce_catalog_ordering(); ?>
		</div>

		<?php if ( woocommerce_product_loop() ) : ?>

			<div class="lt-product-grid">
			<?php
			while ( have_posts() ) :
				the_post();

				$product = wc_get_product( get_the_ID() );
				if ( ! $product ) {
					continue;
				}

				$img_url    = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_single' ) ?: wc_placeholder_img_src();
				$title      = $product->get_name();
				$link       = get_permalink();
				$on_sale    = $product->is_on_sale();
				$price_html = $product->get_price_html();
				$disc_text  = get_post_meta( get_the_ID(), '_wc_bulk_discount_label', true );
			?>

				<article class="lt-product-card" id="post-<?php echo absint( get_the_ID() ); ?>">

					<a href="<?php echo esc_url( $link ); ?>" class="lt-product-card__image-wrap" tabindex="-1" aria-hidden="true">
						<img src="<?php echo esc_url( $img_url ); ?>"
						     alt="<?php echo esc_attr( $title ); ?>"
						     loading="lazy">
						<?php if ( $on_sale ) : ?>
							<span class="lt-product-card__badge">Sale!</span>
						<?php endif; ?>
					</a>

					<div class="lt-product-card__body">
						<a href="<?php echo esc_url( $link ); ?>" class="lt-product-card__title">
							<?php echo esc_html( $title ); ?>
						</a>
						<p class="lt-product-card__price">
							<?php echo wp_kses_post( $price_html ); ?>
						</p>
						<?php if ( $disc_text ) : ?>
							<p class="lt-product-card__discount"><?php echo esc_html( $disc_text ); ?></p>
						<?php endif; ?>
					</div>

				</article>

			<?php endwhile; ?>
			</div><!-- .lt-product-grid -->

			<!-- Pagination -->
			<div class="lt-pagination">
				<?php
				echo paginate_links( [
					'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
					'format'    => '?paged=%#%',
					'current'   => max( 1, get_query_var( 'paged' ) ),
					'total'     => wc_get_loop_prop( 'total_pages' ),
					'prev_text' => 'Previous',
					'next_text' => 'Next',
				] );
				?>
			</div>

		<?php else : ?>
			<?php do_action( 'woocommerce_no_products_found' ); ?>
		<?php endif; ?>

	</main>

	<!-- Right: vendor shops -->
	<aside class="lt-shop-sidebar lt-shop-sidebar--right">
		<h3 class="lt-shops-heading">Shops</h3>
		<?php if ( ! empty( $vendors ) ) : ?>
			<ul class="lt-vendor-list">
				<?php foreach ( $vendors as $vendor ) :
					$vid        = $vendor->get_id();
					$store_info = $vendor->get_shop_info();
					$store_name = $store_info['store_name'] ?? $vendor->data->display_name;
					$store_url  = dokan_get_store_url( $vid );
					$logo_id    = $store_info['gravatar'] ?? 0;
					$logo_url   = $logo_id
						? wp_get_attachment_image_url( $logo_id, 'thumbnail' )
						: get_avatar_url( $vid, [ 'size' => 80 ] );
				?>
					<li>
						<a href="<?php echo esc_url( $store_url ); ?>" title="<?php echo esc_attr( $store_name ); ?>">
							<img src="<?php echo esc_url( $logo_url ); ?>"
							     alt="<?php echo esc_attr( $store_name ); ?>"
							     class="lt-vendor-logo<?php echo get_user_meta( $vid, '_lt_logo_invert', true ) ? ' lt-vendor-logo--invert' : ''; ?>"
							     loading="lazy">
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</aside>

</div><!-- .lt-shop-layout -->

<?php get_footer(); ?>
