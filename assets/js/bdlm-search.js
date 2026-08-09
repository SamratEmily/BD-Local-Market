/**
 * BD Local Market — Live Search JavaScript
 *
 * Features:
 * - Attaches to Astra's search input field
 * - Debounced AJAX requests (300ms)
 * - Keyboard navigation (↑ ↓ Enter Escape)
 * - Positions dropdown below the search input
 * - Accessible: role="listbox", aria-selected
 *
 * @package BD_Local_Market
 */

/* global bdlmData, jQuery */
( function ( $, window, document ) {
	'use strict';

	if ( ! bdlmData || bdlmData.enableLiveSearch !== '1' ) return;

	const BDLM_Search = {
		$input:    null,
		$dropdown: null,
		$results:  null,
		$viewAll:  null,
		timer:     null,
		cache:     {},
		activeIdx: -1,
		lastQuery: '',

		selectors: {
			// Astra search field selectors (try multiple).
			input: [
				'.ast-search-menu-icon .search-field',
				'.search-field',
				'input[name="s"][type="search"]',
			].join( ', ' ),
			dropdown: '#bdlm-search-dropdown',
			results:  '#bdlm-search-results',
			viewAll:  '#bdlm-search-view-all',
		},

		init() {
			const $input = $( this.selectors.input ).first();
			if ( ! $input.length ) return;

			this.$input    = $input;
			this.$dropdown = $( this.selectors.dropdown );
			this.$results  = $( this.selectors.results );
			this.$viewAll  = $( this.selectors.viewAll );

			if ( ! this.$dropdown.length ) return;

			this.bindEvents();
		},

		bindEvents() {
			this.$input.on( 'input.bdlm', ( e ) => {
				const query = e.target.value.trim();
				clearTimeout( this.timer );

				if ( query.length < 2 ) {
					this.hide();
					return;
				}

				this.timer = setTimeout( () => this.search( query ), 300 );
			} );

			// Keyboard navigation.
			this.$input.on( 'keydown.bdlm', ( e ) => {
				if ( this.$dropdown.attr( 'hidden' ) !== undefined && this.$dropdown.is( '[hidden]' ) ) return;

				const items = this.$results.find( '.bdlm-search-item' );
				if ( ! items.length ) return;

				switch ( e.key ) {
					case 'ArrowDown':
						e.preventDefault();
						this.activeIdx = Math.min( this.activeIdx + 1, items.length - 1 );
						this.highlightItem( items );
						break;
					case 'ArrowUp':
						e.preventDefault();
						this.activeIdx = Math.max( this.activeIdx - 1, -1 );
						this.highlightItem( items );
						break;
					case 'Enter':
						if ( this.activeIdx >= 0 ) {
							e.preventDefault();
							const href = $( items[ this.activeIdx ] ).attr( 'href' );
							if ( href ) window.location.href = href;
						}
						break;
					case 'Escape':
						this.hide();
						this.$input.blur();
						break;
				}
			} );

			// Close on outside click.
			$( document ).on( 'click.bdlm', ( e ) => {
				if ( ! $( e.target ).closest( this.selectors.input + ', ' + this.selectors.dropdown ).length ) {
					this.hide();
				}
			} );

			// Focus: re-show if query is still there.
			this.$input.on( 'focus.bdlm', () => {
				const query = this.$input.val().trim();
				if ( query.length >= 2 && this.$results.children().length ) {
					this.show();
				}
			} );
		},

		search( query ) {
			if ( query === this.lastQuery ) {
				this.show();
				return;
			}

			// Use cache if available.
			if ( this.cache[ query ] ) {
				this.render( this.cache[ query ], query );
				return;
			}

			this.showLoading();

			$.ajax( {
				url:      bdlmData.ajaxUrl,
				method:   'GET',
				dataType: 'json',
				data: {
					action: 'bdlm_search',
					nonce:  bdlmData.nonce,
					query:  query,
				},
				success: ( response ) => {
					if ( response.success ) {
						this.cache[ query ] = response.data.results;
						this.render( response.data.results, query );
					} else {
						this.showEmpty();
					}
					this.lastQuery = query;
				},
				error: () => {
					this.showEmpty();
				},
			} );
		},

		render( results, query ) {
			this.activeIdx = -1;

			if ( ! results || ! results.length ) {
				this.showEmpty();
				return;
			}

			const html = results.map( ( product, idx ) => {
				const title = this.highlight( product.title, query );
				const saleBadge = product.on_sale
					? `<span class="bdlm-search-item__sale-badge">SALE</span>`
					: '';

				return `
				<a href="${ this.escapeHtml( product.url ) }"
					class="bdlm-search-item"
					role="option"
					id="bdlm-search-option-${ idx }"
					aria-selected="false">
					<img
						class="bdlm-search-item__thumb"
						src="${ this.escapeHtml( product.thumb ) }"
						alt="${ this.escapeHtml( product.title ) }"
						loading="lazy"
						width="48" height="48" />
					<div class="bdlm-search-item__info">
						<div class="bdlm-search-item__title">${ title }</div>
						${ product.category ? `<div class="bdlm-search-item__cat">${ this.escapeHtml( product.category ) }</div>` : '' }
					</div>
					<div class="bdlm-search-item__price-wrap">
						<span class="bdlm-search-item__price">${ this.escapeHtml( product.price ) }</span>
						${ saleBadge }
					</div>
				</a>`;
			} );

			this.$results.html( html.join( '' ) );

			// View all link.
			const searchUrl = `/?s=${ encodeURIComponent( query ) }&post_type=product`;
			this.$viewAll
				.attr( 'href', searchUrl )
				.removeAttr( 'hidden' )
				.show();

			this.show();
		},

		showLoading() {
			this.$results.html(
				`<div class="bdlm-search-loading">🔍 ${ bdlmData.searchPlaceholder || 'Searching…' }</div>`
			);
			this.$viewAll.hide();
			this.show();
		},

		showEmpty() {
			this.$results.html(
				`<div class="bdlm-search-empty">${ bdlmData.noResultsText || 'No products found.' }</div>`
			);
			this.$viewAll.hide();
			this.show();
		},

		show() {
			// Position dropdown below search input.
			const rect = this.$input[ 0 ].getBoundingClientRect();
			this.$dropdown.css( {
				position: 'fixed',
				top:      rect.bottom + 4 + 'px',
				left:     Math.max( 0, rect.left ) + 'px',
				width:    Math.min( rect.width || 400, 600 ) + 'px',
			} );
			this.$dropdown.removeAttr( 'hidden' ).show();
		},

		hide() {
			this.$dropdown.attr( 'hidden', '' ).hide();
			this.activeIdx = -1;
		},

		highlightItem( items ) {
			items.removeClass( 'bdlm-search-item--active' ).attr( 'aria-selected', 'false' );
			if ( this.activeIdx >= 0 ) {
				$( items[ this.activeIdx ] )
					.addClass( 'bdlm-search-item--active' )
					.attr( 'aria-selected', 'true' )[ 0 ]
					.scrollIntoView( { block: 'nearest' } );
			}
		},

		/**
		 * Highlight query text within a product title.
		 *
		 * @param {string} text  Full text.
		 * @param {string} query Search query.
		 * @return {string} HTML with highlighted match.
		 */
		highlight( text, query ) {
			const escaped = this.escapeHtml( text );
			const escapedQ = query.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
			return escaped.replace(
				new RegExp( '(' + escapedQ + ')', 'gi' ),
				'<mark style="background:#d4f5e2;border-radius:2px;padding:0 1px;">$1</mark>'
			);
		},

		escapeHtml( str ) {
			const div = document.createElement( 'div' );
			div.appendChild( document.createTextNode( String( str ) ) );
			return div.innerHTML;
		},
	};

	$( document ).ready( () => BDLM_Search.init() );

} )( jQuery, window, document );
