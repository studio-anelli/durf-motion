# DURF Motion

WordPress motion and generative Dots plugin for DURF Campus, built for WPBakery / Impreza.

**Baseline:** v1.9.0 with optional mobile overrides and scroll / pointer interactions.

## Installation

Install the packaged plugin ZIP containing a single top-level `durf-motion/` directory from WordPress → Plugins → Add New → Upload Plugin.

## Structure

- `durf-motion.php`: plugin bootstrap, settings, Dots shortcode and WPBakery controls
- `assets/`: scripts and styles for motion, dots and sliders
- `includes/sliders.php`: slider component
- `HISTORY.md`: original development notes

## Releases

Create changes on a feature branch. After staging tests, merge to `main`, bump PHP header and `DURF_Motion::VERSION`, and tag `vX.Y.Z`. The release workflow performs syntax checks and publishes an installable `durf-motion.zip`.

## WordPress updates

Git Updater can detect newer GitHub versions based on the `GitHub Plugin URI` and `Primary Branch: main` headers. Configure a token in Git Updater only if the GitHub repository is private. Never commit tokens. Use staging testing and manually approve updates on the live site.

License: GPL-2.0-or-later.
