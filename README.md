# IELTS Science BuddyBoss child theme

This child theme is packaged as an installable WordPress theme ZIP for wp-env. It requires the BuddyBoss parent theme to be installed separately. The `studyieltsvn` branch is intentionally a bare child theme with no template, PHP, CSS, JavaScript, or WooCommerce overrides.

## Package locally

Run `npm ci`, then `npm run package`. The generated artifact is `dist/buddyboss-theme-child.zip`; `npm run validate` checks its top-level directory, required files, and WordPress metadata.

## Release

Create and push a version tag:

```sh
git tag v1.0.0
git push origin v1.0.0
```

GitHub Actions creates `buddyboss-theme-child.zip` and its SHA-256 checksum in the release. The stable public URL is:

`https://github.com/bi1101/buddyboss-theme-child-ielts-science/releases/download/studyieltsvn-v1.0.0/buddyboss-theme-child.zip`

Use it in wp-env after the parent theme is available:

```yaml
services:
  wordpress:
    themes:
      - https://github.com/bi1101/buddyboss-theme-child-ielts-science/releases/latest/download/buddyboss-theme-child.zip
```

GitHub Releases must be public for wp-env to download the URL. The `latest` URL does not select prereleases.
