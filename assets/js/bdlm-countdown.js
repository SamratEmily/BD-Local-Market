/**
 * BD Local Market — Countdown Timer JavaScript
 *
 * Reads deal end time from .bdlm-deals-section[data-deal-end]
 * or falls back to bdlmData.dealEndTime (Unix timestamp in ms).
 *
 * Updates #bdlm-cd-hours, #bdlm-cd-mins, #bdlm-cd-secs every second.
 * Gracefully hides the countdown and resets on expiry.
 *
 * @package BD_Local_Market
 */

/* global bdlmData */
( function ( window, document ) {
	'use strict';

	const BDLM_Countdown = {
		interval:   null,
		endTime:    null,

		init() {
			// Try to get end time from DOM first (most reliable, handles timezone).
			const section = document.querySelector( '.bdlm-deals-section[data-deal-end]' );
			const isoStr  = section ? section.dataset.dealEnd : null;

			if ( isoStr ) {
				this.endTime = new Date( isoStr ).getTime();
			} else if ( window.bdlmData && bdlmData.dealEndTime ) {
				this.endTime = parseInt( bdlmData.dealEndTime, 10 );
			}

			if ( ! this.endTime || isNaN( this.endTime ) ) return;

			// If already expired on page load, hide immediately.
			if ( Date.now() >= this.endTime ) {
				this.onExpired();
				return;
			}

			this.tick();
			this.interval = setInterval( () => this.tick(), 1000 );
		},

		tick() {
			const now       = Date.now();
			const remaining = this.endTime - now;

			if ( remaining <= 0 ) {
				this.onExpired();
				return;
			}

			const totalSecs = Math.floor( remaining / 1000 );
			const hours     = Math.floor( totalSecs / 3600 );
			const mins      = Math.floor( ( totalSecs % 3600 ) / 60 );
			const secs      = totalSecs % 60;

			this.setEl( 'bdlm-cd-hours', hours );
			this.setEl( 'bdlm-cd-mins',  mins );
			this.setEl( 'bdlm-cd-secs',  secs );
		},

		setEl( id, value ) {
			const el = document.getElementById( id );
			if ( el ) {
				const padded = String( value ).padStart( 2, '0' );
				if ( el.textContent !== padded ) {
					el.textContent = padded;
					// Flip animation.
					el.classList.remove( 'bdlm-cd-flip' );
					void el.offsetWidth; // reflow
					el.classList.add( 'bdlm-cd-flip' );
				}
			}
		},

		onExpired() {
			if ( this.interval ) {
				clearInterval( this.interval );
				this.interval = null;
			}

			const countdown = document.querySelector( '.bdlm-countdown' );
			if ( countdown ) {
				countdown.style.transition = 'opacity 0.5s ease';
				countdown.style.opacity    = '0';
				setTimeout( () => {
					countdown.style.display = 'none';
				}, 500 );
			}
		},
	};

	document.addEventListener( 'DOMContentLoaded', () => BDLM_Countdown.init() );

} )( window, document );
