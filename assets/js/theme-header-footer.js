// 361theme header/footer interactions (vanilla JS)
(function(){
  const header = document.getElementById('site-header');
  const drawer = document.getElementById('mobile-drawer');
  const hamburger = document.getElementById('hamburger');
  const closeBtn = drawer ? drawer.querySelector('[data-action="close-drawer"]') : null;
  const searchToggle = document.querySelector('[data-action="open-search"]');
  const searchBox = document.getElementById('header-search');
  const backTop = document.getElementById('back-to-top');
  const nearToggle = document.querySelector('[data-action="toggle-distance"]');

  let lastY = 0;
  function onScroll(){
    const y = window.scrollY || window.pageYOffset;
    if (!header) return;
    header.classList.toggle('is-scrolled', y > 8);
    // Back to top
    if (backTop) {
      if (y > 300) backTop.removeAttribute('hidden'); else backTop.setAttribute('hidden','');
    }
    lastY = y;
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Drawer
  function openDrawer(){ if (!drawer || !hamburger) return; drawer.removeAttribute('hidden'); document.body.classList.add('no-scroll'); hamburger.setAttribute('aria-expanded','true'); }
  function closeDrawer(){ if (!drawer || !hamburger) return; drawer.setAttribute('hidden',''); document.body.classList.remove('no-scroll'); hamburger.setAttribute('aria-expanded','false'); }
  if (hamburger) hamburger.addEventListener('click', openDrawer);
  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
  if (drawer) drawer.addEventListener('click', (e)=>{ if (e.target === drawer) closeDrawer(); });

  // Search toggle
  if (searchToggle && searchBox){
    searchToggle.addEventListener('click', ()=>{
      const expanded = searchToggle.getAttribute('aria-expanded') === 'true';
      if (expanded){ searchBox.setAttribute('hidden',''); searchToggle.setAttribute('aria-expanded','false'); }
      else { searchBox.removeAttribute('hidden'); searchToggle.setAttribute('aria-expanded','true'); try{ searchBox.querySelector('input[type="search"]').focus(); } catch(_){} }
    });
  }

  // Back to top
  if (backTop){ backTop.addEventListener('click', ()=> window.scrollTo({top:0, behavior:'smooth'})); }

  // Distance sort toggle (optional integration)
  // Stores state in localStorage `plbSort` and emits `plb:toggleDistance`
  if (nearToggle){
    try {
      const mode = localStorage.getItem('plbSort') || 'new';
      nearToggle.setAttribute('aria-pressed', String(mode === 'near'));
    } catch(_){}
    nearToggle.addEventListener('click', ()=>{
      let mode = 'new';
      try {
        mode = localStorage.getItem('plbSort') || 'new';
        const next = mode === 'near' ? 'new' : 'near';
        localStorage.setItem('plbSort', next);
        nearToggle.setAttribute('aria-pressed', String(next === 'near'));
        window.dispatchEvent(new CustomEvent('plb:toggleDistance', { detail: { sort: next } }));
      } catch(_){}
    });
  }
})();

