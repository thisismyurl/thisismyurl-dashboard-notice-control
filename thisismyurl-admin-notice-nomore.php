<?php
/**
 * Plugin Name:       This Is My URL Admin Notice NoMore
 * Plugin URI:        https://thisismyurl.com
 * Description:       Automatically dismisses and hides all WordPress admin notices.
 * Version:           1.6140
 * Author:            This Is My URL
 * Author URI:        https://thisismyurl.com
 * Requires at least: 6.0
 * Tested up to:      6.8
 * Requires PHP:      7.4
 * Text Domain:       thisismyurl-admin-notice-nomore
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

/**
 * Suppresses all admin notices across wp-admin.
 */
final class ThisIsMyURL_Admin_Notice_NoMore {

	/**
	 * Plugin version.
	 */
	const VERSION = '1.6140';

	/**
	 * Query var used for one-request bypass.
	 */
	const BYPASS_QUERY_VAR = 'thisismyurl_nomore_show_notices';

	/**
	 * Query var carrying nonce for one-request bypass.
	 */
	const BYPASS_NONCE_VAR = 'thisismyurl_nomore_nonce';

	/**
	 * Action name for bypass nonce generation.
	 */
	const BYPASS_NONCE_ACTION = 'thisismyurl_nomore_show_notices';

	/**
	 * Boot plugin hooks.
	 */
	public static function init() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_bypass_link' ), 999 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'add_plugin_action_links' ) );

		if ( ! self::is_enabled() || self::is_bypassed() ) {
			return;
		}

		add_action( 'admin_init', array( __CLASS__, 'remove_notice_actions' ), 9999 );
		add_action( 'admin_head', array( __CLASS__, 'hide_notices_css' ), 9999 );
		if ( self::auto_dismiss_enabled() ) {
			add_action( 'admin_footer', array( __CLASS__, 'auto_dismiss_notices_js' ), 9999 );
		}
	}

	/**
	 * Determine whether suppression is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$enabled = true;

		if ( defined( 'THISISMYURL_ADMIN_NOTICE_NOMORE_ENABLED' ) ) {
			$enabled = (bool) THISISMYURL_ADMIN_NOTICE_NOMORE_ENABLED;
		}

		return (bool) apply_filters( 'thisismyurl_admin_notice_nomore_enabled', $enabled );
	}

	/**
	 * Determine whether suppression is bypassed for the current request.
	 *
	 * @return bool
	 */
	public static function is_bypassed() {
		$bypass = false;

		if ( defined( 'THISISMYURL_ADMIN_NOTICE_NOMORE_BYPASS' ) ) {
			$bypass = (bool) THISISMYURL_ADMIN_NOTICE_NOMORE_BYPASS;
		}

		if ( current_user_can( 'manage_options' ) && isset( $_GET[ self::BYPASS_QUERY_VAR ] ) ) {
			$nonce = isset( $_GET[ self::BYPASS_NONCE_VAR ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::BYPASS_NONCE_VAR ] ) ) : '';
			if ( wp_verify_nonce( $nonce, self::BYPASS_NONCE_ACTION ) ) {
				$bypass = true;
			}
		}

		if ( current_user_can( 'manage_options' ) && isset( $_GET[ self::BYPASS_QUERY_VAR ] ) && ! isset( $_GET[ self::BYPASS_NONCE_VAR ] ) ) {
			$bypass = true;
		}

		return (bool) apply_filters( 'thisismyurl_admin_notice_nomore_bypass', $bypass );
	}

	/**
	 * Determine whether JS auto-dismiss is enabled.
	 *
	 * @return bool
	 */
	public static function auto_dismiss_enabled() {
		$enabled = false;

		if ( defined( 'THISISMYURL_ADMIN_NOTICE_NOMORE_AUTO_DISMISS' ) ) {
			$enabled = (bool) THISISMYURL_ADMIN_NOTICE_NOMORE_AUTO_DISMISS;
		}

		return (bool) apply_filters( 'thisismyurl_admin_notice_nomore_auto_dismiss', $enabled );
	}

	/**
	 * Remove all known notice action stacks before they render.
	 */
	public static function remove_notice_actions() {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
	}

	/**
	 * Hide notices in case anything still prints directly.
	 */
	public static function hide_notices_css() {
		$selectors = array(
			'#wpbody-content .notice',
			'#wpbody-content .update-nag',
			'#wpbody-content > .error',
			'#wpbody-content > .updated',
		);

		$selectors = (array) apply_filters( 'thisismyurl_admin_notice_nomore_css_selectors', $selectors );
		$selectors = array_filter( array_map( 'sanitize_text_field', $selectors ) );

		if ( empty( $selectors ) ) {
			return;
		}

		$selector_css = implode( ',', $selectors );

		echo '<style id="thisismyurl-admin-notice-nomore">';
		echo esc_html( $selector_css ) . '{display:none !important;}';
		echo '</style>';
	}

	/**
	 * Dismiss dismissible notices so plugins that persist flags are satisfied.
	 */
	public static function auto_dismiss_notices_js() {
		echo '<script id="thisismyurl-admin-notice-nomore-js">';
		echo '(function(){';
		echo 'var notices=document.querySelectorAll("#wpbody-content .notice.is-dismissible");';
		echo 'for(var i=0;i<notices.length;i++){';
		echo 'var button=notices[i].querySelector(".notice-dismiss");';
		echo 'if(button){button.click();}';
		echo 'notices[i].style.display="none";';
		echo '}';
		echo '})();';
		echo '</script>';
	}

	/**
	 * Build a URL that bypasses suppression for one request.
	 *
	 * @param string $base_url Base URL.
	 * @return string
	 */
	public static function bypass_url( $base_url = '' ) {
		if ( '' === $base_url ) {
			$base_url = admin_url();
		}

		$url = add_query_arg(
			array(
				self::BYPASS_QUERY_VAR => '1',
			),
			$base_url
		);

		return wp_nonce_url( $url, self::BYPASS_NONCE_ACTION, self::BYPASS_NONCE_VAR );
	}

	/**
	 * Add convenience links on the Plugins screen.
	 *
	 * @param array<string> $links Existing links.
	 * @return array<string>
	 */
	public static function add_plugin_action_links( $links ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( self::bypass_url( admin_url() ) ),
			esc_html__( 'Show Notices Once', 'thisismyurl-admin-notice-nomore' )
		);

		return $links;
	}

	/**
	 * Add an admin-bar shortcut for one-request bypass.
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar object.
	 * @return void
	 */
	public static function add_admin_bar_bypass_link( $admin_bar ) {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'thisismyurl-admin-notice-nomore-bypass',
				'title' => esc_html__( 'Show Notices Once', 'thisismyurl-admin-notice-nomore' ),
				'href'  => esc_url( self::bypass_url( admin_url() ) ),
			)
		);
	}
}

ThisIsMyURL_Admin_Notice_NoMore::init();
