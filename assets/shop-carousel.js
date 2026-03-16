( function () {
	'use strict';

	var strip = document.getElementById( 'lt-shops-strip' );
	if ( ! strip ) return;

	var chips = Array.from( strip.querySelectorAll( '.lt-shop-chip' ) );
	if ( ! chips.length ) return;

	function updateScales() {
		var rect   = strip.getBoundingClientRect();
		var center = rect.left + rect.width / 2;

		chips.forEach( function ( chip ) {
			var cr         = chip.getBoundingClientRect();
			var chipCenter = cr.left + cr.width / 2;
			var dist       = Math.abs( center - chipCenter );
			var maxDist    = rect.width * 0.52;
			var t          = Math.min( dist / maxDist, 1 );
			var scale      = ( 1.28 - t * 0.38 ).toFixed( 3 ); // 1.28 → 0.90
			var opacity    = ( 1 - t * 0.45 ).toFixed( 2 );    // 1.0  → 0.55
			chip.style.transform = 'scale(' + scale + ')';
			chip.style.opacity   = opacity;
		} );
	}

	// On mobile/tablet when the strip actually scrolls, center the middle chip on load.
	function centerInitial() {
		if ( strip.scrollWidth <= strip.clientWidth ) {
			// All chips visible — CSS justify-content:center handles layout.
			updateScales();
			return;
		}
		// Find the chip closest to the middle index and scroll to center it.
		var mid     = Math.floor( chips.length / 2 );
		var target  = chips[ mid ];
		var offset  = target.offsetLeft - ( strip.clientWidth / 2 ) + ( target.offsetWidth / 2 );
		strip.scrollLeft = Math.max( 0, offset );
		updateScales();
	}

	centerInitial();

	strip.addEventListener( 'scroll', updateScales, { passive: true } );
	window.addEventListener( 'resize', centerInitial );
} )();
