<?php
/** DURF Motion v0.8: independently editable photo sliders. */
if (!defined('ABSPATH')) exit;

final class DURF_Motion_Sliders {
    const TYPE = 'durf_slider';
    const META = '_dm_slider';

    public static function init() {
        add_action('init', array(__CLASS__, 'register_type'));
        add_action('add_meta_boxes', array(__CLASS__, 'meta_boxes'));
        add_action('save_post_' . self::TYPE, array(__CLASS__, 'save'), 10, 2);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'frontend_assets'));
        add_shortcode('durf_slider', array(__CLASS__, 'shortcode'));
        add_filter('manage_' . self::TYPE . '_posts_columns', array(__CLASS__, 'columns'));
        add_action('manage_' . self::TYPE . '_posts_custom_column', array(__CLASS__, 'column_content'), 10, 2);
    }
    public static function register_type() {
        register_post_type(self::TYPE, array(
            'label' => 'DURF Sliders', 'labels'=>array('name'=>'DURF Sliders', 'singular_name'=>'DURF Slider','add_new_item'=>'Add new DURF slider','edit_item'=>'Edit DURF slider'),
            'public'=>false, 'show_ui'=>true, 'show_in_menu'=>'durf-motion',
            'menu_icon'=>'dashicons-images-alt2', 'supports'=>array('title'),
            'capability_type'=>'post', 'map_meta_cap'=>true,
            'show_in_rest'=>false, 'exclude_from_search'=>true,
        ));
    }
    public static function defaults() {
        return array('ids'=>array(), 'layout'=>'editorial','ratio'=>'16/9','fit'=>'cover','position'=>'50% 50%','gap'=>18,'peek'=>16,'speed'=>650,'loop'=>0,'autoplay'=>0,'arrows'=>0,'captions'=>0,'direction'=>'horizontal','scroll_response'=>'off','scroll_strength'=>60,'smoothness'=>72,'viewport_height'=>76,'vertical_slide_height'=>64,'image_drift'=>1,'autoplay_speed'=>25,'pause_hover'=>0,'allow_drag'=>1,'mask_grid'=>'6','mask_playback'=>'scroll','mask_duration'=>1100,'mask_interval'=>3500,'mask_scroll_distance'=>100,'mask_auto_scroll_strength'=>0,'mask_velocity'=>0,'mask_velocity_strength'=>35,'mask_height'=>75,'mask_easing'=>'editorial','mask_smoothing'=>45,'mask_spacing'=>85,'container_radius'=>0,'image_radius'=>0,'mask_interleave'=>'off','mask_color'=>'section','mask_color_handoff'=>20,'mask_direction'=>'grow');
    }
    public static function settings($id) {
        $value=get_post_meta($id,self::META,true);
        $raw=is_array($value)?$value:array();
        return wp_parse_args($raw,self::defaults());
    }
    public static function meta_boxes() {
        add_meta_box('dm_slider_editor','Images & slider settings',array(__CLASS__,'editor'),self::TYPE,'normal','high');
    }
    public static function admin_assets($hook) {
        $screen=function_exists('get_current_screen')?get_current_screen():null;
        if (!$screen || $screen->post_type!==self::TYPE || !in_array($hook,array('post.php','post-new.php'),true)) return;
        wp_enqueue_media();
        wp_enqueue_script('dm-slider-admin',plugins_url('../assets/slider-admin.js', __FILE__),array('jquery'),DURF_Motion::VERSION,true);
        // plugin_dir_url(__FILE__) would resolve to includes/, so use its parent.
        wp_add_inline_style('wp-admin', '.dm-slider-images{display:grid;grid-template-columns:repeat(auto-fill,minmax(135px,1fr));gap:12px;margin:15px 0}.dm-slider-image{padding:8px;border:1px solid #ddd;background:#fff}.dm-slider-image img{width:100%;height:100px;object-fit:cover}.dm-slider-image-actions{display:flex;gap:5px;justify-content:center}.dm-slider-controls{display:grid;grid-template-columns:repeat(3,minmax(150px,1fr));gap:15px}.dm-slider-controls label{display:grid;gap:6px}.dm-slider-controls input,.dm-slider-controls select{max-width:100%}@media(max-width:800px){.dm-slider-controls{grid-template-columns:repeat(2,minmax(120px,1fr))}}');
    }
    public static function editor($post) {
        $s=self::settings($post->ID);
        wp_nonce_field('dm_slider_save','dm_slider_nonce');
        echo '<p><strong>WPBakery shortcode:</strong> <code>[durf_slider id="' . (int)$post->ID . '"]</code> <small>(Save the slider to activate it.)</small></p>';
        echo '<p><button type="button" id="dm-slider-add" class="button button-primary">Choose / add photos</button> <span>Use arrows below each thumbnail to reorder images.</span></p>';
        echo '<div id="dm-slider-images" class="dm-slider-images" aria-live="polite"></div>';
        echo '<input type="hidden" id="dm-slider-ids" name="dm_slider[ids]" value="' . esc_attr(implode(',',array_map('absint',$s['ids']))) . '">';
        echo '<h3>Appearance</h3><div class="dm-slider-controls">';
        self::select('layout','Composition',array('editorial'=>'Editorial: one dominant image','gallery'=>'Gallery: multiple images','dot-mask'=>'Dot Mask: expanding circle reveal'),$s);
        echo '</div><h3>Corner styling</h3><div class="dm-slider-controls">';
        self::integer('container_radius','Container border radius (px)',0,200,$s);
        self::integer('image_radius','Image border radius (px)',0,200,$s);
        echo '</div><div class="dm-slider-regular"><div class="dm-slider-controls">';
        self::select('direction','Direction',array('horizontal'=>'Horizontal: image ribbon','vertical'=>'Vertical: filmstrip'),$s);
        self::select('scroll_response','Respond to page scrolling',array('off'=>'Off: drag / swipe only','gentle'=>'Gentle: follow scroll','dynamic'=>'Dynamic: accelerate with scroll'),$s);
        self::select('ratio','Image ratio',array('16/9'=>'16:9','4/3'=>'4:3','1/1'=>'Square','3/2'=>'3:2','auto'=>'Original (natural)'),$s);
        self::select('fit','Image fit',array('cover'=>'Cover','contain'=>'Contain'),$s);
        self::select('position','Focal point',array('50% 50%'=>'Center','50% 25%'=>'Top','50% 75%'=>'Bottom','25% 50%'=>'Left','75% 50%'=>'Right'),$s);
        self::integer('gap','Gap (px)',0,60,$s);
        self::integer('peek','Editorial next image visible (%)',5,35,$s);
        self::integer('speed','Snap transition (ms)',200,1800,$s);
        self::integer('scroll_strength','Scroll influence (0–150%)',0,150,$s);
        self::integer('smoothness','Ease / inertia (0–95)',0,95,$s);
        self::integer('viewport_height','Vertical frame height (vh)',35,100,$s);
        self::integer('vertical_slide_height','Vertical photo height (vh)',25,100,$s);
        echo '</div><h3>Playback & interaction</h3><div class="dm-slider-controls">';
        self::checkbox('autoplay','Continuous autoplay',$s);
        self::integer('autoplay_speed','Autoplay speed (px / second)',3,160,$s);
        self::checkbox('loop','Seamless infinite loop',$s);
        self::checkbox('allow_drag','Allow mouse dragging / touch swiping',$s);
        self::checkbox('pause_hover','Pause autoplay while hovering',$s);
        self::checkbox('image_drift','Subtle cinematic image movement',$s);
        self::checkbox('captions','Show Media Library captions',$s);
        echo '</div></div><div class="dm-slider-mask-settings" style="display:none"><h3>Dot Mask — reveal</h3><div class="dm-slider-controls">';
        self::select('mask_grid','Circle grid',array('1'=>'1 dot','4'=>'4 dots (2 × 2)','6'=>'6 dots (3 × 2)','8'=>'8 dots (4 × 2)','12'=>'12 dots (4 × 3)'),$s);
        self::select('mask_playback','Playback',array('scroll'=>'Track page scroll position','auto'=>'Automatic'),$s);
        self::integer('mask_duration','Reveal duration (ms)',250,6000,$s);
        self::integer('mask_interval','Autoplay hold per slide (ms)',1000,15000,$s);
        self::integer('mask_auto_scroll_strength','Automatic: page scroll influence (0–100%)',0,100,$s);
        self::integer('mask_scroll_distance','Scroll distance per slide (vh)',40,250,$s);
        self::integer('mask_height','Automatic slider height (vh)',35,100,$s);
        self::select('mask_easing','Growth easing',array('linear'=>'Linear','soft'=>'Soft','editorial'=>'Editorial (smoothest)'),$s);
        self::select('mask_direction','Circle transition',array('grow'=>'Small to big (reveal)','shrink'=>'Big to small (uncover)'),$s);
        self::integer('mask_smoothing','Scroll smoothing (0–95)',0,95,$s);
        self::integer('mask_spacing','Equal grid spacing (%)',30,100,$s);
        self::select('mask_interleave','Interleave solid colour',array('off'=>'Off: photos only','between'=>'Between every photo'),$s);
        self::select('mask_color','Solid colour',array('section'=>'Match section background','cream'=>'DURF Cream','orange'=>'DURF Orange','green'=>'DURF Green','pink'=>'DURF Pink','ink'=>'DURF Ink'),$s);
        self::integer('mask_color_handoff','Second circle growth lag (%)',0,65,$s);
        self::checkbox('mask_velocity','React to scroll speed',$s);
        self::integer('mask_velocity_strength','Scroll velocity influence (%)',0,100,$s);
        self::select('fit','Image fit',array('cover'=>'Cover','contain'=>'Contain'),$s);
        self::select('position','Focal point',array('50% 50%'=>'Center','50% 25%'=>'Top','50% 75%'=>'Bottom','25% 50%'=>'Left','75% 50%'=>'Right'),$s);
        self::checkbox('pause_hover','Pause automatic playback on hover',$s);
        echo '</div><p><strong>Companion text:</strong> use WPBakery’s own sticky column option. The plugin no longer moves neighbouring columns.</p><p>All dots expand together on an equally spaced grid. The outgoing photograph stays visible under the mask, and the next photo never reveals against a blank frame. Choose easing and optional scroll smoothing. Velocity response is limited to the current transition. Reduced-motion visitors see a static or stepped transition.</p></div><p><strong>Editorial idea:</strong> use a vertical filmstrip for a photographic column beside text, or a wide horizontal ribbon. Page-scroll motion never blocks ordinary site scrolling. Autoplay and page-scroll influence work simultaneously: scrolling briefly accelerates or reverses the filmstrip, then autoplay resumes. Infinite loop seamlessly joins the last image to the first. Turn dragging off when you want motion to be entirely automatic. Reduced-motion visitors see a static, manually navigable gallery.</p>';
    }
    private static function select($key,$label,$options,$s) {
        echo '<label><span>'.esc_html($label).'</span><select name="dm_slider['.esc_attr($key).']">';
        foreach($options as $value=>$name)echo '<option value="'.esc_attr($value).'" '.selected($s[$key],$value,false).'>'.esc_html($name).'</option>';
        echo '</select></label>';
    }
    private static function integer($key,$label,$min,$max,$s) {
        echo '<label><span>'.esc_html($label).'</span><input type="number" name="dm_slider['.esc_attr($key).']" min="'.(int)$min.'" max="'.(int)$max.'" value="'.(int)$s[$key].'"></label>';
    }
    private static function checkbox($key,$label,$s) {
        echo '<label><span>'.esc_html($label).'</span><span><input type="checkbox" name="dm_slider['.esc_attr($key).']" value="1" '.checked(!empty($s[$key]),true,false).'> Enable</span></label>';
    }
    public static function save($post_id,$post) {
        if (!isset($_POST['dm_slider_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dm_slider_nonce'])),'dm_slider_save'))return;
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post',$post_id))return;
        $input=isset($_POST['dm_slider']) && is_array($_POST['dm_slider'])?wp_unslash($_POST['dm_slider']):array();
        $d=self::defaults();$ids=explode(',',is_scalar($input['ids']??null)?(string)$input['ids']:'');
        $ids=array_values(array_unique(array_filter(array_map('absint',$ids),function($id){return $id>0 && wp_attachment_is_image($id);})));$ids=array_slice($ids,0,80);
        $d['ids']=$ids;
        foreach(array('layout'=>array('editorial','gallery','dot-mask'),'direction'=>array('horizontal','vertical'),'scroll_response'=>array('off','gentle','dynamic'),'ratio'=>array('16/9','4/3','1/1','3/2','auto'),'fit'=>array('cover','contain'),'position'=>array('50% 50%','50% 25%','50% 75%','25% 50%','75% 50%')) as $key=>$allowed){$v=$input[$key]??$d[$key];$d[$key]=in_array($v,$allowed,true)?$v:$d[$key];}
        foreach(array('gap'=>array(0,60),'peek'=>array(5,35),'speed'=>array(200,1800),'scroll_strength'=>array(0,150),'smoothness'=>array(0,95),'viewport_height'=>array(35,100),'vertical_slide_height'=>array(25,100),'autoplay_speed'=>array(3,160),'mask_duration'=>array(250,6000),'mask_interval'=>array(1000,15000),'mask_scroll_distance'=>array(40,250),'mask_auto_scroll_strength'=>array(0,100),'mask_velocity_strength'=>array(0,100),'mask_height'=>array(35,100),'mask_smoothing'=>array(0,95),'mask_spacing'=>array(30,100),'container_radius'=>array(0,200),'image_radius'=>array(0,200),'mask_color_handoff'=>array(0,65)) as $key=>$bounds){$d[$key]=max($bounds[0],min($bounds[1],absint($input[$key]??$d[$key])));}
        foreach(array('image_drift','captions','loop','autoplay','pause_hover','allow_drag','mask_velocity') as $key)$d[$key]=!empty($input[$key])?1:0;
        $d['mask_grid']=in_array((string)($input['mask_grid']??$d['mask_grid']),array('1','4','6','8','12'),true)?(string)$input['mask_grid']:'6';
        $d['mask_playback']=in_array(($input['mask_playback']??''),array('scroll','auto'),true)?$input['mask_playback']:'scroll';
        $d['mask_easing']=in_array(($input['mask_easing']??''),array('linear','soft','editorial'),true)?$input['mask_easing']:'editorial';
        $d['mask_direction']=in_array(($input['mask_direction']??''),array('grow','shrink'),true)?$input['mask_direction']:'grow';
        $d['mask_interleave']=in_array(($input['mask_interleave']??''),array('off','between'),true)?$input['mask_interleave']:'off';
        $d['mask_color']=in_array(($input['mask_color']??''),array('section','cream','orange','green','pink','ink'),true)?$input['mask_color']:'section';
        update_post_meta($post_id,self::META,$d);
    }
    public static function frontend_assets() {
        wp_enqueue_style('dm-slider',plugins_url('../assets/slider.css',__FILE__),array(),DURF_Motion::VERSION);
        wp_register_script('dm-slider',plugins_url('../assets/slider.js',__FILE__),array(),DURF_Motion::VERSION,true);
        // Enqueue in wp_head: shortcodes may render after wp_head, too late for a stylesheet.
        wp_enqueue_style('dm-dot-mask',plugins_url('../assets/dot-mask.css',__FILE__),array(),DURF_Motion::VERSION);
        wp_register_script('dm-dot-mask',plugins_url('../assets/dot-mask.js',__FILE__),array(),DURF_Motion::VERSION,true);
    }
    public static function shortcode($atts) {
        $a=shortcode_atts(array('id'=>0),$atts,'durf_slider');$id=absint($a['id']);
        if (!$id || get_post_type($id)!==self::TYPE || get_post_status($id)!=='publish')return '';
        $s=self::settings($id);if(empty($s['ids']))return '';
        if($s['layout']==='dot-mask')return self::mask_shortcode($id,$s);
        wp_enqueue_style('dm-slider');wp_enqueue_script('dm-slider');
        $html='';$found=0;
        foreach($s['ids'] as $attachment_id){
            $src=wp_get_attachment_image_src($attachment_id,'large');if(!$src)continue;
            $alt=get_post_meta($attachment_id,'_wp_attachment_image_alt',true);
            $caption=wp_get_attachment_caption($attachment_id);
            $image=wp_get_attachment_image($attachment_id,'large',false,array('loading'=>'lazy','decoding'=>'async','alt'=>$alt,'sizes'=>'(max-width:767px) 94vw, (max-width:1100px) 75vw, 70vw'));
            if(!$image)continue;
            $html.='<li class="splide__slide"><figure class="dm-slide-frame">'.$image;
            if(!empty($s['captions'])&&$caption)$html.='<figcaption>'.esc_html($caption).'</figcaption>';
            $html.='</figure></li>';
            $found++;
        }
        if(!$found)return '';
        $params=array('layout'=>$s['layout'],'gap'=>(int)$s['gap'],'peek'=>(int)$s['peek'],'speed'=>(int)$s['speed'],'loop'=>!empty($s['loop']),'autoplay'=>!empty($s['autoplay']),'autoplaySpeed'=>(int)$s['autoplay_speed'],'pauseHover'=>!empty($s['pause_hover']),'allowDrag'=>!empty($s['allow_drag']),'direction'=>$s['direction'],'scrollResponse'=>$s['scroll_response'],'scrollStrength'=>(int)$s['scroll_strength'],'smoothness'=>(int)$s['smoothness'],'imageDrift'=>!empty($s['image_drift']));
        $classes='dm-slider dm-slider--'.$s['layout'].' dm-slider--'.$s['direction'];
        $styles='--dm-slider-container-radius:'.(int)$s['container_radius'].'px;--dm-slider-image-radius:'.(int)$s['image_radius'].'px;--dm-slider-gap:'.$s['gap'].'px;--dm-slider-ratio:'.($s['ratio']==='auto'?'auto':$s['ratio']).';--dm-slider-fit:'.$s['fit'].';--dm-slider-position:'.$s['position'].';--dm-slider-peek:'.$s['peek'].'%;--dm-slider-viewport-height:'.$s['viewport_height'].'svh;--dm-slider-vertical-slide-height:'.$s['vertical_slide_height'].'svh;';
        return '<div class="'.esc_attr($classes).' splide" aria-label="'.esc_attr(get_the_title($id)).'" data-dm-slider="'.esc_attr(wp_json_encode($params)).'" style="'.esc_attr($styles).'">'
          .'<div class="splide__track"><ul class="splide__list">'.$html.'</ul></div>'
          .'</div>';
    }

    private static function mask_shortcode($id,$s){
        $sources=array();
        foreach($s['ids'] as $attachment_id){
            $src=wp_get_attachment_image_url($attachment_id,'full');
            if(!$src)continue;
            $sources[]=array('type'=>'image','src'=>$src,'alt'=>(string)get_post_meta($attachment_id,'_wp_attachment_image_alt',true));
        }
        if(count($sources)<2)return '';
        $palette=DURF_Motion::settings();
        $color_key='color_'.$s['mask_color'];
        $color=$s['mask_color']==='section'?'':(isset($palette[$color_key])?sanitize_hex_color($palette[$color_key]):'#F8F4EC');
        // Colour is now a transient accent within each photo-to-photo transition.
        // Do not insert a full-duration colour source into the slideshow timeline.
        wp_enqueue_style('dm-dot-mask');wp_enqueue_script('dm-dot-mask');
        $grid=(int)$s['mask_grid'];$dimensions=array(1=>array(1,1),4=>array(2,2),6=>array(3,2),8=>array(4,2),12=>array(4,3));
        if(!isset($dimensions[$grid]))$grid=6;
        list($cols,$rows)=$dimensions[$grid];
        $uid=function_exists('wp_unique_id')?wp_unique_id('dm-mask-'):uniqid('dm-mask-',false);
        $clips='';
        for($row=0;$row<$rows;$row++)for($col=0;$col<$cols;$col++){
            $cx=($col+0.5)*1000/$cols;$cy=($row+0.5)*1000/$rows;
            $clips.='<circle data-col="'.(int)$col.'" data-row="'.(int)$row.'" cx="'.esc_attr(round($cx,3)).'" cy="'.esc_attr(round($cy,3)).'" r="0"/>';
        }
        $opts=array(
          'playback'=>$s['mask_playback'],'duration'=>(int)$s['mask_duration'],'interval'=>(int)$s['mask_interval'],
          'scrollDistance'=>(int)$s['mask_scroll_distance'],'velocity'=>!empty($s['mask_velocity']),
          'velocityStrength'=>(int)$s['mask_velocity_strength'],'fit'=>$s['fit'],'position'=>$s['position'],
          'pauseHover'=>!empty($s['pause_hover']),'autoScrollStrength'=>(int)$s['mask_auto_scroll_strength'],'columns'=>$cols,'rows'=>$rows,
          'easing'=>$s['mask_easing'],'smoothing'=>(int)$s['mask_smoothing'],'spacing'=>(int)$s['mask_spacing'],
          'imageRadius'=>(int)$s['image_radius'],'matchSection'=>$s['mask_color']==='section',
          'colorInterleave'=>$s['mask_interleave']==='between','accentColor'=>$color,
          'colorHandoff'=>(int)$s['mask_color_handoff'],
          'maskDirection'=>$s['mask_direction']
        );
        $source_html='';
        foreach($sources as $source){
            $type=$source['type'];
            $source_html.='<span class="dm-mask-source" data-type="'.esc_attr($type).'" data-src="'.esc_url($source['src']??'').'" data-color="'.esc_attr($source['color']??'').'" data-alt="'.esc_attr($source['alt']??'').'"></span>';
        }
        $classes='dm-mask-slider '.($s['mask_playback']==='scroll'?'dm-mask--scroll':'dm-mask--auto');
        $scroll_height=100+(count($sources)-1)*(int)$s['mask_scroll_distance'];
        $style='--dm-mask-height:'.(int)$s['mask_height'].'svh;--dm-mask-scroll-height:'.$scroll_height.'svh;--dm-mask-container-radius:'.(int)$s['container_radius'].'px;--dm-mask-background:'.((int)$s['image_radius']>0?'transparent':'#25231f').';';
        $first=$sources[0];
        return '<div class="'.esc_attr($classes).'" data-dm-mask="'.esc_attr(wp_json_encode($opts)).'" style="'.esc_attr($style).'">'
          .$source_html.'<div class="dm-mask-stage"><svg class="dm-mask-art" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" preserveAspectRatio="none" aria-label="'.esc_attr(get_the_title($id)).'" role="img">'
          .'<defs><clipPath class="dm-mask-clip dm-mask-clip-photo" id="'.esc_attr($uid).'-photo" clipPathUnits="userSpaceOnUse">'.$clips.'</clipPath>'
          .'<clipPath class="dm-mask-clip-color" id="'.esc_attr($uid).'-color" clipPathUnits="userSpaceOnUse">'.$clips.'</clipPath>'
          .'<clipPath id="'.esc_attr($uid).'-round" clipPathUnits="userSpaceOnUse"><rect class="dm-mask-rounded-rect" x="0" y="0" width="1000" height="1000" rx="0" ry="0"/></clipPath></defs>'
          .'<g clip-path="url(#'.esc_attr($uid).'-round)">'
          .'<image class="dm-mask-base" href="'.esc_url($first['src']).'" x="0" y="0" width="1000" height="1000" preserveAspectRatio="xMidYMid slice"/>'
          .'<g class="dm-mask-color-layer" clip-path="url(#'.esc_attr($uid).'-color)"><rect class="dm-mask-color-fill" x="0" y="0" width="1000" height="1000" fill="transparent"/></g>'
          .'<g class="dm-mask-incoming-layer" clip-path="url(#'.esc_attr($uid).'-photo)">'
          .'<image class="dm-mask-incoming" href="'.esc_url($sources[1]['src']??'').'" x="0" y="0" width="1000" height="1000" preserveAspectRatio="xMidYMid slice"/>'
          .'</g></g></svg></div></div>';
    }
    public static function columns($cols){$cols['dm_slider_shortcode']='Shortcode';return $cols;}
    public static function column_content($column,$id){if($column==='dm_slider_shortcode')echo '<code>[durf_slider id="'.(int)$id.'"]</code>';}
}
