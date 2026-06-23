<?php
/**
 * Plugin Name:       Admin Notice NoMore by Christopher Ross
 * Plugin URI:        https://thisismyurl.com
 * Description:       Automatically dismisses and hides all WordPress admin notices.
 * Version:           1.6174.1641
 * Author:            Christopher Ross
 * Author URI:        https://thisismyurl.com
 * Requires at least: 6.0
 * Tested up to:      7.0
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
	const VERSION = '1.6174.1641';

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
	 * WP option name for the plugin allowlist.
	 */
	const ALLOWLIST_OPTION = 'thisismyurl_nomore_allowlist';

	/**
	 * Settings page menu slug.
	 */
	const SETTINGS_SLUG = 'thisismyurl-nomore-settings';

	/**
	 * Boot plugin hooks.
	 */
	public static function init() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_bypass_link' ), 999 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'add_plugin_action_links' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

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
	 * Get the list of allowed plugin slugs whose notices should pass through.
	 *
	 * @return string[] Array of sanitized plugin slugs.
	 */
	public static function get_allowlist() {
		$raw   = get_option( self::ALLOWLIST_OPTION, '' );
		$lines = explode( "\n", $raw );
		$slugs = array();

		foreach ( $lines as $line ) {
			$slug = sanitize_key( trim( $line ) );
			if ( '' !== $slug ) {
				$slugs[] = $slug;
			}
		}

		return $slugs;
	}

	/**
	 * Check whether a callback's source file is within an allowed plugin's directory.
	 *
	 * @param callable $callback The callback to inspect.
	 * @return bool True if the callback belongs to an allowlisted plugin.
	 */
	public static function callback_is_allowlisted( $callback ) {
		$allowlist = self::get_allowlist();

		if ( empty( $allowlist ) ) {
			return false;
		}

		try {
			if ( is_array( $callback ) ) {
				$ref  = new ReflectionMethod( $callback[0], $callback[1] );
			} elseif ( is_string( $callback ) && false !== strpos( $callback, '::' ) ) {
				list( $class, $method ) = explode( '::', $callback, 2 );
				$ref = new ReflectionMethod( $class, $method );
			} elseif ( $callback instanceof Closure || ( is_string( $callback ) && function_exists( $callback ) ) ) {
				$ref = new ReflectionFunction( $callback );
			} else {
				return false;
			}

			$file = $ref->getFileName();

			if ( ! $file ) {
				return false;
			}

			foreach ( $allowlist as $slug ) {
				if ( false !== strpos( $file, '/plugins/' . $slug . '/' ) ) {
					return true;
				}
			}
		} catch ( ReflectionException $e ) {
			// If reflection fails, do not allowlist the callback.
			return false;
		}

		return false;
	}

	/**
	 * Remove all known notice action stacks before they render,
	 * skipping callbacks whose source files belong to allowlisted plugins.
	 *
	 * Per-site notice hooks (`admin_notices`, `all_admin_notices`) are always
	 * cleared. The network-scoped hooks (`network_admin_notices`,
	 * `user_admin_notices`) are cleared too by default, but that suppression is
	 * global to the whole multisite network and stomps every other plugin's
	 * network notices, so it is gated behind a filter that can opt out without
	 * disabling the plugin entirely.
	 */
	public static function remove_notice_actions() {
		$notice_hooks = array(
			'admin_notices',
			'all_admin_notices',
		);

		/**
		 * Filter whether network-scoped admin notices are also suppressed.
		 *
		 * Defaults to true to preserve historical behaviour. Set to false to
		 * leave `network_admin_notices` and `user_admin_notices` untouched so a
		 * single site's notice-suppression does not silence the whole network.
		 *
		 * @param bool $suppress Whether to suppress network-scoped notices.
		 */
		if ( apply_filters( 'thisismyurl_admin_notice_nomore_suppress_network', true ) ) {
			$notice_hooks[] = 'network_admin_notices';
			$notice_hooks[] = 'user_admin_notices';
		}

		$allowlist = self::get_allowlist();

		foreach ( $notice_hooks as $hook ) {
			if ( empty( $allowlist ) ) {
				// Fast path: no allowlist, remove everything.
				remove_all_actions( $hook );
				continue;
			}

			// Slow path: inspect each callback individually.
			global $wp_filter;

			if ( ! isset( $wp_filter[ $hook ] ) ) {
				continue;
			}

			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback_id => $callback_data ) {
					$callback = $callback_data['function'];

					if ( self::callback_is_allowlisted( $callback ) ) {
						// Leave this callback in place.
						continue;
					}

					remove_action( $hook, $callback, $priority );
				}
			}
		}
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

		// Constrain to safe CSS selector characters before embedding in a style block.
		$safe_selectors = array_filter(
			array_map(
				function ( $sel ) {
					return preg_replace( '/[^a-zA-Z0-9\s\-_\.#>+~:\[\]="*^$|]/', '', $sel );
				},
				$selectors
			)
		);

		if ( empty( $safe_selectors ) ) {
			return;
		}

		$selector_css = implode( ',', $safe_selectors );

		echo '<style id="thisismyurl-admin-notice-nomore">';
		echo $selector_css . '{display:none !important;}'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized via CSS-selector allowlist above
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
	 * Register the settings page under Settings menu.
	 */
	public static function add_settings_page() {
		add_options_page(
			esc_html__( 'Admin Notice NoMore', 'thisismyurl-admin-notice-nomore' ),
			esc_html__( 'Admin Notice NoMore', 'thisismyurl-admin-notice-nomore' ),
			'manage_options',
			self::SETTINGS_SLUG,
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register the allowlist setting via the Settings API.
	 */
	public static function register_settings() {
		register_setting(
			self::SETTINGS_SLUG,
			self::ALLOWLIST_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_allowlist_option' ),
				'default'           => '',
				'autoload'          => false,
			)
		);
	}

	/**
	 * Sanitize the allowlist option value.
	 *
	 * Each line is treated as a plugin slug and run through sanitize_key().
	 * Empty lines are stripped; the result is re-joined with newlines.
	 *
	 * @param string $value Raw textarea value.
	 * @return string Sanitized newline-delimited list of slugs.
	 */
	public static function sanitize_allowlist_option( $value ) {
		$lines = explode( "\n", (string) $value );
		$clean = array();

		foreach ( $lines as $line ) {
			$slug = sanitize_key( trim( $line ) );
			if ( '' !== $slug ) {
				$clean[] = $slug;
			}
		}

		return implode( "\n", $clean );
	}

	/**
	 * Render the Settings > Admin Notice NoMore page.
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'thisismyurl-admin-notice-nomore' ) );
		}

		$current_value = get_option( self::ALLOWLIST_OPTION, '' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Admin Notice NoMore Settings', 'thisismyurl-admin-notice-nomore' ); ?></h1>

			<p><?php esc_html_e( 'Enter plugin slugs (one per line) whose admin notices should pass through even when suppression is active.', 'thisismyurl-admin-notice-nomore' ); ?></p>
			<p><?php esc_html_e( 'Use the folder name of the plugin as it appears in wp-content/plugins/. For example: woocommerce, jetpack, akismet.', 'thisismyurl-admin-notice-nomore' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( self::SETTINGS_SLUG ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::ALLOWLIST_OPTION ); ?>">
								<?php esc_html_e( 'Allowed Plugin Slugs', 'thisismyurl-admin-notice-nomore' ); ?>
							</label>
						</th>
						<td>
							<textarea
								id="<?php echo esc_attr( self::ALLOWLIST_OPTION ); ?>"
								name="<?php echo esc_attr( self::ALLOWLIST_OPTION ); ?>"
								rows="10"
								cols="50"
								class="large-text code"
							><?php echo esc_textarea( $current_value ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'One plugin slug per line. Each slug must match the plugin\'s folder name exactly.', 'thisismyurl-admin-notice-nomore' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( esc_html__( 'Save Allowlist', 'thisismyurl-admin-notice-nomore' ) ); ?>
			</form>
		</div>
		<?php
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

		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'options-general.php?page=' . self::SETTINGS_SLUG ) ),
			esc_html__( 'Settings', 'thisismyurl-admin-notice-nomore' )
		);

		// Prepend Settings so it appears first.
		array_unshift( $links, $settings_link );

		$links[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( self::bypass_url( admin_url() ) ),
			esc_html__( 'Show Notices Once', 'thisismyurl-admin-notice-nomore' )
		);

		$links[] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://github.com/sponsors/thisismyurl' ),
			esc_html__( 'Sponsor', 'thisismyurl-admin-notice-nomore' )
		);

		return $links;
	}

	/**
	 * Add an admin-bar indicator showing suppression state, with a bypass link.
	 *
	 * Notice suppression is otherwise invisible, which generates "where did my
	 * update notice go?" support tickets. This persistent indicator names the
	 * state ("Notices: hidden" / "Notices: showing") so administrators always
	 * know the plugin is active, and nests the one-request bypass link under it.
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar object.
	 * @return void
	 */
	public static function add_admin_bar_bypass_link( $admin_bar ) {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$suppressing = self::is_enabled() && ! self::is_bypassed();

		$title = $suppressing
			? esc_html__( 'Notices: hidden', 'thisismyurl-admin-notice-nomore' )
			: esc_html__( 'Notices: showing', 'thisismyurl-admin-notice-nomore' );

		$admin_bar->add_node(
			array(
				'id'    => 'thisismyurl-admin-notice-nomore',
				'title' => $title,
				'href'  => esc_url( self::bypass_url( admin_url() ) ),
				'meta'  => array(
					'title' => $suppressing
						? esc_attr__( 'Admin notices are being suppressed by Admin Notice NoMore. Click to show notices for one request.', 'thisismyurl-admin-notice-nomore' )
						: esc_attr__( 'Admin notices are visible for this request.', 'thisismyurl-admin-notice-nomore' ),
				),
			)
		);

		if ( $suppressing ) {
			$admin_bar->add_node(
				array(
					'parent' => 'thisismyurl-admin-notice-nomore',
					'id'     => 'thisismyurl-admin-notice-nomore-bypass',
					'title'  => esc_html__( 'Show notices once', 'thisismyurl-admin-notice-nomore' ),
					'href'   => esc_url( self::bypass_url( admin_url() ) ),
				)
			);
		}
	}
}

ThisIsMyURL_Admin_Notice_NoMore::init();
