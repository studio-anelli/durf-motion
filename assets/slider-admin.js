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

 function updateSliderSettings(){
   var mask=$('select[name="dm_slider[layout]"]').val()==='dot-mask';
   $('.dm-slider-regular').toggle(!mask);
   $('.dm-slider-mask-settings').toggle(mask);
   var auto=$('select[name="dm_slider[mask_playback]"]').val()==='auto';
   var velocity=$('input[name="dm_slider[mask_velocity]"]').is(':checked');
   ['mask_interval','mask_duration','mask_height','pause_hover','mask_auto_scroll_strength'].forEach(function(key){
     $('.dm-slider-mask-settings [name="dm_slider['+key+']"]').closest('label').toggle(!mask||auto);
   });
   ['mask_scroll_height','mask_scroll_distance','mask_velocity','mask_smoothing'].forEach(function(key){
     $('.dm-slider-mask-settings [name="dm_slider['+key+']"]').closest('label').toggle(!auto);
   });
   $('.dm-slider-mask-settings [name="dm_slider[mask_velocity_strength]"]').closest('label').toggle(!auto&&velocity);
   var interleave=$('select[name="dm_slider[mask_interleave]"]').val()==='between';
   ['mask_color','mask_color_handoff'].forEach(function(key){
     $('.dm-slider-mask-settings [name="dm_slider['+key+']"]').closest('label').toggle(interleave);
   });
 }
 $(document).on('change','select[name="dm_slider[layout]"],select[name="dm_slider[mask_playback]"],input[name="dm_slider[mask_velocity]"],select[name="dm_slider[mask_interleave]"]',updateSliderSettings);
 updateSliderSettings();
 });})(jQuery);
