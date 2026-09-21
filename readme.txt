=== Doctype Inserter ===
Contributors: cemfirat
Tags: doctype, html, source code, comments, developer
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add simple personal messages or an advanced trusted snippet immediately after the HTML doctype.

== Description ==

Doctype Inserter is a small tool for messages in a WordPress site's page source: website credits, developer greetings, hiring notes, or other short public messages.

Version 1.2 includes a Simple comment mode. Write normal text and the plugin wraps it in an HTML comment. Double hyphens are made comment-safe automatically. The settings page includes a source preview, optional templates, an output toggle, and an on-demand home-page output check.

Existing saved snippets from versions 1.0 and 1.1 automatically remain in Advanced snippet mode and are not rewritten. Advanced raw HTML or JavaScript requires both manage_options and unfiltered_html.

Only ordinary front-end HTML documents are changed. Feeds, REST, AJAX, cron, XML-RPC, HEAD requests, robots.txt, favicons, downloads, encoded responses and fixed Content-Length responses are skipped.

Stable updates are delivered from the public GitHub repository. Update checks contact api.github.com and package downloads use github.com. No saved message or site content is sent to GitHub.

== Installation ==

1. Download doctype-inserter.zip from the latest GitHub release.
2. In WordPress, open Plugins > Add New > Upload Plugin and upload the ZIP.
3. Activate Doctype Inserter and open Settings > Doctype Inserter.
4. Write a source-code message and save it.
5. Clear page/CDN caches if necessary and inspect View Source.

Version 1.0 has no updater. Install version 1.1.0 or newer manually once before future releases can appear automatically.

== Frequently Asked Questions ==

= Is the simple message visible on the page? =

No. Simple mode creates an HTML comment. It is public in the HTML response and View Source, but it does not appear in the rendered page layout.

= What happens to my existing snippet? =

It remains unchanged. Existing installations with a saved legacy snippet automatically use Advanced mode until you choose another mode.

= Can I still insert scripts or raw HTML? =

Yes, in Advanced mode when WordPress grants unfiltered_html. Content before the opening HTML element can affect browser parsing, so comments are the recommended use case.

= Why does a saved change not appear? =

Clear page and CDN caches, check HTML minification, then use Check home page output on the settings screen.

== Changelog ==

= 1.2.0 =
* Add Simple comment mode for plain-text source messages.
* Preserve existing snippets in Advanced mode.
* Add live source preview and optional credits, hiring and developer templates.
* Add an output enable/disable switch without deleting saved content.
* Add an on-demand home-page output check for cache/minification troubleshooting.
* Add project/help links, author metadata and GPL-2.0-or-later licensing.

= 1.1.0 =
* English settings, messages and documentation.
* Preserve literal dollar signs, backslashes and a snippet of "0".
* Require WordPress unfiltered HTML permission when saving raw snippets.
* Bound output buffering and skip non-HTML responses.
* Add stable GitHub release updates and retain existing plugin directories.
* Add regression tests, WordPress integration checks and release packaging.

= 1.0 =
* Initial version.
