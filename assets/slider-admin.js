(function($){'use strict';$(function(){var list=$('#dm-slider-images'),field=$('#dm-slider-ids');if(!list.length)return;
 function saveOrder() {
   field.val(list.children('.dm-slider-image').map(function () {
     return this.getAttribute('data-id');
   }).get().join(','));
 }

 function addCard(id, attachment) {
   if (list.children('[data-id="' + id + '"]').length) return;

   var card = $('<div class="dm-slider-image"/>').attr('data-id', id);
   var image = $('<img alt="" draggable="false"/>').appendTo(card);
   var buttons = $('<div class="dm-slider-image-actions"/>').appendTo(card);
   [['←', 'up', 'Move earlier'], ['→', 'down', 'Move later'], ['×', 'remove', 'Remove image']].forEach(function (parts) {
     $('<button type="button" class="button button-small"/>').text(parts[0])
       .attr({'data-action': parts[1], 'aria-label': parts[2]}).appendTo(buttons);
   });
   list.append(card);

   function setThumbnail(model) {
     var sizes = model.get('sizes') || {};
     var thumbnail = sizes.thumbnail || sizes.medium;
     var url = thumbnail ? thumbnail.url : model.get('url');
     if (url) image.attr('src', url);
   }

   if (attachment) {
     setThumbnail(attachment);
   } else {
     var model = wp.media.attachment(id);
     if (model.get('url')) setThumbnail(model);
     else model.fetch().done(function () { setThumbnail(model); });
   }
 }

 (field.val() || '').split(',').map(Number).filter(Boolean).forEach(function (id) {
   addCard(id);
 });

 list.sortable({
   items: '> .dm-slider-image',
   cancel: 'button',
   placeholder: 'dm-slider-image-placeholder',
   forcePlaceholderSize: true,
   tolerance: 'pointer',
   update: saveOrder
 });

 $('#dm-slider-add').on('click', function () {
   var frame = wp.media({title:'Choose slider images', button:{text:'Use these images'}, library:{type:'image'}, multiple:true});
   frame.on('select', function () {
     frame.state().get('selection').each(function (model) {
       addCard(model.id, model);
     });
     saveOrder();
   });
   frame.open();
 });

 list.on('click', 'button[data-action]', function () {
   var card = $(this).closest('.dm-slider-image');
   var action = $(this).data('action');
   if (action === 'remove') {
     card.remove();
   } else {
     var earlier = action === 'up';
     var neighbor = earlier ? card.prev() : card.next();
     if (!neighbor.length) return;
     if (earlier) neighbor.insertAfter(card);
     else neighbor.insertBefore(card);
   }
   saveOrder();
 });

 function setting(key) {
   return $('[name="dm_slider[' + key + ']"]');
 }

 function updateSliderSettings() {
   var layout = setting('layout').val();
   var mask = layout === 'dot-mask';
   var horizontal = setting('direction').val() === 'horizontal';
   var original = setting('ratio').val() === 'auto';
   var naturalGallery = horizontal && layout === 'gallery' && original;
   var automatic = setting('mask_playback').val() === 'auto';
   var autoplay = setting('autoplay').is(':checked');
   var fit = setting('fit').val();
   var colourInterleave = setting('mask_interleave').val() === 'between';

   $('.dm-slider-regular').toggle(!mask);
   $('.dm-slider-mask-settings').toggle(mask);

   var visible = {
     fit: !naturalGallery,
     position: mask || !horizontal || !original,
     pause_hover: mask ? automatic : autoplay,
     ratio: horizontal,
     peek: horizontal && layout === 'editorial',
     viewport_height: !horizontal,
     vertical_slide_height: !horizontal,
     natural_gallery_height: naturalGallery,
     scroll_strength: setting('scroll_response').val() !== 'off',
     autoplay_speed: autoplay,
     image_drift: !naturalGallery && fit === 'cover',
     mask_interval: automatic,
     mask_duration: automatic,
     mask_height: automatic,
     mask_auto_scroll_strength: automatic,
     mask_scroll_height: !automatic,
     mask_scroll_distance: !automatic,
     mask_velocity: !automatic,
     mask_smoothing: !automatic,
     mask_velocity_strength: !automatic && setting('mask_velocity').is(':checked'),
     mask_spacing: setting('mask_grid').val() !== '1',
     mask_color: colourInterleave,
     mask_color_handoff: colourInterleave
   };
   Object.keys(visible).forEach(function (key) {
     setting(key).closest('label').toggle(visible[key]);
   });
 }

 list.closest('#post').on('change', 'select[name^="dm_slider["], input[type="checkbox"][name^="dm_slider["]', updateSliderSettings);
 updateSliderSettings();
 });})(jQuery);
