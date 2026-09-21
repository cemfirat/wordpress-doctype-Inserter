# Changelog

## 1.1.0

- Use English throughout the settings, plugin metadata, messages and documentation.
- Preserve snippets exactly, including `$1`, backslashes and the string `0`.
- Require both `manage_options` and `unfiltered_html` when saving raw snippets; retain the previous value on invalid submissions.
- Escape snippets in the editor and preserve the Settings API nonce protection.
- Skip non-HTML requests and responses, downloads, redirects and fixed-length or encoded output.
- Stream output after finding the document doctype; limit preamble inspection to 64 KiB and handle doctypes split across flushes.
- Preserve the existing `doctype_inserter_text` option.
- Discover stable GitHub releases through the native WordPress update mechanism with cached metadata, safe failure handling and a version details dialog.
- Keep the installed plugin folder during updates, including custom folder names.
- Add automated regression checks, WordPress integration tests and reproducible release ZIPs.

Version 1.0 needs one manual upgrade because it does not contain an update client.

## 1.0

Initial version: a settings field and full-response output buffering.
