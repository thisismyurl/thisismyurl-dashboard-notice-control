# This Is My URL Admin Notice NoMore

Current version: 1.6140

This Is My URL Admin Notice NoMore is a lightweight WordPress plugin that suppresses admin notices across wp-admin.

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

1. Copy the plugin to `wp-content/plugins/thisismyurl-admin-notice-nomore`.
2. Activate **ThisIsMyURL Admin Notice NoMore** from the Plugins screen.
3. Reload wp-admin pages.

## Scope and Trade-Offs

- Runs in wp-admin only.
- Hides all admin notices, including high-priority update/security nags.
- Can mask helpful guidance from plugins that rely on notice-based UX.

Use in staging first, and only deploy to production when your update/security process does not depend on admin notice visibility.

## Safety Controls

- Emergency request bypass for administrators: `?thisismyurl_nomore_show_notices=1`
- Constant toggle: `THISISMYURL_ADMIN_NOTICE_NOMORE_ENABLED`
- Constant bypass: `THISISMYURL_ADMIN_NOTICE_NOMORE_BYPASS`
- Optional JS auto-dismiss: `THISISMYURL_ADMIN_NOTICE_NOMORE_AUTO_DISMISS`
- Filters: `thisismyurl_admin_notice_nomore_enabled`, `thisismyurl_admin_notice_nomore_bypass`, `thisismyurl_admin_notice_nomore_auto_dismiss`, `thisismyurl_admin_notice_nomore_css_selectors`

## Ease of Use

- Plugin action link on the Plugins screen: Show Notices Once
- Admin bar shortcut for administrators: Show Notices Once
- Both links generate nonce-protected one-request bypass URLs

## Accessibility Notes

This plugin intentionally suppresses admin status/error notice visibility and may not be suitable for accessibility-sensitive admin environments.

Accessibility review outcome:

- Specialist review completed.
- Result: all-notice suppression can create WCAG risk depending on admin-user needs.
- Recommendation: use only in controlled/internal environments with alternate operational alerting.

## Security and Quality Notes

- Direct file access blocked using `ABSPATH` guard.
- No data is stored in options or custom tables.
- No external network calls.
- Minimal runtime footprint.

## Changelog

### 1.6140

- Updated display name to This Is My URL Admin Notice NoMore.
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
