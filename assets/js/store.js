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
    paypalButtonsContainer.style.display = 'none';
    modal.hidden = false;
  });

  // Cerrar modal
  closeBtn && closeBtn.addEventListener('click', ()=> modal.hidden = true);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') modal.hidden = true; });

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

})();
