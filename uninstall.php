<?php
/**
 * Uninstall handler for This Is My URL Admin Notice NoMore.
 *
 * Runs when the plugin is deleted from Plugins > Installed Plugins.
 * Removes the allowlist option from wp_options.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'thisismyurl_nomore_allowlist' );
