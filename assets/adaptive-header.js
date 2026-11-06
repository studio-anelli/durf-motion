/* DURF Motion — adaptive Impreza header.
   Read the section actually behind the fixed header, not the dominant viewport row. */
(function(){
  'use strict';
  const config=window.DURFMotionConfig||{};
  if(!config.headerEnabled)return;
  const colors=config.colors||{};
  const headerSelector=config.headerSelector||'.l-header';
  const mode=config.headerMode==='match'?'match':'transparent';
  const contrastMode=config.headerContrast||'auto';
  const clamp=(n,a,b)=>Math.max(a,Math.min(b,n));
  function hexRGB(hex){
    if(!/^#[0-9a-f]{6}$/i.test(hex||''))return null;
    return [1,3,5].map(i=>parseInt(hex.slice(i,i+2),16));
  }
  function rgb(css){
    if(!css||css==='transparent')return null;
    if(css[0]==='#')return hexRGB(css);
    const m=css.match(/^rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)(?:[,\s/]+([\d.]+))?/i);
    if(!m||m[4]!==undefined&&Number(m[4])<.95)return null;
    return m.slice(1,4).map(Number);
  }
  function luminance(c){
    return c.map(v=>{const n=v/255;return n<=.04045?n/12.92:Math.pow((n+.055)/1.055,2.4)})
      .reduce((n,v,i)=>n+v*[.2126,.7152,.0722][i],0);
  }
  function toCSS(c){return 'rgb('+c.map(Math.round).join(', ')+')'}
  const light=hexRGB(colors.cream||'#F8F4EC')||[248,244,236];
  const dark=hexRGB(colors.ink||'#25231F')||[37,35,31];
  const palette=['cream','orange','green','pink','ink'];
  function sectionColor(section){
    const custom=rgb(section.getAttribute('data-dm-header-bg'))||rgb(section.getAttribute('data-dm-color'));
    if(custom)return custom;
    // When the colour-flow engine paints a blend, use the LIVE rendered colour.
    // Static palette names would jump before the section transition completes.
    if(section.classList.contains('dm-color-flow')){
      const blended=rgb(getComputedStyle(section).backgroundColor);
      if(blended)return blended;
    }
    for(const key of palette){
      if(section.classList.contains('dm-color-'+key))return rgb(colors[key]);
    }
    const actual=rgb(getComputedStyle(section).backgroundColor);
    if(actual)return actual;
    // Sometimes background is set on inner Impreza wrapper.
    const inner=section.querySelector(':scope > .l-section-h');
    return inner?rgb(getComputedStyle(inner).backgroundColor):null;
  }
  function findSection(anchorY){
    // A header can overlay a row; don't use elementFromPoint (it sees the header).
    // Prefer flow rows with known palette backgrounds over transparent wrappers.
    const candidates=document.querySelectorAll(
      '.dm-color-flow, .l-section, .vc_row, .wpb_row, .dm-header-light, .dm-header-dark, [data-dm-header-bg]'
    );
    let best=null,bestPriority=-Infinity;
    const x=window.innerWidth/2;
    for(const el of candidates){
      if(el.closest('.l-header, header.site-header'))continue;
      const rect=el.getBoundingClientRect();
      if(rect.height<8||rect.width<8||rect.top>anchorY||rect.bottom<=anchorY)continue;
      if(rect.right<x||rect.left>x)continue;
      const explicit=el.matches('.dm-header-light,.dm-header-dark,[data-dm-header-bg]');
      const priority=(explicit?200:0)+(el.classList.contains('dm-color-flow')?100:0)
        +(el.classList.contains('l-section')?50:0)+Math.min(rect.width,2000)/10000;
      if(priority>bestPriority){best=el;bestPriority=priority}
    }
    return best;
  }
  const header=document.querySelector(headerSelector);
  if(!header)return;
  header.classList.add('dm-adaptive-header');
  header.classList.add(mode==='match'?'dm-adaptive-header--match':'dm-adaptive-header--transparent');
  if(config.headerMonoLogo)header.classList.add('dm-adaptive-header--mono-logo');

  let last='',scheduled=false,stopped=false;
  function update(){
    scheduled=false;
    if(stopped)return;
    const rect=header.getBoundingClientRect();
    const y=clamp(Math.max(8,rect.bottom-2),0,Math.max(8,window.innerHeight-2));
    const section=findSection(y);
    let bg=section?sectionColor(section):null;
    if(!bg)bg=rgb(config.headerFallbackColor)||light;
    let contrast=contrastMode;
    if(section?.classList.contains('dm-header-light'))contrast='light';
    if(section?.classList.contains('dm-header-dark'))contrast='dark';
    // Pick the palette ink/cream with stronger contrast against the background.
    // A simple brightness threshold incorrectly makes orange use light text.
    const bgL=luminance(bg),lightL=luminance(light),darkL=luminance(dark);
    const contrastRatio=l=> (Math.max(bgL,l)+.05)/(Math.min(bgL,l)+.05);
    const fg=contrast==='light'?light:contrast==='dark'?dark:
      (contrastRatio(darkL)>=contrastRatio(lightL)?dark:light);
    const newValue=toCSS(bg)+'|'+toCSS(fg);
    if(last===newValue)return;
    last=newValue;
    header.style.setProperty('--dm-header-bg',toCSS(bg));
    header.style.setProperty('--dm-header-fg',toCSS(fg));
    header.style.setProperty('--dm-header-logo-filter',luminance(fg)>.42?'brightness(0) invert(1)':'brightness(0)');
    header.dataset.dmHeaderSection=section?.className?.toString().slice(0,100)||'none';
  }
  function schedule(){if(!scheduled){scheduled=true;requestAnimationFrame(update)}}
  window.addEventListener('scroll',schedule,{passive:true});
  window.addEventListener('resize',schedule,{passive:true});
  window.addEventListener('pageshow',schedule);
  document.addEventListener('visibilitychange',schedule);
  if(typeof MutationObserver==='function'){
    // This observes style changes made by the existing colour-flow engine,
    // not the header; only the section underneath is relevant.
    const rows=document.querySelectorAll('.dm-color-flow');
    const observer=new MutationObserver(schedule);
    rows.forEach(row=>observer.observe(row,{attributes:true,attributeFilter:['style']}));
    window.addEventListener('pagehide',()=>observer.disconnect(),{once:true});
  }
  update();
  window.DURFAdaptiveHeader={refresh:schedule,diagnostics:()=>({
    enabled:true,mode,selector:headerSelector,section:header.dataset.dmHeaderSection,
    background:header.style.getPropertyValue('--dm-header-bg'),
    foreground:header.style.getPropertyValue('--dm-header-fg')
  })};
})();
