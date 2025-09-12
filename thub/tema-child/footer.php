<?php
/**
 * [THUB_FOOTER] Footer THUB
 * - Sinistra: Pubblicità | Soluzioni aziendali
 * - Destra:  Privacy | Termini
 * - Le pagine saranno create con gli slug indicati.
 */
?>
<footer class="site-footer thub-footer">
  <div class="thub-footer-inner">
    <!-- [THUB_ft_left] Colonna sinistra -->
    <div class="thub-footer-left">
      <a href="<?php echo esc_url(home_url('/pubblicita/')); ?>" aria-label="Pubblicità">Pubblicità</a>
      <a href="<?php echo esc_url(home_url('/soluzioni-aziendali/')); ?>" aria-label="Soluzioni aziendali">Soluzioni aziendali</a>
    </div>

    <!-- [THUB_ft_right] Colonna destra -->
    <div class="thub-footer-right">
      <a href="<?php echo esc_url(home_url('/privacy/')); ?>" aria-label="Privacy">Privacy</a>
      <a href="<?php echo esc_url(home_url('/termini/')); ?>" aria-label="Termini">Termini</a>

      <!-- =========================================================
          [THUB_LANG_FOOTER] Trigger lingua nel footer (per tutti)
          - Mostra: icona globo + sigla (es. IT)
          - Al click: apre tendina con elenco lingue supportate
          - Salvataggio:
            • loggato  → user_meta (AJAX)
            • ospite   → cookie (client) oppure AJAX nopriv (cookie server)
          ========================================================= -->
      <?php
        // Composizione sigla corrente (es. "IT") e label completa (es. "Italiano (Italia)")
        if ( function_exists('thub_get_current_locale') && function_exists('thub_get_locale_sigla') && function_exists('thub_get_locale_label') && function_exists('thub_get_supported_locales') ):
          $thub_locale_cur   = thub_get_current_locale();
          $thub_locale_sigla = thub_get_locale_sigla($thub_locale_cur);   // "IT"
          $thub_locale_label = thub_get_locale_label($thub_locale_cur);   // "Italiano (Italia)"
          $thub_locales_all  = thub_get_supported_locales();
      ?>
        <span id="thub-foot-lang" class="thub-lang" style="position:relative; margin-left:.6rem;">
          <!-- [THUB_LANG_BTN] Bottone trigger (discreto: icona + sigla) -->
          <button id="thub-lang-btn"
                  class="thub-lang__btn"
                  type="button"
                  aria-haspopup="true"
                  aria-expanded="false"
                  aria-controls="thub-lang-dd"
                  title="Cambia lingua"
                  style="display:inline-flex;align-items:center;gap:.35rem;border:0;background:transparent;cursor:pointer;color:#555;">
            <!-- Icona SVG globo -->
            <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="9" stroke="currentColor"/>
              <path d="M3 12h18M12 3c2.7 3.2 2.7 14.8 0 18M12 3c-2.7 3.2-2.7 14.8 0 18" stroke="currentColor"/>
            </svg>
            <strong style="font-weight:600;"><?php echo esc_html($thub_locale_sigla); ?></strong>
            <!-- caret -->
            <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none">
              <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" />
            </svg>
          </button>

          <!-- [THUB_LANG_DROPDOWN] Tendina lingue (riutilizza stile .thub-dropdown) -->
          <div id="thub-lang-dd"
              class="thub-dropdown thub-dropdown--footer"
              aria-hidden="true"
              style="position:absolute; right:0; bottom: calc(100% + 8px); min-width:240px;">
            <ul style="list-style:none;margin:0;padding:.25rem;">
              <?php foreach($thub_locales_all as $loc => $label): ?>
                <li>
                  <button type="button"
                          class="thub-lang__opt"
                          data-locale="<?php echo esc_attr($loc); ?>"
                          style="display:block;width:100%;text-align:left;padding:.5rem .6rem;border:0;background:#fff;cursor:pointer;border-radius:.5rem;">
                    <?php
                      // [THUB_LANG_OPT_LABEL] Sigla + nome lingua senza paese: "IT - Italiano"
                      $abbr = thub_get_locale_sigla($loc);                           // es. IT
                      $name = preg_replace('/\s*\(.*\)$/', '', (string)$label);      // "Italiano (Italia)" → "Italiano"
                      echo esc_html($abbr . ' - ' . $name);
                    ?>
                  </button>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- [THUB_VOICE] Binder dettatura per tutte le form.thub-search -->
  <script>
  (function(){
    document.querySelectorAll('form.thub-search').forEach(function(form){
      var mic   = form.querySelector('.thub-mic-btn');
      var input = form.querySelector('input[type="search"]');
      if(!mic || !input) return;
      var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
      if(!SR){ mic.style.opacity=.45; mic.style.cursor='not-allowed'; mic.title='Dettatura non supportata'; return; }
      var recog = new SR(); var listening=false;
      recog.lang='it-IT'; recog.interimResults=false; recog.maxAlternatives=1;
      mic.addEventListener('click', function(e){ e.preventDefault(); listening ? recog.stop() : recog.start(); });
      recog.addEventListener('start', function(){ listening=true; mic.style.outline='2px solid #7249a4'; });
      recog.addEventListener('end',   function(){ listening=false; mic.style.outline='none'; });
      recog.addEventListener('result', function(e){ try{ input.value = e.results[0][0].transcript || ''; }catch(_){} });
    });
  })();
  </script>
</footer>

<?php wp_footer(); ?><!-- [THUB_wp] hook footer -->

<script>
  /* [THUB_PROFILE_UPLOAD_BOOT] Variabili globali per AJAX upload */
  window.THUB_PROFILE_UPLOAD = {
    url: "<?php echo esc_url( admin_url('admin-ajax.php') ); ?>",
    nonce: "<?php echo esc_js( wp_create_nonce('thub_profile_upload') ); ?>"
  };
</script>

<!-- [THUB_MODAL_JS] Toggle dropdown + aria + clone avatar -->
<script>
(function(){
  const $  = (s, c=document) => c.querySelector(s);
  const $$ = (s, c=document) => Array.from(c.querySelectorAll(s));

  const btnAccount = document.querySelector('.thub-avatar');      // bottone avatar header
  const btnApps    = document.querySelector('.thub-apps-btn');    // bottone tastierino header
  const ddAccount  = $('#thub-dropdown-account');
  const ddApps     = $('#thub-dropdown-apps');

  function toggle(dd, open){
    if(!dd) return;
    const isHidden = dd.getAttribute('aria-hidden') !== 'false';
    const willOpen = (typeof open === 'boolean') ? open : isHidden;
    dd.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
  }
  function closeAll(){
    [ddAccount, ddApps].forEach(dd => dd && dd.setAttribute('aria-hidden','true'));
    btnAccount && btnAccount.setAttribute('aria-expanded','false');
    btnApps && btnApps.setAttribute('aria-expanded','false');
  }

  // Aperture sincronizzate + aria
  btnAccount && btnAccount.addEventListener('click', (e)=>{
    e.stopPropagation();
    toggle(ddAccount, true);
    toggle(ddApps, false);
    btnAccount.setAttribute('aria-expanded','true');
    btnApps && btnApps.setAttribute('aria-expanded','false');
  });
  btnApps && btnApps.addEventListener('click', (e)=>{
    e.stopPropagation();
    toggle(ddApps, true);
    toggle(ddAccount, false);
    btnApps.setAttribute('aria-expanded','true');
    btnAccount && btnAccount.setAttribute('aria-expanded','false');
  });

  // Pulsanti chiudi nei modali
  $$('[data-thub-close]').forEach(b=>{
    b.addEventListener('click', ()=>{
      const sel = b.getAttribute('data-thub-close');
      const el = sel ? document.querySelector(sel) : null;
      if (el) el.setAttribute('aria-hidden','true');
      btnAccount && btnAccount.setAttribute('aria-expanded','false');
      btnApps && btnApps.setAttribute('aria-expanded','false');
    });
  });

  // Click fuori + ESC chiudono tutto
  document.addEventListener('click', (e)=>{
    const inside = e.target.closest('.thub-dropdown, .thub-avatar, .thub-apps-btn');
    if (!inside) closeAll();
  });
  document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeAll(); });

  // [THUB_clone] Tastierino: clona avatar header nello slot se è un <img>, altrimenti iniziale
  const headerAvatarImg = document.querySelector('.thub-avatar img');
  const headerIni       = document.querySelector('.thub-avatar .thub-avatar-initial');
  const cloneSlot       = document.querySelector('[data-thub-avatar-clone]');
  if (cloneSlot){
    cloneSlot.innerHTML = '';
    if (headerAvatarImg){
      const img = headerAvatarImg.cloneNode(true);
      img.style.width = '24px'; img.style.height='24px';
      img.style.borderRadius='50%'; img.alt = 'Account';
      cloneSlot.appendChild(img);
    } else if (headerIni){
      const b = document.createElement('div');
      b.textContent = headerIni.textContent.trim().slice(0,1).toUpperCase();
      b.style.width='24px'; b.style.height='24px'; b.style.borderRadius='50%';
      b.style.display='grid'; b.style.placeItems='center';
      b.style.fontWeight='800'; b.style.color='#111';
      cloneSlot.appendChild(b);
    }
  }
})();
</script>

<!-- [THUB_MODAL_JS_HOOKS] Eventi globali per aprire il dropdown Account -->
<script>
(function(){
  const $ = (s,c=document)=>c.querySelector(s);
  const ddAccount = $('#thub-dropdown-account');
  const btnAccount = $('.thub-avatar');
  const ddApps = $('#thub-dropdown-apps');
  const btnApps = $('.thub-apps-btn');

  function openAccount(){
    if (!ddAccount) return;
    ddAccount.setAttribute('aria-hidden','false');
    if (btnAccount) btnAccount.setAttribute('aria-expanded','true');
    if (ddApps) ddApps.setAttribute('aria-hidden','true');
    if (btnApps) btnApps.setAttribute('aria-expanded','false');
  }

  // Evento globale per aprire il dropdown Account
  window.addEventListener('thub:openAccountDropdown', openAccount);

  // Fallback richiamabile da console
  window.thubOpenAccount = openAccount;

  console.debug('[THUB_MODAL_JS] hook ready', { hasAccount: !!ddAccount, hasBtn: !!btnAccount });
})();
</script>

<!-- [THUB_PROFILE_UPLOAD_JS] Gestione upload foto profilo dal modal Account -->
<script>
(function(){
  const wrap = document.getElementById('thub-acc-avatar-wrap');
  const inp  = document.getElementById('thub-upload-profile');
  const msg  = document.getElementById('thub-profile-msg');
  if(!wrap || !inp) return;

  // Click sull’avatar = apri file picker
  wrap.addEventListener('click', function(){
    inp.click();
  });

  // Al cambio file → invia via AJAX a admin-ajax.php (action: thub_upload_profile_photo)
  inp.addEventListener('change', function(){
    const f = inp.files && inp.files[0];
    if(!f) return;

    // Validazioni base lato client
    const max = 2 * 1024 * 1024; // 2MB
    if (f.size > max){ show('File troppo grande (max 2MB).', true); return; }
    if (!/^image\/(jpeg|png|webp)$/i.test(f.type)){ show('Usa JPG, PNG o WEBP.', true); return; }

    show('Caricamento in corso…');

    const fd = new FormData();
    fd.append('action', 'thub_upload_profile_photo');
    fd.append('nonce',  (window.THUB_PROFILE_UPLOAD && THUB_PROFILE_UPLOAD.nonce) || '');
    fd.append('profile_photo', f, f.name);

    fetch( (window.THUB_PROFILE_UPLOAD && THUB_PROFILE_UPLOAD.url) || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      body: fd
    })
    .then(r => r.json())
    .then(data => {
      if(!data || !data.success){ throw new Error(data && data.data && data.data.message ? data.data.message : 'Errore sconosciuto'); }
      const url = (data.data && data.data.url) ? data.data.url : '';
      // Aggiorna immagine nel modal
      updateModalAvatar(url);
      // Aggiorna avatar header
      updateHeaderAvatar(url);
      // Aggiorna icona Account nel tastierino
      updateAppsAvatar(url);
      show('Foto aggiornata 👍', false, true);
    })
    .catch(err => {
      show(err.message || 'Errore durante l’upload.', true);
    })
    .finally(()=>{ inp.value = ''; });
  });

  function show(t, isErr=false, isOK=false){
    if(!msg) return;
    msg.textContent = t || '';
    msg.classList.remove('is-error','is-ok');
    if(isErr) msg.classList.add('is-error');
    if(isOK)  msg.classList.add('is-ok');
  }

  function updateModalAvatar(url){
    if(!url) return;
    const img = document.querySelector('.thub-account-dropdown .thub-acc__avatar-img');
    if (img){
      img.src = cacheBust(url);
    } else {
      const fb = document.querySelector('.thub-account-dropdown .thub-acc__avatar-fallback');
      if (fb){
        const parent = fb.parentNode;
        const newImg = document.createElement('img');
        newImg.className = 'thub-acc__avatar-img';
        newImg.alt = 'Foto profilo';
        newImg.src = cacheBust(url);
        newImg.style.width = '56px';
        newImg.style.height = '56px';
        newImg.style.borderRadius = '50%';
        newImg.style.objectFit = 'cover';
        parent.replaceChild(newImg, fb);
      }
    }
  }

  function updateHeaderAvatar(url){
    if(!url) return;
    const btn = document.querySelector('.thub-avatar');
    if(!btn) return;
    let img = btn.querySelector('img');
    if (img){
      img.src = cacheBust(url);
    } else {
      // sostituisci iniziale con img
      btn.innerHTML = '';
      img = document.createElement('img');
      img.src = cacheBust(url);
      img.alt = 'Foto profilo';
      img.style.width='100%'; img.style.height='100%';
      img.style.objectFit='cover'; img.style.borderRadius='9999px';
      btn.appendChild(img);
    }
  }

  function updateAppsAvatar(url){
    if(!url) return;
    const slot = document.querySelector('[data-thub-avatar-clone]');
    if(!slot) return;
    slot.innerHTML = '';
    const img = document.createElement('img');
    img.src = cacheBust(url);
    img.alt = 'Account';
    img.style.width='24px'; img.style.height='24px';
    img.style.borderRadius='50%'; img.style.objectFit='cover';
    slot.appendChild(img);
  }

  function cacheBust(u){ return u + (u.includes('?') ? '&' : '?') + 't=' + Date.now(); }
})();
</script>

<!-- [THUB_APPS_TRUNCATE_9] semplice: mostra esattamente 9 caratteri per app -->
<script>
(function(){
  const LIMIT = 9;
  document.querySelectorAll('.thub-apps-label').forEach(function(label){
    // Sorgente: data-label-full se presente, altrimenti il testo attuale
    const full = (label.getAttribute('data-label-full') || label.textContent || '').trim();
    // Taglio sicuro per accenti/emoji: Array.from = code points
    const short = Array.from(full).slice(0, LIMIT).join('');
    label.textContent = short; // scrive 9 caratteri netti
  });
})();
</script>

<script>
  /* [THUB_LANG_CONF] Config AJAX locale */
  window.THUB_LOCALE = {
    ajax: "<?php echo esc_url( admin_url('admin-ajax.php') ); ?>",
    nonce: "<?php echo esc_js( wp_create_nonce('thub_locale') ); ?>",
    isLogged: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>
  };
</script>

<script>
/* ============================================================
   [THUB_LANG_FOOTER_JS] Toggle + salvataggio lingua
   - Click bottone → apre/chiude tendina
   - Click opzione → loggato = AJAX user_meta, ospite = cookie
   - Click fuori / ESC → chiude
   ============================================================ */
(function(){
  const btn = document.getElementById('thub-lang-btn');
  const dd  = document.getElementById('thub-lang-dd');
  if(!btn || !dd) return;

  function isOpen(){ return dd.getAttribute('aria-hidden') === 'false'; }
  function openDD(){
    dd.setAttribute('aria-hidden', 'false');
    btn.setAttribute('aria-expanded', 'true');
  }
  function closeDD(){
    dd.setAttribute('aria-hidden', 'true');
    btn.setAttribute('aria-expanded', 'false');
  }

  // Toggle apertura
  btn.addEventListener('click', (e)=>{
    e.stopPropagation();
    isOpen() ? closeDD() : openDD();
  });

  // Selezione lingua
  dd.addEventListener('click', async (e)=>{
    const b = e.target.closest('.thub-lang__opt');
    if(!b) return;
    const loc = b.getAttribute('data-locale');
    if(!loc) return;

    const cfg = window.THUB_LOCALE || {};
    // Se loggato → AJAX (user_meta); se ospite → cookie client (o AJAX nopriv)
    if (cfg.isLogged){
      try{
        const res = await fetch(cfg.ajax, {
          method:'POST',
          headers:{ 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
          body: new URLSearchParams({
            action: 'thub_set_locale',
            locale: loc,
            nonce:  cfg.nonce
          })
        });
        const json = await res.json();
        if(!res.ok || !json || !json.success) throw new Error('Errore salvataggio lingua');
      }catch(err){
        alert(err.message || 'Errore salvataggio lingua');
        return;
      }
    } else {
      // Ospite: salvo cookie 1 anno
      var cookie = 'thub_user_locale=' + encodeURIComponent(loc) + '; path=/; max-age=' + (60*60*24*365).toString();
      if (location.protocol === 'https:') cookie += '; secure';
      cookie += '; samesite=Lax';
      document.cookie = cookie;
    }

    closeDD();
    location.reload();
  });

  // Click fuori chiude
  document.addEventListener('click', (e)=>{
    const inside = e.target.closest('#thub-foot-lang');
    if(!inside && isOpen()) closeDD();
  });
  // ESC chiude
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && isOpen()) closeDD(); });
})();
</script>

</body>
</html>