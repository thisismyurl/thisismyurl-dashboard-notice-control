<?php
/**
 * Plugin Name:       Thisismyurl Dashboard Notice Control
 * Plugin URI:        https://thisismyurl.com/downloads/thisismyurl-dashboard-notice-control/
 * Description:       Hides admin notices in wp-admin, with a per-plugin allowlist and a one-request bypass for administrators.
 * Version:           1.6192.1604
 * Author:            Christopher Ross
 * Author URI:        https://thisismyurl.com
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * Text Domain:       thisismyurl-dashboard-notice-control
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

/**
 * Suppresses all admin notices across wp-admin.
 */
final class ThisIsMyURL_Dashboard_Notice_Control {

	/**
	 * Plugin version.
	 */
	const VERSION = '1.6192.1604';

	/**
	 * Query var used for one-request bypass.
	 */
	const BYPASS_QUERY_VAR = 'thisismyurl_dnc_show_notices';

	/**
	 * Query var carrying nonce for one-request bypass.
	 */
	const BYPASS_NONCE_VAR = 'thisismyurl_dnc_nonce';

	/**
	 * Action name for bypass nonce generation.
	 */
	const BYPASS_NONCE_ACTION = 'thisismyurl_dnc_show_notices';

	/**
	 * WP option name for the plugin allowlist.
	 */
	const ALLOWLIST_OPTION = 'thisismyurl_dashboard_notice_control_allowlist';

	/**
	 * WP option name for the master on/off switch.
	 */
	const ENABLED_OPTION = 'thisismyurl_dashboard_notice_control_enabled';

	/**
	 * Settings page menu slug.
	 */
	const SETTINGS_SLUG = 'thisismyurl-dashboard-notice-control-settings';

	/**
	 * Boot plugin hooks.
	 */
	public static function init() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_bypass_link' ), 999 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'add_plugin_action_links' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		// The enable/bypass gates CANNOT be evaluated here. init() runs at
		// plugin-include time, before pluggable.php loads, and is_bypassed()
		// calls current_user_can() — which fatals at that point. Register the
		// suppression callbacks unconditionally; each evaluates suppression_active()
		// itself at hook time, when the gates are safe to check.
		add_action( 'admin_init', array( __CLASS__, 'remove_notice_actions' ), 9999 );
		add_action( 'admin_head', array( __CLASS__, 'hide_notices_css' ), 9999 );
		add_action( 'admin_footer', array( __CLASS__, 'auto_dismiss_notices_js' ), 9999 );
	}

	/**
	 * Whether suppression applies to the current request (master gate + bypass).
	 *
	 * Evaluated per-callback at hook time, never at include time — the bypass
	 * check needs current_user_can()/wp_verify_nonce(), which only exist once
	 * pluggable.php has loaded.
	 *
	 * @return bool
	 */
	public static function suppression_active() {
		return self::is_enabled() && ! self::is_bypassed();
	}

	/**
	 * Determine whether suppression is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		// Default ON preserves existing behaviour for anyone upgrading, but the setting gives a
		// site owner a way to turn suppression off from the UI. This plugin hides update and
		// security notices; requiring a PHP constant to stop that is a trap for the one user who
		// most needs the off switch — the one who cannot edit wp-config.php.
		$enabled = ( '0' !== get_option( self::ENABLED_OPTION, '1' ) );

		// A constant or filter still wins, so existing deployments that pin this in code keep
		// working exactly as before.
		if ( defined( 'THISISMYURL_DASHBOARD_NOTICE_CONTROL_ENABLED' ) ) {
			$enabled = (bool) THISISMYURL_DASHBOARD_NOTICE_CONTROL_ENABLED;
		}

		return (bool) apply_filters( 'thisismyurl_dashboard_notice_control_enabled', $enabled );
	}

	/**
	 * Determine whether suppression is bypassed for the current request.
	 *
	 * @return bool
	 */
	public static function is_bypassed() {
		$bypass = false;

		if ( defined( 'THISISMYURL_DASHBOARD_NOTICE_CONTROL_BYPASS' ) ) {
			$bypass = (bool) THISISMYURL_DASHBOARD_NOTICE_CONTROL_BYPASS;
		}

		if ( current_user_can( 'manage_options' ) && isset( $_GET[ self::BYPASS_QUERY_VAR ] ) ) {
			$nonce = isset( $_GET[ self::BYPASS_NONCE_VAR ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::BYPASS_NONCE_VAR ] ) ) : '';
			if ( wp_verify_nonce( $nonce, self::BYPASS_NONCE_ACTION ) ) {
				$bypass = true;
			}
		}

		return (bool) apply_filters( 'thisismyurl_dashboard_notice_control_bypass', $bypass );
	}

	/**
	 * Determine whether JS auto-dismiss is enabled.
	 *
	 * @return bool
	 */
	public static function auto_dismiss_enabled() {
		$enabled = false;

		if ( defined( 'THISISMYURL_DASHBOARD_NOTICE_CONTROL_AUTO_DISMISS' ) ) {
			$enabled = (bool) THISISMYURL_DASHBOARD_NOTICE_CONTROL_AUTO_DISMISS;
		}

		return (bool) apply_filters( 'thisismyurl_dashboard_notice_control_auto_dismiss', $enabled );
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

			// getFileName() returns an OS-native path, so on Windows hosts this is
			// C:\...\wp-content\plugins\foo\bar.php. Matching a hard-coded '/plugins/' against
			// backslashes never succeeds, which silently made the entire allowlist inert on every
			// Windows install: a documented feature doing nothing, with no error to notice.
			$file = wp_normalize_path( (string) $ref->getFileName() );

			if ( ! $file ) {
				return false;
			}

			// Anchor to the real plugin directory rather than searching anywhere in the path. An
			// unanchored match would also accept .../plugins/other/vendor/plugins/<slug>/x.php.
			$plugin_dir = trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) );

			foreach ( $allowlist as $slug ) {
				if ( 0 === strpos( $file, $plugin_dir . $slug . '/' ) ) {
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
		if ( ! self::suppression_active() ) {
			return;
		}

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
		if ( apply_filters( 'thisismyurl_dashboard_notice_control_suppress_network', true ) ) {
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
		if ( ! self::suppression_active() ) {
			return;
		}

		$selectors = array(
			'#wpbody-content .notice',
			'#wpbody-content .update-nag',
			'#wpbody-content > .error',
			'#wpbody-content > .updated',
		);

		$selectors = (array) apply_filters( 'thisismyurl_dashboard_notice_control_css_selectors', $selectors );
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

		echo '<style id="thisismyurl-dashboard-notice-control">';
		echo $selector_css . '{display:none !important;}'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized via CSS-selector allowlist above
		echo '</style>';
	}

	/**
	 * Dismiss dismissible notices so plugins that persist flags are satisfied.
	 */
	public static function auto_dismiss_notices_js() {
		if ( ! self::suppression_active() || ! self::auto_dismiss_enabled() ) {
			return;
		}

		echo '<script id="thisismyurl-dashboard-notice-control-js">';
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
			esc_html__( 'Thisismyurl Dashboard Notice Control', 'thisismyurl-dashboard-notice-control' ),
			esc_html__( 'Thisismyurl Dashboard Notice Control', 'thisismyurl-dashboard-notice-control' ),
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
			self::ENABLED_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_enabled_option' ),
				'default'           => '1',
				'autoload'          => true,
			)
		);

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
	 * Sanitize the master on/off option to a strict '1' or '0'.
	 *
	 * An unchecked checkbox is not submitted at all, so anything that is not an explicit
	 * truthy value is stored as '0'.
	 *
	 * @param mixed $value Raw submitted value.
	 * @return string '1' or '0'.
	 */
	public static function sanitize_enabled_option( $value ) {
		return ( '1' === (string) $value || 'on' === (string) $value ) ? '1' : '0';
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
	 * Render the Settings > Thisismyurl Dashboard Notice Control page.
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'thisismyurl-dashboard-notice-control' ) );
		}

		$current_value    = get_option( self::ALLOWLIST_OPTION, '' );
		$enabled_value    = ( '0' !== get_option( self::ENABLED_OPTION, '1' ) );
		$pinned_in_code   = defined( 'THISISMYURL_DASHBOARD_NOTICE_CONTROL_ENABLED' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Thisismyurl Dashboard Notice Control Settings', 'thisismyurl-dashboard-notice-control' ); ?></h1>

			<p><?php esc_html_e( 'This plugin hides admin notices, including update and security messages. Turn suppression off here whenever you need to see them again.', 'thisismyurl-dashboard-notice-control' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( self::SETTINGS_SLUG ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Notice suppression', 'thisismyurl-dashboard-notice-control' ); ?>
						</th>
						<td>
							<label for="<?php echo esc_attr( self::ENABLED_OPTION ); ?>">
								<input
									type="checkbox"
									id="<?php echo esc_attr( self::ENABLED_OPTION ); ?>"
									name="<?php echo esc_attr( self::ENABLED_OPTION ); ?>"
									value="1"
									<?php checked( $enabled_value ); ?>
									<?php disabled( $pinned_in_code ); ?>
								/>
								<?php esc_html_e( 'Hide admin notices in wp-admin', 'thisismyurl-dashboard-notice-control' ); ?>
							</label>
							<p class="description">
								<?php
								if ( $pinned_in_code ) {
									esc_html_e( 'This setting is currently pinned by the THISISMYURL_DASHBOARD_NOTICE_CONTROL_ENABLED constant in your site configuration, so this checkbox has no effect until that constant is removed.', 'thisismyurl-dashboard-notice-control' );
								} else {
									esc_html_e( 'Uncheck to show all admin notices again. Your allowlist below is kept either way.', 'thisismyurl-dashboard-notice-control' );
								}
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::ALLOWLIST_OPTION ); ?>">
								<?php esc_html_e( 'Allowed Plugin Slugs', 'thisismyurl-dashboard-notice-control' ); ?>
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
								<?php esc_html_e( 'One plugin slug per line: the folder name as it appears in wp-content/plugins/, for example woocommerce, jetpack, akismet. Slugs are stored in lowercase, so enter them in lowercase. Notices from these plugins pass through even while suppression is on.', 'thisismyurl-dashboard-notice-control' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( esc_html__( 'Save Allowlist', 'thisismyurl-dashboard-notice-control' ) ); ?>
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
			esc_html__( 'Settings', 'thisismyurl-dashboard-notice-control' )
		);

		// Prepend Settings so it appears first.
		array_unshift( $links, $settings_link );

		$links[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( self::bypass_url( admin_url() ) ),
			esc_html__( 'Show Notices Once', 'thisismyurl-dashboard-notice-control' )
		);

		// No sponsorship link in the plugins-row actions: that row is functional UI, and a
		// solicitation there reads as promotional under guideline 11. The readme's Donate link
		// header and Description already carry the ask, which is the sanctioned placement.
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
			? esc_html__( 'Notices: hidden', 'thisismyurl-dashboard-notice-control' )
			: esc_html__( 'Notices: showing', 'thisismyurl-dashboard-notice-control' );

		$admin_bar->add_node(
			array(
				'id'    => 'thisismyurl-dashboard-notice-control',
				'title' => $title,
				'href'  => esc_url( self::bypass_url( admin_url() ) ),
				'meta'  => array(
					'title' => $suppressing
						? esc_attr__( 'Admin notices are being suppressed by Thisismyurl Dashboard Notice Control. Click to show notices for one request.', 'thisismyurl-dashboard-notice-control' )
						: esc_attr__( 'Admin notices are visible for this request.', 'thisismyurl-dashboard-notice-control' ),
				),
			)
		);

		if ( $suppressing ) {
			$admin_bar->add_node(
				array(
					'parent' => 'thisismyurl-dashboard-notice-control',
					'id'     => 'thisismyurl-dashboard-notice-control-bypass',
					'title'  => esc_html__( 'Show notices once', 'thisismyurl-dashboard-notice-control' ),
					'href'   => esc_url( self::bypass_url( admin_url() ) ),
				)
			);
		}
	}
}

ThisIsMyURL_Dashboard_Notice_Control::init();
