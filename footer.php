</div><!-- #lt-page-wrap -->

<footer class="lt-footer" id="lt-footer">
	<div class="lt-footer__inner">

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="lt-footer__logo">
			Loothtool.com
		</a>

		<p class="lt-footer__tagline">The Guitar Gear Marketplace</p>

		<div class="lt-footer__social">
			<a href="https://instagram.com/loothtool" target="_blank" rel="noopener" aria-label="Instagram" class="lt-social lt-social--instagram">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18" aria-hidden="true">
					<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.8" fill="currentColor" stroke="none"/>
				</svg>
			</a>
			<a href="https://youtube.com/@loothtool" target="_blank" rel="noopener" aria-label="YouTube" class="lt-social lt-social--youtube">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18" aria-hidden="true">
					<rect x="2" y="4" width="20" height="16" rx="4"/><polygon points="10 8.5 16 12 10 15.5" fill="currentColor" stroke="none"/>
				</svg>
			</a>
			<a href="https://facebook.com/loothtool" target="_blank" rel="noopener" aria-label="Facebook" class="lt-social lt-social--facebook">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18" aria-hidden="true">
					<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>
				</svg>
			</a>
		</div>

		<div class="lt-footer__divider"></div>

		<div class="lt-footer__bottom">
			<span class="lt-footer__copy">&copy; <?php echo date( 'Y' ); ?> Loothtool.com &mdash; All rights reserved.</span>
			<?php
			$bug_url = 'https://github.com/vanlaarhovenguitars/loothtool-wordpress-theme/issues/new'
				. '?labels=bug&title=[Bug]+&body='
				. rawurlencode(
					"**Page URL:**\n" . esc_url_raw( ( is_ssl() ? 'https' : 'http' ) . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ) . "\n\n"
					. "**Describe the bug:**\n\n"
					. "**Steps to reproduce:**\n1. \n2. \n\n"
					. "**Expected behaviour:**\n\n"
					. "**Screenshots:** (paste or drag in)"
				);
			?>
			<a href="<?php echo esc_url( $bug_url ); ?>" target="_blank" rel="noopener" class="lt-bug-report">
				<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
				Report a bug
			</a>
		</div>

	</div>
</footer>

<div class="lt-fab-group">
	<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="lt-fab lt-fab--checkout" aria-label="Go to checkout">
		<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
			<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
			<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
		</svg>
		<span class="lt-fab__label">Checkout</span>
	</a>
	<button class="lt-dark-toggle lt-fab" id="lt-dark-toggle" aria-label="Toggle dark mode">
		<svg class="lt-icon-moon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
			<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
		</svg>
		<svg class="lt-icon-sun" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
			<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
		</svg>
	</button>
</div>
<script>
(function(){
	var btn = document.getElementById('lt-dark-toggle');
	if (!btn) return;
	btn.addEventListener('click', function(){
		var isDark = document.body.classList.toggle('lt-dark');
		localStorage.setItem('lt-dark', isDark ? '1' : '0');
	});
})();
</script>
<?php wp_footer(); ?>
</body>
</html>
