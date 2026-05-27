# This Is My URL - Admin Notice NoMore

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)](https://wordpress.org/) [![License](https://img.shields.io/badge/License-GPL--2.0-blue)](LICENSE)

This Is My URL Admin Notice NoMore is a lightweight WordPress plugin that suppresses admin notices across wp-admin.

## What it does

- Removes all registered callbacks for WordPress notice hooks.
- Hides remaining notice UI via scoped admin-side CSS.
- Optional auto-dismiss mode for dismissible notices (disabled by default).

## Why it exists

Some admin environments are overwhelmed by plugin and update notices, which can reduce focus and increase dashboard friction.

This plugin provides an explicit, all-or-nothing suppression mode for teams that already handle updates, alerts, and security checks outside the dashboard UI.

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

1. Copy the plugin to `wp-content/plugins/thisismyurl-admin-notice-nomore`.
2. Activate **This Is My URL Admin Notice NoMore** from the Plugins screen.
3. Reload wp-admin pages.

## Scope and trade-offs

- Runs in wp-admin only.
- Hides all admin notices, including high-priority update and security nags.
- Can mask helpful guidance from plugins that rely on notice-based UX.

Use it in staging first, and only deploy to production when your update and security process does not depend on admin notice visibility.

## Safety controls

- Emergency request bypass for administrators: `?thisismyurl_nomore_show_notices=1`
- Constant toggle: `THISISMYURL_ADMIN_NOTICE_NOMORE_ENABLED`
- Constant bypass: `THISISMYURL_ADMIN_NOTICE_NOMORE_BYPASS`
- Optional JS auto-dismiss: `THISISMYURL_ADMIN_NOTICE_NOMORE_AUTO_DISMISS`
- Filters: `thisismyurl_admin_notice_nomore_enabled`, `thisismyurl_admin_notice_nomore_bypass`, `thisismyurl_admin_notice_nomore_auto_dismiss`, `thisismyurl_admin_notice_nomore_css_selectors`

## Ease of use

- Plugin action link on the Plugins screen: Show Notices Once
- Admin bar shortcut for administrators: Show Notices Once
- Both links generate nonce-protected one-request bypass URLs

## Accessibility notes

This plugin intentionally suppresses admin status and error notice visibility, and may not be suitable for accessibility-sensitive admin environments.

Accessibility review outcome:

- Specialist review completed.
- Result: all-notice suppression can create WCAG risk depending on admin-user needs.
- Recommendation: use only in controlled or internal environments with alternate operational alerting.

## Security and quality notes

- Direct file access blocked using an `ABSPATH` guard.
- No data is stored in options or custom tables.
- No external network calls.
- Minimal runtime footprint.

## Changelog

See [releases](../../releases) or [readme.txt](readme.txt).

---

## Support and donations

I build these tools because WordPress sites in the wild keep hitting the same problems, and a small, focused plugin is usually the right fix. They're free to use, with no tracking and no ads.

If one of them saves you time, here are the genuine ways to help:

- **Sponsor the work.** [GitHub Sponsors](https://github.com/sponsors/thisismyurl) is the simplest way, and the Sponsor button at the top of this repo lists it alongside Bitcoin, Dogecoin, PayPal, and Interac e-transfer. Any amount helps, and none of it is expected.
- **Contribute code or ideas.** A pull request, a bug report, or a tested edge case is worth as much as a donation. See [CONTRIBUTING.md](CONTRIBUTING.md) to get started.
- **Share it.** A note on [WordPress.org](https://profiles.wordpress.org/thisismyurl/), [GitHub](https://github.com/thisismyurl), or [LinkedIn](https://linkedin.com/in/thisismyurl) helps other people find work that might save them the same afternoon.

### Report issues and questions

- **Found a bug or want a feature?** Open an issue on the [Issues](../../issues) tab. Include your WordPress and PHP versions and the steps to reproduce it.
- **Have a question?** Start a thread on the [Discussions](../../discussions) tab.

### Contributing code

Code contributions are welcome. The short version:

1. Fork the repository and clone your fork.
2. Create a branch with a clear name, like `feature/short-descriptive-name`.
3. Make your change and test it against the edge cases.
4. Run the coding-standards check before you open the pull request.
5. Open a pull request that explains what changed and why.

The full workflow and standards live in [CONTRIBUTING.md](CONTRIBUTING.md). Contributing is never required, but it is always appreciated.

## About This Is My URL

This plugin is built and maintained by [This Is My URL](https://thisismyurl.com/), the WordPress development and technical SEO practice of Christopher Ross. I help teams build WordPress sites that stay secure, fast, and maintainable, and I write small, focused plugins like this one for the problems those sites keep running into.

### My background

- On the web since 1996, and in WordPress since 2007
- WordPress.org plugin developer with 19 plugins published since 2009
- Technical SEO practitioner focused on performance, security, and search visibility
- Lead instructor and curriculum architect at the M.L. Campbell Training Center, the Sherwin-Williams® international training facility for its industrial wood division

### Ways to connect

- **Website:** [thisismyurl.com](https://thisismyurl.com/)
- **WordPress.org:** [profiles.wordpress.org/thisismyurl](https://profiles.wordpress.org/thisismyurl/)
- **GitHub:** [github.com/thisismyurl](https://github.com/thisismyurl)
- **LinkedIn:** [linkedin.com/in/thisismyurl](https://linkedin.com/in/thisismyurl)

## Contributors

- **Christopher Ross** ([@thisismyurl](https://github.com/thisismyurl)) — author and maintainer
- Thanks to everyone who has reported issues, tested edge cases, and contributed code

## License

GPL-2.0-or-later — see [LICENSE](LICENSE) or [gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).

---
*This project follows the [10 Core Pillars](PILLARS.md). Support quality work [here](https://github.com/sponsors/thisismyurl).*
