/* DURF Motion 1.1 — inertial, optionally looping editorial filmstrip. */
(function () {
  'use strict';
  const clamp=(n,a,b)=>Math.min(b,Math.max(a,n));
  const reduced=()=>window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function init(root) {
    if(root.dataset.dmSliderReady)return;
    let opt={};try{opt=JSON.parse(root.dataset.dmSlider||'{}');}catch(e){}
    const track=root.querySelector('.splide__track');
    const list=root.querySelector('.splide__list');
    const originals=Array.from(list?list.children:[]);
    if(!track||!list||!originals.length)return;
    root.dataset.dmSliderReady='1';
    const vertical=opt.direction==='vertical';
    const normalScroll=()=>vertical?track.scrollTop:track.scrollLeft;
    const writeScroll=v=>{if(vertical)track.scrollTop=v;else track.scrollLeft=v;};
    const travel=()=>vertical?track.scrollHeight-track.clientHeight:track.scrollWidth-track.clientWidth;
    const axisSize=()=>vertical?track.clientHeight:track.clientWidth;
    const reduce=reduced();
    const looping=!!opt.loop&&!reduce&&originals.length>1;
    const autoplay=!!opt.autoplay&&!reduce&&originals.length>1;
    const dragAllowed=opt.allowDrag!==false;
    const mode=reduce?'off':(opt.scrollResponse||'off');
    const strength=clamp(Number(opt.scrollStrength)||0,0,150)/100;
    const scrollDriven=mode!=='off'&&strength>0&&originals.length>1;
    const response=clamp((100-(Number(opt.smoothness)||72))/100,0.04,0.55);
    const speed=clamp(Number(opt.autoplaySpeed)||25,3,160);
    const hoverPause=!!opt.pauseHover;
    const useContinuous=looping||autoplay||scrollDriven;
    let period=0,base=0,target=0,current=0,frame=0,lastTick=0;
    let impulse=0,dragging=false,dragOrigin=0,dragScroll=0,hold=false;
    let scrollY=window.scrollY,scrollTime=performance.now();
    let suppressClick=false;
    const children=()=>Array.from(list.children);
    const clones=[];
    if(looping){
      // Clones on BOTH sides guarantee that dragging in either direction is continuous.
      // The middle set is the canonical one; when scrolling crosses the boundary,
      // both the visual position and its target are shifted by one complete period.
      for(const item of originals){const clone=item.cloneNode(true);clone.dataset.dmClone='1';clone.setAttribute('aria-hidden','true');clone.querySelectorAll('a,button,input,[tabindex]').forEach(el=>el.tabIndex=-1);list.insertBefore(clone,originals[0]);clones.push(clone);}
      for(const item of originals){const clone=item.cloneNode(true);clone.dataset.dmClone='1';clone.setAttribute('aria-hidden','true');clone.querySelectorAll('a,button,input,[tabindex]').forEach(el=>el.tabIndex=-1);list.appendChild(clone);clones.push(clone);}
    }
    root.classList.toggle('dm-slider--loop',looping);
    root.classList.toggle('dm-slider--autoplay',autoplay);
    root.classList.toggle('dm-slider--no-drag',!dragAllowed);
    root.classList.toggle('dm-slider--scroll-driven',scrollDriven);
    root.classList.toggle('dm-slider--drift',!!opt.imageDrift&&!reduce);
    track.setAttribute('role','region');
    track.setAttribute('aria-roledescription','carousel');
    track.setAttribute('aria-label',root.getAttribute('aria-label')||'Photographic gallery');
    track.tabIndex=0;
    function offset(el){return vertical?el.offsetTop:el.offsetLeft;}
    function extent(el){return vertical?el.offsetHeight:el.offsetWidth;}
    function layout(reset){
      if(looping){
        // Distance from the middle first slide to the third (cloned) first slide.
        period=Math.max(1,offset(children()[originals.length*2])-offset(children()[originals.length]));
        base=offset(children()[originals.length]);
        if(reset){current=target=base;writeScroll(base);}
        else {
          const previousRelative=current-base;
          if(Math.abs(previousRelative)>period*1.5){current=target=base;writeScroll(base);}
          else wrap();
        }
      } else if(reset){current=target=normalScroll();}
      applyDrift();
    }
    function wrap(){
      if(!looping||!period)return;
      let delta=0;
      while(current<base-period*.5){current+=period;delta+=period;}
      while(current>=base+period*.5){current-=period;delta-=period;}
      if(delta){target+=delta;dragScroll+=delta;writeScroll(current);}
    }
    function inViewport(){const b=root.getBoundingClientRect();return b.bottom>0&&b.top<window.innerHeight;}
    function applyDrift(){
      if(!opt.imageDrift||reduce)return;
      const centre=current+axisSize()/2;
      children().forEach(slide=>{
        const image=slide.querySelector('img');if(!image)return;
        const delta=clamp((offset(slide)+extent(slide)/2-centre)/Math.max(1,axisSize()),-1,1);
        image.style.transform=vertical?`scale(1.06) translate3d(0,${(delta*8).toFixed(2)}px,0)`:`scale(1.06) translate3d(${(delta*8).toFixed(2)}px,0,0)`;
      });
    }
    function tick(now){
      const dt=lastTick?Math.min(0.05,(now-lastTick)/1000):0;
      lastTick=now;
      if(document.hidden){frame=requestAnimationFrame(tick);return;}
      // Scroll impulse is transient. The base velocity survives and resumes.
      const active=inViewport()&&!dragging;
      if(active&&autoplay&&(!hoverPause||!hold))target+=speed*dt;
      if(active&&scrollDriven)target+=impulse*dt;
      impulse*=Math.exp(-dt*5.0);
      if(!looping)target=clamp(target,0,Math.max(0,travel()));
      if(!dragging){
        const k=1-Math.pow(1-response,Math.max(0.01,dt*60));
        current+=(target-current)*k;
        if(Math.abs(current-target)<0.02)current=target;
        wrap();writeScroll(current);applyDrift();
      }
      if(autoplay||scrollDriven||looping||Math.abs(current-target)>0.1)frame=requestAnimationFrame(tick);
      else{frame=0;lastTick=0;}
    }
    function ensure(){if(!frame){lastTick=0;frame=requestAnimationFrame(tick);}}
    function onPageScroll(){
      const next=window.scrollY,now=performance.now(),dy=next-scrollY,dt=Math.max(8,now-scrollTime);
      scrollY=next;scrollTime=now;
      if(!scrollDriven||!dy||!inViewport())return;
      const velocity=Math.abs(dy)/dt*1000;
      const acceleration=mode==='dynamic'?(1+Math.min(2,velocity/1500)):1;
      // Translate page travel into a short-lived velocity impulse.
      impulse=clamp(impulse+dy/dt*1000*strength*acceleration*.35,-1100,1100);
      ensure();
    }
    window.addEventListener('scroll',onPageScroll,{passive:true});
    if(hoverPause){root.addEventListener('mouseenter',()=>hold=true);root.addEventListener('mouseleave',()=>hold=false);}
    if(dragAllowed){
      track.addEventListener('pointerdown',e=>{
        if(e.pointerType!=='mouse'||e.button!==0)return;
        dragging=true;dragOrigin=vertical?e.clientY:e.clientX;dragScroll=current;
        track.classList.add('dm-slider-dragging');
      });
      window.addEventListener('pointermove',e=>{
        if(!dragging)return;
        const delta=(vertical?e.clientY:e.clientX)-dragOrigin;
        if(Math.abs(delta)>3){suppressClick=true;current=target=dragScroll-delta;wrap();writeScroll(current);applyDrift();}
      });
      window.addEventListener('pointerup',()=>{
        if(!dragging)return;
        dragging=false;target=current;track.classList.remove('dm-slider-dragging');
        setTimeout(()=>{suppressClick=false;},150);ensure();
      });
      track.addEventListener('click',e=>{if(suppressClick){e.preventDefault();e.stopPropagation();}},true);
      // Let touch gestures scroll the filmstrip naturally, without RAF fighting touch.
      track.addEventListener('touchstart',()=>{dragging=true;},{passive:true});
      track.addEventListener('touchend',()=>{
        current=target=normalScroll();wrap();dragging=false;ensure();
      },{passive:true});
      track.addEventListener('touchcancel',()=>{dragging=false;current=target=normalScroll();wrap();ensure();},{passive:true});
    }
    track.addEventListener('scroll',()=>{
      if(dragging)return;
      // Detect actual user scrolling of the track; our own scroll writes are not a target.
      if(!frame){current=target=normalScroll();wrap();applyDrift();}
    },{passive:true});
    track.addEventListener('keydown',e=>{
      const forward=(e.key==='ArrowRight'&&!vertical)||(e.key==='ArrowDown'&&vertical);
      const reverse=(e.key==='ArrowLeft'&&!vertical)||(e.key==='ArrowUp'&&vertical);
      if(!forward&&!reverse)return;
      e.preventDefault();
      target+=(forward?1:-1)*axisSize()*.7;
      if(!looping)target=clamp(target,0,Math.max(0,travel()));
      ensure();
    });
    layout(true);
    if(typeof ResizeObserver==='function'){
      const ro=new ResizeObserver(()=>{
        const prior=looping&&period?(current-base)/period:0;
        layout(false);
        if(looping){current=target=base+prior*period;wrap();writeScroll(current);}
      });ro.observe(track);
    }
    if(autoplay||scrollDriven||looping)ensure();
  }
  function boot(){document.querySelectorAll('.dm-slider[data-dm-slider]').forEach(init);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
  window.DURFSlider={refresh:boot};
})();
