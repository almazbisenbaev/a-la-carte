=== Seitenmeister ===
Contributors: almazbisenbaev
Tags: disable comments, disable gutenberg, custom post types, admin bar, cleanup
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.0.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Seven small tools in one plugin: disable comments or Gutenberg, add post types, clean junk, tidy your HTML, and check your system info.

== Description ==

Every WordPress site needs the same handful of fixes. You disable comments. You strip the junk WordPress prints into your `<head>`. You add a custom post type. You clear out thousands of stale revisions.

The usual answer is seven separate single-purpose plugins, each with its own settings page, its own update cycle, and its own overhead. Seitenmeister puts those seven jobs in one place.

Every tool is **off until you switch it on**. An inactive tool registers no hooks and loads no code, so the plugin costs you nothing on the front end until you actually use it.

= Available tools =

**Toolbar Toggler** — The black admin bar sits on top of your site and shifts your layout by 32 pixels, so what you see while logged in isn't what visitors see. This hides the bar and replaces it with a small button that brings it back when you need it.

**Gutenberg Disabler** — Restores the classic TinyMCE editor. Disable the block editor everywhere, or only for the post types you choose, so a client's page builder can keep working while your posts stay classic. Your content is not modified.

**Custom Post Types Manager** — Register post types from a form instead of hand-writing `register_post_type()` calls in `functions.php`. Choose public or admin-only visibility, labels, menu icon, archive and hierarchy options, and editing features. Permalinks refresh automatically when routes change.

**HTML Junk Remover** — WordPress prints things into every page that most sites never use: the generator version tag that advertises which WordPress you run, RSD and WLW manifest links, shortlink and oEmbed discovery tags, REST API links, and the emoji detection script and styles. This removes them.

**Junk Cleaner** — Delete revision history in batches. Review drafts by title, post type, and last modified date, then move selected drafts to Trash.

**Comments Disabler** — Turns comments off site-wide and removes the traces: the admin menu, the toolbar bubble, the dashboard widget, the post editor metaboxes, and the comments widget. It also closes the REST API and XML-RPC comment endpoints, which is where most comment spam arrives once the front-end form is gone.

**System Information** — One screen showing your WordPress, PHP, MySQL and server versions, memory limit, execution time, upload size, disk space, and active plugins. Useful when a host asks what your setup is, or when you're filing a bug report.

= Privacy =

Seitenmeister sends nothing anywhere. There is no telemetry, no analytics, no external API calls, and no third-party services. All data stays in your own database.

== Installation ==

From your WordPress dashboard:

1. Go to **Plugins → Add New**.
2. Search for **Seitenmeister**.
3. Click **Install Now**, then **Activate**.
4. Open **Seitenmeister** in the admin menu and switch on the tools you want.

Manual installation:

1. Download the ZIP and upload it under **Plugins → Add New → Upload Plugin**, or extract it into `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu.
3. Open **Seitenmeister** in the admin menu and switch on the tools you want.

Every tool starts switched off. Nothing changes on your site until you enable something.

== Frequently Asked Questions ==

= Do I have to use all seven tools? =
No. Each one is enabled separately, and a tool that is off does nothing at all — it registers no hooks and runs no code. Enable one tool and the other six are inert.

= Will disabling Gutenberg delete my content? =
No. Your content is untouched. It only changes which editor loads when you open a post, switching you back to the classic TinyMCE editor. Existing block content still displays normally on the front end, and you can switch back at any time.

= What exactly does Junk Cleaner delete? =
Revision cleanup permanently deletes saved revisions, including autosaves, in batches of 50. It skips revisions created after the preview and those you do not have permission to delete. With JavaScript enabled the batches run automatically while the page is open. If interrupted, reopen the cleaner to see what remains.

Draft cleanup moves only the drafts you select to WordPress Trash. You can restore them from the post type’s Trash until WordPress empties it. Draft cleanup is unavailable on sites with Trash disabled.

= What does admin-only mean for a post type? =
Clear **Public** to keep the editing screens in wp-admin and disable public URLs, archives, site search, navigation menu selection, and the post type’s REST routes. Existing types saved with Public unchecked also use this behavior. WordPress uses the classic editing screen for these types because the block editor requires REST access.

= Does removing the emoji script break emoji? =
No. It removes the compatibility script WordPress loads to render emoji as images on browsers that lack native support. Every current browser renders emoji natively, so they keep working — you just save the request.

= Does this plugin work on multisite? =
Yes. Every tool is configured per site, and its settings apply to that site only.

= What happens to my settings if I uninstall the plugin? =
Deleting Seitenmeister removes all of its settings. Content you created through the Custom Post Types Manager is deliberately left alone — those are your own posts, and only the post type registration goes away.

= Is it translation-ready? =
Yes. All strings are translatable and contributions are welcome through translate.wordpress.org.

== Screenshots ==

1. Seitenmeister dashboard, where each tool is switched on or off.
2. Junk Cleaner showing the drafts and revisions found.
3. The Gutenberg Disabler, set to disable the block editor for selected post types.
4. The Custom Post Types Manager.

== Changelog ==

= 1.0.2 =
* Review and trash selected drafts separately from batched revision cleanup.
* Keep admin-only post types editable in wp-admin and remove their public routes.
* Refresh permalinks automatically when a managed post type's routes change.

= 1.0.1 =
* Renamed AlmazBro Site Utilities to Seitenmeister. Existing settings and tools are preserved.
* Updated admin screen detection for the new menu name.

= 1.0.0 =
* Initial release.
* Toolbar Toggler: hide the admin bar behind a small toggle button.
* Gutenberg Disabler: restore the classic editor globally or per post type.
* Custom Post Types Manager: create and manage post types from the admin.
* HTML Junk Remover: strip the generator tag, RSD/WLW links, shortlinks, oEmbed and REST discovery links, and the emoji scripts.
* Junk Cleaner: permanently delete drafts and post revisions to reclaim database space.
* Comments Disabler: disable comments site-wide and close the REST API and XML-RPC comment endpoints.
* System Information: view your WordPress, PHP, MySQL, server and plugin details.

== Upgrade Notice ==

= 1.0.2 =
Post types previously saved with Public unchecked become admin-only, including their REST routes. Enable Public for types that need public URLs or REST access.

= 1.0.1 =
AlmazBro Site Utilities is now Seitenmeister. Your existing settings are preserved.

= 1.0.0 =
Initial release.
