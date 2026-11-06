/* DURF Motion — layered circular photo transitions, reversible scroll + autoplay. */
(function(){
  'use strict';
  const clamp=(v,a,b)=>Math.min(b,Math.max(a,v));
  const easing={
    linear:t=>t,
    soft:t=>t*t*(3-2*t),
    editorial:t=>t<.5?4*t*t*t:1-Math.pow(2-2*t,3)/2
  };
  function init(root){
    if(root.dataset.dmMaskReady==='1')return;
    let o={};try{o=JSON.parse(root.getAttribute('data-dm-mask')||'{}')}catch(e){}
    const stage=root.querySelector('.dm-mask-stage');
    const svg=root.querySelector('.dm-mask-art');
    const base=root.querySelector('.dm-mask-base');
    const photo=root.querySelector('.dm-mask-incoming');
    const photoLayer=root.querySelector('.dm-mask-incoming-layer');
    const colourLayer=root.querySelector('.dm-mask-color-layer');
    const colourRect=root.querySelector('.dm-mask-color-fill');
    const rounded=root.querySelector('.dm-mask-rounded-rect');
    const photoDots=Array.from(root.querySelectorAll('clipPath.dm-mask-clip-photo circle'));
    const colourDots=Array.from(root.querySelectorAll('clipPath.dm-mask-clip-color circle'));
    const sources=Array.from(root.querySelectorAll('.dm-mask-source'));
    if(!stage||!svg||!base||!photo||!photoLayer||!colourLayer||!colourRect||!rounded||!photoDots.length||photoDots.length!==colourDots.length||sources.length<2)return;
    root.dataset.dmMaskReady='1';
    const reduced=!!(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    const count=sources.length;
    const scrollMode=o.playback==='scroll';
    const reverse=o.maskDirection==='shrink';
    const withColour=Boolean(o.colorInterleave);
    const delay=clamp(Number(o.colorHandoff)||0,0,65)/100;
    const cols=Math.max(1,Number(o.columns)||1),rows=Math.max(1,Number(o.rows)||1);
    const spacing=clamp(Number(o.spacing)||85,30,100)/100;
    const imageRadius=clamp(Number(o.imageRadius)||0,0,200);
    const interval=clamp(Number(o.interval)||3500,1000,15000);
    const duration=clamp(Number(o.duration)||1100,250,6000);
    const ease=easing[o.easing]||easing.soft;
    const smoothing=clamp(Number(o.smoothing)||0,0,95)/100;
    const velocityFactor=o.velocity?clamp(Number(o.velocityStrength)||35,0,100)/100:0;
    const focal=String(o.position||'50% 50%').split(' ').map(Number);
    const x=(focal[0]||50)<40?'xMin':(focal[0]||50)>60?'xMax':'xMid';
    const y=(focal[1]||50)<40?'YMin':(focal[1]||50)>60?'YMax':'YMid';
    const aspect=x+y+(o.fit==='contain'?' meet':' slice');
    [base,photo].forEach(img=>img.setAttribute('preserveAspectRatio',aspect));

    // Load the next photographs before their transitions become visible.
    const preload=[];
    sources.forEach(item=>{
      if(item.dataset.type==='color')return;
      const image=new Image();image.decoding='async';image.src=item.dataset.src||'';preload.push(image);
    });
    const entry=i=>sources[((i%count)+count)%count];
    const source=i=>entry(i).dataset.src||'';
    const alt=i=>entry(i).dataset.alt||'';

    function getSectionColour(){
      let elem=root.closest('.vc_row')||root.parentElement;
      while(elem&&elem!==document.documentElement){
        const c=getComputedStyle(elem).backgroundColor;
        if(c&&c!=='transparent'&&c!=='rgba(0, 0, 0, 0)')return c;
        elem=elem.parentElement;
      }
      return '#F8F4EC';
    }
    function colour(){return o.accentColor||getSectionColour()}
    function stickyCompanion(){
      if(!scrollMode||!o.stickyText)return;
      let own=root.closest('.wpb_column,.vc_column_container');
      if(!own)return;
      const row=own.parentElement;
      if(!row||!row.classList.contains('vc_row'))return;
      const companions=Array.from(row.children).filter(el=>el!==own&&el.matches('.wpb_column,.vc_column_container'));
      const companion=companions.find(col=>col.querySelector('.vc_column-inner > .wpb_wrapper'));
      if(!companion)return;
      row.classList.add('dm-mask-story-row');
      companion.classList.add('dm-mask-sticky-companion');
      companion.style.setProperty('--dm-mask-text-top',clamp(Number(o.stickyOffset)||0,0,300)+'px');
      // A sticky element needs a tall parent. Stretch the column and its inner
      // wrapper to the height of the scroll-driven slider column.
      companion.style.alignSelf='stretch';
    }

    let fullRadius=1200;
    let pairFrom=-1,pairTo=-1,lastPhotoRadius=-1,lastColourRadius=-1;
    let target=0,current=0,velocity=0,prevScroll=window.scrollY,prevScrollTime=performance.now();
    let autoTime=0,paused=false,lastFrame=performance.now(),raf=0;
    function setRadii(nodes,r,key){
      if(key==='photo'&&Math.abs(lastPhotoRadius-r)<.02)return;
      if(key==='colour'&&Math.abs(lastColourRadius-r)<.02)return;
      nodes.forEach(node=>node.setAttribute('r',String(r)));
      if(key==='photo')lastPhotoRadius=r;else lastColourRadius=r;
    }
    function resize(){
      const w=Math.max(1,stage.clientWidth),h=Math.max(1,stage.clientHeight);
      const vbWidth=1000,vbHeight=1000*h/w;
      svg.setAttribute('viewBox','0 0 '+vbWidth+' '+vbHeight);
      [base,photo].forEach(img=>{img.setAttribute('width',String(vbWidth));img.setAttribute('height',String(vbHeight))});
      [colourRect,rounded].forEach(rect=>{rect.setAttribute('width',String(vbWidth));rect.setAttribute('height',String(vbHeight))});
      const r=imageRadius*vbWidth/w;
      rounded.setAttribute('rx',String(r));rounded.setAttribute('ry',String(r));
      const pitch=Math.min(vbWidth/cols,vbHeight/rows)*spacing;
      const left=vbWidth/2-(cols-1)*pitch/2;
      const top=vbHeight/2-(rows-1)*pitch/2;
      [photoDots,colourDots].forEach(nodes=>nodes.forEach(node=>{
        const col=Number(node.dataset.col)||0,row=Number(node.dataset.row)||0;
        node.setAttribute('cx',String(left+col*pitch));
        node.setAttribute('cy',String(top+row*pitch));
      }));
      const right=left+(cols-1)*pitch,bottom=top+(rows-1)*pitch;
      const xGap=Math.max(left,vbWidth-right,pitch/2);
      const yGap=Math.max(top,vbHeight-bottom,pitch/2);
      fullRadius=Math.hypot(xGap,yGap)*1.035;
      lastPhotoRadius=lastColourRadius=-1;
    }
    function setPair(i,j){
      if(pairFrom===i&&pairTo===j)return;
      pairFrom=i;pairTo=j;
      // In shrink mode the next photograph is already the base; the old
      // photograph contracts away above it. In grow mode the next expands in.
      base.setAttribute('href',source(reverse?j:i));
      photo.setAttribute('href',source(reverse?i:j));
      root.setAttribute('aria-label',alt(i)||'Photographic slideshow');
      colourRect.setAttribute('fill',withColour?colour():'transparent');
      colourLayer.setAttribute('opacity',withColour?'1':'0');
      photoLayer.setAttribute('opacity','1');
      lastPhotoRadius=lastColourRadius=-1;
    }
    function mask(t){
      const progress=clamp(t,0,1);
      let photoProgress,colourProgress;
      if(withColour){
        // Two independent circular fields. In grow mode colour leads and the
        // next image follows. In shrink mode the outgoing photo contracts first,
        // revealing colour, then colour contracts to reveal the next photo.
        if(reverse){
          photoProgress=clamp(progress/Math.max(.01,1-delay),0,1);
          colourProgress=clamp((progress-delay)/Math.max(.01,1-delay),0,1);
        }else{
          colourProgress=clamp(progress/Math.max(.01,1-delay*.30),0,1);
          photoProgress=clamp((progress-delay)/Math.max(.01,1-delay),0,1);
        }
      }else{
        photoProgress=progress;colourProgress=0;
      }
      const photoSize=reduced?(photoProgress>=.5?1:0):ease(photoProgress);
      const colourSize=reduced?(colourProgress>=.5?1:0):ease(colourProgress);
      setRadii(photoDots,fullRadius*(reverse?1-photoSize:photoSize),'photo');
      if(withColour)setRadii(colourDots,fullRadius*(reverse?1-colourSize:colourSize),'colour');
    }
    function render(total,loop){
      if(!loop&&total>=count-1){
        // Stable final state, without a blank frame.
        pairFrom=pairTo=-1;
        base.setAttribute('href',source(count-1));
        photoLayer.setAttribute('opacity','0');
        colourLayer.setAttribute('opacity','0');
        return;
      }
      const idx=Math.max(0,Math.floor(total));
      const t=clamp(total-idx,0,1);
      const i=loop?idx%count:Math.min(idx,count-2);
      setPair(i,(i+1)%count);
      mask(t);
    }
    function scrollPosition(){
      const rect=root.getBoundingClientRect();
      const available=Math.max(1,rect.height-window.innerHeight);
      return clamp(-rect.top/available,0,1)*(count-1);
    }
    function onScroll(){
      const now=performance.now();
      velocity=clamp((window.scrollY-prevScroll)/Math.max(16,now-prevScrollTime),-3,3);
      prevScroll=window.scrollY;prevScrollTime=now;
      target=scrollPosition();
    }
    function frame(now){
      const dt=Math.min(64,Math.max(0,now-lastFrame));lastFrame=now;
      if(scrollMode){
        target=scrollPosition();
        const alpha=smoothing<=0?1:1-Math.exp(-dt/(20+smoothing*540));
        current+=(target-current)*alpha;
        if(Math.abs(target-current)<.0001)current=target;
        velocity*=Math.exp(-dt/145);
        let display=current;
        if(velocityFactor&&!reduced){
          const index=Math.floor(display),fraction=display-index;
          if(fraction>.001&&fraction<.999&&index<count-1){
            const boost=clamp(velocity*velocityFactor*.045,-.085,.085);
            display=index+clamp(fraction+boost,.001,.999);
          }
        }
        render(display,false);
      }else{
        if(!paused&&!reduced)autoTime+=dt;
        const cycle=interval+duration;
        const index=Math.floor(autoTime/cycle);
        const within=autoTime%cycle;
        render(index+clamp((within-interval)/duration,0,1),true);
      }
      raf=requestAnimationFrame(frame);
    }
    resize();stickyCompanion();
    if(scrollMode){target=current=scrollPosition();window.addEventListener('scroll',onScroll,{passive:true})}
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
