<?php
/**
 * Uninstall handler for Thisismyurl Dashboard Notice Control.
 *
 * Runs when the plugin is deleted from Plugins > Installed Plugins.
 * Removes the allowlist option from wp_options.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'thisismyurl_dashboard_notice_control_allowlist' );
