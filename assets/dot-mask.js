/* DURF Motion — circular photo reveal, position-led and reversible. */
(function(){
  'use strict';
  const clamp=(v,min,max)=>Math.min(max,Math.max(min,v));
  const reduced=window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const easing={
    linear:t=>t,
    soft:t=>t*t*(3-2*t),
    editorial:t=>t<.5?4*t*t*t:1-Math.pow(-2*t+2,3)/2
  };

  function init(root){
    if(root.dataset.dmMaskReady==='1')return;
    let o={};try{o=JSON.parse(root.getAttribute('data-dm-mask')||'{}')}catch(e){}
    const stage=root.querySelector('.dm-mask-stage');
    const svg=root.querySelector('.dm-mask-art');
    const base=root.querySelector('.dm-mask-base');
    const incoming=root.querySelector('.dm-mask-incoming');
    const incomingLayer=root.querySelector('.dm-mask-incoming-layer');
    const baseFill=root.querySelector('.dm-mask-base-fill');
    const incomingFill=root.querySelector('.dm-mask-incoming-fill');
    const rounded=root.querySelector('.dm-mask-rounded-rect');
    const nodes=Array.from(root.querySelectorAll('clipPath.dm-mask-clip circle'));
    const sources=Array.from(root.querySelectorAll('.dm-mask-source'));
    const imageRadius=clamp(Number(o.imageRadius)||0,0,200);
    if(!stage||!svg||!base||!incoming||!incomingLayer||!baseFill||!incomingFill||!rounded||!nodes.length||sources.length<2)return;
    root.dataset.dmMaskReady='1';

    const count=sources.length;
    const scrollMode=o.playback==='scroll';
    const columns=Math.max(1,Number(o.columns)||3);
    const rows=Math.max(1,Number(o.rows)||2);
    const spacing=clamp(Number(o.spacing)||85,30,100)/100;
    const duration=clamp(Number(o.duration)||1100,250,6000);
    const interval=clamp(Number(o.interval)||3500,1000,15000);
    const easingFn=easing[o.easing]||easing.soft;
    const smoothness=clamp(Number(o.smoothing)||0,0,95)/100;
    const velocityFactor=o.velocity?clamp(Number(o.velocityStrength)||35,0,100)/100:0;
    const focal=String(o.position||'50% 50%').split(' ').map(Number);
    const x=(focal[0]||50)<40?'xMin':(focal[0]||50)>60?'xMax':'xMid';
    const y=(focal[1]||50)<40?'YMin':(focal[1]||50)>60?'YMax':'YMid';
    const aspect=x+y+(o.fit==='contain'?' meet':' slice');
    base.setAttribute('preserveAspectRatio',aspect);
    incoming.setAttribute('preserveAspectRatio',aspect);

    // Keep images warm and allow solid palette frames between photographs.
    const preload=[];
    sources.forEach(item=>{
      if(item.dataset.type==='color')return;
      const img=new Image();img.decoding='async';img.src=item.dataset.src||'';preload.push(img);
    });
    function entry(i){return sources[(i+count)%count]}
    function sectionBackground(){
      let item=root.closest('.vc_row')||root.parentElement;
      while(item&&item!==document.documentElement){
        const color=getComputedStyle(item).backgroundColor;
        if(color && !/^transparent$|^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*0(?:\.0+)?\s*\)$/i.test(color)&& color!=='rgba(0, 0, 0, 0)')return color;
        item=item.parentElement;
      }
      return '#F8F4EC';
    }
    function setVisual(image,rect,i){
      const item=entry(i),isColor=item.dataset.type==='color';
      if(isColor){
        rect.setAttribute('fill',item.dataset.color||sectionBackground());
        image.setAttribute('opacity','0');
        image.removeAttribute('href');
      }else{
        rect.setAttribute('fill','transparent');
        image.setAttribute('href',item.dataset.src||'');
        image.setAttribute('opacity','1');
      }
    }
    const alt=i=>entry(i).dataset.alt||'';
    function stickyCompanion(){
      if(!scrollMode||!o.stickyText)return;
      const row=root.closest('.vc_row');
      const own=root.closest('.wpb_column,.vc_column_container');
      if(!row||!own)return;
      const columns=Array.from(row.children).filter(el=>el.matches('.wpb_column,.vc_column_container'));
      const companion=columns.find(col=>col!==own&&!col.contains(root));
      if(!companion||!companion.querySelector('.vc_column-inner'))return;
      row.classList.add('dm-mask-story-row');
      companion.classList.add('dm-mask-sticky-companion');
      companion.style.setProperty('--dm-mask-text-top',clamp(Number(o.stickyOffset)||0,0,300)+'px');
    }

    let width=1000,height=1000,fullRadius=1200;
    let from=-1,to=-1,lastRadius=-1,lastOpacity=-1;
    let scrollTarget=0,scrollCurrent=0,velocity=0,previousY=window.scrollY,previousTime=performance.now();
    let autoTime=0,paused=false,lastFrame=performance.now(),raf=0;

    function resize(){
      const w=Math.max(1,stage.clientWidth),h=Math.max(1,stage.clientHeight);
      width=1000;height=1000*h/w;
      svg.setAttribute('viewBox','0 0 '+width+' '+height);
      base.setAttribute('width',String(width));
      incoming.setAttribute('width',String(width));
      base.setAttribute('height',String(height));
      incoming.setAttribute('height',String(height));

      // Uniform X/Y pitch in actual rendered pixels, centred in both axes.
      const pitch=Math.min(width/columns,height/rows)*spacing;
      const x0=width/2-(columns-1)*pitch/2;
      const y0=height/2-(rows-1)*pitch/2;
      const xs=[],ys=[];
      for(let c=0;c<columns;c++)xs.push(x0+c*pitch);
      for(let r=0;r<rows;r++)ys.push(y0+r*pitch);
      [baseFill,incomingFill,rounded].forEach(rect=>{rect.setAttribute('width',String(width));rect.setAttribute('height',String(height))});
      const rx=imageRadius*width/w;
      rounded.setAttribute('rx',String(rx));rounded.setAttribute('ry',String(rx));
      nodes.forEach(circle=>{
        const col=Number(circle.dataset.col)||0,row=Number(circle.dataset.row)||0;
        circle.setAttribute('cx',String(xs[col]));
        circle.setAttribute('cy',String(ys[row]));
      });
      // Calculate the largest distance to a nearest dot, including frame edges.
      const gapX=Math.max(xs[0],width-xs[xs.length-1],pitch/2);
      const gapY=Math.max(ys[0],height-ys[ys.length-1],pitch/2);
      fullRadius=Math.hypot(gapX,gapY)*1.025;
      lastRadius=-1;
    }

    function pair(i,j){
      if(from===i&&to===j)return;
      from=i;to=j;
      setVisual(base,baseFill,i);
      setVisual(incoming,incomingFill,j);
      root.setAttribute('aria-label',alt(i)||'Photographic slideshow');
      lastRadius=-1;
    }
    function opacity(value){
      if(value===lastOpacity)return;
      incomingLayer.setAttribute('opacity',String(value));lastOpacity=value;
    }
    function mask(t){
      const p=easingFn(clamp(t,0,1));
      const radius=reduced?(p>=.5?fullRadius:0):Math.min(fullRadius,fullRadius*p);
      if(Math.abs(lastRadius-radius)>.03){
        nodes.forEach(node=>node.setAttribute('r',String(radius)));
        lastRadius=radius;
      }
    }
    function render(total,loop){
      if(!loop&&total>=count-1){
        // The last image remains fully visible. Never remove the base.
        pair(count-1,count-1);
        opacity(0);mask(0);return;
      }
      const index=Math.max(0,Math.floor(total));
      const t=clamp(total-index,0,1);
      const i=loop?index%count:Math.min(index,count-2);
      pair(i,(i+1)%count);
      opacity(1);
      mask(t);
    }
    function scrollPosition(){
      const rect=root.getBoundingClientRect();
      const distance=Math.max(1,rect.height-window.innerHeight);
      return clamp(-rect.top/distance,0,1)*(count-1);
    }
    function onScroll(){
      const now=performance.now();
      const delta=window.scrollY-previousY;
      velocity=clamp(delta/Math.max(16,now-previousTime),-3,3);
      previousY=window.scrollY;previousTime=now;
      scrollTarget=scrollPosition();
    }
    function frame(now){
      const dt=Math.min(64,Math.max(0,now-lastFrame));
      lastFrame=now;
      if(scrollMode){
        scrollTarget=scrollPosition();
        // Frame-rate independent smoothing; a strong but finite lag.
        const alpha=smoothness<=0?1:1-Math.exp(-dt/(20+smoothness*540));
        scrollCurrent+= (scrollTarget-scrollCurrent)*alpha;
        if(Math.abs(scrollTarget-scrollCurrent)<.0001)scrollCurrent=scrollTarget;
        velocity*=Math.exp(-dt/145);
        let display=scrollCurrent;
        if(velocityFactor&&!reduced){
          // Limit the boost to the current transition; cannot skip a slide.
          const baseIndex=Math.floor(display),fraction=display-baseIndex;
          if(fraction>.001&&fraction<.999&&baseIndex<count-1){
            const boost=clamp(velocity*velocityFactor*.045,-.085,.085);
            display=baseIndex+clamp(fraction+boost,.001,.999);
          }
        }
        render(display,false);
      }else{
        if(!paused&&!reduced)autoTime+=dt;
        const cycle=interval+duration;
        const step=Math.floor(autoTime/cycle);
        const within=autoTime%cycle;
        // Automatic mode transitions last -> first seamlessly.
        render(step+clamp((within-interval)/duration,0,1),true);
      }
      raf=requestAnimationFrame(frame);
    }
    resize();
    stickyCompanion();
    if(scrollMode){scrollTarget=scrollCurrent=scrollPosition();window.addEventListener('scroll',onScroll,{passive:true});}
    if(typeof ResizeObserver==='function')new ResizeObserver(resize).observe(stage);
    else window.addEventListener('resize',resize,{passive:true});
    if(o.pauseHover&&!scrollMode){
      stage.addEventListener('pointerenter',()=>{paused=true});
      stage.addEventListener('pointerleave',()=>{paused=false});
    }
    document.addEventListener('visibilitychange',()=>{if(!scrollMode)paused=document.hidden});
    raf=requestAnimationFrame(frame);
    window.addEventListener('pagehide',()=>cancelAnimationFrame(raf),{once:true});
  }
  function boot(){document.querySelectorAll('.dm-mask-slider[data-dm-mask]').forEach(init)}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
  window.DURFMaskSlider={refresh:boot};
})();
