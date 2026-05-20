=== This Is My URL Admin Notice NoMore ===
Contributors: thisismyurl
Donate link: https://thisismyurl.com/donate/
Tags: admin notices, dashboard cleanup, wp admin, notifications, admin ui
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.6140
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically dismisses and hides WordPress admin notices across wp-admin.

== Description ==

This Is My URL Admin Notice NoMore suppresses all admin notices in the WordPress dashboard.

What it does:

* Removes callbacks attached to `admin_notices`, `all_admin_notices`, `network_admin_notices`, and `user_admin_notices`.
* Hides notice-like UI via scoped admin CSS in `#wpbody-content`.
* Optional JS auto-dismiss mode for dismissible notices (disabled by default).

Important:

* This plugin intentionally hides all notices, including potentially important update or security messages.
* Recommended for controlled environments where notice noise blocks productivity and you have an alternative update/security monitoring process.

Safety controls (v1.0.1):

* Emergency request bypass for administrators:
	`?thisismyurl_nomore_show_notices=1`
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
	`thisismyurl_admin_notice_nomore_css_selectors`

Ease-of-use shortcuts:

* Plugin action link on Plugins screen: "Show Notices Once"
* Admin bar shortcut for administrators: "Show Notices Once"

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
No. Since 1.0.1, auto-dismiss is opt-in using `THISISMYURL_ADMIN_NOTICE_NOMORE_AUTO_DISMISS` or the matching filter.

== Changelog ==

= 1.6140 =
* Updated display name to "This Is My URL Admin Notice NoMore".
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

= 1.0.1 =
Adds emergency bypass controls, safer defaults, and scoped suppression behavior.
