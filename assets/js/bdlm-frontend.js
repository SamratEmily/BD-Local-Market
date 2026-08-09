/**
 * BD Local Market — Frontend JavaScript
 *
 * Handles:
 * - Mobile bottom navigation bar rendering
 * - WC cart fragment updates (cart count badge)
 * - Section scroll-reveal via Intersection Observer
 * - AJAX add-to-cart button state management
 *
 * @package BD_Local_Market
 */

/* global bdlmData, jQuery */
( function ( $, window, document ) {
	'use strict';

	// =========================================================================
	// 1. MOBILE BOTTOM NAVIGATION
	// =========================================================================
	const BDLM_MobileNav = {
		init() {
			if ( window.innerWidth > 768 ) return;

			const nav = this.createNav();
			document.body.appendChild( nav );
			this.updateCartCount();

			// Listen for WooCommerce cart fragment updates.
			$( document.body ).on( 'wc_fragments_refreshed wc_fragments_loaded', () => {
				this.updateCartCount();
			} );
		},

		createNav() {
			const nav = document.createElement( 'nav' );
			nav.className = 'bdlm-mobile-nav';
			nav.setAttribute( 'aria-label', 'Mobile navigation' );

			const items = [
				{ icon: '🏠', label: 'Home',    href: bdlmData.homeUrl || '/',                      id: 'bdlm-nav-home'   },
				{ icon: '🛍️', label: 'Shop',    href: bdlmData.shopUrl || '/shop',                  id: 'bdlm-nav-shop'   },
				{ icon: '🔍', label: 'Search',  href: bdlmData.searchUrl || '/?s=&post_type=product', id: 'bdlm-nav-search' },
				{ icon: '🛒', label: 'Cart',    href: bdlmData.cartUrl || '/cart',                  id: 'bdlm-nav-cart', isCart: true },
				{ icon: '👤', label: 'Account', href: bdlmData.accountUrl || '/my-account',         id: 'bdlm-nav-account' },
			];

			items.forEach( ( item ) => {
				const a = document.createElement( 'a' );
				a.href        = item.href;
				a.className   = 'bdlm-mobile-nav__item';
				a.id          = item.id;
				a.setAttribute( 'aria-label', item.label );

				const iconSpan = document.createElement( 'span' );
				iconSpan.className    = 'bdlm-mobile-nav__icon';
				iconSpan.textContent  = item.icon;
				iconSpan.setAttribute( 'aria-hidden', 'true' );

				const labelSpan = document.createElement( 'span' );
				labelSpan.className   = 'bdlm-mobile-nav__label';
				labelSpan.textContent = item.label;

				a.appendChild( iconSpan );
				a.appendChild( labelSpan );

				// Cart badge placeholder.
				if ( item.isCart ) {
					const badge = document.createElement( 'span' );
					badge.className   = 'bdlm-mobile-nav__cart-badge';
					badge.id          = 'bdlm-cart-count';
					badge.style.display = 'none';
					a.appendChild( badge );
				}

				nav.appendChild( a );
			} );

			// Highlight active item based on current URL.
			const currentPath = window.location.pathname;
			nav.querySelectorAll( '.bdlm-mobile-nav__item' ).forEach( ( link ) => {
				const linkPath = new URL( link.href, window.location.origin ).pathname;
				if (
					( linkPath === '/' && currentPath === '/' ) ||
					( linkPath !== '/' && currentPath.startsWith( linkPath ) )
				) {
					link.classList.add( 'active' );
					link.setAttribute( 'aria-current', 'page' );
				}
			} );

			return nav;
		},

		updateCartCount() {
			const badge = document.getElementById( 'bdlm-cart-count' );
			if ( ! badge ) return;

			// Try to read from WC fragment count span.
			const wcCartCount = document.querySelector( '.cart-contents-count, .woocommerce-cart-count' );
			let count = 0;

			if ( wcCartCount ) {
				count = parseInt( wcCartCount.textContent, 10 ) || 0;
			} else {
				// Try the header cart widget.
				const cartWidget = document.querySelector( '.widget_shopping_cart .woocommerce-mini-cart__product' );
				const cartWidgets = document.querySelectorAll( '.woocommerce-mini-cart__product' );
				count = cartWidgets ? cartWidgets.length : 0;
			}

			if ( count > 0 ) {
				badge.textContent   = count > 9 ? '9+' : count;
				badge.style.display = 'flex';
			} else {
				badge.style.display = 'none';
			}
		},
	};

	// =========================================================================
	// 2. SCROLL-REVEAL FOR SECTIONS
	// =========================================================================
	const BDLM_ScrollReveal = {
		init() {
			if ( ! ( 'IntersectionObserver' in window ) ) return;

			const sections = document.querySelectorAll( '.bdlm-section' );
			if ( ! sections.length ) return;

			// Set initial state for off-screen sections.
			sections.forEach( ( section ) => {
				section.style.opacity  = '0';
				section.style.transform = 'translateY(24px)';
				section.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
			} );

			const observer = new IntersectionObserver(
				( entries ) => {
					entries.forEach( ( entry ) => {
						if ( entry.isIntersecting ) {
							entry.target.style.opacity  = '1';
							entry.target.style.transform = 'translateY(0)';
							observer.unobserve( entry.target );
						}
					} );
				},
				{ threshold: 0.08 }
			);

			sections.forEach( ( section ) => observer.observe( section ) );
		},
	};

	// =========================================================================
	// 3. ENHANCED ADD-TO-CART BUTTON STATES
	// =========================================================================
	const BDLM_CartButton = {
		init() {
			$( document.body ).on( 'adding_to_cart', ( e, $button ) => {
				$button.text( bdlmData.addingText || 'Adding…' );
				$button.addClass( 'bdlm-adding' ).prop( 'disabled', true );
			} );

			$( document.body ).on( 'added_to_cart', ( e, fragments, cartHash, $button ) => {
				if ( $button && $button.length ) {
					const cartUrl = bdlmData.cartUrl || '/cart';
					$button
						.html( '<span aria-hidden="true">🛒</span> ✓ Added — View Cart →' )
						.addClass( 'bdlm-added bdlm-go-to-cart' )
						.removeClass( 'bdlm-adding ajax_add_to_cart add_to_cart_button' )
						.prop( 'disabled', false )
						.attr( 'href', cartUrl );
				}
				BDLM_MobileNav.updateCartCount();
			} );
		},
	};

	// =========================================================================
	// 4. SMOOTH HORIZONTAL SCROLL FOR PRODUCT ROWS
	// =========================================================================
	const BDLM_ScrollRows = {
		init() {
			const scrollWraps = document.querySelectorAll( '.bdlm-products-scroll-wrap' );
			scrollWraps.forEach( ( wrap ) => {
				let isDown = false, startX, scrollLeft;

				wrap.addEventListener( 'mousedown', ( e ) => {
					isDown     = true;
					startX     = e.pageX - wrap.offsetLeft;
					scrollLeft = wrap.scrollLeft;
					wrap.style.cursor = 'grabbing';
				} );

				wrap.addEventListener( 'mouseleave',   () => { isDown = false; wrap.style.cursor = ''; } );
				wrap.addEventListener( 'mouseup',      () => { isDown = false; wrap.style.cursor = ''; } );
				wrap.addEventListener( 'mousemove', ( e ) => {
					if ( ! isDown ) return;
					e.preventDefault();
					const x  = e.pageX - wrap.offsetLeft;
					const dx = ( x - startX ) * 1.2;
					wrap.scrollLeft = scrollLeft - dx;
				} );
			} );
		},
	};

	// =========================================================================
	// INIT
	// =========================================================================
	$( document ).ready( () => {
		BDLM_MobileNav.init();
		BDLM_ScrollReveal.init();
		BDLM_CartButton.init();
		BDLM_ScrollRows.init();
	} );

} )( jQuery, window, document );
