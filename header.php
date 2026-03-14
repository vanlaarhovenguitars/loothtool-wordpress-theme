<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="lt-header" id="lt-header">
	<div class="lt-header__inner">

		<nav class="lt-nav" role="navigation" aria-label="Primary">
			<ul class="lt-nav__list">
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Loothtool.com</a></li>

				<li class="lt-nav__item--dropdown">
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="lt-nav__dropdown-toggle">
						My Account <span class="lt-nav__arrow" aria-hidden="true">&#9662;</span>
					</a>
					<ul class="lt-nav__dropdown">
						<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
							<li>
								<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>">
									<?php echo esc_html( $label ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</li>

				<li><a href="https://loothgroup.com" target="_blank" rel="noopener">Loothgroup.com</a></li>
			</ul>
		</nav>

		<div class="lt-header__actions">
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>" class="lt-header__account-link">
					<?php echo esc_html( wp_get_current_user()->display_name ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="lt-header__account-link">Sign In</a>
				<span class="lt-header__sep">|</span>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="lt-header__account-link">Sign Up</a>
			<?php endif; ?>

			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="lt-cart-link" aria-label="Cart">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20" aria-hidden="true">
					<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>
				</svg>
				<?php
				$count = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
				if ( $count > 0 ) :
				?>
					<span class="lt-cart-count"><?php echo absint( $count ); ?></span>
				<?php endif; ?>
			</a>
		</div>

	</div>
</header>

<div id="lt-page-wrap">
