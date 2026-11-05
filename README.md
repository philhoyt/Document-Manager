# Document Manager

Stores files and URLs as documents with a permanent link that redirects to the current destination.

Each document is a `ph_document` post that is either a media library file or an external URL. Its permalink (`/documents/annual-report/` by default) answers with a 302 redirect to wherever the document currently points, so a link placed in a post, a button, or a navigation menu keeps working after the file is replaced or the URL changes. Every change to the destination is kept in a revision list and can be restored.

There is no block or shortcode. Documents appear in the block editor and classic editor link pickers, and the permalink is the only integration point.

## Requirements

- WordPress 6.3 or later
- PHP 7.4 or later
- Node.js 22 or later and Composer to build from source

## Installation

1. Download `ph-document-manager.zip` from the [latest release](https://github.com/philhoyt/Document-Manager/releases/latest).
2. In the WordPress admin, go to Plugins > Add New Plugin > Upload Plugin and choose the zip.
3. Activate the plugin.

Updates come from GitHub releases through Plugin Update Checker. The plugin is not on WordPress.org. The zip includes `vendor/`, which holds the plugin's autoloader and the update checker; a plain git checkout needs `composer install` before it will load.

## Usage

Go to Documents > Add New. Give the document a title, choose File or URL, then either pick a file from the media library or enter the URL. Publish. The Document Link box shows the permalink with a copy button.

A document cannot be published without a destination: if the file is missing or the URL is empty or invalid, the post is saved as a draft and a notice on the edit screen says why.

To update a document, open it and pick a new file or enter a new URL. The old destination moves into the Revision History box with the date and the user who made the change, and can be restored from there. Old files are left in the media library.

The Documents list has Type, Destination and Category columns, a category filter, and sorts by Type.

### Document Categories

A hierarchical taxonomy, `ph_document_category`, shown in the sidebar and available over REST. It is for organising the admin list; it has no front-end archive.

## Configuration

Documents > Settings:

| Setting | Option | Default | Effect |
|---------|--------|---------|--------|
| Permalink slug | `ph_document_manager_slug` | `documents` | URL base for all document permalinks. Rewrite rules are flushed on save. |
| 404 Fallback URL | `ph_document_manager_fallback_url` | empty | Where to redirect when a document's file no longer exists or it has no destination. Empty means a 404. |

### Capabilities

Activation grants `manage_ph_documents` to the administrator and editor roles. The Documents menu, the edit screen, and the REST endpoint all check it. Grant it to other roles with any role editor. Uninstall removes it (and the legacy `upload_ph_documents`) from every built-in role; deactivation leaves it in place.

### Search engines

Documents are excluded from the core sitemap, the Yoast SEO sitemap and the Rank Math sitemap. Every redirect response carries `X-Robots-Tag: noindex, nofollow`.

### Meta keys

| Key | Holds |
|-----|-------|
| `_ph_document_source_type` | `file` or `url` |
| `_ph_document_file_id` | Attachment ID of the current file |
| `_ph_document_url` | Current URL for URL documents |
| `_ph_document_revisions` | Array of previous destinations with timestamp, user ID and optional label |

## REST API

Documents are a public post type with `show_in_rest`, so they appear at `/wp-json/wp/v2/ph_document`. The plugin replaces the controller's read permission checks: listing or reading a document over REST requires the edit capability for that document. This is what keeps file URLs away from anonymous visitors while still letting the link picker, which runs as a logged-in editor, find documents.

## Development

```bash
npm install
composer install
npm start              # watch src/ and rebuild into build/
npm run build          # production build
npm run lint:js
npm run lint:css
composer run lint      # phpcs
composer run lint:fix
composer run analyse   # phpstan
npm run test:unit      # wp-scripts test-unit-js (no JS tests yet; passes with none)
```

PHPUnit runs inside wp-env against the tests in `tests/phpunit/` (REST access, revisions, validation):

```bash
npm run env:start
npm run test:php
```

The `test:php` script uses `--env-cwd wp-content/plugins/Document-Manager`, so the checkout folder must be named `Document-Manager`.

CI (`.github/workflows/ci.yml`) runs `composer audit`, phpcs and phpstan on PHP 7.4 through 8.4, lints and builds the JavaScript, and runs PHPUnit in wp-env, on every push to `main` and every pull request.

## Releases

Bump the version in `ph-document-manager.php` (header and `PH_DOCUMENT_MANAGER_VERSION`), `package.json` and `Stable tag` in `readme.txt`, add the changelog entry to `readme.txt`, then push a lowercase `v` tag:

```bash
git tag v1.1.1 && git push origin v1.1.1
```

The tag must start with a lowercase `v`. Plugin Update Checker strips the prefix with a case-sensitive `ltrim`, so an uppercase `V1.1.0` tag would never be offered as an update (the repository's `V1.0.0` tag is an example of this and is ignored by the workflow).

The release workflow runs `npm run plugin-zip` (build, `composer install --no-dev`, zip), checks that every path listed in `package.json` `files` is in the zip, takes that version's section of the `readme.txt` changelog as the release notes, and attaches `ph-document-manager.zip` and `readme.txt` to the GitHub release.

## Limitations

- Redirects are always 302. There is no option for 301.
- Document permalinks are public. The plugin does not restrict who can download a file; it only hides the file URL from the REST API. Access control is out of scope (see `plans/PROTECTED-DOCUMENTS.md`).
- One file or one URL per document.
- Files are never deleted by the plugin, including on uninstall. Uninstall removes the two options and the capabilities and leaves documents and media in place.
- Yoast SEO and Rank Math are the only third-party sitemaps handled.

## License

GPL-2.0-or-later
