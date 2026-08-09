<?php
/**
 * BDLM Public — Public-facing (front-end) functionality.
 *
 * Handles all front-end output that isn't tied to a specific feature class.
 * Feature classes (BDLM_Product_Card, BDLM_Homepage, etc.) are still
 * registered by BDLM_Core; this class owns shared public concerns:
 *
 * - Free shipping notice bar
 * - Cart/checkout notices sourced from plugin settings
 * - Body-class additions for active sections
 * - Output of localized settings into the page head for JS consumption
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BDLM_Public
 */
class BDLM_Public {

	/**
	 * Cached general settings.
	 *
	 * @var array
	 */
	private $general  = array();

	/**
	 * Cached homepage settings.
	 *
	 * @var array
	 */
	private $homepage = array();

	/**
	 * Cached product card settings.
	 *
	 * @var array
	 */
	private $card     = array();

	/**
	 * Cached checkout settings.
	 *
	 * @var array
	 */
	private $checkout = array();

	/**
	 * Constructor — load settings and register hooks.
	 */
	public function __construct() {
		$this->general  = wp_parse_args( (array) get_option( 'bdlm_general_settings',      array() ), bdlm_default_general_settings() );
		$this->homepage = wp_parse_args( (array) get_option( 'bdlm_homepage_settings',     array() ), bdlm_default_homepage_settings() );
		$this->card     = wp_parse_args( (array) get_option( 'bdlm_product_card_settings', array() ), bdlm_default_product_card_settings() );
		$this->checkout = wp_parse_args( (array) get_option( 'bdlm_checkout_settings',     array() ), bdlm_default_checkout_settings() );

		// Free shipping bar on cart & checkout.
		add_action( 'woocommerce_before_cart',     array( $this, 'render_free_shipping_bar' ) );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'render_free_shipping_bar' ), 3 );

		// Body classes.
		add_filter( 'body_class', array( $this, 'add_body_classes' ) );

		// Inline JSON config for JS modules.
		add_action( 'wp_head', array( $this, 'output_js_config' ), 1 );
	}

	// =========================================================================
	// Free Shipping Notice Bar
	// =========================================================================

	/**
	 * Render a green notice bar with the free-shipping threshold text.
	 */
	public function render_free_shipping_bar() {
		$text = trim( $this->general['free_shipping_threshold_text'] ?? '' );
		if ( empty( $text ) ) {
			return;
		}
		printf(
			'<div class="bdlm-free-shipping-bar" role="note"><span class="bdlm-free-shipping-bar__icon" aria-hidden="true">🚚</span> %s</div>',
			esc_html( $text )
		);
	}

	// =========================================================================
	// Body Classes
	// =========================================================================

	/**
	 * Add contextual body classes for CSS targeting.
	 *
	 * @param array $classes Existing body classes.
	 * @return array
	 */
	public function add_body_classes( $classes ) {
		$classes[] = 'bdlm-active';

		// Badge style class for CSS variants.
		$badge_style = $this->card['badge_style'] ?? 'pill';
		$classes[]   = 'bdlm-badge-style--' . sanitize_html_class( $badge_style );

		// Homepage section enables as body classes.
		$sections = array( 'hero', 'categories', 'recommended', 'deals', 'trending', 'featured', 'brands' );
		foreach ( $sections as $s ) {
			if ( ! empty( $this->homepage[ "enable_{$s}" ] ) ) {
				$classes[] = "bdlm-section-{$s}-enabled";
			}
		}

		return $classes;
	}

	// =========================================================================
	// JS Config Output
	// =========================================================================

	/**
	 * Output a small JSON block in <head> so JS can access settings
	 * without a separate AJAX call.
	 *
	 * Note: sensitive data (nonces etc.) are localized in BDLM_Assets.
	 * This block carries only non-security-sensitive UI config.
	 */
	public function output_js_config() {
		$config = array(
			'currencySymbol'    => $this->general['currency_symbol']    ?? '৳',
			'deliveryTimeText'  => $this->general['delivery_time_text'] ?? '1-2 hours',
			'addToBagText'      => $this->card['add_to_bag_text']       ?? 'Add to Bag',
			'addingText'        => $this->card['adding_text']           ?? 'Adding…',
			'addedText'         => $this->card['added_text']            ?? '✓ Added',
			'showSavingsBadge'  => ! empty( $this->card['show_savings_badge'] ),
			'showDeliveryBadge' => ! empty( $this->card['show_delivery_badge'] ),
			'showUnitInfo'      => ! empty( $this->card['show_unit_info'] ),
			'badgeStyle'        => $this->card['badge_style']           ?? 'pill',
		);

		printf(
			'<script id="bdlm-config">window.bdlmConfig = %s;</script>' . "\n",
			wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP )
		);
	}

	// =========================================================================
	// Public Getters (for other classes to consume settings cleanly)
	// =========================================================================

	/** @return array General settings. */
	public function get_general()  { return $this->general;  }

	/** @return array Homepage section settings. */
	public function get_homepage() { return $this->homepage; }

	/** @return array Product card settings. */
	public function get_card()     { return $this->card;     }

	/** @return array Checkout settings. */
	public function get_checkout() { return $this->checkout; }

	/**
	 * Helper: get a value from general settings.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public function general( $key, $default = '' ) {
		return $this->general[ $key ] ?? $default;
	}

	/**
	 * Helper: get a value from homepage settings.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public function homepage( $key, $default = '' ) {
		return $this->homepage[ $key ] ?? $default;
	}

	/**
	 * Helper: check if a homepage section is enabled.
	 *
	 * @param string $section Section slug (e.g. 'hero', 'deals').
	 * @return bool
	 */
	public function section_enabled( $section ) {
		return ! empty( $this->homepage[ "enable_{$section}" ] );
	}
}
