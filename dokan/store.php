<?php
/**
 * Loothtool — Vendor Storefront Template.
 * Overrides dokan-lite/templates/store.php.
 *
 * Displays: color banner, logo, store name, bio, YouTube embed, product grid.
 */

defined( 'ABSPATH' ) || exit;

$store_user = dokan()->vendor->get( get_query_var( 'author' ) );
$store_info = $store_user->get_shop_info();
$vendor_id  = $store_user->get_id();

// ── Store info ────────────────────────────────────────────────────────────────
$store_name  = $store_info['store_name'] ?? $store_user->data->display_name;
$logo_id     = $store_info['gravatar'] ?? 0;
$logo_url    = $logo_id
	? wp_get_attachment_image_url( $logo_id, 'medium' )
	: get_avatar_url( $vendor_id, [ 'size' => 160 ] );
$description = $store_info['store_description'] ?? '';

// ── Loothtool custom branding meta ────────────────────────────────────────────
$bio        = get_user_meta( $vendor_id, '_lt_vendor_bio', true ) ?: $description;
$color      = get_user_meta( $vendor_id, '_lt_vendor_color', true ) ?: '#a42325';
$youtube    = get_user_meta( $vendor_id, '_lt_vendor_youtube', true );
// Custom banner takes priority; fall back to Dokan's built-in banner field.
$banner_id  = (int) get_user_meta( $vendor_id, '_lt_vendor_banner', true );
if ( ! $banner_id ) {
	$banner_id = (int) ( $store_info['banner'] ?? 0 );
}
$banner_url = $banner_id ? wp_get_attachment_image_url( $banner_id, 'full' ) : '';

// Sanitize YouTube to embed URL
$youtube_embed = '';
if ( $youtube ) {
	// Accept watch?v=, youtu.be/, or full embed URL
	if ( preg_match( '/(?:v=|youtu\.be\/|embed\/)([a-zA-Z0-9_-]{11})/', $youtube, $m ) ) {
		$youtube_embed = 'https://www.youtube.com/embed/' . $m[1];
	}
}

get_header();
?>

<!-- ═══════════════════════════════════════════
     BREADCRUMB
════════════════════════════════════════════ -->
<nav class="lt-breadcrumb lt-breadcrumb--store" aria-label="Breadcrumb">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
	<span>/</span>
	<?php echo esc_html( $store_name ); ?>
</nav>

<!-- ═══════════════════════════════════════════
     VENDOR STORE HEADER
════════════════════════════════════════════ -->
<div class="lt-store-header" style="--lt-store-color: <?php echo esc_attr( $color ); ?>">

	<!-- Banner: photo if set, otherwise colour gradient -->
	<div class="lt-store-banner<?php echo $banner_url ? ' lt-store-banner--image' : ''; ?>"
	     <?php if ( $banner_url ) : ?>style="background-image: url('<?php echo esc_url( $banner_url ); ?>')"<?php endif; ?>>
	</div>

	<div class="lt-store-identity">
		<div class="lt-store-logo-wrap">
			<img class="lt-store-logo"
			     src="<?php echo esc_url( $logo_url ); ?>"
			     alt="<?php echo esc_attr( $store_name ); ?>">
		</div>
		<h1 class="lt-store-name"><?php echo esc_html( $store_name ); ?></h1>
		<?php if ( $bio ) : ?>
			<p class="lt-store-bio"><?php echo nl2br( esc_html( $bio ) ); ?></p>
		<?php endif; ?>
	</div>

</div><!-- .lt-store-header -->

<!-- ═══════════════════════════════════════════
     YOUTUBE EMBED (optional)
════════════════════════════════════════════ -->
<?php if ( $youtube_embed ) : ?>
<div class="lt-store-video-wrap">
	<div class="lt-store-video">
		<iframe src="<?php echo esc_url( $youtube_embed ); ?>"
		        title="<?php echo esc_attr( $store_name ); ?> — video"
		        frameborder="0"
		        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
		        allowfullscreen
		        loading="lazy"></iframe>
	</div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     PRODUCTS
════════════════════════════════════════════ -->
<div class="lt-store-products-wrap">
	<div class="lt-store-products-inner">

		<h2 class="lt-store-products-heading">Products</h2>

		<?php if ( have_posts() ) : ?>

			<div class="lt-product-grid lt-store-product-grid">
				<?php while ( have_posts() ) :
					the_post();
					$product    = wc_get_product( get_the_ID() );
					if ( ! $product ) continue;

					$img_url    = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_single' ) ?: wc_placeholder_img_src();
					$title      = $product->get_name();
					$link       = get_permalink();
					$on_sale    = $product->is_on_sale();
					$price_html = $product->get_price_html();
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
							<a href="<?php echo esc_url( $link ); ?>" class="lt-product-card__title">
								<?php echo esc_html( $title ); ?>
							</a>
							<p class="lt-product-card__price"><?php echo wp_kses_post( $price_html ); ?></p>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<!-- Pagination -->
			<div class="lt-pagination">
				<?php echo paginate_links( [
					'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
					'format'    => '?paged=%#%',
					'current'   => max( 1, get_query_var( 'paged' ) ),
					'total'     => $GLOBALS['wp_query']->max_num_pages,
					'prev_text' => 'Previous',
					'next_text' => 'Next',
				] ); ?>
			</div>

		<?php else : ?>
			<p class="lt-store-no-products">This shop has no products yet.</p>
		<?php endif; ?>

	</div>
</div>

<?php get_footer(); ?>
