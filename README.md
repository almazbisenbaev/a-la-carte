
# PowerTools

Simple tools that solve common problems during WordPress development and maximize your productivity

---

## Available tools:
- Toolbar Toggler: Remove the annoying black bar on top of your website and replace it with a nice button
- Gutenberg Disabler: Disable the new visual editor and return to the legacy one (TinyMCE)
- Custom Post Types Manager: Easily create and manage custom post types
- HTML Junk Remover: Removes unnecessary code from the HTML such as generator version, WordPress emojis etc.
- Junk Cleaner: This tool lets you delete the drafts and revisions that are taking up your disc space
- Code Snippets: Easily insert and manage custom code snippets (CSS, JS, HTML) in the header, body, or footer
- Comments Disabler: Completely disable WordPress comments globally and clean up your admin area, prevent spam comments via REST API
- System Information: View a detailed overview of your WordPress environment and server configuration

---

## Project structure

```
powertools.php          Plugin header, constants, autoloader, bootstrap
uninstall.php           Removes every option and snippet on uninstall
includes/autoloader.php Maps PowerTools\ onto src/
src/                    Classes only (PSR-4 under the PowerTools namespace)
  Plugin.php            Boots the tool registry and the admin side
  Admin/                Tool registry, menu, asset loading, shared UI helpers
  <Feature>/            One directory per tool
views/                  Admin markup (dashboard)
assets/                 css/, js/ and images/ shipped with the plugin
languages/              Translation files
.wordpress-org/         Plugin directory artwork (not shipped in the zip)
```

Each tool is a class listed in `src/Admin/Tool_Manager.php`. A tool is only
instantiated when it has been switched on, registers its own hooks in its
constructor, and renders its screen from `render_settings_page()`. Tools whose
only setting is an on/off switch extend `Admin\Simple_Toggle_Tool`.

---

Author: Almaz Bissenbayev (https://helloalmaz.com)
