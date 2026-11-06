# Changelog

## 1.10.2
- Keep outgoing image continuously visible underneath the incoming dot-mask reveal, including the last frame of scroll mode.
- Add Editorial / Soft / Linear growth easing and adjustable scroll smoothing.
- Centre dot grids on equal X and Y spacing; one spacing control for all patterns.
- Prevent automatic transitions from jumping when cycling the last image back to the first.
- Preload slider imagery, retain reversible scroll position, and keep optional velocity influence bounded to the current reveal.
- Hide scroll-only controls for automatic playback. Existing filmstrip slider behaviour is unchanged.

## 1.10.1
- Fix Dot Mask Slider initialization by matching SVG clipping circle selectors to the rendered markup.
- Ensure Dot Mask styles load in the document head for shortcodes rendered after WordPress `wp_head`.
- No change to existing filmstrip sliders or saved settings.

## 1.10.0
- Dot Mask composition for DURF Sliders: simultaneous expanding circular reveal between photos.
- Selectable 1, 4, 6, 8 or 12-dot layouts.
- Autoplay with hold/reveal timings, or reversible scroll-position-controlled playback.
- Optional scroll-velocity influence, off by default.
- Conditional editor settings; existing editorial and gallery sliders are unchanged.
- SVG clipping respects responsive aspect ratios and `prefers-reduced-motion`.

## 1.9.0 — first repository baseline

- Responsive mobile geometry for Dots (optional numeric overrides)
- Matrix, Horizontal, Vertical and Repeater modes
- Scroll and pointer interactions
- Text, image, background, parallax, colour and slider motion

Earlier versions were delivered as installable ZIP files.
