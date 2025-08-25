/* [THUB_ACCOUNT_JS] Evidenziazione attiva “al volo” (cosmetica) */
(function(){
  document.addEventListener('click', function(e){
    const a = e.target.closest('.thub-account__link');
    if(!a) return;
    document.querySelectorAll('.thub-account__item.is-active')
      .forEach(li => li.classList.remove('is-active'));
    a.closest('li')?.classList.add('is-active');
  }, true);
})();

/*-- =========================
     [THUB_ACCOUNT_PRIVACY_AUTOSAVE] JS: toggle → salvataggio immediato via AJAX
     Requisiti:
     - admin-ajax attivo (ajaxurl), altrimenti usa /wp-admin/admin-ajax.php
     - handler in functions.php (wp_ajax_thub_toggle_privacy_save)
     ========================= */
(function(){
  const root = document.getElementById('thub-privacy');
  if(!root) return;

  const nonce = root.getAttribute('data-nonce') || '';
  const ajaxURL =
  (window.thubAccount && window.thubAccount.ajaxurl) ||
  (window.ajaxurl) ||
  '/wp-admin/admin-ajax.php';

  // helper POST x-www-form-urlencoded
  const post = (data) => fetch(ajaxURL, {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams(data).toString(),
    credentials: 'same-origin'
  });

  const showMsg = (boxEl, ok=true, text) => {
    const msg = boxEl.querySelector('.thub-save-msg');
    if(!msg) return;
    msg.textContent = text || (ok ? 'Salvato ✓' : 'Errore');
    msg.classList.toggle('thub-save-ok', ok);
    msg.classList.toggle('thub-save-err', !ok);
    msg.hidden = false;
    clearTimeout(msg._t);
    msg._t = setTimeout(()=>{ msg.hidden = true; }, 1800);
  };

  root.addEventListener('change', async (e)=>{
    const input = e.target;
    if(!(input instanceof HTMLInputElement)) return;
    if(input.type !== 'checkbox') return;

    const metaKey = input.getAttribute('data-meta-key');
    if(!metaKey) return;

    const box = input.closest('.thub-box') || root;
    const prev = !input.checked; // memorizza stato precedente per eventuale rollback
    input.disabled = true;

    try{
      const res = await post({
        action: 'thub_toggle_privacy_save',
        meta_key: metaKey,
        value: input.checked ? '1' : '0',
        _ajax_nonce: nonce
      });
      const json = await res.json();
      if(res.ok && json && json.success){
        showMsg(box, true);
      }else{
        input.checked = prev; // rollback
        showMsg(box, false, (json && json.data && json.data.message) ? json.data.message : 'Errore');
      }
    }catch(err){
      input.checked = prev; // rollback
      showMsg(box, false, 'Errore di rete');
    }finally{
      input.disabled = false;
    }
  }, true);
})();