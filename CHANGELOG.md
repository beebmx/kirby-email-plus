# Changelog

All notable changes to `Email Plus` will be documented in this file.

## 2.0.1 - 2026-11-27

- Added support for Kirby 4.9+ (PHP 8.4)
- Fixed the plugin errors (missing API key, invalid `beforeSend` client) on Kirby 4
- Added Kirby 4 to the GitHub workflow

## 2.0.0 - 2026-11-27

- Added support for Kirby 6, keeping support for Kirby 5
- Added support for PHP 8.5 and PHP 8.6
- **Breaking:** PHP 8.4 is now the minimum version
- Added the Kirby compatibility (`^5.0 || ^6.0`) to `composer.json`
- Upgraded to Pest 5
- Upgraded to kirbyup 4 and Vue 3.5 (Node.js 24 or newer is needed to build the Panel assets)
- Upgraded the development dependencies to their latest versions
- Fixed a `TypeError` when neither `beebmx.email-plus.type` nor `email.transport.type` is set
- Only concrete Email+ providers are resolved from the transport type
- Fixed the plugin options when `extensions/options.php` was already loaded
- Fixed `beforeSend`: the Resend or Mailgun client it returns is now used to send the email, like Kirby's built-in mailer
- The Resend and Mailgun tests run against a mocked HTTP client and never call the real APIs
- The Composer package no longer includes the Panel sources (`resources/`, `package.json`)
- Fixed the Composer suggestions: Mailgun needs `mailgun/mailgun-php`, not `symfony/mailgun-mailer`
- Updated the GitHub workflow to test PHP 8.4 – 8.6 with Kirby 5 and Kirby 6, the code style and the Panel build
- Added tests for the plugin registration, options and transport resolution

## 1.2.1 - 2025-11-11

- Update README with better installation instructions

## 1.2.0 - 2025-11-10

- Refactored EmailPlusProviders for better maintainability

## 1.1.1 - 2025-11-03

- Fixed namespace

## 1.1.0 - 2025-11-03

- Fixed namespace

## 1.0.1 - 2025-11-03

- Update phpunit.dist.xml for GitHub Actions

## 1.0.0 - 2025-11-03

- Initial release
