# DURF Motion

WordPress motion and generative Dots plugin for the DURF Campus website, with WPBakery / Impreza integration.

## Baseline

Version **1.9.0** introduced responsive Dots geometry (mobile overrides), alongside matrix, horizontal, vertical and repeater layouts; scroll and pointer interactions; typography/image effects; colour transitions; parallax; sliders and smooth scrolling.

## Installation
Install the release asset `durf-motion.zip` through WordPress → Plugins → Add New → Upload Plugin. The ZIP must contain one top-level `durf-motion/` folder.

## Source layout
- `durf-motion.php` — bootstrap, settings, shortcodes and WPBakery elements
- `assets/` — animation, dot editor and slider CSS/JS
- `includes/sliders.php` — slider implementation

## Release process
Changes go into branches, are tested on staging, and merged to `main`. Tag `vX.Y.Z` to trigger the GitHub Actions workflow that lints and packages the plugin for GitHub Releases.

WordPress one-click updating can later use Git Updater, reading the `GitHub Plugin URI` and `Primary Branch` headers from the plugin. The repository must have installable source and a release before enabling this.

**Do not automatically update the production WordPress installation until tested.**
