<?php
/**
 * BDLM Admin — Settings page for BD Local Market plugin.
 *
 * Located at: WooCommerce → BD Local Market
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BDLM_Admin
 */
class BDLM_Admin {

	/**
	 * Option key in wp_options.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'bdlm_settings';

	/**
	 * Constructor — register admin hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_bd_local_market', array( $this, 'render_settings_page' ) );
		add_action( 'woocommerce_update_options_bd_local_market', array( $this, 'save_settings' ) );

		// Admin menu shortcut directly under Plugins for non-WC installs.
		add_action( 'admin_menu', array( $this, 'add_admin_menu_fallback' ) );

		// Quick links on plugins page.
		add_filter( 'plugin_action_links_bd-local-market/bd-local-market.php', array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Add a tab to WooCommerce settings.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function add_settings_tab( $tabs ) {
		$tabs['bd_local_market'] = __( 'BD Local Market', 'bd-local-market' );
		return $tabs;
	}

	/**
	 * Render the settings page using WC Settings API fields.
	 */
	public function render_settings_page() {
		include BDLM_PATH . 'admin/views/settings-page.php';
	}

	/**
	 * Save settings using WC Settings API.
	 */
	public function save_settings() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'bd-local-market' ) );
		}

		$current = (array) get_option( self::OPTION_KEY, array() );

		$new_settings = array(
			'delivery_time_text'      => isset( $_POST['bdlm_delivery_time_text'] )
				? sanitize_text_field( wp_unslash( $_POST['bdlm_delivery_time_text'] ) )
				: '2-4 hrs delivery',

			'deal_end_time'           => isset( $_POST['bdlm_deal_end_time'] )
				? sanitize_text_field( wp_unslash( $_POST['bdlm_deal_end_time'] ) )
				: '',

			'hero_title'              => isset( $_POST['bdlm_hero_title'] )
				? sanitize_text_field( wp_unslash( $_POST['bdlm_hero_title'] ) )
				: '',

			'hero_subtitle'           => isset( $_POST['bdlm_hero_subtitle'] )
				? sanitize_textarea_field( wp_unslash( $_POST['bdlm_hero_subtitle'] ) )
				: '',

			'hero_cta_text'           => isset( $_POST['bdlm_hero_cta_text'] )
				? sanitize_text_field( wp_unslash( $_POST['bdlm_hero_cta_text'] ) )
				: '',

			'hero_cta_url'            => isset( $_POST['bdlm_hero_cta_url'] )
				? esc_url_raw( wp_unslash( $_POST['bdlm_hero_cta_url'] ) )
				: '',

			'enable_live_search'      => ! empty( $_POST['bdlm_enable_live_search'] ) ? '1' : '0',

			'enable_phone_validation' => ! empty( $_POST['bdlm_enable_phone_validation'] ) ? '1' : '0',

			'search_results_limit'    => isset( $_POST['bdlm_search_results_limit'] )
				? absint( $_POST['bdlm_search_results_limit'] )
				: 8,

			'currency_symbol'         => isset( $_POST['bdlm_currency_symbol'] )
				? sanitize_text_field( wp_unslash( $_POST['bdlm_currency_symbol'] ) )
				: '৳',
		);
		// phpcs:enable

		update_option( self::OPTION_KEY, wp_parse_args( $new_settings, $current ) );

		// Clear any cached search transients.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bdlm_search_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		// Re-schedule deal expiry cron if time changed.
		if ( wp_next_scheduled( 'bdlm_expire_deals' ) ) {
			wp_clear_scheduled_hook( 'bdlm_expire_deals' );
		}

		WC_Admin_Settings::add_message( __( 'BD Local Market settings saved.', 'bd-local-market' ) );
	}

	/**
	 * Fallback admin menu page (useful if WC tab is not found).
	 */
	public function add_admin_menu_fallback() {
		add_submenu_page(
			'woocommerce',
			__( 'BD Local Market', 'bd-local-market' ),
			__( 'BD Local Market', 'bd-local-market' ),
			'manage_woocommerce',
			'bd-local-market',
			array( $this, 'render_standalone_settings' )
		);
	}

	/**
	 * Render standalone settings page (non-WC-tab version).
	 */
	public function render_standalone_settings() {
		// Same view, WC message system may not be available but form still works.
		include BDLM_PATH . 'admin/views/settings-page.php';
	}

	/**
	 * Add Settings link to plugins list.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_plugin_action_links( $links ) {
		$settings_url  = admin_url( 'admin.php?page=wc-settings&tab=bd_local_market' );
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'bd-local-market' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
