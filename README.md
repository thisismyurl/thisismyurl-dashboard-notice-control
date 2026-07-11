# Thisismyurl Dashboard Notice Control

Current version: 1.6192.1604

Thisismyurl Dashboard Notice Control is a lightweight WordPress plugin that suppresses admin notices across wp-admin.

If it saves you time or removes dashboard friction, you can support the work here: https://thisismyurl.com/donate/

## What It Does

- Removes all registered callbacks for WordPress notice hooks.
- Hides remaining notice UI via scoped admin-side CSS.
- Optional auto-dismiss mode for dismissible notices (disabled by default).

## Why It Exists

Some admin environments are overwhelmed by plugin and update notices, which can reduce focus and increase dashboard friction.

This plugin provides an explicit, all-or-nothing suppression mode for teams that already handle updates, alerts, and security checks outside the dashboard UI.

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

1. Copy the plugin to `wp-content/plugins/thisismyurl-dashboard-notice-control`.
2. Activate **Thisismyurl Dashboard Notice Control** from the Plugins screen.
3. Reload wp-admin pages.

## Scope and Trade-Offs

- Runs in wp-admin only.
- Hides all admin notices, including high-priority update/security nags.
- Can mask helpful guidance from plugins that rely on notice-based UX.

Use in staging first, and only deploy to production when your update/security process does not depend on admin notice visibility.

## Support and Donations

- Donate: https://thisismyurl.com/donate/
- Support: https://thisismyurl.com/contact/
- Source and releases: https://github.com/thisismyurl/thisismyurl-dashboard-notice-control

## Safety Controls

- Emergency request bypass for administrators: `?thisismyurl_dnc_show_notices=1`
- Constant toggle: `THISISMYURL_DASHBOARD_NOTICE_CONTROL_ENABLED`
- Constant bypass: `THISISMYURL_DASHBOARD_NOTICE_CONTROL_BYPASS`
- Optional JS auto-dismiss: `THISISMYURL_DASHBOARD_NOTICE_CONTROL_AUTO_DISMISS`
- Filters: `thisismyurl_dashboard_notice_control_enabled`, `thisismyurl_dashboard_notice_control_bypass`, `thisismyurl_dashboard_notice_control_auto_dismiss`, `thisismyurl_dashboard_notice_control_css_selectors`

## Ease of Use

- Settings page at Settings > Thisismyurl Dashboard Notice Control (menu slug: thisismyurl-dashboard-notice-control-settings)
- Plugin action links on the Plugins screen: Settings and Show Notices Once
- Admin bar shortcut for administrators: Show Notices Once
- Bypass links generate nonce-protected one-request bypass URLs

## Per-Plugin Allowlist

Enter plugin slugs at Settings > Thisismyurl Dashboard Notice Control. One slug per line, matching the plugin folder name (e.g. woocommerce, jetpack). Notices from allowlisted plugins pass through suppression. The plugin uses PHP Reflection to resolve each callback's source file and checks whether its path contains /plugins/<slug>/.

## Accessibility Notes

This plugin intentionally suppresses admin status/error notice visibility and may not be suitable for accessibility-sensitive admin environments.

Accessibility review outcome:

- Specialist review completed.
- Result: all-notice suppression can create WCAG risk depending on admin-user needs.
- Recommendation: use only in controlled/internal environments with alternate operational alerting.

## Security and Quality Notes

- Direct file access blocked using `ABSPATH` guard.
- Allowlist option sanitized via sanitize_key() per slug.
- Settings form uses register_setting() / settings_fields() for nonce and capability handling.
- Reflection calls wrapped in try/catch; reflection failures default to removing the callback.
- No external network calls.
- Minimal runtime footprint.

## Changelog

### 1.6174.1641

- Added Settings > Thisismyurl Dashboard Notice Control settings page.
- Added per-plugin allowlist: enter plugin slugs whose notices pass through suppression.
- Allowlist stored in option thisismyurl_dashboard_notice_control_allowlist; slugs sanitized via sanitize_key().
- Callback source files resolved via PHP Reflection (ReflectionFunction / ReflectionMethod).
- Added Settings link to plugin action links row.
- Bumped version to 1.6174.1641.

### 1.6140

- Updated display name to Thisismyurl Dashboard Notice Control.
- Added nonce-protected one-request bypass URLs.
- Added quick bypass shortcuts in plugin action links and admin bar.
- Version moved to calendar format `1.6NNN`.

### 1.0.1

- Added emergency bypass support for administrators via query parameter.
- Added constants and filters for enable/bypass/autodismiss control.
- Switched auto-dismiss to opt-in default.
- Scoped CSS selectors to notice contexts in `#wpbody-content`.
- Added accessibility and operational guidance.

### 1.0.0

- Initial release.
- Added global removal of admin notice hooks.
- Added CSS fallback for directly printed notice markup.
- Added JavaScript auto-dismiss for dismissible notices.

## License

GPL-2.0-or-later
