=== Document Manager ===
Contributors: philhoyt
Tags: documents, files, redirects, media, links
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage files and URLs and link to them from anywhere on your site.

== Description ==

Document Manager stores files and URLs as posts with their own permalinks. Link to a document from anywhere on your site, and when the destination changes, every link on the site updates automatically.

Links placed anywhere on the site — block editor, button blocks, navigation menus — always resolve to wherever the document currently points. Replace a file or update a URL once, and every link that references that document follows automatically.

* **File documents** — Upload via the media library. The permalink redirects to the file's direct URL.
* **URL documents** — Point to any external URL. Useful for Google Drive files, Dropbox shares, or any link that needs to be managed centrally.
* **Revision history** — Every destination change is recorded. Restore any previous version with one click.
* **Required field validation** — Documents without a valid destination stay at draft with an admin notice. Publishing an incomplete document isn't possible.
* **No custom block needed** — The core link picker discovers documents natively via REST.
* **SEO clean** — Excluded from WordPress core, Yoast, and RankMath sitemaps. Redirect responses include `X-Robots-Tag: noindex, nofollow`.
* **Document categories** — Hierarchical taxonomy for organizing by department, project, or type.
* **Configurable slug** — Change the permalink base from the settings page. Rewrite rules flush on save.
* **Capability-based access** — `manage_ph_documents` and `upload_ph_documents`, assigned to administrators and editors on activation.

== Installation ==

1. Upload the `ph-document-manager` folder to `/wp-content/plugins/`.
2. Activate via the **Plugins** screen.
3. Go to **Documents** in the admin sidebar.

== Frequently Asked Questions ==

= What happens if I delete a file from the media library? =

The permalink falls back to the configured 404 fallback URL, or returns a 404 if none is set. Files are never deleted automatically.

= Can I change the permalink slug? =

Yes. Go to **Documents → Settings**. Rewrite rules flush automatically on save.

= Who can manage documents? =

Administrators and editors by default. `manage_ph_documents` covers full CRUD, `upload_ph_documents` covers file upload. Both can be assigned to any role.

== Changelog ==

= 1.0.0 =
* Initial release.