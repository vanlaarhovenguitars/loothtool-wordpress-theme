<?php
/**
 * Single product page template.
 * Layout: gallery (left) | summary (right) | description + reviews below.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	global $product;
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) continue;

	// Gallery images
	$gallery_ids  = $product->get_gallery_image_ids();
	$main_img_id  = $product->get_image_id();
	$main_img_url = wp_get_attachment_image_url( $main_img_id, 'woocommerce_single' ) ?: wc_placeholder_img_src();
	$all_ids      = array_merge( [ $main_img_id ], $gallery_ids );

	// Video (stored as product meta by most video plugins)
	$video_url = get_post_meta( get_the_ID(), '_wc_video_url', true )
	          ?: get_post_meta( get_the_ID(), 'video_url', true );

	// Breadcrumb
	$cats = wc_get_product_category_list( get_the_ID(), ', ', '<span>', '</span>' );
?>

<div class="lt-single-wrap">

	<!-- Breadcrumb -->
	<nav class="lt-breadcrumb" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
		<?php
		$terms = get_the_terms( get_the_ID(), 'product_cat' );
		if ( $terms && ! is_wp_error( $terms ) ) :
			$term = reset( $terms );
			?>
			<span>/</span>
			<a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a>
		<?php endif; ?>
		<span>/</span>
		<?php the_title(); ?>
	</nav>

	<!-- Top: gallery + summary -->
	<div class="lt-single-top">

		<!-- Gallery -->
		<div class="lt-gallery">
			<div class="lt-gallery__main" id="lt-gallery-main">
				<?php if ( $product->is_on_sale() ) : ?>
					<span class="lt-gallery__sale">Sale!</span>
				<?php endif; ?>
				<img src="<?php echo esc_url( $main_img_url ); ?>"
				     alt="<?php echo esc_attr( $product->get_name() ); ?>"
				     id="lt-main-image"
				     loading="eager">
			</div>

			<?php if ( count( $all_ids ) > 1 ) : ?>
				<div class="lt-gallery__thumbs">
					<?php foreach ( $all_ids as $i => $img_id ) :
						$thumb_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
						$full_url  = wp_get_attachment_image_url( $img_id, 'woocommerce_single' );
						if ( ! $thumb_url ) continue;
					?>
						<img src="<?php echo esc_url( $thumb_url ); ?>"
						     data-full="<?php echo esc_url( $full_url ); ?>"
						     alt=""
						     class="lt-gallery__thumb<?php echo $i === 0 ? ' active' : ''; ?>"
						     loading="lazy">
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div><!-- .lt-gallery -->

		<!-- Summary -->
		<div class="lt-summary">

			<h1 class="lt-summary__title"><?php the_title(); ?></h1>

			<?php if ( $product->get_short_description() ) : ?>
				<div class="lt-summary__short-desc">
					<?php echo wp_kses_post( $product->get_short_description() ); ?>
				</div>
			<?php endif; ?>

			<div class="lt-summary__price">
				<?php echo wp_kses_post( $product->get_price_html() ); ?>
			</div>

			<?php
			// Bulk discount text — check common meta keys used by discount plugins
			$discount_text = get_post_meta( get_the_ID(), '_wc_bulk_discount_label', true )
			              ?: get_post_meta( get_the_ID(), 'pricing_rules_label', true );
			if ( $discount_text ) : ?>
				<p class="lt-summary__discount"><?php echo esc_html( $discount_text ); ?></p>
			<?php endif; ?>

			<!-- Add to cart -->
			<div class="lt-summary__cart">
				<?php woocommerce_template_single_add_to_cart(); ?>
			</div>

			<!-- Category / tags meta -->
			<div class="lt-summary__meta">
				<?php if ( $cats ) : ?>
					<p>Category <?php echo wp_kses_post( $cats ); ?></p>
				<?php endif; ?>
				<?php $tags = wc_get_product_tag_list( get_the_ID(), ', ', '<span>', '</span>' ); ?>
				<?php if ( $tags ) : ?>
					<p>Tags <?php echo wp_kses_post( $tags ); ?></p>
				<?php endif; ?>
			</div>

			<!-- Embedded video -->
			<?php if ( $video_url ) : ?>
				<div class="lt-summary__video">
					<?php
					$video_id = '';
					if ( is_string( $video_url ) && preg_match( '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/', $video_url, $m ) ) {
						$video_id = $m[1];
					}
					if ( $video_id ) : ?>
						<iframe src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr( $video_id ); ?>"
						        title="Product video"
						        allowfullscreen
						        loading="lazy"></iframe>
					<?php else : ?>
						<video src="<?php echo esc_url( $video_url ); ?>" controls></video>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div><!-- .lt-summary -->

	</div><!-- .lt-single-top -->

	<!-- Below fold: description + reviews -->
	<div class="lt-single-bottom">

		<?php if ( $product->get_description() ) : ?>
			<div class="lt-product-desc">
				<?php echo wp_kses_post( $product->get_description() ); ?>
			</div>
		<?php endif; ?>

		<!-- Reviews accordion -->
		<?php if ( comments_open() ) : ?>
			<button class="lt-reviews-toggle" id="lt-reviews-toggle" aria-expanded="false">
				+ Reviews
				<span class="lt-reviews-toggle__icon" aria-hidden="true">&#9662;</span>
			</button>
			<div class="lt-reviews-body" id="lt-reviews-body" hidden>
				<?php comments_template(); ?>
			</div>
		<?php endif; ?>

	</div><!-- .lt-single-bottom -->

	<!-- Related products -->
	<?php
	$related_ids = wc_get_related_products( get_the_ID(), 8 );
	if ( ! empty( $related_ids ) ) :
		$related_products = array_map( 'wc_get_product', $related_ids );
		$related_products = array_filter( $related_products );
	?>
		<section class="lt-related">
			<h2 class="lt-related__title">Related Items</h2>
			<div class="lt-product-grid">
				<?php foreach ( $related_products as $rel ) :
					$rel_link  = get_permalink( $rel->get_id() );
					$rel_img   = wp_get_attachment_image_url( $rel->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src();
					$rel_title = $rel->get_name();
					$rel_sale  = $rel->is_on_sale();
				?>
					<article class="lt-product-card">
						<a href="<?php echo esc_url( $rel_link ); ?>" class="lt-product-card__image-wrap" tabindex="-1">
							<img src="<?php echo esc_url( $rel_img ); ?>"
							     alt="<?php echo esc_attr( $rel_title ); ?>"
							     loading="lazy">
							<?php if ( $rel_sale ) : ?>
								<span class="lt-product-card__badge">Sale!</span>
							<?php endif; ?>
						</a>
						<div class="lt-product-card__body">
							<a href="<?php echo esc_url( $rel_link ); ?>" class="lt-product-card__title">
								<?php echo esc_html( $rel_title ); ?>
							</a>
							<p class="lt-product-card__price">
								<?php echo wp_kses_post( $rel->get_price_html() ); ?>
							</p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

</div><!-- .lt-single-wrap -->

<script>
(function () {
	// Thumbnail gallery switcher
	document.querySelectorAll('.lt-gallery__thumb').forEach(function (thumb) {
		thumb.addEventListener('click', function () {
			var main = document.getElementById('lt-main-image');
			if (main && this.dataset.full) {
				main.src = this.dataset.full;
			}
			document.querySelectorAll('.lt-gallery__thumb').forEach(function (t) {
				t.classList.remove('active');
			});
			this.classList.add('active');
		});
	});

	// Reviews accordion
	var toggle = document.getElementById('lt-reviews-toggle');
	var body   = document.getElementById('lt-reviews-body');
	if (toggle && body) {
		toggle.addEventListener('click', function () {
			var open = body.hasAttribute('hidden');
			if (open) {
				body.removeAttribute('hidden');
				toggle.setAttribute('aria-expanded', 'true');
				toggle.textContent = '− Reviews';
			} else {
				body.setAttribute('hidden', '');
				toggle.setAttribute('aria-expanded', 'false');
				toggle.textContent = '+ Reviews';
			}
		});
	}
})();
</script>

<?php
endwhile;
get_footer();
?>
