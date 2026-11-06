=== Document Manager ===
Contributors: philhoyt
Tags: documents, files, redirects, media, links
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stores files and URLs as documents with a permanent link that redirects to the current destination

== Description ==

Document Manager adds a Documents post type. Each document is either a file from the media library or an external URL, and each one gets a permalink such as `/documents/annual-report/`. Visiting that permalink sends a 302 redirect to the current destination. Link to the permalink from anywhere (link picker, buttons, navigation menus) and when you replace the file or change the URL, every existing link keeps working because the permalink has not changed.

What the plugin does:

* File documents: pick a file from the media library. The permalink redirects to the attachment URL.
* URL documents: enter any URL, for example a Google Drive or Dropbox share. The permalink redirects there.
* Revision history: each time the file or URL changes, the previous destination is recorded with who changed it and when. Any earlier destination can be restored from the edit screen.
* Validation: a document with no file or no valid URL is held at draft and an admin notice explains why.
* Link picker: documents show up in the block editor and classic editor link search like any other content.
* Document Categories: a hierarchical taxonomy for grouping documents, with a filter on the Documents list.
* Sitemaps: documents are left out of the core, Yoast SEO and Rank Math sitemaps, and redirect responses send `X-Robots-Tag: noindex, nofollow`.
* Settings: the permalink base (default `documents`) and a fallback URL for documents whose destination is missing.
* Access: editing documents needs the `manage_ph_documents` capability, granted to administrators and editors on activation.

There is no block or shortcode. The permalink is the integration point.

== Installation ==

1. Download `ph-document-manager.zip` from the latest GitHub release.
2. In the WordPress admin, go to Plugins > Add New Plugin > Upload Plugin and choose the zip.
3. Activate the plugin. Documents appears in the admin sidebar.

Updates are delivered from GitHub releases. The plugin is not listed on WordPress.org.

== Frequently Asked Questions ==

= What happens if the file is deleted from the media library? =

The permalink redirects to the 404 Fallback URL set under Documents > Settings, or returns a 404 if none is set. The plugin never deletes files itself; old files stay in the media library after a replacement.

= Can I change the permalink base? =

Yes. Go to Documents > Settings and change the slug. Rewrite rules are flushed when you save.

= Who can manage documents? =

Administrators and editors by default. The `manage_ph_documents` capability can be granted to any role. The REST endpoint for documents also requires an editing capability, so logged-out visitors cannot list documents or read their file URLs; the permalinks themselves are public.

= Does it work with the classic editor? =

Yes. Documents appear in the classic editor link dialog as well as the block editor link picker.

== Changelog ==

= 1.1.2 =
* Fixed the update checker so it always installs the release zip, not whichever file GitHub lists first.
* Rewrote the readme files in plain English.

= 1.1.1 =
* Tested up to WordPress 7.1.

= 1.1.0 =
* Added documents to the block and classic editor link pickers. The post type was registered private, and both pickers only search public post types, so this never worked before.
* Added sorting of the Documents list by the Type column.
* Added screen reader announcements when copying a document link and when selecting or removing a file.
* Fixed restoring a revision, which failed with a blank error page because the handler was never registered.
* Fixed saving a document with revision history, which discarded the change and redirected to the posts list.
* Fixed removing a file from a document dropping it from revision history instead of recording it.
* Fixed publishing a document with no destination cutting the save short, which skipped work other plugins do on save.
* The documents REST endpoint now requires an editing capability. Anonymous visitors could previously list every document and read its file URL. Permalinks are unaffected.
* Stopped granting the unused `upload_ph_documents` capability. It is still removed on uninstall for existing sites.
* Lowered the minimum WordPress version to 6.3 to match the readme. Nothing in the plugin needed 6.7.
* Removed the unused document capability setting, which had no field on the settings screen and was never read.

= 1.0.0 =
* Initial release.
