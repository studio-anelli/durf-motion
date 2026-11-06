(function($){'use strict';$(function(){var list=$('#dm-slider-images'),field=$('#dm-slider-ids');if(!list.length)return;
 var ids=(field.val()||'').split(',').map(Number).filter(Boolean);
 function save(){field.val(ids.join(','));}
 function card(id,attachment){var u=attachment&&attachment.url||'';if(attachment&&attachment.sizes)u=(attachment.sizes.thumbnail||attachment.sizes.medium||attachment).url||u;
  var el=$('<div class="dm-slider-image"/>').attr('data-id',id);el.append($('<img alt=""/>').attr('src',u));
  var btns=$('<div class="dm-slider-image-actions"/>');
  [['↑','up','Move left'],['↓','down','Move right'],['×','remove','Remove image']].forEach(function(parts){btns.append($('<button type="button" class="button button-small"/>').text(parts[0]).attr('data-action',parts[1]).attr('aria-label',parts[2]));});el.append(btns);list.append(el);
 }
 function render(){list.empty();ids.forEach(function(id){var el=$('<div class="dm-slider-image"/>').attr('data-id',id).append('<div style="height:100px">Loading…</div>');list.append(el);var a=wp.media.attachment(id);a.fetch().done(function(){var u=a.get('sizes')&&a.get('sizes').thumbnail?a.get('sizes').thumbnail.url:a.get('url');el.empty();el.append($('<img alt=""/>').attr('src',u||''));var btns=$('<div class="dm-slider-image-actions"/>');[['↑','up','Move left'],['↓','down','Move right'],['×','remove','Remove image']].forEach(function(parts){btns.append($('<button type="button" class="button button-small"/>').text(parts[0]).attr('data-action',parts[1]).attr('aria-label',parts[2]));});el.append(btns);});});save();}
 $('#dm-slider-add').on('click',function(){var frame=wp.media({title:'Choose slider images',button:{text:'Use these images'},library:{type:'image'},multiple:true});frame.on('select',function(){frame.state().get('selection').each(function(m){if(ids.indexOf(m.id)===-1)ids.push(m.id);});render();});frame.open();});
 function updateSliderSettings(){
   var mask=$('select[name="dm_slider[layout]"]').val()==='dot-mask';
   $('.dm-slider-regular').toggle(!mask);
   $('.dm-slider-mask-settings').toggle(mask);
   var auto=$('select[name="dm_slider[mask_playback]"]').val()==='auto';
   var velocity=$('input[name="dm_slider[mask_velocity]"]').is(':checked');
   ['mask_interval','mask_duration','mask_height','pause_hover'].forEach(function(key){
     $('.dm-slider-mask-settings [name="dm_slider['+key+']"]').closest('label').toggle(!mask||auto);
   });
   ['mask_scroll_distance','mask_velocity','mask_smoothing'].forEach(function(key){
     $('.dm-slider-mask-settings [name="dm_slider['+key+']"]').closest('label').toggle(!auto);
   });
   $('.dm-slider-mask-settings [name="dm_slider[mask_velocity_strength]"]').closest('label').toggle(!auto&&velocity);
   var sticky=$('input[name="dm_slider[mask_sticky_text]"]').is(':checked');
   $('.dm-slider-mask-settings [name="dm_slider[mask_sticky_text]"]').closest('label').toggle(!auto);
   $('.dm-slider-mask-settings [name="dm_slider[mask_sticky_offset]"]').closest('label').toggle(!auto&&sticky);
   var interleave=$('select[name="dm_slider[mask_interleave]"]').val()==='between';
   $('.dm-slider-mask-settings [name="dm_slider[mask_color]"]').closest('label').toggle(interleave);
 }
 $(document).on('change','select[name="dm_slider[layout]"],select[name="dm_slider[mask_playback]"],input[name="dm_slider[mask_velocity]"],input[name="dm_slider[mask_sticky_text]"],select[name="dm_slider[mask_interleave]"]',updateSliderSettings);
 updateSliderSettings();
 list.on('click','button',function(){var card=$(this).closest('[data-id]'),idx=ids.indexOf(Number(card.attr('data-id'))),action=$(this).data('action');if(idx<0)return;if(action==='remove')ids.splice(idx,1);if(action==='up'&&idx>0){var t=ids[idx-1];ids[idx-1]=ids[idx];ids[idx]=t;}if(action==='down'&&idx<ids.length-1){var v=ids[idx+1];ids[idx+1]=ids[idx];ids[idx]=v;}render();});render();
 });})(jQuery);
