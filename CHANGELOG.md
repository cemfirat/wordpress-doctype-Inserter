# Changelog

## 1.2.2

- Preserve ordinary double hyphens in Simple comment mode.
- Neutralize only HTML comment delimiter sequences that would make the generated comment non-conforming.
- Exercise Simple mode, its permissions, upgrade persistence and the output toggle in real WordPress/HTTP integration tests.

## 1.2.1

- Restore the complete GNU GPL v2 license text in `LICENSE`.
- Add a packaging guard so an empty or truncated license cannot be shipped again.
- No runtime behavior changes from 1.2.0.

## 1.2.0

- Add a Simple comment mode that turns plain text into a safe HTML comment after the doctype.
- Keep existing 1.0/1.1 snippets unchanged and automatically select Advanced mode for upgraded installations.
- Add a live escaped source preview and optional templates for website credits, hiring and developer greetings.
- Add an enable/disable switch that pauses output without deleting saved content.
- Add an on-demand home-page output check to help diagnose page caches and HTML minification.
- Add project/help links, Cem Firat author metadata, and GPL-2.0-or-later license metadata and file.
- Keep the bounded streaming output engine and GitHub update behavior from 1.1.

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
