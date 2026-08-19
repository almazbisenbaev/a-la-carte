=== PowerTools ===
Contributors: almazbisenbaev
Tags: disable comments, disable gutenberg, custom post types, code snippets, admin bar
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Eight small tools in one plugin: disable comments or Gutenberg, add post types, insert code snippets, clean junk, and tidy your HTML.

== Description ==

Every WordPress site needs the same handful of fixes. You disable comments. You strip the junk WordPress prints into your `<head>`. You add a custom post type. You drop a tracking script into the footer.

The usual answer is eight separate single-purpose plugins, each with its own settings page, its own update cycle, and its own overhead. PowerTools puts those eight jobs in one place.

Every tool is **off until you switch it on**. An inactive tool registers no hooks and loads no code, so the plugin costs you nothing on the front end until you actually use it.

= Available tools =

**Toolbar Toggler** — The black admin bar sits on top of your site and shifts your layout by 32 pixels, so what you see while logged in isn't what visitors see. This hides the bar and replaces it with a small button that brings it back when you need it.

**Gutenberg Disabler** — Restores the classic TinyMCE editor. Disable the block editor everywhere, or only for the post types you choose, so a client's page builder can keep working while your posts stay classic. Your content is not modified.

**Custom Post Types Manager** — Register post types from a form instead of hand-writing `register_post_type()` calls in `functions.php`. Set the labels, menu icon, menu position, archive and hierarchy options, and which features (title, editor, thumbnail, and so on) each type supports.

**HTML Junk Remover** — WordPress prints things into every page that most sites never use: the generator version tag that advertises which WordPress you run, RSD and WLW manifest links, shortlink and oEmbed discovery tags, REST API links, and the emoji detection script and styles. This removes them.

**Junk Cleaner** — Shows how many drafts and post revisions are sitting in your database and deletes them in one click, so revision history doesn't quietly grow to thousands of rows. Read the FAQ below before using it — the deletion is permanent and covers *all* drafts.

**Code Snippets** — Add HTML or JavaScript to `wp_head`, `wp_body_open`, or `wp_footer` without touching your theme, so your snippets survive a theme update. Each snippet can be limited to specific user roles or post types, and switched on and off individually. Administrators can optionally enable PHP snippets — see the FAQ for what that means and how it's restricted.

**Comments Disabler** — Turns comments off site-wide and removes the traces: the admin menu, the toolbar bubble, the dashboard widget, the post editor metaboxes, and the comments widget. It also closes the REST API and XML-RPC comment endpoints, which is where most comment spam arrives once the front-end form is gone.

**System Information** — One screen showing your WordPress, PHP, MySQL and server versions, memory limit, execution time, upload size, disk space, and active plugins. Useful when a host asks what your setup is, or when you're filing a bug report.

= Privacy =

PowerTools sends nothing anywhere. There is no telemetry, no analytics, no external API calls, and no third-party services. All data stays in your own database.

== Installation ==

From your WordPress dashboard:

1. Go to **Plugins → Add New**.
2. Search for **PowerTools**.
3. Click **Install Now**, then **Activate**.
4. Open **PowerTools** in the admin menu and switch on the tools you want.

Manual installation:

1. Download the ZIP and upload it under **Plugins → Add New → Upload Plugin**, or extract it into `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu.
3. Open **PowerTools** in the admin menu and switch on the tools you want.

Every tool starts switched off. Nothing changes on your site until you enable something.

== Frequently Asked Questions ==

= Do I have to use all eight tools? =
No. Each one is enabled separately, and a tool that is off does nothing at all — it registers no hooks and runs no code. Enable one tool and the other seven are inert.

= Will disabling Gutenberg delete my content? =
No. Your content is untouched. It only changes which editor loads when you open a post, switching you back to the classic TinyMCE editor. Existing block content still displays normally on the front end, and you can switch back at any time.

= What exactly does Junk Cleaner delete? =
Two things: every post revision, and **every draft of every post type**. Both are deleted permanently and do not go to the trash, so they cannot be recovered afterwards.

That second part matters. If you have half-finished posts saved as drafts, this tool deletes them along with the abandoned ones — it does not distinguish between a draft you started yesterday and one from three years ago. The screen tells you exactly how many of each it found before you confirm. Back up your database first if you are not certain.

Inactive code snippets created by PowerTools are stored separately and are not affected.

= Does the Code Snippets tool really execute PHP? =
Yes, if you deliberately turn it on. PHP snippets run with the same privileges as a theme or plugin file, which is what makes them useful and also why they are risky.

The feature ships disabled. You must tick "Enable PHP Snippet Execution" before any PHP runs. Only administrators can create or edit PHP snippets — network administrators on multisite — and the option is unavailable entirely on sites that define `DISALLOW_FILE_EDIT` or `DISALLOW_FILE_MODS`, which many managed hosts set by default.

If a PHP snippet throws an error, it is caught, that snippet is disabled automatically, and you get a notice in the admin area, rather than the error taking your site down. HTML and JavaScript snippets are unaffected by any of this and need no special permission.

= Will my snippets survive a theme change? =
Yes. Snippets are stored in the database, not in your theme files, so switching or updating your theme leaves them in place. They are also unaffected by plugin updates.

= Does removing the emoji script break emoji? =
No. It removes the compatibility script WordPress loads to render emoji as images on browsers that lack native support. Every current browser renders emoji natively, so they keep working — you just save the request.

= Does this plugin work on multisite? =
Yes, with one restriction: only network administrators can manage PHP snippets or change the PHP execution setting, since a PHP snippet runs across the whole install. Every other tool works normally per site.

= What happens to my settings if I uninstall the plugin? =
Deleting PowerTools removes all of its settings and its stored code snippets. Content you created through the Custom Post Types Manager is deliberately left alone — those are your own posts, and only the post type registration goes away. If you want to keep your snippets, copy them out before deleting the plugin.

= Is it translation-ready? =
Yes. All strings are translatable and contributions are welcome through translate.wordpress.org.

== Screenshots ==

1. The PowerTools dashboard, where each tool is switched on or off.
2. Junk Cleaner showing the drafts and revisions found.
3. The Gutenberg Disabler, set to disable the block editor for selected post types.
4. The Custom Post Types Manager.
5. The Code Snippets list, showing PHP, JS and HTML snippets and their locations.
6. The Code Snippets editor with conditional display rules.

== Changelog ==

= 1.0.0 =
* Initial release.
* Toolbar Toggler: hide the admin bar behind a small toggle button.
* Gutenberg Disabler: restore the classic editor globally or per post type.
* Custom Post Types Manager: create and manage post types from the admin.
* HTML Junk Remover: strip the generator tag, RSD/WLW links, shortlinks, oEmbed and REST discovery links, and the emoji scripts.
* Junk Cleaner: permanently delete drafts and post revisions to reclaim database space.
* Code Snippets: insert HTML and JavaScript into the header, body or footer, with per-role and per-post-type display rules, plus optional administrator-only PHP snippets that are disabled by default.
* Comments Disabler: disable comments site-wide and close the REST API and XML-RPC comment endpoints.
* System Information: view your WordPress, PHP, MySQL, server and plugin details.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
