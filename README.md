# IELTS Science BuddyBoss child theme

This child theme is packaged as an installable WordPress theme ZIP for wp-env. It requires the BuddyBoss parent theme to be installed separately. The `studyieltsvn` branch bundles the MUI light palette and typography through native BuddyBoss options and Custom Fonts. It has no template overrides or JavaScript customizations.


## Design setup

Install and activate the ZIP with BuddyBoss installed. On the first request with this child theme active, setup registers the bundled Linotte and Baloo Bhaina 2 fonts and applies the visual preset in `config/buddyboss-preset.json`. Installing an update over an already-active copy also runs this initial setup; no manual font upload or settings import is needed.

The preset uses white page/content backgrounds, red primary buttons, navy secondary buttons, and the MUI semantic colors. Body text uses Linotte; headings use Baloo Bhaina 2, named **Baloo Bhaina** in BuddyBoss because its font renderer does not support a standalone digit in an unquoted family name.

Only the listed visual options change. Existing font sizes, logos, menus, layouts, content, and plugin settings remain intact. Existing published Custom Fonts with the same family names are reused. Font files are served locally from this child theme, without Google Fonts requests.

Setup runs once per site. Later edits in BuddyBoss Theme Options or Custom Fonts survive page loads, theme reactivation, and updates. Existing BuddyBoss options are backed up in the `studyieltsvn_design_options_backup` WordPress option before the initial change. Deactivation does not restore that backup or remove fonts; keep the child theme files installed while its fonts are selected. To deliberately reapply the preset, delete the `studyieltsvn_design_installed` option with WP-CLI, then load the site with this child theme active.

The preset targets BuddyBoss settings; WooCommerce Blocks and other plugins can supply their own styles. It does not recreate the MUI component system or add a dark mode.

## Virtual checkout

For nonempty carts containing only products marked **Virtual**, the child theme removes company, street, apartment, city, state, and postcode fields through WooCommerce locale metadata. These fields are also excluded from required-field validation in the Store API. A small checkout-only CSS rule hides the country selector while preserving WooCommerce's existing country value for its locale and Store API validation.

Blank billing first/last names prefill from the current account profile. Explicit billing names and guest customers are left intact. Email and optional phone remain available. Products must be marked Virtual in WooCommerce; mixed/physical carts keep normal address requirements. This customization is independent of the one-time design setup.

To verify with WooCommerce active on a disposable site:

```sh
wp eval-file wp-content/themes/buddyboss-theme-child/tests/virtual-checkout.php
```

## Verify locally

Run `php -l functions.php`, `npm run package`, and `npm run validate`. On a local disposable WordPress site with BuddyBoss and this child theme active, run:

```sh
wp eval-file wp-content/themes/buddyboss-theme-child/tests/design-setup.php
```

The integration check exercises fresh font registration, palette application, backups, cache clearing, preservation of unrelated settings/font sizes, and repeated setup. It restores the site's options/font records afterward. Development tests are excluded from the release ZIP.

## Package locally

Run `npm ci`, then `npm run package`. The generated artifact is `dist/buddyboss-theme-child.zip`; `npm run validate` checks its top-level directory, required files, and WordPress metadata.

## Release

Create and push a version tag:

```sh
git tag v1.0.0
git push origin v1.0.0
```

GitHub Actions creates `buddyboss-theme-child.zip` and its SHA-256 checksum in the release. The stable public URL is:

`https://github.com/bi1101/buddyboss-theme-child-ielts-science/releases/download/studyieltsvn-v1.1.1/buddyboss-theme-child.zip`

Use it in wp-env after the parent theme is available:

```yaml
services:
  wordpress:
    themes:
      - https://github.com/bi1101/buddyboss-theme-child-ielts-science/releases/latest/download/buddyboss-theme-child.zip
```

GitHub Releases must be public for wp-env to download the URL. The `latest` URL does not select prereleases.
