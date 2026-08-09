<?php
/**
 * Uninstall script for BD Local Market.
 *
 * Runs when the plugin is deleted from the WordPress admin.
 *
 * @package BD_Local_Market
 */

// Exit if not called from WordPress uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'bdlm_settings' );

// Clean up any transients we may have set.
delete_transient( 'bdlm_live_search_cache' );
