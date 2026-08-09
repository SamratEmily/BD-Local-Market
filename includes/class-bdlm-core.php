<?php
/**
 * BDLM Core — Plugin bootstrap and hooks registry.
 *
 * Singleton that instantiates every feature class and provides a
 * shared settings accessor so all feature classes read from one source.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BDLM_Core
 */
class BDLM_Core {

	/**
	 * Singleton instance.
	 *
	 * @var BDLM_Core|null
	 */
	private static $instance = null;

	/**
	 * Public-facing module (source of truth for front-end settings).
	 *
	 * @var BDLM_Public
	 */
	public $public;

	/**
	 * Get the singleton instance.
	 *
	 * @return BDLM_Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — private to enforce singleton.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->register_hooks();
	}

	/**
	 * Instantiate all feature classes.
	 */
	private function load_dependencies() {
		// Public module is the settings source of truth.
		$this->public = new BDLM_Public();

		// Feature classes.
		new BDLM_Assets();
		new BDLM_Product_Card();
		new BDLM_Homepage();
		new BDLM_Shortcodes();
		new BDLM_Search();
		new BDLM_Checkout();
		new BDLM_Countdown();
		new BDLM_Category();
	}

	/**
	 * Register global plugin hooks.
	 */
	private function register_hooks() {
		// "Add to cart" → "Add to Bag" (pulled from settings).
		add_filter( 'woocommerce_product_add_to_cart_text',        array( $this, 'add_to_bag_text' ) );
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'add_to_bag_text' ) );
	}

	/**
	 * Return the configured "Add to Bag" button label.
	 *
	 * @return string
	 */
	public function add_to_bag_text() {
		$card_settings = (array) get_option( 'bdlm_product_card_settings', array() );
		return ! empty( $card_settings['add_to_bag_text'] )
			? $card_settings['add_to_bag_text']
			: __( 'Add to Bag', 'bd-local-market' );
	}

	// =========================================================================
	// Convenience Accessors (delegates to BDLM_Public)
	// =========================================================================

	/**
	 * Get a value from the general settings option.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public function get_setting( $key, $default = '' ) {
		return $this->public->general( $key, $default );
	}

	/**
	 * Check if a homepage section is enabled in settings.
	 *
	 * @param string $section Section slug.
	 * @return bool
	 */
	public function section_enabled( $section ) {
		return $this->public->section_enabled( $section );
	}

	/**
	 * Get a homepage section setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public function get_homepage( $key, $default = '' ) {
		return $this->public->homepage( $key, $default );
	}
}
