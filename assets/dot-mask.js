/* DURF Motion — simultaneous expanding-circle photo reveal. */
(function(){
  'use strict';
  const clamp=(n,min,max)=>Math.max(min,Math.min(max,n));
  const reduced=window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const layouts={1:[1,1],4:[2,2],6:[3,2],8:[4,2],12:[4,3]};
  const ease=t=>t*t*(3-2*t);

  function init(root){
    if(root.dataset.dmMaskReady)return;
    let opts;try{opts=JSON.parse(root.getAttribute('data-dm-mask')||'{}')}catch(e){return}
    const sources=Array.from(root.querySelectorAll('.dm-mask-source'));
    const stage=root.querySelector('.dm-mask-stage');
    const base=root.querySelector('.dm-mask-base');
    const incoming=root.querySelector('.dm-mask-incoming');
    const circles=Array.from(root.querySelectorAll('.dm-mask-clip circle'));
    if(sources.length<2||!stage||!base||!incoming||!circles.length)return;
    root.dataset.dmMaskReady='1';
    const scrollMode=opts.playback==='scroll';
    const count=sources.length;
    let radiusEnd=1600;
    let canvasHeight=1000;
    const duration=clamp(Number(opts.duration)||1100,250,6000);
    const interval=clamp(Number(opts.interval)||3500,1000,15000);
    const velocityInfluence=opts.velocity?clamp(Number(opts.velocityStrength)||35,0,100)/100:0;
    const imageFit=opts.fit==='contain'?'xMidYMid meet':'xMidYMid slice';
    const focal=String(opts.position||'50% 50%').split(' ').map(x=>parseFloat(x));
    function aspect(){
      const x=(focal[0]||50)<40?'xMin':(focal[0]||50)>60?'xMax':'xMid';
      const y=(focal[1]||50)<40?'YMin':(focal[1]||50)>60?'YMax':'YMid';
      return x+y+(opts.fit==='contain'?' meet':' slice');
    }
    const preserve=aspect();
    base.setAttribute('preserveAspectRatio',preserve);
    incoming.setAttribute('preserveAspectRatio',preserve);
    let from=-1,to=-1,phase=-1,lastIndex=-1,velocity=0,lastPageY=window.scrollY,lastTime=performance.now();
    let autoStart=performance.now(),paused=false,raf=0;
    function image(index){return sources[((index%count)+count)%count].getAttribute('data-src')||''}
    function alt(index){return sources[((index%count)+count)%count].getAttribute('data-alt')||''}
    function setPair(i,j){
      if(i===from&&j===to)return;
      from=i;to=j;phase=-1;
      base.setAttribute('href',image(i));
      incoming.setAttribute('href',image(j));
      root.setAttribute('aria-label',alt(i)||'Photo slider');
    }
    function resizeMask(){
      const w=Math.max(1,stage.clientWidth),h=Math.max(1,stage.clientHeight);
      canvasHeight=1000*h/w;
      radiusEnd=Math.hypot(1000,canvasHeight)*1.2;
      const art=root.querySelector('.dm-mask-art');
      if(art)art.setAttribute('viewBox','0 0 1000 '+canvasHeight);
      [base,incoming].forEach(image=>{image.setAttribute('height',String(canvasHeight));image.setAttribute('width','1000')});
      circles.forEach(circle=>{
        const col=Number(circle.getAttribute('data-col'));
        const row=Number(circle.getAttribute('data-row'));
        circle.setAttribute('cx',String((col+.5)*1000/Math.max(1,Number(opts.columns)||1)));
        circle.setAttribute('cy',String((row+.5)*canvasHeight/Math.max(1,Number(opts.rows)||1)));
      });
      phase=-1;
    }
    function showMask(progress){
      if(reduced){incoming.setAttribute('opacity',progress>=0.5?'1':'0');return}
      incoming.setAttribute('opacity','1');
      const eased=ease(clamp(progress,0,1));
      if(Math.abs(phase-eased)<0.0005)return;
      phase=eased;
      const r=eased*radiusEnd;
      circles.forEach(circle=>circle.setAttribute('r',String(r)));
    }
    function renderIndex(index,t){
      index=clamp(index,0,count-1);
      if(index>=count-1){setPair(count-1,count-1);showMask(0);incoming.setAttribute('opacity','0');lastIndex=count-1;return}
      setPair(index,index+1);
      if(lastIndex===count-1)phase=-1;
      lastIndex=index;
      showMask(t);
    }
    function scrollProgress(){
      const rect=root.getBoundingClientRect();
      const available=Math.max(1,rect.height-window.innerHeight);
      return clamp(-rect.top/available,0,1);
    }
    function renderScroll(){
      const total=(count-1)*scrollProgress();
      const index=Math.min(count-2,Math.floor(total));
      let t=clamp(total-index,0,1);
      // Position is the source of truth; velocity gives a temporary reversible radius boost.
      if(velocityInfluence>0 && !reduced && t>0 && t<1){
        t=clamp(t+velocity*velocityInfluence*0.12,0,1);
      }
      renderIndex(index,t);
    }
    function onScroll(){
      const now=performance.now();
      const dt=Math.max(16,now-lastTime);
      const delta=window.scrollY-lastPageY;
      velocity=clamp(delta/dt,-3,3);
      lastPageY=window.scrollY;lastTime=now;
      if(scrollMode)renderScroll();
    }
    function onVisibility(){paused=document.hidden}
    function frame(now){
      if(scrollMode){
        velocity*=0.88;
        renderScroll();
      }else if(!paused && !reduced){
        const elapsed=now-autoStart;
        const cycle=interval+duration;
        const current=Math.floor(elapsed/cycle)%count;
        const within=elapsed%cycle;
        renderIndex(current,clamp((within-interval)/duration,0,1));
      }else if(reduced){
        renderIndex(0,0);
      }
      raf=requestAnimationFrame(frame);
    }
    resizeMask();
    window.addEventListener('resize',resizeMask,{passive:true});
    if(typeof ResizeObserver==='function')new ResizeObserver(resizeMask).observe(stage);
    if(scrollMode){
      window.addEventListener('scroll',onScroll,{passive:true});
      window.addEventListener('resize',renderScroll,{passive:true});
      renderScroll();
    }else{
      if(opts.pauseHover){
        stage.addEventListener('pointerenter',()=>{paused=true});
        stage.addEventListener('pointerleave',()=>{paused=false;autoStart=performance.now()});
      }
      document.addEventListener('visibilitychange',onVisibility);
      renderIndex(0,0);
    }
    raf=requestAnimationFrame(frame);
    window.addEventListener('pagehide',()=>cancelAnimationFrame(raf),{once:true});
  }
  function boot(){document.querySelectorAll('.dm-mask-slider[data-dm-mask]').forEach(init)}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
  window.DURFMaskSlider={refresh:boot};
})();
