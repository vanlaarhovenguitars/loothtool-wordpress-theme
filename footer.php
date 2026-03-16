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
			<button class="lt-footer__top" id="lt-scroll-top" aria-label="Scroll to top">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16" aria-hidden="true">
					<polyline points="18 15 12 9 6 15"/>
				</svg>
			</button>
		</div>

	</div>
</footer>

<script>
(function () {
	var btn = document.getElementById('lt-scroll-top');
	if (btn) {
		btn.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	}
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
