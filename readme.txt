=== Doctype Inserter ===
Contributors: cemfirat
Tags: doctype, html, snippet
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 1.1.0

Insert a custom snippet immediately after the HTML doctype on front-end pages.

== Description ==

Doctype Inserter adds a trusted snippet once, immediately after the document's HTML doctype. Configure it under Settings > Doctype Inserter. Leave the snippet empty to disable insertion.

The interface, documentation and plugin messages are in English. Existing version 1.0 settings are retained. Editing raw HTML or scripts requires both manage_options and unfiltered_html; on Multisite, normally only a Super Admin has both.

Only HTML documents are changed. Feeds, REST, AJAX, cron, XML-RPC, HEAD requests, robots.txt, favicons, downloads, encoded responses and responses with a fixed Content-Length are skipped. Documents without an HTML doctype in the first 64 KiB are unchanged.

Stable updates are delivered from the public GitHub repository. During WordPress update checks, the plugin requests release metadata from api.github.com. Installation downloads the release ZIP from github.com. No snippet or site content is sent. GitHub receives normal connection data such as the server IP address.

== Installation ==

1. Download doctype-inserter.zip from the latest GitHub release.
2. In WordPress, open Plugins > Add New > Upload Plugin and upload the ZIP.
3. Activate Doctype Inserter and open Settings > Doctype Inserter.
4. Save a trusted snippet and clear any page/CDN cache.

Version 1.0 has no updater. Install 1.1.0 manually once before future releases can appear in WordPress. See README.md for migration instructions for custom folder or single-file installations.

== Frequently Asked Questions ==

= How do updates work? =

Keep the plugin active. WordPress checks stable GitHub releases during its normal plugin update checks. Select Dashboard > Updates > Check Again to refresh manually. Automatic installation is optional and follows the plugin's Enable auto-updates setting. Draft and prerelease versions are ignored.

= Can I insert scripts or visible HTML? =

Yes, if WordPress allows you to save unfiltered HTML. This position is before the opening HTML element, so comments are recommended. Other markup can change browser parsing; code intended for the head or body should use those locations instead. PHP is never evaluated.

= Why does a saved change not appear? =

Clear page and CDN caches, confirm the page has a leading HTML doctype, and inspect the response source. The plugin does not change non-HTML responses or output that has already been sent.

== Changelog ==

= 1.1.0 =
* English settings, messages and documentation.
* Preserve literal dollar signs, backslashes and a snippet of "0".
* Require WordPress unfiltered HTML permission when saving raw snippets.
* Bound output buffering and skip non-HTML responses.
* Add stable GitHub release updates and retain existing plugin directories.
* Add regression tests, WordPress integration checks and release packaging.

= 1.0 =
* Initial version.
