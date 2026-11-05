# DURF Motion v1.8.3 — Dots editor UX refresh

Editor-only improvement based on the complete v1.8.2 release. Existing markup and shortcode rendering logic are unchanged.

**WPBakery DURF Dots**
- Obsolete **Paired rows** and its row-gap field removed from the editor. Existing shortcode attributes `rows="2"` continue to render for backward compatibility. Repeater replaces paired rows for new designs.
- Settings follow a task sequence: **Layout → Interaction → Pattern → Matrix / Repeater → Design → Advanced**. Mode-specific tabs already existed; dependencies are tightened.
- The first Interaction option is now **Animation source**, so the pointer controls are hidden for Scroll only or Static. Pointer-specific controls hide when Pointer behaviour is Off.
- X/Y values (grid size, spacing, patterns, offsets; overall offsets) use two-column WPBakery controls where supported.
- Uniform diameter only appears when the horizontal/vertical/repeater pattern is Uniform; copy minimum scale only appears when Copy progression actually changes size; vertical repeater height only when repeating columns.
- Some older, rarely changed controls move to Advanced, including legacy reverse and scroll focal offsets.
- This release does **not** alter frontend animation code or dot geometry, and preserves other plugin features.

Note: WPBakery's basic dependency system supports one field dependency at a time. Its nested dependencies are designed to cascade; test the exact visibility in the installed editor (which is not available here). If an older instance uses `rows="2"`, keep it until you choose a new Repeater layout; editing old instances can cause WPBakery to drop deprecated attributes.

# DURF Motion v1.8.2 — Repeater scroll morph fix

Fixes the Repeater + Uniform pattern not visually changing on scroll when using Grow/Shrink/Peak/Valley copy-size progression. The endpoint now uses the **reversed copy-size progression**. For example, with Shrink copies, large top rows become smaller and small bottom rows become larger. Scroll up reverses the transformation.

Keep **Animation Source = Scroll only** and **Settings → Dots → Enable dots animation** enabled. A Repeater using *Uniform copies + Uniform dot pattern* has identical size at both endpoints and therefore intentionally stays static; choose Grow or Shrink progression for a visible effect.

No shortcode arguments or existing saved settings change. Existing horizontal, vertical, Matrix, and pointer interactions are retained.

# DURF Motion v1.8.1 — Pointer interaction for every Dots layout

The **Interaction** tab now works for Horizontal, Vertical, Repeater and Matrix. Each supports:

- Scroll + Pointer, Pointer only, Scroll only, or Static
- Grow / Shrink, Graphic / WPBakery row / Viewport reach
- Influence radius, strength and Direct / Soft / Cinematic easing

All existing shortcodes stay unchanged by default (pointer interaction is off). Existing v1.8.0 animation and slider features are retained. For long horizontal and vertical strips, pointer influence is scaled to the long axis so it doesn't become imperceptibly narrow. Reduced-motion preferences and touch-device safeguards are retained.

Examples:

`[durf_dots direction="horizontal" pattern="uniform" count="10" pointer_mode="grow" animation_source="pointer" pointer_scope="row" pointer_reach="600"]`

`[durf_dots direction="vertical" pattern="uniform" count="10" pointer_mode="shrink" animation_source="both" pointer_scope="viewport" pointer_reach="1000"]`

`[durf_dots direction="repeater" copies="5" repeat_axis="horizontal" pointer_mode="grow" animation_source="pointer" pointer_scope="row"]`

---

# DURF Motion v1.8.0 — Repeater + Uniform patterns

**New:** Horizontal and Vertical Dots each support **Uniform**, making every circle the same diameter. Set **Uniform dot diameter (%)** in Pattern tab.

**New:** WPBakery DURF Dots → Layout → **Repeater**. This adds a dedicated Repeater tab with:

- Horizontal rows or vertical columns; 1–12 copies; 3–20 dots per copy
- Copy spacing, overlap strength, per-copy stagger/shift
- Same vs alternating patterns; copy scale progression (uniform, grow, shrink, peak, valley)
- Uniform, Peak, Valley, Ascending, Descending dot patterns
- Existing Dots scroll size morph, colour, alignment, and transparent SVG

Examples:

`[durf_dots direction="horizontal" pattern="uniform" count="7" uniform_size="85" color="#F16A37"]`

`[durf_dots direction="repeater" repeat_axis="horizontal" pattern="uniform" repeat_count="7" copies="5" copy_spacing="75" repeat_overlap="18" copy_progression="shrink" copy_min_scale="30" color="#F16A37"]`

`[durf_dots direction="repeater" repeat_axis="vertical" pattern="peak" repeat_count="9" copies="4" copy_shift="-25" height="850"]`

**Compatibility:** prior `[durf_dots]` shortcodes and their legacy six-dot output remain unchanged, as do the Matrix mode and the rest of DURF Motion.

---

# DURF Motion v1.7.5 — Uniform Matrix and long-range pointer detection

The complete v1.7.4 plugin with all existing motion/slider/colour features is retained.

## WPBakery → DURF Dots → Matrix

- Set both **Grid X pattern** and **Grid Y pattern** to **Uniform — even dots**.
- Set **Uniform dot diameter (%)** to choose the identical initial size of every dot. Default: 35% of the configured maximum diameter.
- The grid retains circle centre spacing, overlap strength, colour, positioning, and all previous controls.

## WPBakery → DURF Dots → Interaction

- Set **Animation Source** to **Pointer only** and **Pointer behaviour** to **Grow** (or Shrink).
- For interaction over nearby text columns use **Pointer detection area: Whole WPBakery row**.
- For cursor reaction from distant parts of the screen use **Visible screen**, with **Pointer Reach** at 500–1200 px. Dots gradually respond more strongly as the cursor approaches the matrix.
- **Influence radius**, **Strength**, and **Follow easing** still control the actual deformation and its motion.
- Touch and reduced-motion environments retain their existing non-pointer behaviour.

Note: the size field is a *percentage of maximum diameter*, not an absolute pixel dimension; the graphic still scales with WPBakery layout.

# DURF Motion v1.7.4 — Footer Ink transition fix

In v1.7.3, footer detection could read the WHITE outer Impreza footer wrapper and use it as the transition target, even when the visible footer is black/ink. This update makes the `dm-color-to-footer` transition use the **Ink** palette value from the Colours settings (default `#25231F`). It **does not modify the footer background itself**.

- Last row example: `dm-color-flow dm-color-pink dm-color-to-footer`.
- Ensure the footer is ink-black in the Impreza footer design settings.
- Optional exact colour override, added via a row HTML attribute if your setup allows it: `data-dm-footer-color="#25231F"`.
- For a hard cut, simply remove `dm-color-to-footer`.
- All existing other motion classes remain the same.

# DURF Motion v1.7.3

### Dots: animation source (per Matrix instance)

In WPBakery > DURF Dots > Interaction, select **Scroll + Pointer**, **Pointer only**, **Scroll only**, or **Static**. Default is Scroll + Pointer, preserving existing instances. Pointer only works even when the global Dots scroll animation is disabled.

### Optional footer colour connection

The Impreza footer normally sits outside the main WPBakery row hierarchy, so the ordinary colour-flow adjacency rule cannot blend across it. On your last colour-flow row, add `dm-color-to-footer` alongside `dm-color-flow dm-color-pink` (or the appropriate colour) to opt into a blend toward the footer's computed solid background colour. Leave it off for a hard cut. The plugin paints the outer footer container during the transition, not nested footer elements with their own backgrounds. If your footer contains opaque inner rows, adjust their background in Impreza.

This footer effect is opt-in and has not been visually tested on your SiteGround staging site. For the familiar hard cut, ensure the last row has no `dm-color-to-footer`.

---

# DURF Motion v1.7.2 — Pointer interaction across columns

The Matrix pointer listener now supports **Pointer detection area** under WPBakery → DURF Dots → Interaction: **Whole WPBakery row** (default), Graphic only, or Visible screen. With Whole row, hovering over an adjacent text column still affects the nearest dots. Pointer reach specifies extra distance beyond the selected area. Pointer influence/strength/easing still work independently. Touch/reduced-motion fallbacks are unchanged.

# DURF Motion v1.7.1 — Interaction tab + Pointer Reach

This is a complete update based on v1.7.0, retaining all other animation systems and saved shortcode attributes.

### DURF Dots → Interaction
- Pointer behaviour: Off / Grow / Shrink. Default Off.
- Pointer Reach: 0–1000 px outside the SVG bounds, default 220 px. The response fades smoothly as the pointer approaches.
- Influence Radius: 5–100% of the SVG's smaller dimension.
- Strength: 0–200%.
- Easing: Direct / Soft / Cinematic.
- Scroll response remains controlled under DURF Motion → Settings → Dots, and coexists with per-element pointer interaction.

Example: `[durf_dots direction="grid" pointer_mode="grow" pointer_reach="300" pointer_radius="35" pointer_strength="90" pointer_easing="soft"]`

Pointer detection uses viewport coordinates and document pointer movement, so it works even if an overlay is positioned in front of the artwork. No hover outline. Reduced motion and touch remain unaffected.

---

# DURF Motion v1.7.0 — Interactive Matrix Dots

New **DURF Dots → Matrix** controls: **Pointer interaction** (Off / Grow / Shrink), **Pointer influence radius** (5–100%), **Pointer strength** (0–200%), and **Pointer follow easing** (Direct / Soft / Cinematic).

The pointer interaction is per Matrix instance, defaulting to **Off**. It uses real pointer coordinates within the responsive inline SVG, applying a local gaussian influence to each circle radius. The original scroll-driven radius morph continues to run and is combined with the pointer effect, not replaced. When the pointer leaves, the matrix returns smoothly to the scroll-driven shape. Desktop fine pointers only; reduced-motion disables the interaction.

Example:

`[durf_dots direction="grid" grid_cols="9" grid_rows="11" grid_overlap="55" pointer_mode="grow" pointer_radius="35" pointer_strength="90" pointer_easing="soft"]`

All prior shortcodes, WPBakery layout tabs, other animation features and sliders are preserved from the full v1.6.2 plugin.

---

# DURF Motion v1.6.2 — Matrix overlap control

New **Matrix → Grid overlap strength (%)** setting for DURF Dots WPBakery element.

- 0% keeps all existing graphics unchanged (backward compatible).
- 40% enlarges each matrix dot radius by 40%, without changing dot centres.
- 70% creates more pronounced merging bands. You can use values from 0 to 150%.
- SVG viewBox expands to avoid cropping the enlarged circle radii.
- Scroll-driven pattern morph, focal offsets, colour, responsive widths, and all other plugin modules are preserved.

To approach the dense orange dot field: use grid 9 columns × 11 rows, Peak / Peak, Average, gap X and Y 85–95%, overlap 45–70%, colour #F16A37. For an even denser centre, reduce gap X/Y to 70–80%.

Shortcode example:
`[durf_dots direction="grid" grid_cols="9" grid_rows="11" grid_gap_x="85" grid_gap_y="85" grid_overlap="55" grid_pattern_x="peak" grid_pattern_y="peak" grid_blend="average" color="#F16A37"]`

---

# DURF Motion v1.6.1 — WPBakery Dots editor tabs

The DURF Dots element has native WPBakery tabs: **Layout**, **Pattern**, **Matrix**, **Design**, **Advanced**. Each layout-specific control is only shown for appropriate modes. All shortcodes, setting names, defaults and frontend animation code are unchanged.

- Layout: direction, composition, counts, vertical height.
- Pattern: horizontal/vertical pattern, offsets, scroll focal positions.
- Matrix: grid geometry, X/Y patterns, blend and scroll morph; shown in Grid mode.
- Design: colour, width, alignment, offsets and circle size.
- Advanced: extra CSS class.

This is an editor-only update, and existing WPBakery Dots elements retain their saved values.

---

# DURF Motion v1.6.0 — Dots Matrix / Grid

New third Dots layout: **Matrix / Grid**, available through the same **DURF Dots** WPBakery element.

Matrix-only controls: row/column counts; independent X/Y spacing; min/max radius; X/Y patterns (Peak, Valley, Ascending, Descending); blend (Average, Multiply, X only, Y only); independent X/Y focal offsets; scroll morph (Invert, Shift focal point, None); ending offsets. Existing Dots global scroll start/end, easing and inversion strength apply.

- Default: 9 columns × 11 rows, 94% spacing on both axes, 20% minimum diameter, 45 SVG-unit max radius, Peak patterns and Average blend.
- Use **grid_blend="average"** for a broad, dense field like the reference; use `multiply` for a concentrated central bulge.
- Values below 100 for the grid spacing allow circles to touch or overlap.
- SVG has a transparent background and fluid width; no forced aspect distortion.
- Existing horizontal and vertical layouts and original shortcodes are retained unchanged.

Example shortcode:

`[durf_dots direction="grid" grid_cols="9" grid_rows="11" grid_gap_x="94" grid_gap_y="94" grid_min="20" grid_max="45" grid_pattern_x="peak" grid_pattern_y="peak" grid_blend="average" color="#F16A37" width="100"]`

Animate the focal point across the matrix:

`[durf_dots direction="grid" grid_cols="9" grid_rows="11" grid_morph="shift" grid_offset_x="-25" grid_end_x="30" grid_offset_y="10" grid_end_y="-20" color="#F16A37"]`

---

# DURF Motion v1.5.0 — Vertical Dots and Pattern Offset

This is a complete update of v1.4.0 (not the incomplete v1.4.1 placeholder). Existing animation features and shortcodes remain available.

In WPBakery's **DURF Dots** element, select **Direction → Horizontal rows / Vertical columns**. With Vertical chosen, paired rows become two adjacent columns. Set desktop and mobile graphic heights in px (the width scales proportionally).

**Pattern Offset** shifts the centre of the size distribution (-100 to +100) without moving the circles. **Pattern Spread** changes how broadly the sizes vary. Optional **Scroll Start Offset** and **Scroll End Offset** make the focal point travel as the pattern inverts during scrolling.

Vertical single-column example:

`[durf_dots direction="vertical" pattern="peak" count="10" height="850" mobile_height="520" color="#F16A37" pattern_offset="30" pattern_spread="90"]`

Vertical paired columns:

`[durf_dots direction="vertical" rows="2" pattern="valley" count="10" height="900" row_gap="50" start_offset="-35" end_offset="45" pattern_spread="85"]`

**Backward compatibility:** an unchanged `[durf_dots]` stays horizontal with the original six-circle profile. All per-instance width/position/colour controls remain in place.

---

# DURF Motion v1.4.0 — Dots patterns, paired rows, and visible overflow

**WPBakery:** Add a DURF Dots element. Set Pattern (Ascending, Descending, Valley, Peak), Number of circles per row (3–20), Composition (single / paired), gap between rows, width (desktop/mobile), position, spacing, colour, and size. The paired layout uses complementary rows. Scroll reverses each row's size distribution.

**Example** to reproduce the paired reference:

`[durf_dots pattern="valley" rows="2" count="10" row_gap="80" width="100" spacing="95" color="#F16A37"]`

Set `dm-dots-section` on the **row containing the graphic**. If the shortcode is in another section, the animation will follow the nearest tagged row; otherwise it follows the graphic.

**Overflow:** the SVG includes room for the circles at both extreme sizes and uses visible overflow. WPBakery ancestor containers holding the Dots element are also set to visible overflow. A page-level clipping rule or adjacent section with higher stacking order may still need theme-specific CSS.

**Backward compatibility:** existing `[durf_dots]` and WPBakery elements without the new pattern setting retain their six-circle sequence and scroll animation. The new shape patterns require choosing a pattern in the element.

**Global settings:** DURF Motion → Dots still controls enabled, scroll start/end, strength and easing. Every instance controls its geometry independently.

**Custom inline SVG:** the `dm-dots` class still animates `<circle>` elements; optionally add `data-dm-to-r` to define each circle's target radius instead of reverse-order interpolation.

---

# DURF Motion 1.3.0 — Editable Dots for WPBakery

**New:** In WPBakery, insert **DURF Dots** (category **DURF Motion**) instead of manually entering a shortcode. Each Dots element has its own visual controls for desktop/mobile width, alignment, X/Y offsets, circle size, circle spacing, colour and direction. The SVG is transparent, and the original scroll-driven radius inversion remains.

For existing `[durf_dots]` elements no changes are required. To enlarge an existing element without opening the WPBakery component, you can use `[durf_dots width="180" align="center" offset_y="-30"]`.

**Important:** The dots SVG does not paint a rectangle. A solid rectangle comes from a WPBakery row or column background, not the shortcode. If a row/column has `overflow:hidden`, expanded artwork can still be clipped. For best responsive positioning, use an unclipped dedicated column and test mobile separately.

**Scope:** This is a generated six-circle composition with independent controls. It doesn't automatically import or animate external SVG files used as `<img>` elements. Global scroll timing, easing and inversion strength remain under DURF Motion → Settings → Dots. All previous motion effects and sliders are preserved.

# DURF Motion v1.0.2 — WPBakery smooth scrolling hotfix

Fixes the condition that blocked Lenis on normal published WPBakery pages. `wpb-js-composer` is not an editor-mode indicator. The plugin no longer exits on that class.

## Test on a Mac trackpad

1. Replace the old plugin ZIP with v1.0.2. In **Settings → DURF Motion → Scroll**, enable smooth scrolling and save.
2. Purge SiteGround and browser caches. Open the actual published homepage, not WPBakery's frontend editor.
3. Run in the browser developer console: `window.DURFMotion?.diagnostics()`.
   - `smoothScroll: true`: engine initialized.
   - `lenisLoaded: true`: Lenis JavaScript available.
   - `scrollReason`: explains why the engine did not initialize (if applicable).
4. Compare smooth scrolling ON/OFF. Try smoothing 0.07–0.10 and wheel response 0.85–1.0.

`syncTouch: false` does **not** disable Mac trackpads; Lenis handles their wheel events using `smoothWheel: true`. Touchscreen support remains native. A `prefers-reduced-motion` setting disables the effect intentionally. Lenis is still loaded from the external jsDelivr CDN as in v1.0.

All v1.0 animation, carousel, and colour features are preserved. The edit affects only Lenis initialization and diagnostics.

## Colour-flow hard breaks (new in v1.0.2)

Add `dm-color-break` **to the first row of the new sequence**, along with its normal `dm-color-flow` and colour class. This prevents a fade into that row but allows a fade from it to the next row.

- Orange: `dm-color-flow dm-color-orange`
- Green: `dm-color-flow dm-color-green`
- Cream: `dm-color-flow dm-color-cream dm-color-break`
- Pink: `dm-color-flow dm-color-pink`

No blank row is needed. The break blocks only Green → Cream, not Cream → Pink.


## v1.1.0 — Editorial slider playback

Manage the plugin from **DURF Motion** in the WordPress dashboard (top-level menu). Settings and DURF Sliders are grouped under it.

Each slider has independent controls for continuous autoplay (pixels/second), infinite looping, pause on hover, and drag/swipe enable/disable. Autoplay and scroll-response modes are additive: scrolling changes the filmstrip velocity temporarily; base movement returns as the scroll influence fades. Both directions are supported. With reduced-motion preference, autoplay/automatic scroll movement is disabled.


## Dots / scroll-responsive SVG (v1.2.0)

In the row Extra class name enter `dm-dots-section`. Inside the row insert a WPBakery Shortcode element with `[durf_dots]` (or `[durf_dots color="#DD8EB9"]`). The six SVG circles invert their sizes as the row scrolls into and out of view, and reverse when scrolling upward. Set strength, scroll start/end and easing from **DURF Motion → Dots**. This does not alter row size or background.

For an existing custom graphic, use inline SVG with individual `<circle>` elements and class `dm-dots`; externally embedded SVG files used via `<img>` cannot expose their circle nodes to page scripts. For an exact custom path/gradient asset, create a compatible inline SVG rather than tracing the circles from a screenshot.
