/* DURF Dots 1.9: keep Mobile numeric overrides scoped to the selected layout.
   Works in standard WPBakery modal; harmless if markup differs. */
(function(){
  'use strict';
  var byLayout={
    horizontal:['count','spacing','size','offset_x','offset_y'],
    vertical:['count','spacing','size','offset_x','offset_y'],
    grid:['grid_cols','grid_rows','grid_gap_x','grid_gap_y','grid_min','grid_max','grid_overlap','size','offset_x','offset_y'],
    repeater:['repeat_count','copies','copy_spacing','copy_min_scale','repeat_overlap','copy_shift','spacing','size','offset_x','offset_y']
  };
  function update(){
    document.querySelectorAll('.vc_ui-panel-window, .vc_ui-panel, .vc_shortcode-edit-form').forEach(function(panel){
      var select=panel.querySelector('[data-vc-shortcode-param-name="direction"] select, select[name="direction"], select.wpb_vc_param_value.direction');
      if(!select)return;
      var mode=byLayout[select.value]?select.value:'horizontal';
      panel.querySelectorAll('[data-vc-shortcode-param-name^="mobile_"]').forEach(function(field){
        var key=(field.getAttribute('data-vc-shortcode-param-name')||'').replace(/^mobile_/, '');
        field.classList.toggle('dm-mobile-not-relevant',!byLayout[mode].includes(key) && key!=='width' && key!=='height');
      });
    });
  }
  var queued=false;
  function schedule(){if(queued)return;queued=true;requestAnimationFrame(function(){queued=false;update()});}
  document.addEventListener('change',schedule,true);
  document.addEventListener('click',schedule,true);
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
  function start(){
    var style=document.createElement('style');
    style.textContent='.vc_shortcode-param.dm-mobile-not-relevant{display:none!important}';
    document.head.appendChild(style);
    var observer=new MutationObserver(schedule);
    observer.observe(document.body,{childList:true,subtree:true});
    schedule();
  }
})();
