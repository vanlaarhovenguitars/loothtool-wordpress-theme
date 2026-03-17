( function () {
	'use strict';

	var input    = document.querySelector( '.lt-hero__input' );
	var form     = document.querySelector( '.lt-hero__search' );
	if ( ! input || ! form ) return;

	var dropdown = document.createElement( 'div' );
	dropdown.className = 'lt-search-dropdown';
	form.appendChild( dropdown );

	var timer   = null;
	var current = -1; // keyboard nav index
	var lastQ   = '';

	function close() {
		dropdown.innerHTML = '';
		dropdown.classList.remove( 'lt-search-dropdown--open' );
		current = -1;
	}

	function open( html ) {
		dropdown.innerHTML = html;
		dropdown.classList.add( 'lt-search-dropdown--open' );
		current = -1;
	}

	function doSearch( q ) {
		if ( q === lastQ ) return;
		lastQ = q;

		if ( q.length < 2 ) { close(); return; }

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', ltSearch.ajax_url, true );
		xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
		xhr.onload = function () {
			if ( xhr.status !== 200 ) return;
			var res;
			try { res = JSON.parse( xhr.responseText ); } catch(e) { return; }
			if ( ! res.success || ! res.data.length ) {
				open( '<div class="lt-search-dropdown__none">No results for &ldquo;' + escHtml(q) + '&rdquo;</div>' );
				return;
			}
			var html = '';
			res.data.forEach( function( p ) {
				html += '<a class="lt-search-dropdown__item" href="' + escHtml(p.url) + '">' +
					'<img class="lt-search-dropdown__img" src="' + escHtml(p.img) + '" alt="" loading="lazy">' +
					'<div class="lt-search-dropdown__info">' +
						'<div class="lt-search-dropdown__name">' + escHtml(p.name) + '</div>' +
						'<div class="lt-search-dropdown__meta">' +
							'<span class="lt-search-dropdown__vendor">' + escHtml(p.vendor) + '</span>' +
							'<span class="lt-search-dropdown__price">' + p.price + '</span>' +
						'</div>' +
					'</div>' +
				'</a>';
			} );
			html += '<a class="lt-search-dropdown__all" href="' + ltSearch.shop_url + '?s=' + encodeURIComponent(q) + '&post_type=product">See all results for &ldquo;' + escHtml(q) + '&rdquo; &rarr;</a>';
			open( html );
		};
		xhr.send( 'action=lt_search_products&nonce=' + ltSearch.nonce + '&q=' + encodeURIComponent( q ) );
	}

	function escHtml( s ) {
		return s.replace( /&/g,'&amp;' ).replace( /</g,'&lt;' ).replace( />/g,'&gt;' ).replace( /"/g,'&quot;' );
	}

	input.addEventListener( 'input', function () {
		clearTimeout( timer );
		var q = this.value.trim();
		timer = setTimeout( function () { doSearch( q ); }, 220 );
	} );

	// Keyboard navigation
	input.addEventListener( 'keydown', function ( e ) {
		var items = dropdown.querySelectorAll( '.lt-search-dropdown__item' );
		if ( ! items.length ) return;
		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			current = Math.min( current + 1, items.length - 1 );
			items[ current ].focus();
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			current = Math.max( current - 1, -1 );
			if ( current === -1 ) { input.focus(); } else { items[ current ].focus(); }
		} else if ( e.key === 'Escape' ) {
			close();
			input.focus();
		}
	} );

	// Close on outside click
	document.addEventListener( 'click', function ( e ) {
		if ( ! form.contains( e.target ) ) close();
	} );

	// Keep dropdown open when tabbing through results
	dropdown.addEventListener( 'keydown', function ( e ) {
		var items = dropdown.querySelectorAll( '.lt-search-dropdown__item' );
		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			current = Math.min( current + 1, items.length - 1 );
			items[ current ].focus();
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			current = Math.max( current - 1, -1 );
			if ( current === -1 ) { input.focus(); } else { items[ current ].focus(); }
		} else if ( e.key === 'Escape' ) {
			close(); input.focus();
		}
	} );
} )();
