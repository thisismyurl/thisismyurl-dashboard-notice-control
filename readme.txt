=== Admin Notice NoMore by Christopher Ross ===
Contributors: thisismyurl
Author: Christopher Ross
Author URI: https://thisismyurl.com/
Plugin URI: https://github.com/thisismyurl/thisismyurl-admin-notice-nomore
Donate link: https://github.com/sponsors/thisismyurl
Support Link: https://thisismyurl.com/contact/
Tags: admin notices, dashboard cleanup, wp admin, notifications, admin ui
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.6174.1641
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically dismisses and hides WordPress admin notices across wp-admin.

== Description ==

Admin Notice NoMore by Christopher Ross suppresses all admin notices in the WordPress dashboard.

If this plugin saves you time, consider supporting the work at https://github.com/sponsors/thisismyurl.

What it does:

* Removes callbacks attached to `admin_notices`, `all_admin_notices`, `network_admin_notices`, and `user_admin_notices`.
* Hides notice-like UI via scoped admin CSS in `#wpbody-content`.
* Optional JS auto-dismiss mode for dismissible notices (disabled by default).

Important:

* This plugin intentionally hides all notices, including potentially important update or security messages.
* Recommended for controlled environments where notice noise blocks productivity and you have an alternative update/security monitoring process.

Safety controls (v1.6174.1641):

* Emergency request bypass for administrators (use the nonce-signed link from
	the admin bar or Plugins screen; the nonce is required):
	`?thisismyurl_nomore_show_notices=1&thisismyurl_nomore_nonce=...`
* Constant toggle:
	`THISISMYURL_ADMIN_NOTICE_NOMORE_ENABLED`
* Constant bypass:
	`THISISMYURL_ADMIN_NOTICE_NOMORE_BYPASS`
* Optional JS auto-dismiss toggle (disabled by default):
	`THISISMYURL_ADMIN_NOTICE_NOMORE_AUTO_DISMISS`
* Filters:
	`thisismyurl_admin_notice_nomore_enabled`,
	`thisismyurl_admin_notice_nomore_bypass`,
	`thisismyurl_admin_notice_nomore_auto_dismiss`,
	`thisismyurl_admin_notice_nomore_css_selectors`,
	`thisismyurl_admin_notice_nomore_suppress_network`

Ease-of-use shortcuts:

* Settings page at Settings > Admin Notice NoMore
* Plugin action links on Plugins screen: "Settings" and "Show Notices Once"
* Admin bar shortcut for administrators: "Show Notices Once"

Per-plugin allowlist (v1.6174.1641):

Enter plugin slugs (one per line) at Settings > Admin Notice NoMore. Notices from allowlisted plugins pass through suppression. Matching is done by checking whether the callback source file path contains /plugins/<slug>/.

Support:

* Donations: https://github.com/sponsors/thisismyurl
* Support: https://thisismyurl.com/contact/

== Accessibility Considerations ==

This plugin can create accessibility and compliance risk because it suppresses status and error messaging in wp-admin.

If your admin users rely on assistive technologies, do not run this plugin without testing and operational controls.

Minimum recommended practice:

* Keep `THISISMYURL_ADMIN_NOTICE_NOMORE_AUTO_DISMISS` disabled (default).
* Use the bypass query parameter during troubleshooting.
* Validate critical admin flows with keyboard-only and screen reader testing.

Recent a11y review outcome:

* Automated and specialist review completed.
* Result: not universally accessibility-safe as an all-or-nothing policy plugin.
* Recommendation: internal/controlled-use deployment unless your organization accepts this trade-off and has alternate alerting workflows.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin from Plugins > Installed Plugins.
3. Reload wp-admin pages. Notices will be suppressed automatically.

== Frequently Asked Questions ==

= Does this hide core update nags too? =
Yes. It is designed to hide all admin notices, including update nags.

= Does this affect front-end visitors? =
No. It only runs in wp-admin.

= Can this break other plugins? =
It can interfere with plugins that rely on visible notices for workflow guidance. Test in staging before production rollout.

= Why auto-dismiss notices with JavaScript? =
Some plugins store a "dismissed" state only after the dismiss button is clicked. Auto-dismiss helps keep those plugins in a consistent state.

= Is auto-dismiss enabled by default? =
No. Auto-dismiss is opt-in using `THISISMYURL_ADMIN_NOTICE_NOMORE_AUTO_DISMISS` or the matching filter.

== Changelog ==

= 1.6174.1641 =
* Added Settings > Admin Notice NoMore settings page (menu slug thisismyurl-nomore-settings).
* Added per-plugin allowlist: enter plugin slugs whose notices should pass through suppression.
* Allowlist stored in option thisismyurl_nomore_allowlist; each slug sanitized via sanitize_key().
* Callback allowlist check uses ReflectionFunction / ReflectionMethod to resolve source file paths.
* Added "Settings" link to plugin action links row.
* Security: replaced esc_html() with a CSS-selector character allowlist regex for inline style output.
* Added autoload => false to register_setting() for the allowlist option.
* Added uninstall.php to clean up options on plugin deletion.
* Added .distignore to exclude .git/, CHANGELOG.md, README.md from distribution zip.

= 1.6158 =
* CSS context: replaced esc_html() with wp_strip_all_tags() for inline style selector output (correct escaping context for a style block).
* Version constant: synchronised VERSION class constant with plugin header on every release.

= 1.6148 =
* Security: the administrator bypass URL now requires a valid nonce on every path. The previous nonce-less fallback made the nonce-protected bypass claim decorative.
* Multisite: network-scoped notice suppression (`network_admin_notices`, `user_admin_notices`) is now gated behind the `thisismyurl_admin_notice_nomore_suppress_network` filter (defaults to true) so one site no longer silences the whole network without an opt-out.
* Support: the admin-bar item now shows whether notices are hidden or showing, so administrators can see suppression is active and don't lose update or security nags silently.

= 1.6147 =
* Unified plugin versioning to the x.Yddd calendar-version scheme.
* Confirmed compatibility with WordPress 7.0.

= 1.6143 =
* Updated `Tested up to` to WordPress 7.0.
* Standardized the donation link to GitHub Sponsors.
* Added project governance files (PILLARS, CONTRIBUTING, SECURITY) and README badges.

= 1.6140 =
* Updated display name to "Admin Notice NoMore by Christopher Ross".
* Added nonce-protected bypass URLs.
* Added quick bypass shortcuts in plugin action links and admin bar.
* Version moved to calendar format `1.6NNN`.

= 1.0.1 =
* Added emergency bypass (`?thisismyurl_nomore_show_notices=1`) for administrators.
* Added constants and filters to enable, bypass, and configure behavior.
* Disabled JS auto-dismiss by default (now opt-in).
* Scoped CSS selectors to notice contexts in `#wpbody-content`.
* Added accessibility and operational safety guidance to documentation.

= 1.0.0 =
* Initial release.
* Added global notice suppression for wp-admin.
* Added CSS fallback hiding for direct-rendered notices.
* Added JavaScript auto-dismiss for dismissible notices.

== Upgrade Notice ==

= 1.6174.1641 =
Adds per-plugin allowlist settings page, uninstall cleanup, and security hardening for the CSS output and bypass nonce path.

= 1.6148 =
Security fix: bypass now requires a valid nonce unconditionally. Multisite filter added. Admin-bar suppression indicator added.

= 1.6140 =
Adds easier bypass controls, nonce-protected bypass URLs, and the `1.6NNN` calendar-version release.
