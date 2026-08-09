<?php
/**
 * Plugin Name:       BD Local Market
 * Plugin URI:        https://github.com/samrathossen/bd-local-market
 * Description:       A production-ready Bangladeshi grocery/supermarket-style WooCommerce store plugin. Inspired by Shwapno. Built for the Bangladeshi market with BDT currency, Bangla + English support.
 * Version:           2.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Samrat Hossen
 * Author URI:        https://github.com/samrathossen
 * Text Domain:       bd-local-market
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:      9.9
 *
 * @package BD_Local_Market
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Guard: only load once.
if ( defined( 'BDLM_LOADED' ) ) {
	return;
}
define( 'BDLM_LOADED', true );

// ============================================================
// Constants
// ============================================================
define( 'BDLM_VERSION',     '2.1.0' );
define( 'BDLM_FILE',        __FILE__ );
define( 'BDLM_PATH',        plugin_dir_path( __FILE__ ) );
define( 'BDLM_URL',         plugin_dir_url( __FILE__ ) );
define( 'BDLM_SLUG',        'bd-local-market' );
define( 'BDLM_TEXT_DOMAIN', 'bd-local-market' );

// ============================================================
// Autoloader
// ============================================================
spl_autoload_register( function ( $class ) {
	if ( strpos( $class, 'BDLM_' ) !== 0 ) {
		return;
	}
	$file_name = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

	$search_dirs = array(
		BDLM_PATH . 'includes/',
		BDLM_PATH . 'admin/',
		BDLM_PATH . 'public/',
	);

	foreach ( $search_dirs as $dir ) {
		$file = $dir . $file_name;
		if ( file_exists( $file ) ) {
			require_once $file;
			return;
		}
	}
} );

// ============================================================
// Activation Hook
// ============================================================
register_activation_hook( __FILE__, 'bdlm_activate' );

/**
 * Plugin activation callback.
 * Seeds default option values and flushes rewrite rules.
 */
function bdlm_activate() {
	// Seed General settings.
	if ( false === get_option( 'bdlm_general_settings' ) ) {
		update_option( 'bdlm_general_settings', bdlm_default_general_settings() );
	}
	// Seed Homepage settings.
	if ( false === get_option( 'bdlm_homepage_settings' ) ) {
		update_option( 'bdlm_homepage_settings', bdlm_default_homepage_settings() );
	}
	// Seed Checkout settings.
	if ( false === get_option( 'bdlm_checkout_settings' ) ) {
		update_option( 'bdlm_checkout_settings', bdlm_default_checkout_settings() );
	}
	// Seed Product Card settings.
	if ( false === get_option( 'bdlm_product_card_settings' ) ) {
		update_option( 'bdlm_product_card_settings', bdlm_default_product_card_settings() );
	}

	flush_rewrite_rules();
}

// ============================================================
// Deactivation Hook
// ============================================================
register_deactivation_hook( __FILE__, 'bdlm_deactivate' );

/**
 * Plugin deactivation callback.
 */
function bdlm_deactivate() {
	// Clear deal expiry cron.
	wp_clear_scheduled_hook( 'bdlm_expire_deals' );
	flush_rewrite_rules();
}

// ============================================================
// Default Option Factories (shared by activation + Settings API)
// ============================================================

/** @return array Default General settings. */
function bdlm_default_general_settings() {
	return array(
		'currency_symbol'              => '৳',
		'delivery_time_text'           => '1-2 hours',
		'free_shipping_threshold_text' => 'Free shipping on orders over ৳500!',
		'deal_end_time'                => '',
		'enable_live_search'           => '1',
		'search_results_limit'         => '8',
	);
}

/** @return array Default Homepage Section settings. */
function bdlm_default_homepage_settings() {
	return array(
		// Hero.
		'enable_hero'             => '1',
		'hero_title'              => 'Fresh Groceries, Delivered Fast',
		'hero_subtitle'           => 'Shop fresh from our curated selection — delivered to your door.',
		'hero_cta_text'           => 'Shop Now',
		'hero_cta_url'            => '',
		// Categories.
		'enable_categories'       => '1',
		'categories_title'        => 'Shop by Category',
		'categories_limit'        => '12',
		'featured_categories'     => '', // Comma-separated category slugs/IDs to highlight on homepage
		// Recommended.
		'enable_recommended'      => '1',
		'recommended_title'       => 'Recommended For You',
		'recommended_limit'       => '10',
		// Deals.
		'enable_deals'            => '1',
		'deals_title'             => "Today's Best Deals",
		'deals_limit'             => '8',
		// Trending.
		'enable_trending'         => '1',
		'trending_title'          => 'Hot & Trending 🔥',
		'trending_limit'          => '10',
		// Featured.
		'enable_featured'         => '1',
		'featured_title'          => 'Featured Finds ✨',
		'featured_limit'          => '8',
		// Brands.
		'enable_brands'           => '1',
		'brands_title'            => 'Shop by Brand',
		'brands_limit'            => '10',
	);
}

/** @return array Default Checkout settings. */
function bdlm_default_checkout_settings() {
	return array(
		'enable_guest_notice'      => '1',
		'enable_phone_validation'  => '1',
		'phone_placeholder'        => '01XXXXXXXXX',
		'require_phone'            => '1',
		'address_required'         => '0',
		'guest_notice_text'        => 'Quick Checkout! No account needed — just your name, phone & address.',
		'fee_inside_lohagara'      => '30',
		'fee_outside_lohagara'     => '60',
	);
}

/** @return array Default Product Card settings. */
function bdlm_default_product_card_settings() {
	return array(
		'show_savings_badge'   => '1',
		'show_delivery_badge'  => '1',
		'show_unit_info'       => '1',
		'add_to_bag_text'      => 'Add to Bag',
		'adding_text'          => 'Adding…',
		'added_text'           => '✓ Added',
		'badge_style'          => 'pill', // pill | square | ribbon
	);
}

// ============================================================
// WooCommerce HPOS Compatibility Declaration
// ============================================================
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
} );

// ============================================================
// Boot the plugin
// ============================================================
add_action( 'plugins_loaded', 'bdlm_boot', 5 );

/**
 * Bootstrap the plugin after all plugins are loaded.
 * Checks WooCommerce dependency before loading anything else.
 */
function bdlm_boot() {
	// Load translations.
	load_plugin_textdomain(
		'bd-local-market',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	// WooCommerce check.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html__( 'BD Local Market requires WooCommerce to be installed and activated.', 'bd-local-market' )
			);
		} );
		return;
	}

	// Load feature classes.
	BDLM_Core::get_instance();

	// Always load admin class in admin context.
	if ( is_admin() ) {
		new BDLM_Settings();
	}
}
