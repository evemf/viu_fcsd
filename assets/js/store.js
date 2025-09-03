(function(){
  const $ = (s,c=document)=>c.querySelector(s);
  const $$ = (s,c=document)=>Array.from(c.querySelectorAll(s));

  const modal = $('#store-modal');
  const closeBtn = modal ? modal.querySelector('.store-modal__close') : null;
  const form = modal ? $('#store-checkout-form') : null;
  const emailInput = modal ? $('#store-email') : null;
  const productInput = modal ? $('#store-product-id') : null;
  const paypalButtonsContainer = modal ? $('#paypal-buttons-container') : null;

  // Abrir modal
  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('.js-buy');
    if(!btn) return;
    e.preventDefault();
    const pid = btn.getAttribute('data-product');
    if (!pid) return;
    productInput.value = pid;
    if (paypalButtonsContainer) paypalButtonsContainer.style.display = 'none';
    if (modal) modal.hidden = false;
  });

  // Cerrar modal
  closeBtn && closeBtn.addEventListener('click', ()=> modal.hidden = true);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && modal) modal.hidden = true; });

  // Submit checkout
  form && form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const provider = (window.VIU_STORE && VIU_STORE.provider) || 'stripe';
    const email = emailInput.value.trim();
    const product_id = productInput.value;

    const res = await fetch(VIU_STORE.ajaxUrl + '/checkout', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ email, product_id })
    });
    const data = await res.json();

    if (provider==='stripe'){
      if (data && data.redirect){
        window.location.href = data.redirect;
      } else {
        alert('No se pudo iniciar Stripe');
      }
    } else if (provider==='paypal'){
      // Pintar botones de PayPal
      if (!paypalButtonsContainer){ alert('No hay contenedor para PayPal'); return; }
      paypalButtonsContainer.style.display = 'block';
      if (window.paypal){
        paypalButtonsContainer.innerHTML = '';
        window.paypal.Buttons({
          createOrder: async ()=>{
            const r = await fetch(VIU_STORE.ajaxUrl + '/paypal/create-order', {
              method:'POST', headers:{'Content-Type':'application/json'},
              body: JSON.stringify({ order_id: data.order_id })
            });
            const j = await r.json();
            return j.id;
          },
          onApprove: async (d, actions)=>{
            const r = await fetch(VIU_STORE.ajaxUrl + '/paypal/capture-order', {
              method:'POST', headers:{'Content-Type':'application/json'},
              body: JSON.stringify({ order_id: data.order_id, orderId: d.orderID })
            });
            const j = await r.json();
            if (j.status==='paid'){ window.location.href = '/?checkout=success&order='+data.order_id; }
            else alert('Pago no completado');
          },
          onError: err => { console.error(err); alert('Error en PayPal'); }
        }).render('#paypal-buttons-container');
      } else {
        alert('SDK de PayPal no cargado');
      }
    } else if (provider==='monei'){
      if (data && data.redirect){
        window.location.href = data.redirect;
      } else {
        alert('No se pudo iniciar MONEI');
      }
    }
  });

  // Gestión sencilla de carrito y compra rápida
  function getCart(){
    try{ return JSON.parse(localStorage.getItem('viu_cart')) || {}; }
    catch(e){ return {}; }
  }
  function saveCart(c){ localStorage.setItem('viu_cart', JSON.stringify(c)); }
  function addCart(id, qty, stock){
    const cart = getCart();
    const current = cart[id] || 0;
    if (stock !== '' && !isNaN(stock) && current + qty > stock){
      alert('No hay stock suficiente');
      return false;
    }
    cart[id] = current + qty;
    saveCart(cart);
    return true;
  }

  document.addEventListener('click', (e)=>{
    const add = e.target.closest('.js-add-cart');
    if(add){
      e.preventDefault();
      const id = add.dataset.product;
      const stock = parseInt(add.closest('.product-card')?.dataset.stock || '0',10);
      if(addCart(id,1,stock)){ alert('Añadido al carrito'); }
    }
    const buy = e.target.closest('.js-buy-one');
    if(buy){
      e.preventDefault();
      const id = buy.dataset.product;
      const stock = parseInt(buy.closest('.product-card')?.dataset.stock || '0',10);
      if(addCart(id,1,stock)){ window.location.href = '/checkout'; }
    }
  });

  /* ==========================================================
     FEATURED CAROUSEL — integración con archive-product.php
     Estructura esperada:
      <div class="carousel" data-carousel>
        <button data-carousel-prev>…</button>
        <div class="carousel__track" data-carousel-track> … .carousel__slide … </div>
        <button data-carousel-next>…</button>
      </div>
     ========================================================== */
  (function initFeaturedCarousels(){
    const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const getGap = (el)=>{
      const cs = getComputedStyle(el);
      const g = parseFloat(cs.columnGap || cs.gap || '16');
      return isNaN(g) ? 16 : g;
    };

    const getStep = (track)=>{
      const firstSlide = track.querySelector('.carousel__slide');
      if (!firstSlide) return 280;
      const rect = firstSlide.getBoundingClientRect();
      return Math.max(200, rect.width + getGap(track)); // tamaño slide + gap
    };

    const updateControls = (track, prevBtn, nextBtn)=>{
      // Muestra/oculta prev/next si estamos al inicio/fin
      const atStart = track.scrollLeft <= 2;
      const atEnd   = Math.ceil(track.scrollLeft + track.clientWidth) >= (track.scrollWidth - 2);
      if (prevBtn) prevBtn.disabled = atStart;
      if (nextBtn) nextBtn.disabled = atEnd;
    };

    $$('[data-carousel]').forEach((root)=>{
      const track = $('[data-carousel-track]', root);
      const prev  = $('[data-carousel-prev]', root);
      const next  = $('[data-carousel-next]', root);
      if (!track) return;

      // Botones prev/next
      const scrollByStep = (dir)=>{
        const step = getStep(track) * (dir === 'next' ? 1 : -1);
        track.scrollBy({ left: step, top: 0, behavior: prefersReduced ? 'auto' : 'smooth' });
      };

      prev && prev.addEventListener('click', ()=> scrollByStep('prev'));
      next && next.addEventListener('click', ()=> scrollByStep('next'));

      // Actualiza estado de botones al hacer scroll / resize
      const onScroll = ()=> updateControls(track, prev, next);
      track.addEventListener('scroll', onScroll, { passive:true });
      window.addEventListener('resize', onScroll, { passive:true });
      onScroll();

      // Navegación con teclado (izq/der) cuando el track tiene foco
      track.setAttribute('tabindex', '0');
      track.addEventListener('keydown', (e)=>{
        if (e.key === 'ArrowRight'){ e.preventDefault(); scrollByStep('next'); }
        if (e.key === 'ArrowLeft'){  e.preventDefault(); scrollByStep('prev'); }
      });

      // Arrastrar para desplazar (mouse/touch)
      let isPointerDown = false;
      let startX = 0;
      let startScroll = 0;

      const onPointerDown = (clientX)=>{
        isPointerDown = true;
        startX = clientX;
        startScroll = track.scrollLeft;
        track.classList.add('is-dragging');
      };

      const onPointerMove = (clientX)=>{
        if (!isPointerDown) return;
        const delta = clientX - startX;
        track.scrollLeft = startScroll - delta;
      };

      const endDrag = ()=>{
        isPointerDown = false;
        track.classList.remove('is-dragging');
      };

      track.addEventListener('mousedown', (e)=>{ onPointerDown(e.clientX); });
      track.addEventListener('mousemove', (e)=>{ onPointerMove(e.clientX); });
      document.addEventListener('mouseup', endDrag);

      track.addEventListener('touchstart', (e)=>{ onPointerDown(e.touches[0].clientX); }, {passive:true});
      track.addEventListener('touchmove', (e)=>{ onPointerMove(e.touches[0].clientX); }, {passive:true});
      track.addEventListener('touchend', endDrag);
      track.addEventListener('touchcancel', endDrag);
    });
  })();

})();
