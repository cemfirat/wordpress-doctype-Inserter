# Doctype Inserter

Insert a trusted snippet immediately after the HTML doctype on WordPress front-end pages.

**Requirements:** WordPress 5.8 or later; PHP 7.4 or later. Use maintained WordPress and PHP versions for production.

## Install

1. Download **doctype-inserter.zip** from the [latest release](https://github.com/cemfirat/wordpress-doctype-Inserter/releases/latest).
2. Open **Plugins → Add New → Upload Plugin** in WordPress, upload the ZIP and activate it.
3. Open **Settings → Doctype Inserter**, enter a snippet and select **Save Changes**.
4. Clear any page or CDN cache and inspect the page source.

For example, `<!-- Site verification: example -->` becomes:

```html
<!DOCTYPE html>
<!-- Site verification: example -->
<html>
```

Leave the field empty to disable insertion. The string `0` is a valid nonempty snippet. PHP in a snippet is never executed.

## Upgrade from version 1.0

Version 1.0 has no update client. Publishing a new GitHub version cannot make that installed code discover an update. **Install 1.1.0 manually once.** Subsequent stable releases can then appear in WordPress automatically while the plugin is active.

- If the existing folder is `wordpress-doctype-Inserter`, upload the release ZIP through WordPress and choose to replace the installed version.
- If the existing folder has another name (for example `wordpress-doctype-Inserter-main` or `doctype-inserter`), extract the release ZIP and replace the files **inside that existing folder** through your hosting file manager/SFTP. Keep the folder name and the main filename `doctype-inserter.php` unchanged. Include the new `includes` directory.
- If `doctype-inserter.php` is directly in `wp-content/plugins` with no containing folder, deactivate it, remove that standalone PHP file, install the release ZIP and activate the packaged plugin. Do not leave both copies active.

The existing `doctype_inserter_text` option is retained; no database migration is needed. Back up the existing plugin files before replacing them. The plugin has no uninstall routine that deletes snippets.

## Updates

The plugin uses WordPress's `Update URI` integration to check [stable GitHub releases](https://github.com/cemfirat/wordpress-doctype-Inserter/releases) during ordinary plugin update checks. Keep it active for these checks. Select **Dashboard → Updates → Check Again** to refresh manually. WordPress controls the update schedule; discovery is not instantaneous.

- Only complete stable `vX.Y.Z` releases with the `doctype-inserter.zip` asset and requirement metadata are eligible.
- GitHub metadata is cached for six hours; errors/rate limits are cached for fifteen minutes.
- GitHub outages do not affect normal page rendering. No API request is made just to render a front-end page.
- Automatic installation is optional: select **Enable auto-updates** for this plugin in WordPress. The plugin does not enable it for you.
- Updates keep the existing installation directory and saved snippet.
- Update checks contact `api.github.com`; package downloads use `github.com`. No snippet or site content is transmitted. GitHub receives the server's normal connection metadata.

## Permissions and output behavior

Raw HTML and JavaScript are intentional features. Saving them requires both `manage_options` and `unfiltered_html`, using WordPress's Settings API and nonce validation. Multisite site administrators and installations with `DISALLOW_UNFILTERED_HTML` cannot edit raw snippets. Existing saved snippets continue to render.

HTML comments are recommended at this position. Scripts or visible markup before `<html>` can alter browser parsing; this plugin is not a substitute for `wp_head` or `wp_footer` when those positions are required.

The plugin does nothing when its snippet is empty. When enabled, it buffers in 8 KiB chunks and retains at most a 64 KiB preamble between callbacks, then streams the remaining body. Only a leading HTML doctype (optionally preceded by a UTF-8 BOM, whitespace or comments) is eligible. Dollar signs, backslashes and line breaks are preserved.

Admin pages, feeds, REST, AJAX, cron, XML-RPC, WP-CLI, HEAD requests, robots.txt and favicon requests are excluded. Non-HTML content types, redirects, downloads, already encoded responses and fixed `Content-Length` responses are left unchanged. No doctype is invented for documents without one. A doctype beyond the first 64 KiB is left unchanged. As with other output filters, headers must be set before the body is flushed. External page caches need clearing after changes.

## Development and releases

Run the dependency-free regression suite:

```sh
php tests/run.php
python3 scripts/package.py
```

GitHub Actions also runs syntax checks and WordPress integration tests. The integration suite uses real WordPress APIs to check settings permissions, update discovery and ZIP installation while mocking outbound update metadata.

To publish a new version:

1. Bump the plugin header, `DOCTYPE_INSERTER_VERSION` and `readme.txt` stable tag to the same `X.Y.Z` version.
2. Update `CHANGELOG.md` and the readme changelog.
3. Merge to `main`. After regression and integration checks pass, the workflow builds a ZIP, creates a draft `vX.Y.Z` release, attaches the ZIP, then publishes it as latest.

Existing published tags are not overwritten. The release job needs the repository's normal Actions `contents: write` permission. The `workflow_dispatch` trigger allows retrying a failed draft upload. Future minimum WordPress/PHP requirements are read from the release metadata instead of being hardcoded into the installed updater.

See [CHANGELOG.md](CHANGELOG.md) for changes.
