<?php
/**
 * Uninstall handler for Thisismyurl Dashboard Notice Control.
 *
 * Runs when the plugin is deleted from Plugins > Installed Plugins.
 * Removes this plugin's options from wp_options.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete this plugin's options for the current site.
 */
function thisismyurl_dnc_delete_options() {
	$options = array(
		'thisismyurl_dashboard_notice_control_allowlist',
		'thisismyurl_dashboard_notice_control_enabled',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}
}

// On multisite, delete_option() only touches the site that ran the deletion, so every OTHER site
// in the network keeps an orphaned row forever. Walk the network instead, batched so a large
// network is not loaded into memory at once.
if ( is_multisite() ) {
	$thisismyurl_dnc_paged = 1;

	do {
		$thisismyurl_dnc_site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
				'paged'  => $thisismyurl_dnc_paged,
			)
		);

		foreach ( $thisismyurl_dnc_site_ids as $thisismyurl_dnc_site_id ) {
			switch_to_blog( $thisismyurl_dnc_site_id );
			thisismyurl_dnc_delete_options();
			restore_current_blog();
		}

		++$thisismyurl_dnc_paged;
	} while ( ! empty( $thisismyurl_dnc_site_ids ) );
} else {
	thisismyurl_dnc_delete_options();
}
