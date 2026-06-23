# Changelog

## 1.6174.1641 — 2026-06-23

- Added Settings > Admin Notice NoMore settings page (menu slug `thisismyurl-nomore-settings`) under Settings menu.
- Added per-plugin allowlist textarea: administrators enter plugin slugs (one per line) whose notices should pass through suppression even when the plugin is active.
- Allowlist stored in WP option `thisismyurl_nomore_allowlist`; each slug sanitized via `sanitize_key()`.
- `remove_notice_actions()` now inspects each registered callback individually when an allowlist is present; uses `ReflectionFunction` / `ReflectionMethod` (wrapped in try/catch) to resolve the callback's source file path, then checks for `/plugins/<slug>/` in that path before removing.
- Empty allowlist takes the fast path (`remove_all_actions`) with no reflection overhead.
- Added "Settings" link (prepended) to plugin action links row on the Plugins screen.
- Settings form uses `register_setting()` / `settings_fields()` for nonce protection and capability enforcement.
- All page output escaped with `esc_html_e()`, `esc_attr()`, `esc_url()`, `esc_textarea()`.
- All capability checks via `current_user_can( 'manage_options' )`.

## 1.6140

- Updated display name to This Is My URL Admin Notice NoMore.
- Added nonce-protected one-request bypass URLs.
- Added quick bypass shortcuts in plugin action links and admin bar.
- Version moved to calendar format `1.6NNN`.

## 1.0.1

- Added emergency bypass support for administrators via query parameter.
- Added constants and filters for enable/bypass/autodismiss control.
- Switched auto-dismiss to opt-in default.
- Scoped CSS selectors to notice contexts in `#wpbody-content`.
- Added accessibility and operational guidance.

## 1.0.0

- Initial release.
- Added global removal of admin notice hooks.
- Added CSS fallback for directly printed notice markup.
- Added JavaScript auto-dismiss for dismissible notices.
