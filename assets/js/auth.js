(function(){
  // Tabs simples: busca contenedor de tabs y paneles
  const tabsWrap   = document.querySelector('[data-auth-tabs]');
  const panelsWrap = document.querySelector('[data-auth-panels]');
  if (!tabsWrap || !panelsWrap) return;

  tabsWrap.addEventListener('click', (e)=>{
    const btn = e.target.closest('button[data-target]');
    if (!btn) return;
    const targetSel = btn.getAttribute('data-target');
    const panel = panelsWrap.querySelector(targetSel);
    if (!panel) return;

    // Quitar activos
    tabsWrap.querySelectorAll('.nav-link').forEach(el=>{
      el.classList.remove('active');
      el.setAttribute('aria-selected','false');
    });
    panelsWrap.querySelectorAll('.tab-pane').forEach(el=>{
      el.classList.remove('show','active');
      el.setAttribute('hidden','');
    });

    // Activar seleccionados
    btn.classList.add('active');
    btn.setAttribute('aria-selected','true');
    panel.classList.add('show','active');
    panel.removeAttribute('hidden');
  });

  // Si viene ?tab=register, asegúrate que el panel login no tenga hidden/active desfasado
  const url = new URL(window.location.href);
  const tab = url.searchParams.get('tab');
  if (tab === 'register') {
    const btn = tabsWrap.querySelector('[data-target="#register-tab"]');
    if (btn) btn.click();
  }
})();