# Changelog

## 1.10.5
- Replace the Dot Mask colour-to-photo opacity fade with independent circular masks: the solid colour and the next photograph each reveal through their own expanding circles.
- Add optional inverted big-to-small transition: outgoing-photo circles contract to expose a colour layer and then the next photograph.
- Rename the colour handoff control to Second circle reveal delay (%) (0–65%).
- Fix sticky companion text layout by pinning the content wrapper within a full-height WPBakery sibling column rather than sticking the short column-inner.
- Keep both directions scroll-reversible, preserve the final photograph, and avoid redundant final-frame image reassignments.
- Default motion remains small-to-big with no colour insert; existing editorial/gallery sliders unchanged.

# Changelog

## 1.10.4
- Convert Dot Mask solid-colour interleaves from full-duration empty-looking slides into brief colour accents during photo-to-photo reveals.
- Start revealing the incoming photo while the colour mask is still expanding; adjustable photo handoff point (0–60%).
- Restore the original per-photo scroll distance; colour no longer doubles the length of a scroll-driven sequence.
- Keep the outgoing photograph visible throughout, with no deliberate blank full-frame colour interval.
- Existing filmstrip sliders are unaffected.

## 1.10.3
- Optional sticky companion text column when a scroll-driven Dot Mask Slider shares a WPBakery row with text; configurable top offset in pixels, desktop-only.
- Separate container and image corner radius controls (pixels) for editorial, gallery, and Dot Mask sliders.
- Optional alternating full-frame solid colour slides between photographs, using existing DURF palette or the parent section background.
- Dot Mask SVG keeps image and colour layers under the same circular reveal; optional rounded image clipping.
- Context-sensitive slider settings hide irrelevant controls. All features disabled or set to zero by default.

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
