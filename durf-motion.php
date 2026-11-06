<?php
/**
 * Plugin Name: DURF Motion
 * Description: Lightweight, accessible scroll-triggered motion effects for WPBakery / Impreza and regular WordPress content.
 * Version: 1.11.0
 * Author: Anelli Studio
 * License: GPL-2.0-or-later
 * Text Domain: durf-motion
 * GitHub Plugin URI: https://github.com/studio-anelli/durf-motion
 * Primary Branch: main
 */
if (!defined('ABSPATH')) { exit; }

final class DURF_Motion {
    const OPTION = 'durf_motion_settings';
    const VERSION = '1.11.0';

    public static function defaults() {
        return array('duration'=>900, 'distance'=>36, 'stagger'=>120, 'parallax'=>60, 'image_parallax'=>35, 'background_parallax'=>45, 'background_overscan'=>8, 'section_parallax'=>20, 'dots_enabled'=>1, 'dots_strength'=>100, 'dots_start'=>85, 'dots_end'=>15, 'dots_easing'=>'soft', 'parallax_easing'=>'soft', 'image_easing'=>'soft', 'background_easing'=>'soft', 'section_easing'=>'soft', 'scroll_enabled'=>0, 'scroll_smoothing'=>10, 'scroll_wheel'=>85, 'scroll_focus'=>'off', 'image_trigger'=>45, 'text_trigger'=>95, 'once'=>1, 'enabled'=>1, 'color_enabled'=>1, 'color_position'=>55, 'color_softness'=>'balanced', 'color_start'=>80, 'color_end'=>20, 'color_easing'=> 'smooth', 'color_cream'=>'#F8F4EC', 'color_orange'=>'#F16A37', 'color_green'=>'#507568', 'color_pink'=>'#DD8EB9', 'color_ink'=>'#25231F', 'header_enabled'=>0, 'header_mode'=>'transparent', 'header_contrast'=>'auto', 'header_selector'=>'.l-header', 'header_mono_logo'=>0);
    }
    public static function settings() {
        return wp_parse_args(get_option(self::OPTION, array()), self::defaults());
    }
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'));
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_init', array(__CLASS__, 'register'));
        DURF_Motion_Sliders::init();
        add_shortcode('durf_dots', array(__CLASS__, 'dots_shortcode'));
        add_action('vc_before_init', array(__CLASS__, 'register_dots_wpbakery'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'dots_editor_assets'));
        add_action('vc_frontend_editor_enqueue_js_css', array(__CLASS__, 'dots_editor_assets'));
    }
    public static function dots_editor_assets() {
        wp_enqueue_script('durf-dots-editor', plugin_dir_url(__FILE__).'assets/dots-admin.js', array(), self::VERSION, true);
    }
    public static function assets() {
        if (is_admin()) { return; }
        $settings = self::settings();
        if (empty($settings['enabled'])) { return; }
        wp_enqueue_style('durf-motion', plugin_dir_url(__FILE__) . 'assets/motion.css', array(), self::VERSION);
        if (!empty($settings['scroll_enabled'])) {
            wp_enqueue_script('durf-lenis', 'https://cdn.jsdelivr.net/npm/lenis@1.3.27/dist/lenis.min.js', array(), '1.3.27', true);
        }
        wp_enqueue_script('durf-motion', plugin_dir_url(__FILE__) . 'assets/motion.js', !empty($settings['scroll_enabled']) ? array('durf-lenis') : array(), self::VERSION, true);
        $config = array(
            'duration' => (int) $settings['duration'],
            'distance' => (int) $settings['distance'],
            'stagger'  => (int) $settings['stagger'],
            'parallax' => (int) $settings['parallax'],
            'imageParallax' => (int) $settings['image_parallax'],
            'backgroundParallax' => (int) $settings['background_parallax'],
            'backgroundOverscan' => (int) $settings['background_overscan'],
            'sectionParallax' => (int) $settings['section_parallax'],
            'dotsEnabled' => !empty($settings['dots_enabled']),
            'dotsStrength' => (int) $settings['dots_strength'],
            'dotsStart' => (int) $settings['dots_start'],
            'dotsEnd' => (int) $settings['dots_end'],
            'dotsEasing' => $settings['dots_easing'],
            'easing' => array('image'=>$settings['image_easing'],'background'=>$settings['background_easing'],'section'=>$settings['section_easing'],'legacy'=>$settings['parallax_easing']),
            'scrollEnabled' => !empty($settings['scroll_enabled']),
            'scrollSmoothing' => (int) $settings['scroll_smoothing'],
            'scrollWheel' => (int) $settings['scroll_wheel'],
            'scrollFocus' => $settings['scroll_focus'],
            'imageTrigger' => (int) $settings['image_trigger'],
            'textTrigger' => (int) $settings['text_trigger'],
            'once'     => !empty($settings['once']),
            'colorEnabled' => !empty($settings['color_enabled']),
            'colorPosition' => (int) $settings['color_position'],
            'colorSoftness' => $settings['color_softness'],
            'colorStart' => (int) $settings['color_start'],
            'colorEnd' => (int) $settings['color_end'],
            'colorEasing' => $settings['color_easing'],
            'colors' => array('cream'=>$settings['color_cream'],'orange'=>$settings['color_orange'],'green'=>$settings['color_green'],'pink'=>$settings['color_pink'],'ink'=>$settings['color_ink']),
            'headerEnabled' => !empty($settings['header_enabled']),
            'headerMode' => $settings['header_mode'],
            'headerContrast' => $settings['header_contrast'],
            'headerSelector' => $settings['header_selector'],
            'headerMonoLogo' => !empty($settings['header_mono_logo']),
        );
        wp_add_inline_script('durf-motion', 'window.DURFMotionConfig = ' . wp_json_encode($config) . ';', 'before');
        if (!empty($settings['header_enabled'])) {
            wp_enqueue_style('durf-adaptive-header', plugin_dir_url(__FILE__).'assets/adaptive-header.css', array('durf-motion'), self::VERSION);
            wp_enqueue_script('durf-adaptive-header', plugin_dir_url(__FILE__).'assets/adaptive-header.js', array('durf-motion'), self::VERSION, true);
        }
    }
    /** A native WPBakery element, with controls on EACH instance of the SVG. */
    public static function register_dots_wpbakery() {
        if (!function_exists('vc_map')) { return; }
        vc_map(array(
            'name' => 'DURF Dots',
            'base' => 'durf_dots',
            'icon' => 'icon-wpb-ui-separator-label',
            'category' => 'DURF Motion',
            'description' => 'Scroll-responsive circles with size and positioning controls',
            'params' => array(
                array('type'=>'dropdown','heading'=>'1 · Layout type','description'=>'First choose the graphic structure. Only settings relevant to this mode will be shown.','param_name'=>'direction','value'=>array('Horizontal rows'=>'horizontal','Vertical columns'=>'vertical','Matrix / Grid'=>'grid','Repeater'=>'repeater'),'group'=>'Layout'),
                array('type'=>'textfield','heading'=>'Dots per row / column','param_name'=>'count','value'=>'6','description'=>'3–20 circles. Existing shortcode instances keep their original six-circle appearance.','group'=>'Layout','dependency'=>array('element'=>'direction','value'=>array('horizontal','vertical'))),
                array('type'=>'textfield','heading'=>'Height · desktop (px)','param_name'=>'height','value'=>'800','description'=>'Vertical only: height of the composition. Width stays proportional.','group'=>'Layout','dependency'=>array('element'=>'direction','value'=>array('vertical'))),
                array('type'=>'textfield','heading'=>'Height · mobile (px)','param_name'=>'mobile_height','value'=>'520','group'=>'Layout','dependency'=>array('element'=>'direction','value'=>array('vertical'))),
                array('type'=>'dropdown','heading'=>'1 · Animation source','description'=>'Choose how this graphic reacts. Pointer settings only appear when the chosen source uses the pointer.','param_name'=>'animation_source','value'=>array('Scroll + Pointer'=>'both','Pointer only — no scroll morph'=>'pointer','Scroll only — ignore pointer'=>'scroll','Static — no animation'=>'static'),'group'=>'Interaction'),
                array('type'=>'dropdown','heading'=>'2 · Pointer behaviour','param_name'=>'pointer_mode','value'=>array('Off'=>'off','Grow near pointer'=>'grow','Shrink near pointer'=>'shrink'),'description'=>'Available in Horizontal, Vertical, Matrix and Repeater; disabled on touch and reduced-motion devices.','group'=>'Interaction','dependency'=>array('element'=>'animation_source','value'=>array('both','pointer'))),
                array('type'=>'dropdown','heading'=>'Detection area','param_name'=>'pointer_scope','value'=>array('Whole WPBakery row — recommended'=>'row','Graphic only'=>'graphic','Visible screen'=>'viewport'),'description'=>'Whole row responds even while your pointer is over a different WPBakery column. Graphic only uses the SVG bounds; Visible screen is the broadest interaction.','group'=>'Interaction','dependency'=>array('element'=>'pointer_mode','value'=>array('grow','shrink'))),
                array('type'=>'textfield','heading'=>'Reach (px)','param_name'=>'pointer_reach','value'=>'400','description'=>'How far beyond the chosen detection area the pointer is sensed (0–2500 px). For cross-column or distant reactions, choose Visible screen and increase Pointer Reach.','group'=>'Interaction','dependency'=>array('element'=>'pointer_mode','value'=>array('grow','shrink'))),
                array('type'=>'textfield','heading'=>'Influence radius (%)','param_name'=>'pointer_radius','value'=>'32','description'=>'How broadly dots react around the pointer (percentage of the graphic extent).','group'=>'Interaction','dependency'=>array('element'=>'pointer_mode','value'=>array('grow','shrink'))),
                array('type'=>'textfield','heading'=>'Strength (%)','param_name'=>'pointer_strength','value'=>'75','description'=>'0–200; additional growth or shrink on top of the scroll-driven circle radius.','group'=>'Interaction','dependency'=>array('element'=>'pointer_mode','value'=>array('grow','shrink'))),
                array('type'=>'dropdown','heading'=>'Follow easing','param_name'=>'pointer_easing','value'=>array('Direct'=>'direct','Soft'=>'soft','Cinematic'=>'cinematic'),'description'=>'How gently the field follows and returns after the cursor moves away.','group'=>'Interaction','dependency'=>array('element'=>'pointer_mode','value'=>array('grow','shrink'))),
                array('type'=>'dropdown','heading'=>'Scroll transformation','param_name'=>'grid_morph','value'=>array('Invert both axes'=>'invert','Shift focal point'=>'shift','No size change'=>'none'),'description'=>'The matrix responds to the existing Dots scroll settings. Scroll upward to reverse.','group'=>'Interaction','dependency'=>array('element'=>'direction','value'=>array('grid'))),
                array('type'=>'dropdown','heading'=>'Dot pattern','param_name'=>'pattern','value'=>array('Ascending (small → large)'=>'ascending','Descending (large → small)'=>'descending','Valley (large → small → large)'=>'valley','Peak (small → large → small)'=>'peak','Uniform — same size'=>'uniform'),'description'=>'Choose the dot-size distribution across each row.','group'=>'Pattern','dependency'=>array('element'=>'direction','value'=>array('horizontal','vertical','repeater'))),
                array('type'=>'textfield','heading'=>'Uniform dot diameter (%)','param_name'=>'uniform_size','value'=>'80','description'=>'Only when Pattern is Uniform. 100 = maximum dot size; 80 = 80% of maximum.','group'=>'Pattern','dependency'=>array('element'=>'pattern','value'=>array('uniform'))),
                array('type'=>'textfield','heading'=>'Focal point offset (%)','param_name'=>'pattern_offset','value'=>'0','description'=>'Shift the focal point from -100 to +100 without shifting dot positions.','group'=>'Pattern','dependency'=>array('element'=>'pattern','value'=>array('ascending','descending','peak','valley'))),
                array('type'=>'textfield','heading'=>'Focal point spread (%)','param_name'=>'pattern_spread','value'=>'100','description'=>'25 narrow, 150 broad.','group'=>'Pattern','dependency'=>array('element'=>'pattern','value'=>array('peak','valley'))),
                array('type'=>'textfield','heading'=>'Columns','param_name'=>'grid_cols','value'=>'9','description'=>'Grid mode only: 2–30 columns.','group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Rows','param_name'=>'grid_rows','value'=>'11','description'=>'Grid mode only: 2–30 rows.','group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'dropdown','heading'=>'Pattern X','param_name'=>'grid_pattern_x','value'=>array('Uniform — even dots'=>'uniform','Peak'=>'peak','Valley'=>'valley','Ascending'=>'ascending','Descending'=>'descending'),'group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'dropdown','heading'=>'Pattern Y','param_name'=>'grid_pattern_y','value'=>array('Uniform — even dots'=>'uniform','Peak'=>'peak','Valley'=>'valley','Ascending'=>'ascending','Descending'=>'descending'),'group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'dropdown','heading'=>'Combine X and Y','param_name'=>'grid_blend','value'=>array('Average — balanced'=>'average','Multiply — focused'=>'multiply','X only'=>'x','Y only'=>'y'),'group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid'))),
                array('type'=>'textfield','heading'=>'Spacing X (%)','param_name'=>'grid_gap_x','value'=>'94','description'=>'Horizontal distance between dot centres. 100 = one full maximum diameter; below 100 allows overlap.','group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Spacing Y (%)','param_name'=>'grid_gap_y','value'=>'94','description'=>'Vertical distance between dot centres. 100 = one full maximum diameter; below 100 allows overlap.','group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Minimum diameter (%)','param_name'=>'grid_min','value'=>'20','description'=>'Smallest dot as percentage of the largest dot.','group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid'))),
                array('type'=>'textfield','heading'=>'Maximum radius','param_name'=>'grid_max','value'=>'45','description'=>'Maximum dot radius. SVG scales proportionally to fit its WPBakery width.','group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid'))),
                array('type'=>'textfield','heading'=>'Uniform diameter (%)','param_name'=>'grid_uniform_size','value'=>'35','description'=>'For Uniform patterns on both axes: every dot has exactly this diameter as a percentage of the maximum circle diameter. Pointer Grow/Shrink animates around that constant base.','group'=>'Matrix','dependency'=>array('element'=>'grid_pattern_x','value'=>array('uniform'))),
                array('type'=>'textfield','heading'=>'Overlap strength (%)','param_name'=>'grid_overlap','value'=>'0','description'=>'0 preserves current sizes; 40 enlarges circle radii by 40% without moving centres, causing neighbouring circles to merge. Try 40–70 for dense fields.','group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid'))),
                array('type'=>'textfield','heading'=>'Focal offset X (%)','param_name'=>'grid_offset_x','value'=>'0','description'=>'Shift size-distribution focus without moving the centres.','group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Focal offset Y (%)','param_name'=>'grid_offset_y','value'=>'0','group'=>'Matrix','dependency'=>array('element'=>'direction','value'=>array('grid')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'dropdown','heading'=>'Repeat orientation','param_name'=>'repeat_axis','value'=>array('Repeat horizontal rows'=>'horizontal','Repeat vertical columns'=>'vertical'),'group'=>'Repeater','dependency'=>array('element'=>'direction','value'=>array('repeater'))),
                array('type'=>'textfield','heading'=>'Dots per copy','param_name'=>'repeat_count','value'=>'6','description'=>'Existing count control also applies in Repeater mode (3–20).','group'=>'Repeater','dependency'=>array('element'=>'direction','value'=>array('repeater'))),
                array('type'=>'textfield','heading'=>'Number of copies','param_name'=>'copies','value'=>'4','description'=>'Number of repeated rows or columns (1–12).','group'=>'Repeater','dependency'=>array('element'=>'direction','value'=>array('repeater'))),
                array('type'=>'dropdown','heading'=>'Copy size progression','param_name'=>'copy_progression','value'=>array('Uniform copies'=>'uniform','Grow through copies'=>'grow','Shrink through copies'=>'shrink','Peak in centre'=>'peak','Valley in centre'=>'valley'),'group'=>'Repeater','dependency'=>array('element'=>'direction','value'=>array('repeater'))),
                array('type'=>'textfield','heading'=>'Minimum copy size (%)','param_name'=>'copy_min_scale','value'=>'35','description'=>'Smallest copy scale, relative to the largest copy. Used by copy size progression.','group'=>'Repeater','dependency'=>array('element'=>'copy_progression','value'=>array('grow','shrink','peak','valley'))),
                array('type'=>'dropdown','heading'=>'Pattern alternation','param_name'=>'copy_pattern','value'=>array('Same pattern'=>'same','Alternate peak / valley, ascending / descending'=>'alternate'),'group'=>'Repeater','dependency'=>array('element'=>'direction','value'=>array('repeater'))),
                array('type'=>'textfield','heading'=>'Copy spacing (%)','param_name'=>'copy_spacing','value'=>'85','description'=>'Distance between the rows or columns. Lower values overlap more.','group'=>'Repeater','dependency'=>array('element'=>'direction','value'=>array('repeater'))),
                array('type'=>'textfield','heading'=>'Dot overlap (%)','param_name'=>'repeat_overlap','value'=>'0','description'=>'Enlarge dots without moving their centres, allowing adjacent circles to merge (0–150).','group'=>'Repeater','dependency'=>array('element'=>'direction','value'=>array('repeater'))),
                array('type'=>'textfield','heading'=>'Stagger / offset per copy (%)','param_name'=>'copy_shift','value'=>'0','description'=>'Shift successive rows sideways / columns vertically. Signed percentage of the circle pitch (-100 to +100).','group'=>'Repeater','dependency'=>array('element'=>'direction','value'=>array('repeater'))),
                array('type'=>'textfield','heading'=>'Vertical height (px)','param_name'=>'repeat_height','value'=>'800','description'=>'Used if repeating vertical columns.','group'=>'Repeater','dependency'=>array('element'=>'repeat_axis','value'=>array('vertical'))),
                array('type'=>'colorpicker','heading'=>'Circle colour','param_name'=>'color','value'=>'#DD8EB9','description'=>'Background stays transparent.','group'=>'Design'),
                array('type'=>'textfield','heading'=>'Desktop width (%)','param_name'=>'width','value'=>'100','description'=>'100 fills its column. 180 extends to 180% of the column width.','group'=>'Design'),
                array('type'=>'textfield','heading'=>'Mobile width (%)','param_name'=>'mobile_width','value'=>'100','description'=>'Used below 768px.','group'=>'Design'),
                array('type'=>'dropdown','heading'=>'Horizontal alignment','param_name'=>'align','value'=>array('Left'=>'left','Centre'=>'center','Right'=>'right'),'group'=>'Design'),
                array('type'=>'textfield','heading'=>'Offset X (px)','param_name'=>'offset_x','value'=>'0','description'=>'Negative values move left.','group'=>'Design','edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Offset Y (px)','param_name'=>'offset_y','value'=>'0','description'=>'Negative values move up.','group'=>'Design','edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Circle size (%)','param_name'=>'size','value'=>'100','description'=>'Scales the circles independently of the overall graphic width.','group'=>'Design'),
                array('type'=>'textfield','heading'=>'Circle spacing (%)','param_name'=>'spacing','value'=>'100','description'=>'100 = original; below 100 tighter, above 100 wider.','group'=>'Design','dependency'=>array('element'=>'direction','value'=>array('horizontal','vertical'))),
                array('type'=>'textfield','heading'=>'Scroll focal start (%)','param_name'=>'start_offset','value'=>'','description'=>'Optional; blank uses Pattern offset.','group'=>'Advanced','dependency'=>array('element'=>'direction','value'=>array('horizontal','vertical','repeater'))),
                array('type'=>'textfield','heading'=>'Scroll focal end (%)','param_name'=>'end_offset','value'=>'','description'=>'Optional; blank uses Pattern offset.','group'=>'Advanced','dependency'=>array('element'=>'direction','value'=>array('horizontal','vertical','repeater'))),
                array('type'=>'textfield','heading'=>'Destination X (%)','param_name'=>'grid_end_x','value'=>'30','description'=>'Used only by Shift focal point mode.','group'=>'Advanced','dependency'=>array('element'=>'grid_morph','value'=>array('shift')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Destination Y (%)','param_name'=>'grid_end_y','value'=>'-25','group'=>'Advanced','dependency'=>array('element'=>'grid_morph','value'=>array('shift')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'dropdown','heading'=>'Legacy reverse (older shortcodes)','param_name'=>'reverse','value'=>array('Normal'=>'no','Reverse'=>'yes'),'description'=>'For old six-dot shortcodes. Pattern selection overrides this.','group'=>'Advanced','dependency'=>array('element'=>'direction','value'=>array('horizontal','vertical','repeater'))),
                array('type'=>'dropdown','heading'=>'Mobile overrides','param_name'=>'responsive_enabled','value'=>array('Use desktop settings (recommended)'=>'off','Custom mobile geometry'=>'on'),'description'=>'Choose Custom to reveal only numeric mobile controls. Shared colours, patterns and interaction settings stay unchanged. Mobile begins at 767px.','group'=>'Mobile'),
                array('type'=>'textfield','heading'=>'Dots per row / column','param_name'=>'mobile_count','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Circle spacing (%)','param_name'=>'mobile_spacing','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Circle size (%)','param_name'=>'mobile_size','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Offset X (px)','param_name'=>'mobile_offset_x','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Offset Y (px)','param_name'=>'mobile_offset_y','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Columns','param_name'=>'mobile_grid_cols','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Rows','param_name'=>'mobile_grid_rows','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Spacing X (%)','param_name'=>'mobile_grid_gap_x','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Spacing Y (%)','param_name'=>'mobile_grid_gap_y','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Minimum diameter (%)','param_name'=>'mobile_grid_min','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Maximum radius','param_name'=>'mobile_grid_max','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Overlap strength (%)','param_name'=>'mobile_grid_overlap','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Dots per copy','param_name'=>'mobile_repeat_count','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Number of copies','param_name'=>'mobile_copies','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Copy spacing (%)','param_name'=>'mobile_copy_spacing','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Minimum copy size (%)','param_name'=>'mobile_copy_min_scale','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Dot overlap (%)','param_name'=>'mobile_repeat_overlap','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Stagger / offset (%)','param_name'=>'mobile_copy_shift','value'=>'','description'=>'Optional. Blank inherits desktop.','group'=>'Mobile','dependency'=>array('element'=>'responsive_enabled','value'=>array('on')),'edit_field_class'=>'vc_col-sm-6'),
                array('type'=>'textfield','heading'=>'Extra CSS class','param_name'=>'class','value'=>'','group'=>'Advanced'),
            )
        ));
    }
    /** Translucent, viewBox-safe circle systems. Existing unmodified shortcodes remain six-dot ascending. */
    public static function dots_shortcode($atts=array()) {
        $settings=self::settings();
        $atts=shortcode_atts(array(
            'color'=>$settings['color_pink'], 'reverse'=>'no', 'class'=>'',
            'width'=>'100', 'mobile_width'=>'100', 'align'=>'left',
            'offset_x'=>'0', 'offset_y'=>'0', 'size'=>'100', 'spacing'=>'100',
            'pattern'=>'', 'count'=>'6', 'rows'=>'1', 'row_gap'=>'24', 'uniform_size'=>'80',
            'repeat_axis'=>'horizontal', 'repeat_count'=>'7', 'repeat_height'=>'800', 'copies'=>'4', 'copy_spacing'=>'85', 'repeat_overlap'=>'0', 'copy_pattern'=>'same', 'copy_progression'=>'uniform', 'copy_min_scale'=>'35', 'copy_shift'=>'0',
            'direction'=>'horizontal', 'height'=>'800', 'mobile_height'=>'520', 'pattern_offset'=>'0', 'pattern_spread'=>'100', 'start_offset'=>'', 'end_offset'=>'',
            'grid_cols'=>'9', 'grid_rows'=>'11', 'grid_gap_x'=>'94', 'grid_gap_y'=>'94',
            'grid_min'=>'20', 'grid_max'=>'45', 'grid_uniform_size'=>'35', 'grid_overlap'=>'0', 'grid_pattern_x'=>'peak', 'grid_pattern_y'=>'peak',
            'grid_blend'=>'average', 'grid_offset_x'=>'0', 'grid_offset_y'=>'0',
            'grid_morph'=>'invert', 'grid_end_x'=>'30', 'grid_end_y'=>'-25',
            'pointer_mode'=>'off', 'pointer_scope'=>'row', 'pointer_reach'=>'400', 'pointer_radius'=>'32', 'pointer_strength'=>'75', 'pointer_easing'=>'soft', 'animation_source'=>'both',
            'responsive_enabled'=>'off', 'responsive_render'=>'', 'mobile_count'=>'', 'mobile_spacing'=>'', 'mobile_size'=>'', 'mobile_offset_x'=>'', 'mobile_offset_y'=>'', 'mobile_grid_cols'=>'', 'mobile_grid_rows'=>'', 'mobile_grid_gap_x'=>'', 'mobile_grid_gap_y'=>'', 'mobile_grid_min'=>'', 'mobile_grid_max'=>'', 'mobile_grid_overlap'=>'', 'mobile_repeat_count'=>'', 'mobile_copies'=>'', 'mobile_copy_spacing'=>'', 'mobile_copy_min_scale'=>'', 'mobile_repeat_overlap'=>'', 'mobile_copy_shift'=>''
        ),$atts,'durf_dots');
        // Per-device SVGs are necessary for different circle counts and row geometry.
        // Blank mobile overrides are inherited; a page with no overrides retains
        // the exact original single-SVG markup and behaviour.
        if ($atts['responsive_render']==='' && $atts['responsive_enabled']==='on') {
            $numeric_keys = array('count','spacing','size','offset_x','offset_y','grid_cols','grid_rows','grid_gap_x','grid_gap_y','grid_min','grid_max','grid_overlap','repeat_count','copies','copy_spacing','copy_min_scale','repeat_overlap','copy_shift');
            $applicable = array(
                'horizontal'=>array('count','spacing','size','offset_x','offset_y'),
                'vertical'=>array('count','spacing','size','offset_x','offset_y'),
                'grid'=>array('grid_cols','grid_rows','grid_gap_x','grid_gap_y','grid_min','grid_max','grid_overlap','size','offset_x','offset_y'),
                'repeater'=>array('repeat_count','copies','copy_spacing','copy_min_scale','repeat_overlap','copy_shift','spacing','size','offset_x','offset_y')
            );
            $mode=in_array((string)$atts['direction'],array('horizontal','vertical','grid','repeater'),true)?$atts['direction']:'horizontal';
            $mobile=$atts;
            $has_override=false;
            foreach ($numeric_keys as $key) {
                if (in_array($key,$applicable[$mode],true) && trim((string)$atts['mobile_'.$key])!=='' && is_numeric($atts['mobile_'.$key])) {
                    $mobile[$key]=$atts['mobile_'.$key];
                    $has_override=true;
                }
            }
            if ($has_override) {
                $desktop=$atts;
                $desktop['responsive_render']='desktop';
                $mobile['responsive_render']='mobile';
                // Avoid duplicate custom classes on separate responsive wrappers.
                return self::dots_shortcode($desktop).self::dots_shortcode($mobile);
            }
        }
        $color=sanitize_hex_color($atts['color']) ?: $settings['color_pink'];
        $reverse=in_array(strtolower((string)$atts['reverse']),array('1','true','yes'),true);
        $width=max(20,min(400,(float)$atts['width']));
        $mobile_width=max(20,min(400,(float)$atts['mobile_width']));
        $offset_x=max(-2000,min(2000,(float)$atts['offset_x']));
        $offset_y=max(-2000,min(2000,(float)$atts['offset_y']));
        $size=max(15,min(200,(float)$atts['size']));
        $spacing=max(35,min(150,(float)$atts['spacing']));
        $count=max(3,min(20,(int)$atts['count']));
        $rows=((string)$atts['rows']==='2')?2:1;
        $row_gap=max(0,min(500,(float)$atts['row_gap']));
        $direction=in_array((string)$atts['direction'],array('horizontal','vertical','grid','repeater'),true)?(string)$atts['direction']:'horizontal';
        $uniform_size=max(1,min(200,(float)$atts['uniform_size']))/100;
        $repeat_axis=($atts['repeat_axis']==='vertical')?'vertical':'horizontal';
        $copies=max(1,min(12,(int)$atts['copies']));
        $repeat_count=max(3,min(20,(int)$atts['repeat_count']));
        $repeat_height=max(120,min(2500,(float)$atts['repeat_height']));
        $copy_spacing=max(20,min(200,(float)$atts['copy_spacing']))/100;
        $repeat_overlap=max(0,min(150,(float)$atts['repeat_overlap']))/100;
        $copy_pattern=in_array((string)$atts['copy_pattern'],array('same','alternate'),true)?$atts['copy_pattern']:'same';
        $copy_progression=in_array((string)$atts['copy_progression'],array('uniform','grow','shrink','peak','valley'),true)?$atts['copy_progression']:'uniform';
        $copy_min_scale=max(5,min(100,(float)$atts['copy_min_scale']))/100;
        $copy_shift=max(-100,min(100,(float)$atts['copy_shift']))/100;
        $height=max(120,min(2500,(float)$atts['height']));
        $mobile_height=max(120,min(2000,(float)$atts['mobile_height']));
        $offset=max(-100,min(100,(float)$atts['pattern_offset']));
        $spread=max(25,min(150,(float)$atts['pattern_spread']));
        $start_offset=($atts['start_offset']===''?$offset:max(-100,min(100,(float)$atts['start_offset'])));
        $end_offset=($atts['end_offset']===''?$offset:max(-100,min(100,(float)$atts['end_offset'])));
        $align=in_array($atts['align'],array('left','center','right'),true)?$atts['align']:'left';
        $alignments=array('left'=>'0px','center'=>'50%','right'=>'100%');
        $translate=array('left'=>'0%','center'=>'-50%','right'=>'-100%');
        $classes=array_filter(array_map('sanitize_html_class',preg_split('/\s+/',trim((string)$atts['class']))));
        $classes_str=implode(' ',$classes);
                $grid_cols=max(2,min(30,(int)$atts['grid_cols']));
        $grid_rows=max(2,min(30,(int)$atts['grid_rows']));
        $grid_gap_x=max(35,min(220,(float)$atts['grid_gap_x']));
        $grid_gap_y=max(35,min(220,(float)$atts['grid_gap_y']));
        $grid_overlap=max(0,min(150,(float)$atts['grid_overlap']))/100;
        $grid_min=max(1,min(100,(float)$atts['grid_min']))/100;
        $grid_uniform_size=max(1,min(100,(float)$atts['grid_uniform_size']))/100;
        $grid_max=max(4,min(150,(float)$atts['grid_max']))*$size/100;
        $grid_pattern_x=in_array((string)$atts['grid_pattern_x'],array('peak','valley','ascending','descending','uniform'),true)?(string)$atts['grid_pattern_x']:'peak';
        $grid_pattern_y=in_array((string)$atts['grid_pattern_y'],array('peak','valley','ascending','descending','uniform'),true)?(string)$atts['grid_pattern_y']:'peak';
        $grid_blend=in_array((string)$atts['grid_blend'],array('average','multiply','x','y'),true)?(string)$atts['grid_blend']:'average';
        $grid_morph=in_array((string)$atts['grid_morph'],array('invert','shift','none'),true)?(string)$atts['grid_morph']:'invert';
        $grid_offset_x=max(-100,min(100,(float)$atts['grid_offset_x']));
        $grid_offset_y=max(-100,min(100,(float)$atts['grid_offset_y']));
        $grid_end_x=max(-100,min(100,(float)$atts['grid_end_x']));
        $grid_end_y=max(-100,min(100,(float)$atts['grid_end_y']));
        $animation_source=in_array((string)$atts['animation_source'],array('both','pointer','scroll','static'),true)?(string)$atts['animation_source']:'both';
        $pointer_mode=in_array((string)$atts['pointer_mode'],array('off','grow','shrink'),true)?(string)$atts['pointer_mode']:'off';
        $pointer_scope=in_array((string)$atts['pointer_scope'],array('row','graphic','viewport'),true)?(string)$atts['pointer_scope']:'row';
        $pointer_reach=max(0,min(2500,(float)$atts['pointer_reach']));
        $pointer_radius=max(5,min(100,(float)$atts['pointer_radius']));
        $pointer_strength=max(0,min(200,(float)$atts['pointer_strength']));
        $pointer_easing=in_array((string)$atts['pointer_easing'],array('direct','soft','cinematic'),true)?(string)$atts['pointer_easing']:'soft';
$style=sprintf('--dm-dots-width:%s%%;--dm-dots-mobile-width:%s%%;--dm-dots-height:%spx;--dm-dots-mobile-height:%spx;--dm-dots-x:%spx;--dm-dots-y:%spx;--dm-dots-anchor:%s;--dm-dots-translate:%s;',
          $width,$mobile_width,($direction==='repeater'?$repeat_height:$height),$mobile_height,$offset_x,$offset_y,$alignments[$align],$translate[$align]);
        $patterns=array('ascending','descending','valley','peak','uniform');
        // An old [durf_dots] with no pattern retains its original geometry and radius sequence.
        $legacy=!in_array((string)$atts['pattern'],$patterns,true) && $direction!=='repeater';
        $pattern=$legacy?($reverse?'descending':'ascending'):(in_array((string)$atts['pattern'],$patterns,true)?(string)$atts['pattern']:'uniform');
        $scale=$size/100;
        if($direction==='grid') {
            // Matrix geometry is a single SVG: fixed centres, animated radii, transparent background.
            // Avoid using separate WPBakery columns and avoid transforms that would stretch circles.
            $radius=$grid_max;
            $step_x=2*$radius*$grid_gap_x/100;
            $step_y=2*$radius*$grid_gap_y/100;
            $pad=$radius*(1+$grid_overlap)+2;
            $viewbox=sprintf('0 0 %s %s',round(2*$pad+($grid_cols-1)*$step_x,3),round(2*$pad+($grid_rows-1)*$step_y,3));
            $profile=function($kind,$index,$count,$offset)use($spread){
                $t=$index/max(1,$count-1);
                $focus=0.5+$offset/200;
                $range=max(.001,0.5*$spread/100);
                $distance=min(1,abs($t-$focus)/$range);
                if($kind==='uniform')return 1;
                if($kind==='peak')return 1-$distance;
                if($kind==='valley')return $distance;
                $linear=max(0,min(1,0.5+($t-$focus)/(2*$range)));
                return $kind==='descending'?1-$linear:$linear;
            };
            $combine=function($a,$b)use($grid_blend){
                if($grid_blend==='multiply')return $a*$b;
                if($grid_blend==='x')return $a;
                if($grid_blend==='y')return $b;
                return ($a+$b)/2;
            };
            $opposite=array('peak'=>'valley','valley'=>'peak','ascending'=>'descending','descending'=>'ascending','uniform'=>'uniform');
            $circles='';
            for($y=0;$y<$grid_rows;$y++){
                for($x=0;$x<$grid_cols;$x++){
                    $sx=$profile($grid_pattern_x,$x,$grid_cols,$grid_offset_x);
                    $sy=$profile($grid_pattern_y,$y,$grid_rows,$grid_offset_y);
                    $from=$combine($sx,$sy);
                    if($grid_morph==='shift'){
                        $tx=$profile($grid_pattern_x,$x,$grid_cols,$grid_end_x);
                        $ty=$profile($grid_pattern_y,$y,$grid_rows,$grid_end_y);
                        $to=$combine($tx,$ty);
                    }elseif($grid_morph==='invert'){
                        $tx=$profile($opposite[$grid_pattern_x],$x,$grid_cols,$grid_offset_x);
                        $ty=$profile($opposite[$grid_pattern_y],$y,$grid_rows,$grid_offset_y);
                        $to=$combine($tx,$ty);
                    }else{$to=$from;}
                    // True geometric overlap: grow radii without changing circle centres/pitch.
                    // Apply consistently to both scroll states to avoid a discontinuity.
                    // Two uniform axes yield a true equal-radius field; avoid scroll size
                    // variation even when legacy morph is set to 'invert'.
                    if($grid_pattern_x==='uniform' && $grid_pattern_y==='uniform'){
                        $r1=$radius*$grid_uniform_size*(1+$grid_overlap);
                        $r2=$r1;
                    }else{
                        $r1=$radius*($grid_min+(1-$grid_min)*$from)*(1+$grid_overlap);
                        $r2=$radius*($grid_min+(1-$grid_min)*$to)*(1+$grid_overlap);
                    }
                    $cx=$pad+$x*$step_x;
                    $cy=$pad+$y*$step_y;
                    $circles.=sprintf('<circle cx="%s" cy="%s" r="%s" data-dm-r="%s" data-dm-to-r="%s"/>',
                        esc_attr((string)round($cx,3)),esc_attr((string)round($cy,3)),esc_attr((string)round($r1,3)),
                        esc_attr((string)round($r1,3)),esc_attr((string)round($r2,3)));
                }
            }
        } elseif($direction==='repeater') {
            $count=$repeat_count;
            // Repeated independent rows/columns, preserving circular geometry and WPBakery sizing.
            $min=14*$scale; $max=115*$scale;
            $base_radius=($pattern==='uniform')?$max*$uniform_size:$max;
            $pitch=2*$max*$spacing/100;
            $cross_pitch=2*$max*$copy_spacing;
            $limit_radius=$max*(1+$repeat_overlap)*max(1,$uniform_size);
            $axis_pad=$limit_radius+20;
            $axis_extent=($count-1)*$pitch + abs(($copies-1)*$copy_shift*$pitch) + 2*$axis_pad;
            $cross_extent=($copies-1)*$cross_pitch+2*$limit_radius+40;
            $complements=array('ascending'=>'descending','descending'=>'ascending','peak'=>'valley','valley'=>'peak','uniform'=>'uniform');
            $profile=function($kind,$index,$focus_offset)use($count,$min,$max,$spread,$uniform_size){
                if($kind==='uniform')return $max*$uniform_size;
                $t=$index/max(1,$count-1);
                $focus=.5+$focus_offset/200;
                $range=max(.001,.5*$spread/100);
                if($kind==='ascending'||$kind==='descending'){
                    $v=max(0,min(1,.5+($t-$focus)/(2*$range)));
                    if($kind==='descending')$v=1-$v;
                }else{
                    $dist=min(1,abs($t-$focus)/$range);
                    $v=$kind==='peak'?1-$dist:$dist;
                }
                return $min+($max-$min)*$v;
            };
            $copy_factor=function($j)use($copies,$copy_progression,$copy_min_scale){
                $t=$j/max(1,$copies-1);
                if($copy_progression==='grow')$v=$t;
                elseif($copy_progression==='shrink')$v=1-$t;
                elseif($copy_progression==='peak')$v=1-abs(2*$t-1);
                elseif($copy_progression==='valley')$v=abs(2*$t-1);
                else return 1.0;
                return $copy_min_scale+(1-$copy_min_scale)*$v;
            };
            $circles='';
            for($j=0;$j<$copies;$j++){
                $row_kind=($copy_pattern==='alternate' && $j%2)?$complements[$pattern]:$pattern;
                $next_kind=$complements[$row_kind];
                $factor=$copy_factor($j);
                // The scroll destination reverses the copy-size progression.
                // Previously both states used $factor, so a Uniform pattern had
                // identical starting/ending radii and appeared not to animate.
                $end_factor=$copy_factor($copies-1-$j);
                $offsetAlong=($j*$copy_shift*$pitch);
                $cross=$limit_radius+20+$j*$cross_pitch;
                for($i=0;$i<$count;$i++){
                    $axis=$axis_pad+$i*$pitch+$offsetAlong+max(0,-($copies-1)*$copy_shift*$pitch);
                    $cx=$repeat_axis==='vertical'?$cross:$axis;
                    $cy=$repeat_axis==='vertical'?$axis:$cross;
                    $from=$profile($row_kind,$i,$start_offset)*$factor*(1+$repeat_overlap);
                    $to=$profile($next_kind,$i,$end_offset)*$end_factor*(1+$repeat_overlap);
                    $circles.=sprintf('<circle cx="%s" cy="%s" r="%s" data-dm-r="%s" data-dm-to-r="%s"/>',
                        esc_attr((string)round($cx,3)),esc_attr((string)round($cy,3)),
                        esc_attr((string)round($from,3)),esc_attr((string)round($from,3)),esc_attr((string)round($to,3)));
                }
            }
            $viewbox=$repeat_axis==='vertical'
              ? sprintf('0 0 %s %s',round($cross_extent,2),round($axis_extent,2))
              : sprintf('0 0 %s %s',round($axis_extent,2),round($cross_extent,2));
        } elseif($legacy && $count===6 && $rows===1) {
            // Preserve the original shortcode's six-dot profile by default.
            $radii=array(12,34,56,78,98,112);
            if($reverse)$radii=array_reverse($radii);
            $centres=array();
            foreach(range(0,5) as $i)$centres[]=670+(($i-2.5)*218*$spacing/100);
            $circles='';
            foreach($radii as $i=>$radius){
                $r=$radius*$scale;
                $cx=$direction==='vertical'?130:$centres[$i];
                $cy=$direction==='vertical'?$centres[$i]:130;
                $circles.=sprintf('<circle cx="%s" cy="%s" r="%s" data-dm-r="%s"/>',
                    esc_attr((string)round($cx,3)),esc_attr((string)round($cy,3)),
                    esc_attr((string)round($r,3)),esc_attr((string)round($r,3)));
            }
            $max_radius=112*$scale;
            $min_axis=min($centres)-$max_radius-12;
            $axis_extent=max($centres)-min($centres)+2*$max_radius+24;
            $cross_min=130-$max_radius-12;
            $cross_extent=2*$max_radius+24;
            $viewbox=$direction==='vertical'
              ? implode(' ',array(round($cross_min,2),round($min_axis,2),round($cross_extent,2),round($axis_extent,2)))
              : implode(' ',array(round($min_axis,2),round($cross_min,2),round($axis_extent,2),round($cross_extent,2)));
        } else {
            // Circle centres are fixed: only radii change on scroll.
            $minimum=14*$scale;
            $maximum=115*$scale;
            $step=230*$spacing/100;
            $row_step=2*$maximum+$row_gap;
            $pad=$maximum+24;
            $axis_length=($count-1)*$step+2*$pad;
            $cross_length=$rows*2*$maximum+2*24+($rows-1)*$row_gap;
            $complement=array('ascending'=>'descending','descending'=>'ascending','valley'=>'peak','peak'=>'valley','uniform'=>'uniform');
            $radius_for=function($kind,$index,$focal_offset)use($count,$minimum,$maximum,$spread,$uniform_size) {
                if($kind==='uniform')return $maximum*$uniform_size;
                $t=$index/max(1,$count-1);
                $focus=0.5 + $focal_offset/200;
                $radius_range=max(0.001,0.5*$spread/100);
                if($kind==='ascending'||$kind==='descending'){
                    $amount=max(0,min(1,0.5+($t-$focus)/(2*$radius_range)));
                    if($kind==='descending')$amount=1-$amount;
                }else{
                    $distance=min(1,abs($t-$focus)/$radius_range);
                    $amount=($kind==='peak')?1-$distance:$distance;
                }
                return $minimum+($maximum-$minimum)*$amount;
            };
            $circles='';
            for($row=0;$row<$rows;$row++){
                $source=$row===0?$pattern:$complement[$pattern];
                $target=$complement[$source];
                $cross=$pad+$row*$row_step;
                for($i=0;$i<$count;$i++){
                    $axis=$pad+$i*$step;
                    $cx=$direction==='vertical'?$cross:$axis;
                    $cy=$direction==='vertical'?$axis:$cross;
                    $r=$radius_for($source,$i,$start_offset);
                    $to=$radius_for($target,$i,$end_offset);
                    $circles.=sprintf('<circle cx="%s" cy="%s" r="%s" data-dm-r="%s" data-dm-to-r="%s"/>',
                        esc_attr((string)round($cx,3)),esc_attr((string)round($cy,3)),
                        esc_attr((string)round($r,3)),esc_attr((string)round($r,3)),esc_attr((string)round($to,3)));
                }
            }
            $viewbox=$direction==='vertical'
              ? sprintf('0 0 %s %s',round($cross_length,2),round($axis_length,2))
              : sprintf('0 0 %s %s',round($axis_length,2),round($cross_length,2));
        }
        // Expose the same pointer engine and animation-source selection to every dots layout.
        // Existing shortcodes still default to pointer Off, and preserve their scroll behaviour.
        $pointer_attrs=' data-dm-animation-source="'.esc_attr($animation_source).'" data-dm-dots-layout="'.esc_attr($direction).'"';
        if($pointer_mode!=='off' && in_array($animation_source,array('both','pointer'),true)){
            $pointer_attrs.=sprintf(' data-dm-pointer-mode="%s" data-dm-pointer-scope="%s" data-dm-pointer-reach="%s" data-dm-pointer-radius="%s" data-dm-pointer-strength="%s" data-dm-pointer-easing="%s"', esc_attr($pointer_mode),esc_attr($pointer_scope),esc_attr((string)$pointer_reach),esc_attr((string)$pointer_radius),esc_attr((string)$pointer_strength),esc_attr($pointer_easing));
        }
        $variant_class=$atts['responsive_render']==='desktop'?' dm-dots-desktop-variant':($atts['responsive_render']==='mobile'?' dm-dots-mobile-variant':'');
        return '<div class="dm-dots-wrap '.($direction==='vertical'?'dm-dots-vertical ':($direction==='grid'?'dm-dots-grid ':($direction==='repeater' && $repeat_axis==='vertical'?'dm-dots-vertical dm-dots-repeater-vertical ':($direction==='repeater'?'dm-dots-horizontal dm-dots-repeater ':'dm-dots-horizontal ')))).esc_attr($classes_str).esc_attr($variant_class).'" style="'.esc_attr($style).'">'
             .'<svg class="dm-dots" viewBox="'.esc_attr($viewbox).'" preserveAspectRatio="xMidYMid meet" role="img" aria-label="DURF dots changing size on scroll"'.$pointer_attrs.' style="color:'.esc_attr($color).'" xmlns="http://www.w3.org/2000/svg">'.($pointer_mode!=='off' && in_array($animation_source,array('both','pointer'),true)?'<rect x="0" y="0" width="100%" height="100%" fill="transparent" pointer-events="all" aria-hidden="true"/>':'').$circles.'</svg>'
             .'</div>';
    }
    public static function menu() {
        add_menu_page('DURF Motion', 'DURF Motion', 'manage_options', 'durf-motion', array(__CLASS__, 'page'), 'dashicons-controls-play', 57);
        add_submenu_page('durf-motion', 'Motion settings', 'Settings', 'manage_options', 'durf-motion', array(__CLASS__, 'page'));
    }
    public static function register() {
        register_setting('durf_motion', self::OPTION, array(
            'type'=>'array', 'sanitize_callback'=>array(__CLASS__, 'sanitize'), 'default'=>self::defaults(),
        ));
    }
    public static function sanitize($raw) {
        $raw = is_array($raw) ? $raw : array();
        return array(
            'enabled'=>empty($raw['enabled']) ? 0 : 1,
            'duration'=>max(250, min(2400, absint($raw['duration'] ?? 900))),
            'distance'=>max(0, min(120, absint($raw['distance'] ?? 36))),
            'stagger'=>max(0, min(600, absint($raw['stagger'] ?? 120))),
            'parallax'=>max(0, min(150, absint($raw['parallax'] ?? 60))),
            'image_parallax'=>max(0, min(150, absint($raw['image_parallax'] ?? 35))),
            'background_parallax'=>max(0, min(150, absint($raw['background_parallax'] ?? 45))),
            'background_overscan'=>max(0, min(30, absint($raw['background_overscan'] ?? 8))),
            'section_parallax'=>max(0, min(80, absint($raw['section_parallax'] ?? 20))),
            'dots_enabled'=>empty($raw['dots_enabled']) ? 0 : 1,
            'dots_strength'=>max(0,min(100,absint($raw['dots_strength'] ?? 100))),
            'dots_start'=>max(50,min(100,absint($raw['dots_start'] ?? 85))),
            'dots_end'=>max(0,min(50,absint($raw['dots_end'] ?? 15))),
            'dots_easing'=>in_array($raw['dots_easing'] ?? '',array('linear','soft','cinematic'),true) ? $raw['dots_easing'] : 'soft',
            'parallax_easing'=>self::ease_option($raw['parallax_easing'] ?? 'soft'),
            'image_easing'=>self::ease_option($raw['image_easing'] ?? 'soft'),
            'background_easing'=>self::ease_option($raw['background_easing'] ?? 'soft'),
            'section_easing'=>self::ease_option($raw['section_easing'] ?? 'soft'),
            'scroll_enabled'=>empty($raw['scroll_enabled']) ? 0 : 1,
            'scroll_smoothing'=>max(4,min(22,absint($raw['scroll_smoothing'] ?? 10))),
            'scroll_wheel'=>max(50,min(140,absint($raw['scroll_wheel'] ?? 85))),
            'scroll_focus'=>in_array($raw['scroll_focus'] ?? '',array('off','gentle','defined'),true) ? $raw['scroll_focus'] : 'off',
            'image_trigger'=>max(10, min(95, absint($raw['image_trigger'] ?? 45))),
            'text_trigger'=>max(10, min(95, absint($raw['text_trigger'] ?? 95))),
            'once'=>empty($raw['once']) ? 0 : 1,
            'color_enabled'=>empty($raw['color_enabled']) ? 0 : 1,
            'header_enabled'=>empty($raw['header_enabled']) ? 0 : 1,
            'header_mode'=>in_array($raw['header_mode']??'',array('transparent','match'),true)?$raw['header_mode']:'transparent',
            'header_contrast'=>in_array($raw['header_contrast']??'',array('auto','light','dark'),true)?$raw['header_contrast']:'auto',
            'header_selector'=>self::header_selector($raw['header_selector']??'.l-header'),
            'header_mono_logo'=>empty($raw['header_mono_logo']) ? 0 : 1,
            'color_position'=>max(15,min(85,absint($raw['color_position'] ?? 55))),
            'color_softness'=>in_array($raw['color_softness'] ?? '',array('tight','balanced','atmospheric'),true) ? $raw['color_softness'] : 'balanced',
            'color_start'=>max(30,min(95,absint($raw['color_start'] ?? 80))),
            'color_end'=>max(5,min(70,absint($raw['color_end'] ?? 20))),
            'color_easing'=>in_array($raw['color_easing'] ?? '',array('linear','smooth'),true) ? $raw['color_easing'] : 'smooth',
            'color_cream'=>sanitize_hex_color($raw['color_cream'] ?? '#F8F4EC') ?: '#F8F4EC',
            'color_orange'=>sanitize_hex_color($raw['color_orange'] ?? '#F16A37') ?: '#F16A37',
            'color_green'=>sanitize_hex_color($raw['color_green'] ?? '#507568') ?: '#507568',
            'color_pink'=>sanitize_hex_color($raw['color_pink'] ?? '#DD8EB9') ?: '#DD8EB9',
            'color_ink'=>sanitize_hex_color($raw['color_ink'] ?? '#25231F') ?: '#25231F',
        );
    }
    public static function header_selector($value) {
        $value=is_string($value)?trim($value):'.l-header';
        // A simple, bounded CSS selector avoids accidentally storing arbitrary CSS.
        return preg_match('/^[a-zA-Z0-9.#_ -]{1,100}$/',$value)?$value:'.l-header';
    }
    public static function ease_option($value) { return in_array($value,array('direct','soft','cinematic'),true) ? $value : 'soft'; }
    public static function ease_field($name,$label) {
        $s=self::settings();
        printf('<tr><th scope="row">%s</th><td><select name="%s[%s]">',esc_html($label),esc_attr(self::OPTION),esc_attr($name));
        foreach(array('direct'=>'Direct / responsive','soft'=>'Soft / editorial','cinematic'=>'Cinematic / slow catch-up') as $key=>$value)printf('<option value="%s" %s>%s</option>',esc_attr($key),selected($s[$name],$key,false),esc_html($value));
        echo '</select></td></tr>';
    }
    public static function field($name, $label, $min, $max, $unit='') {
        $s=self::settings();
        printf('<tr><th scope="row"><label for="dm-%1$s">%2$s</label></th><td><input id="dm-%1$s" type="number" name="%3$s[%1$s]" value="%4$d" min="%5$d" max="%6$d" step="1" class="small-text"> %7$s</td></tr>',
            esc_attr($name),esc_html($label),esc_attr(self::OPTION),(int)$s[$name],(int)$min,(int)$max,esc_html($unit));
    }
    public static function field_input($name, $min, $max) {
        $s=self::settings();
        printf('<input type="number" name="%1$s[%2$s]" value="%3$d" min="%4$d" max="%5$d" class="small-text">',esc_attr(self::OPTION),esc_attr($name),(int)$s[$name],(int)$min,(int)$max);
    }
    public static function color_field($name,$title,$class) {
        $s=self::settings();
        printf('<label class="dm-color-field"><span>%1$s</span><input type="color" name="%2$s[%3$s]" value="%4$s"><code>dm-color-%5$s</code></label>',esc_html($title),esc_attr(self::OPTION),esc_attr('color_'.$name),esc_attr($s['color_'.$name]),esc_html($class));
    }
    public static function page() {
        if (!current_user_can('manage_options')) { return; }
        $s=self::settings(); ?>
        <div class="wrap dm-settings"><h1>DURF Motion <small style="font-weight:normal;color:#646970">v<?php echo esc_html(self::VERSION); ?></small></h1>
        <p>Configure your motion system here. In WPBakery, add the relevant class to <strong>Extra class name</strong> on a Row, Text Block, or image element.</p>
        <style>
        .dm-settings{max-width:1080px}.dm-settings .dm-tab-links{display:flex;gap:6px;margin:22px 0 0;border-bottom:1px solid #c3c4c7;flex-wrap:wrap}
        .dm-settings .dm-tab-links button{border:1px solid transparent;border-radius:5px 5px 0 0;padding:11px 18px;background:transparent;cursor:pointer;font-weight:600;color:#50575e}
        .dm-settings .dm-tab-links button[aria-selected="true"]{background:white;border-color:#c3c4c7;border-bottom-color:#fff;color:#135e96;margin-bottom:-1px}
        .dm-settings .dm-panel{display:none;background:#fff;border:1px solid #c3c4c7;border-top:0;padding:22px 28px 18px}
        .dm-settings .dm-panel.dm-active{display:block}.dm-settings .dm-panel h2{margin-top:0}
        .dm-settings .dm-color-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;max-width:920px}
        .dm-settings .dm-color-field{display:flex;flex-direction:column;align-items:start;gap:7px;padding:12px;border:1px solid #ddd;border-radius:6px}
        .dm-settings .dm-color-field input{width:92px;height:42px;padding:2px;cursor:pointer}.dm-settings .dm-tip{background:#f6f7f7;border-left:3px solid #2271b1;padding:11px 15px;max-width:860px}
        .dm-settings .dm-help{max-width:880px}.dm-settings .dm-help td{vertical-align:middle}.dm-settings .dm-demo{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.dm-settings .dm-demo code{font-size:13px}
        @media(max-width:750px){.dm-settings .dm-panel{padding:16px}.dm-settings .dm-tab-links button{padding:9px}}
        </style>
        <form method="post" action="options.php">
          <?php settings_fields('durf_motion'); ?>
          <nav class="dm-tab-links" aria-label="Motion settings categories" role="tablist">
            <?php foreach(array('general'=>'General','typography'=>'Typography','images'=>'Images','parallax'=>'Parallax','dots'=>'Dots','scroll'=>'Scroll','colours'=>'Colours','sliders'=>'Sliders') as $key=>$label): ?>
              <button type="button" role="tab" id="dm-tab-<?php echo esc_attr($key); ?>" data-dm-tab="<?php echo esc_attr($key); ?>" aria-controls="dm-panel-<?php echo esc_attr($key); ?>" aria-selected="<?php echo $key==='general'?'true':'false'; ?>"><?php echo esc_html($label); ?></button>
            <?php endforeach; ?>
          </nav>
          <div role="tabpanel" id="dm-panel-general" class="dm-panel dm-active" aria-labelledby="dm-tab-general">
            <h2>General motion</h2><p>Shared timing and animation behaviour across the site.</p>
            <table class="form-table" role="presentation"><tbody>
            <tr><th scope="row">Enable animations</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="1" <?php checked($s['enabled'],1); ?>> Enable DURF Motion</label></td></tr>
            <?php self::field('duration','Reveal duration',250,2400,'ms');self::field('distance','Reveal movement',0,120,'px');self::field('stagger','Stagger between items or words',0,600,'ms'); ?>
            <tr><th scope="row">Repeat behaviour</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[once]" value="1" <?php checked($s['once'],1); ?>> Reveal only once per page visit</label></td></tr>
            </tbody></table><p class="dm-tip">All effects respect reduced-motion preferences. Content stays visible if the plugin is disabled.</p>
          </div>
          <div role="tabpanel" id="dm-panel-typography" class="dm-panel" aria-labelledby="dm-tab-typography">
            <h2>Typography</h2><p>Word, line, and whole-block reveals. The global stagger value controls the interval between words.</p>
            <table class="form-table" role="presentation"><tbody>
            <?php self::field('text_trigger','Text trigger position',10,95,'% down the viewport'); ?>
            </tbody></table>
            <table class="widefat striped dm-help"><thead><tr><th>Class</th><th>Usage</th></tr></thead><tbody>
            <tr><td><code>dm-reveal</code></td><td>Fade and rise into view</td></tr>
            <tr><td><code>dm-words</code></td><td>Words appear sequentially, preserving manual line breaks. Perfect for Droom. Denk. / Durf. Doe.</td></tr>
            <tr><td><code>dm-words dm-words-manual</code></td><td>Manually wrapped words for precise art-directed layout</td></tr>
            <tr><td><code>dm-lines</code></td><td>Lines separated by authored &lt;br&gt; elements</td></tr>
            <tr><td><code>dm-stagger</code></td><td>Children reveal one by one</td></tr>
            </tbody></table>
          </div>
          <div role="tabpanel" id="dm-panel-images" class="dm-panel" aria-labelledby="dm-tab-images">
            <h2>Image reveals</h2><p>Reveal ordinary images and row background photography without exposed strips at the edges. <strong>Background size, repeat and position now inherit WPBakery / Impreza settings, including responsive changes.</strong> For background parallax only, adjust overscan in the Parallax tab; 0% preserves exact framing.</p>
            <table class="form-table" role="presentation"><tbody>
            <?php self::field('image_trigger','Image trigger position',10,95,'% down the viewport (smaller = later)'); ?>
            </tbody></table>
            <table class="widefat striped dm-help"><thead><tr><th>Class</th><th>Usage</th></tr></thead><tbody>
            <tr><td><code>dm-image</code></td><td>WPBakery Single Image wrapper</td></tr>
            <tr><td><code>dm-bg-reveal</code></td><td>WPBakery/Impreza row with a background photograph</td></tr>
            </tbody></table>
          </div>
          <div role="tabpanel" id="dm-panel-parallax" class="dm-panel" aria-labelledby="dm-tab-parallax">
            <h2>Parallax and floating</h2><p>The photo or inner content moves. Row boundaries and backgrounds remain aligned with adjacent sections.</p>
            <table class="form-table" role="presentation"><tbody>
            <?php self::field('image_parallax','Image parallax',0,150,'px');self::ease_field('image_easing','Image easing');self::field('background_parallax','Background-image parallax',0,150,'px');self::ease_field('background_easing','Background easing');self::field('background_overscan','Background photo overscan',0,30,'% (0 = exact sizing; higher = fewer exposed edges)');self::field('section_parallax','Section-content parallax',0,80,'px');self::ease_field('section_easing','Section easing');self::field('parallax','Legacy graphic parallax',0,150,'px');self::ease_field('parallax_easing','Graphic easing'); ?>
            </tbody></table>
            <p class="dm-tip">Easing makes the visual element catch up with the scroll. It does not slow or block the page itself. Direct follows immediately; Soft and Cinematic add progressively more inertia.</p>
            <p><code>dm-image-parallax</code> on a single image; <code>dm-bg-parallax</code> on a photo row; <code>dm-section-parallax</code> on a content row; <code>dm-float</code> on an individual dot or illustration.</p>
          </div>
          <div role="tabpanel" id="dm-panel-dots" class="dm-panel" aria-labelledby="dm-tab-dots">
            <h2>Scroll-reactive circles</h2>
            <p>Small circles grow while large circles shrink as the section scrolls. Scrolling back reverses the progression. The SVG scales proportionally and never stretches the circles.</p>
            <table class="form-table" role="presentation"><tbody>
              <tr><th scope="row">Enable dots animation</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[dots_enabled]" value="1" <?php checked($s['dots_enabled'],1); ?>> Animate SVG circles on scroll</label></td></tr>
              <?php self::field('dots_strength','Size inversion',0,100,'% (100 = full inversion)'); ?>
              <?php self::field('dots_start','Animation starts',50,100,'% down the viewport'); ?>
              <?php self::field('dots_end','Animation ends',0,50,'% down the viewport'); ?>
              <tr><th scope="row">Easing</th><td><select name="<?php echo esc_attr(self::OPTION); ?>[dots_easing]">
                <option value="linear" <?php selected($s['dots_easing'],'linear'); ?>>Linear / direct</option>
                <option value="soft" <?php selected($s['dots_easing'],'soft'); ?>>Soft / editorial</option>
                <option value="cinematic" <?php selected($s['dots_easing'],'cinematic'); ?>>Cinematic / gradual</option>
              </select></td></tr>
            </tbody></table>
            <h3>WPBakery usage</h3>
            <p>Add <code>dm-dots-section</code> to the full orange Row's Extra class name. Insert <code>[durf_dots]</code> in a WPBakery Text Block or Shortcode element inside the same row.</p>
            <p><strong>Recommended:</strong> Use the <em>DURF Dots</em> element in WPBakery (category: DURF Motion) to edit patterns, circle count, horizontal/vertical/repeater layouts, width, mobile width, alignment, X/Y offsets, size, spacing, and colour without CSS.</p>
            <p>Alternatively, shortcode example: <code>[durf_dots width="180" mobile_width="130" align="center" offset_x="-50" offset_y="25" size="110" spacing="90" color="#DD8EB9"]</code>.</p>
            <p class="description">The SVG itself has no background and allows visible overflow. The plugin also allows overflow through WPBakery dot containers. If another row or image clips the graphic, check the theme row overflow setting. Original [durf_dots] shortcodes retain their six-dot design.</p>
            <p class="description">For a custom SVG, paste inline SVG containing individual <code>&lt;circle&gt;</code> elements into an HTML block and put <code>dm-dots</code> on the SVG. An SVG loaded with <code>&lt;img&gt;</code> is not directly editable by the animation, because its circles are inside an external image document.</p>
          </div>
          <div role="tabpanel" id="dm-panel-colours" class="dm-panel" aria-labelledby="dm-tab-colours">
            <h2>Scroll colour flow</h2><p class="description">Two simple controls: where colours change, and how gently. No scroll speed modification or section translation.</p>
            <p>Set the DURF palette here. Then give each consecutive WPBakery row <code>dm-color-flow</code> plus a colour class. The plugin gradually blends between them while scrolling. The <strong>row itself never moves</strong>.</p>
            <div class="dm-color-grid">
              <?php self::color_field('cream','Cream','cream');self::color_field('orange','Orange','orange');self::color_field('green','Green','green');self::color_field('pink','Pink','pink');self::color_field('ink','Ink','ink'); ?>
            </div>
            <table class="form-table" role="presentation"><tbody>
            <tr><th scope="row">Enable scroll colour flow</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[color_enabled]" value="1" <?php checked($s['color_enabled'],1); ?>> Animate between marked sections</label></td></tr>
            <?php self::field('color_position','Transition position',15,85,'% down viewport'); ?>
            <tr><th scope="row">Blend softness</th><td><select name="<?php echo esc_attr(self::OPTION); ?>[color_softness]"><option value="tight" <?php selected($s['color_softness'],'tight'); ?>>Tight — defined colour change</option><option value="balanced" <?php selected($s['color_softness'],'balanced'); ?>>Balanced — editorial (recommended)</option><option value="atmospheric" <?php selected($s['color_softness'],'atmospheric'); ?>>Atmospheric — long gradual blend</option></select></td></tr>
            <tr><th scope="row">Easing</th><td><select name="<?php echo esc_attr(self::OPTION); ?>[color_easing]"><option value="smooth" <?php selected($s['color_easing'],'smooth'); ?>>Smooth (recommended)</option><option value="linear" <?php selected($s['color_easing'],'linear'); ?>>Linear</option></select></td></tr>
            </tbody></table>
            <p class="dm-tip">Add <code>dm-color-break</code> to a destination row to prevent a fade from the previous colour while keeping its fade to the following row. Example: orange → green, hard cut to cream, then cream → pink. The transition is centred around the chosen viewport position. Softness controls the length of the blend. For a seamless effect, put these classes on full-width adjacent rows. Do not apply colour flow to photographic rows.</p>
            <h3>Adaptive header</h3>
            <p class="description">Optional, off by default. Follow the colour of the section directly behind the header, not the section occupying most of the viewport. Built for Impreza's <code>.l-header</code> structure.</p>
            <table class="form-table" role="presentation"><tbody>
              <tr><th scope="row">Enable adaptive header</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[header_enabled]" value="1" <?php checked($s['header_enabled'],1); ?>> Adapt header colours on scroll</label></td></tr>
              <tr><th scope="row">Header appearance</th><td><select name="<?php echo esc_attr(self::OPTION); ?>[header_mode]">
                <option value="transparent" <?php selected($s['header_mode'],'transparent'); ?>>Transparent — adapt logo/navigation contrast</option>
                <option value="match" <?php selected($s['header_mode'],'match'); ?>>Match section — adapt background and contrast</option>
              </select></td></tr>
              <tr><th scope="row">Text and icon contrast</th><td><select name="<?php echo esc_attr(self::OPTION); ?>[header_contrast]">
                <option value="auto" <?php selected($s['header_contrast'],'auto'); ?>>Automatic (recommended)</option>
                <option value="light" <?php selected($s['header_contrast'],'light'); ?>>Always Cream</option>
                <option value="dark" <?php selected($s['header_contrast'],'dark'); ?>>Always Ink</option>
              </select></td></tr>
              <tr><th scope="row">Header CSS selector</th><td><input class="regular-text" type="text" name="<?php echo esc_attr(self::OPTION); ?>[header_selector]" value="<?php echo esc_attr($s['header_selector']); ?>" placeholder=".l-header"> <p class="description">Default <code>.l-header</code> (Impreza). Change only if your header has a different wrapper.</p></td></tr>
              <tr><th scope="row">Monochrome image logo</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[header_mono_logo]" value="1" <?php checked($s['header_mono_logo'],1); ?>> Also recolour the logo if it is a one-colour image</label><p class="description">Leave off for multicolour, photographic or temporary DURF logos. Inline SVG logos using <code>currentColor</code> adapt automatically.</p></td></tr>
            </tbody></table>
            <p class="dm-tip">Works best with <code>dm-color-flow dm-color-green</code> and other DURF colour classes. On other rows, set <code>data-dm-header-bg="#507568"</code> on the section if necessary. Use <code>dm-header-light</code> or <code>dm-header-dark</code> on a section to override automatic foreground contrast, for example on photographic sections. Some opaque Impreza header variants may require using the <strong>Match section</strong> setting instead of Transparent.</p>
            <h3>WPBakery example</h3><p>Extra class name of each relevant row:</p>
            <div class="dm-demo"><code>dm-color-flow dm-color-cream</code><span>→</span><code>dm-color-flow dm-color-orange</code><span>→</span><code>dm-color-flow dm-color-green</code><span>→</span><code>dm-color-flow dm-color-pink</code></div>
            <p><strong>Footer connection:</strong> Add <code>dm-color-to-footer</code> to your final <code>dm-color-flow</code> row to blend the last row into the DURF Ink palette swatch (or a custom <code>data-dm-footer-color</code> HEX value). The plugin never repaints the footer itself. Leave this class off for a hard section-to-footer edge.</p>
            <p><strong>Separate colour sequences:</strong> <code>dm-color-flow dm-color-orange</code> → <code>dm-color-flow dm-color-green</code> → <code>dm-color-flow dm-color-cream dm-color-break</code> → <code>dm-color-flow dm-color-pink</code>. The cream section cuts in immediately, then fades toward pink.</p>
            <p>If you use a Raw HTML wrapper, you can also define any HEX colour with <code>data-dm-color="#abcdef"</code>.</p>
          </div>
          <div role="tabpanel" id="dm-panel-sliders" class="dm-panel" aria-labelledby="dm-tab-sliders">
            <h2>Image sliders</h2>
            <p>Create photographic sliders using images from the WordPress Media Library. Choose <strong>Editorial</strong> for one large image with a peek of the next, or <strong>Gallery</strong> for multiple images in a row.</p>
            <p><a class="button button-primary" href="<?php echo esc_url(admin_url('edit.php?post_type=durf_slider')); ?>">Manage sliders</a> <a class="button" href="<?php echo esc_url(admin_url('post-new.php?post_type=durf_slider')); ?>">Add new slider</a></p>
            <p class="dm-tip">Paste <code>[durf_slider id="123"]</code> into a WPBakery Text Block (or Raw HTML element that processes shortcodes). The slider output is styled separately from motion classes, so no existing animations are affected.</p>
            <p>Choose horizontal or vertical image flow, with optional page-scroll acceleration and easing. Autoplay, page-scroll influence and seamless looping can run together. Drag/swipe can be turned off for each slider. No visible arrows or counters.</p>
          </div>
          <div role="tabpanel" id="dm-panel-scroll" class="dm-panel" aria-labelledby="dm-tab-scroll">
            <h2>Smooth scrolling · Lenis</h2>
            <p>Optional smooth scroll for the whole page, with native scrolling as fallback. Unlike the previous wheel-resistance experiment, this provides consistent interpolation without shifting WPBakery sections, backgrounds or sticky labels.</p>
            <table class="form-table" role="presentation"><tbody>
              <tr><th scope="row">Smooth scrolling</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[scroll_enabled]" value="1" <?php checked($s['scroll_enabled'],1); ?>> Enable Lenis (off by default)</label><p class="description">Uses a pinned Lenis JavaScript build from jsDelivr. If unavailable, normal browser scrolling remains.</p></td></tr>
              <?php self::field('scroll_smoothing','Smoothing',4,22,'/ 100 (10 = 0.10; lower feels heavier)'); ?>
              <?php self::field('scroll_wheel','Wheel response',50,140,'% (85 = 0.85×)'); ?>
              <tr><th scope="row">Content focus</th><td><select name="<?php echo esc_attr(self::OPTION); ?>[scroll_focus]">
                <?php foreach(array('off'=>'Off (recommended initially)','gentle'=>'Gentle approach resistance','defined'=>'More pronounced approach resistance') as $value=>$label)printf('<option value="%s" %s>%s</option>',esc_attr($value),selected($s['scroll_focus'],$value,false),esc_html($label)); ?>
              </select><p class="description">Optional: affects the wheel input near <code>dm-scroll-focus</code> sections, without snapping or preventing scrolling. Disabled on mobile and for reduced-motion preferences.</p></td></tr>
            </tbody></table>
            <p class="dm-tip">Start with smoothing 10 and wheel 85%, content focus Off. Do not run other smooth-scroll plugins simultaneously. Add <code>data-lenis-prevent</code> to nested custom scroll containers if needed.</p>
          </div>
          <?php submit_button('Save DURF Motion settings'); ?>
        </form>
        <details style="margin-top:16px"><summary><strong>Diagnostics and advanced usage</strong></summary>
          <p>On a published page, open developer tools and run <code>window.DURFMotion?.diagnostics()</code>. Clear SG Cache after updating.</p>
          <p>Optional per-element attributes: <code>data-dm-duration="1200"</code>, <code>data-dm-distance="24"</code>, <code>data-dm-stagger="150"</code>, <code>data-dm-word-delay="140"</code>, <code>data-dm-trigger="40"</code>, <code>data-dm-parallax="35"</code> and <code>data-dm-bg-url="https://example.com/photo.jpg"</code>. Advanced per-row background overrides: <code>data-dm-bg-position="60% 30%"</code>, <code>data-dm-bg-size="contain"</code>, <code>data-dm-bg-overscan="0"</code>.</p>
        </details>
        <script>
        (function(){
          var root=document.querySelector('.dm-settings');if(!root)return;
          var tabs=Array.from(root.querySelectorAll('[data-dm-tab]'));
          function activate(key){
            tabs.forEach(function(tab){var match=tab.dataset.dmTab===key;tab.setAttribute('aria-selected',match?'true':'false');tab.tabIndex=match?0:-1;});
            root.querySelectorAll('.dm-panel').forEach(function(panel){panel.classList.toggle('dm-active',panel.id==='dm-panel-'+key);});
            try{history.replaceState(null,'',location.pathname+location.search+'#'+key);}catch(e){}
          }
          tabs.forEach(function(tab){tab.addEventListener('click',function(){activate(tab.dataset.dmTab);});});
          var valid=tabs.map(function(t){return t.dataset.dmTab;});var hash=location.hash.slice(1);
          if(valid.indexOf(hash)!==-1)activate(hash);
        })();
        </script>
        </div><?php
    }

}
require_once plugin_dir_path(__FILE__) . 'includes/sliders.php';
DURF_Motion::init();
