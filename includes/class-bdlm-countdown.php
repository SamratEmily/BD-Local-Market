<?php
/**
 * BDLM Countdown — Deals countdown timer support.
 *
 * Outputs deal end time data to JS via localization.
 * Actual timer rendering is handled by bdlm-countdown.js.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BDLM_Countdown
 */
class BDLM_Countdown {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Data is already localized via BDLM_Assets.
		// This class is a placeholder for any future PHP-side countdown logic,
		// e.g., auto-removing expired sale prices via WP Cron.
		add_action( 'init', array( $this, 'maybe_schedule_deal_expiry_cron' ) );
		add_action( 'bdlm_expire_deals', array( $this, 'handle_deal_expiry' ) );
	}

	/**
	 * Schedule a WP Cron event to handle deal expiry if a deal end time is set.
	 */
	public function maybe_schedule_deal_expiry_cron() {
		$settings     = (array) get_option( 'bdlm_general_settings', array() );
		$deal_end_raw = $settings['deal_end_time'] ?? '';

		if ( empty( $deal_end_raw ) ) {
			// Clear any scheduled event if no end time is set.
			if ( wp_next_scheduled( 'bdlm_expire_deals' ) ) {
				wp_clear_scheduled_hook( 'bdlm_expire_deals' );
			}
			return;
		}

		$deal_end_timestamp = strtotime( $deal_end_raw );

		if ( false === $deal_end_timestamp ) {
			return;
		}

		// Only schedule if in the future.
		if ( $deal_end_timestamp > time() ) {
			if ( ! wp_next_scheduled( 'bdlm_expire_deals' ) ) {
				wp_schedule_single_event( $deal_end_timestamp, 'bdlm_expire_deals' );
			}
		}
	}

	/**
	 * Handle deal expiry: clear the deal end time from settings.
	 * Allows the homepage to gracefully stop showing the countdown.
	 */
	public function handle_deal_expiry() {
		$settings                 = (array) get_option( 'bdlm_settings', array() );
		$settings['deal_end_time'] = '';
		update_option( 'bdlm_settings', $settings );
	}

	/**
	 * Static helper: get a formatted countdown end time string (ISO 8601).
	 *
	 * @return string
	 */
	public static function get_deal_end_iso() {
		$settings = BDLM_Core::get_instance()->settings;
		$raw      = $settings['deal_end_time'] ?? '';
		if ( empty( $raw ) ) {
			return '';
		}
		$ts = strtotime( $raw );
		return false !== $ts ? gmdate( 'c', $ts ) : '';
	}
}
