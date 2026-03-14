</div><!-- #lt-page-wrap -->

<footer class="lt-footer" id="lt-footer">
	<div class="lt-footer__inner">

		<button class="lt-footer__top" id="lt-scroll-top" aria-label="Scroll to top">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20" aria-hidden="true">
				<polyline points="18 15 12 9 6 15"/>
			</svg>
		</button>

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="lt-footer__logo">
			Loothtool.com
		</a>

		<div class="lt-footer__social">
			<a href="https://instagram.com/loothtool" target="_blank" rel="noopener" aria-label="Instagram" class="lt-social lt-social--instagram">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
					<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" fill="none" stroke="#fff" stroke-width="1.5"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke="#fff" stroke-width="2"/>
				</svg>
			</a>
			<a href="https://youtube.com/@loothtool" target="_blank" rel="noopener" aria-label="YouTube" class="lt-social lt-social--youtube">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
					<path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 00-1.95 1.96A29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="#fff"/>
				</svg>
			</a>
			<a href="https://facebook.com/loothtool" target="_blank" rel="noopener" aria-label="Facebook" class="lt-social lt-social--facebook">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
					<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>
				</svg>
			</a>
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
