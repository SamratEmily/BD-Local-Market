<?php
/**
 * BDLM Assets — Enqueue frontend and admin CSS/JS.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BDLM_Assets
 */
class BDLM_Assets {

	/**
	 * Constructor — attach hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend() {
		$settings = wp_parse_args(
			(array) get_option( 'bdlm_general_settings', array() ),
			bdlm_default_general_settings()
		);

		// Google Fonts: Hind Siliguri (Bangla-ready) + Inter (UI).
		wp_enqueue_style(
			'bdlm-google-fonts',
			'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap',
			array(),
			null
		);

		// Main frontend stylesheet.
		wp_enqueue_style(
			'bdlm-frontend',
			BDLM_URL . 'assets/css/bdlm-frontend.css',
			array( 'bdlm-google-fonts' ),
			BDLM_VERSION
		);

		// Product card stylesheet (Shwapno-style cards + grid).
		wp_enqueue_style(
			'bdlm-product-card',
			BDLM_URL . 'assets/css/bdlm-product-card.css',
			array( 'bdlm-frontend' ),
			BDLM_VERSION
		);

		// Main frontend script.
		wp_enqueue_script(
			'bdlm-frontend',
			BDLM_URL . 'assets/js/bdlm-frontend.js',
			array( 'jquery' ),
			BDLM_VERSION,
			true
		);

		// Live search script.
		if ( ! empty( $settings['enable_live_search'] ) ) {
			wp_enqueue_script(
				'bdlm-search',
				BDLM_URL . 'assets/js/bdlm-search.js',
				array( 'jquery', 'bdlm-frontend' ),
				BDLM_VERSION,
				true
			);
		}

		// Countdown script — only if a deal end time is set.
		if ( ! empty( $settings['deal_end_time'] ) ) {
			wp_enqueue_script(
				'bdlm-countdown',
				BDLM_URL . 'assets/js/bdlm-countdown.js',
				array( 'jquery' ),
				BDLM_VERSION,
				true
			);
		}

		// Localize data for JS.
		$deal_end_timestamp = '';
		if ( ! empty( $settings['deal_end_time'] ) ) {
			$deal_end_timestamp = strtotime( $settings['deal_end_time'] ) * 1000; // JS needs ms.
		}

		wp_localize_script(
			'bdlm-frontend',
			'bdlmData',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'bdlm_nonce' ),
				'dealEndTime'        => $deal_end_timestamp,
				'currencySymbol'     => isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '৳',
				'addToBagText'       => __( 'Add to Bag', 'bd-local-market' ),
				'addingText'         => __( 'Adding...', 'bd-local-market' ),
				'addedText'          => __( '✓ Added', 'bd-local-market' ),
				'searchPlaceholder'  => __( 'Search for groceries, vegetables, fruits...', 'bd-local-market' ),
				'noResultsText'      => __( 'No products found.', 'bd-local-market' ),
				'searchResultsLimit' => isset( $settings['search_results_limit'] ) ? absint( $settings['search_results_limit'] ) : 8,
				'enableLiveSearch'   => ! empty( $settings['enable_live_search'] ) ? '1' : '0',
				'homeUrl'            => home_url( '/' ),
				'shopUrl'            => wc_get_page_permalink( 'shop' ),
				'cartUrl'            => wc_get_cart_url(),
				'accountUrl'         => wc_get_page_permalink( 'myaccount' ),
				'searchUrl'          => home_url( '/?s=&post_type=product' ),
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 */
	public function enqueue_admin( $hook_suffix ) {
		// Load on either our new settings page or the old one.
		$load = (
			false !== strpos( $hook_suffix, 'bd-local-market' ) ||
			false !== strpos( $hook_suffix, 'bd_local_market' )
		);

		if ( ! $load ) {
			return;
		}

		wp_enqueue_style(
			'bdlm-admin',
			BDLM_URL . 'assets/css/bdlm-admin.css',
			array(),
			BDLM_VERSION
		);
	}
}
