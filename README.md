<p align="center">
  <img src="assets/logo.svg" alt="Cem Firat logo" width="120" />
</p>

# Doctype Inserter

A small, free WordPress plugin for personal messages in the page source. Add website credits, a developer greeting, a hiring note, or an advanced trusted snippet immediately after the HTML doctype.

**Author:** [Cem Firat](https://cemfirat.com/)  
**License:** GPL-2.0-or-later  
**Requirements:** WordPress 5.8+ and PHP 7.4+.

## What it does

Version 1.2 adds a **Simple comment** mode. Write normal text and Doctype Inserter creates the HTML comment for you:

```html
<!DOCTYPE html>
<!--
Hello, developers!
We're hiring: https://example.com/jobs
-->
<html>
```

The message does not appear in the visible page layout. It is public to anyone who inspects the response or page source.

The settings screen includes a live source preview, templates for credits/hiring/developer greetings, an output toggle, and a home-page output check for spotting cache or minification issues. The preview displays input as text and does not execute it. Simple mode preserves source-like text such as `<html>`, percent-encoded URLs, dollar signs, backslashes, line breaks and ordinary `--`. It only removes NUL bytes and neutralizes sequences that would conflict with HTML comment delimiters.

## Backward compatibility

Existing 1.0/1.1 installations that already contain `doctype_inserter_text` automatically remain in **Advanced snippet** mode. Their saved snippet is not rewritten. New installations default to Simple comment mode.

Advanced mode remains available for trusted raw HTML/JavaScript and requires both `manage_options` and `unfiltered_html`. Simple comments require only `manage_options`.

## Install

1. Download **doctype-inserter.zip** from the [latest release](https://github.com/cemfirat/wordpress-doctype-Inserter/releases/latest).
2. In WordPress open **Plugins → Add New → Upload Plugin**, upload the ZIP, and activate it.
3. Open **Settings → Doctype Inserter**.
4. Write a message, save it, and inspect the page source.
5. If needed, clear page/CDN caches or use **Check home page output**.

Version 1.0 has no updater. Install 1.1.0 or newer manually once; subsequent stable GitHub releases can then be discovered through WordPress.

## Output behavior

Doctype Inserter only changes ordinary front-end HTML documents with a leading HTML doctype. Admin pages, feeds, REST, AJAX, cron, XML-RPC, WP-CLI, HEAD requests, robots.txt, favicons, redirects, downloads, encoded responses, and fixed `Content-Length` responses are skipped.

The output engine buffers only the document preamble (up to 64 KiB), then streams the remaining response. Pages without an eligible doctype are unchanged.

The **Check home page output** button performs an on-demand request from WordPress to the site's own home page and searches for the active message. Normal page rendering makes no network request.

## Updates and privacy

Stable updates are delivered from this public GitHub repository using WordPress's `Update URI` integration. Update checks contact `api.github.com`; package downloads use `github.com`. No saved message or site content is sent to GitHub.

## Development

Run:

```sh
php tests/run.php
python3 scripts/package.py
```

GitHub Actions also runs syntax checks and WordPress integration tests.

## Project links

- Documentation and releases: https://github.com/cemfirat/wordpress-doctype-Inserter
- Bugs and feature requests: https://github.com/cemfirat/wordpress-doctype-Inserter/issues
- Author: https://cemfirat.com/
- Changelog: [CHANGELOG.md](CHANGELOG.md)

Copyright © 2026 Cem Firat. Doctype Inserter is licensed under GPL-2.0-or-later.
