/* ============================================================
 * [THUB_JS_RECIPE] Scaling porzioni, kcal, carousel, gating
 * Build 2.1.4 — 2025-08-20
 * - UI porzioni con ricalcolo ingredienti e kcal
 * - Carousel Varianti (scroll-snap) con frecce contestuali
 * - Gating Print/Share con tooltip se locked
 * Requisiti markup:
 *  - #thub-porzioni (input number), .thub-porzioni-ui[data-base-porzioni][data-kcal-porz]
 *  - #thub-porzioni-label, #thub-porz-dyn, #thub-kcal-porz, #thub-kcal-tot
 *  - #thub-ingredienti-list .thub-ing[data-base-qta][data-unit] > .thub-ing__qta/.thub-ing__unit
 *  - Varianti: .thub-variants #thub-variants-carousel .thub-variant + .thub-carousel__prev/.thub-carousel__next
 * ============================================================ */
(function(){
  'use strict';

  /* -----------------------------
   * [THUB_QUERY] helper QS
   * ----------------------------- */
  const $  = (sel, ctx=document) => ctx.querySelector(sel);
  const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));

  /* ============================================================
   * [THUB_PORZIONI] Ricalcolo quantità ingredienti + kcal
   * ============================================================ */
  const porzInput  = $('#thub-porzioni');          // input numero porzioni
  const porzUI     = $('.thub-porzioni-ui');       // wrapper con data-base-porzioni / data-kcal-porz
  const porzLabel  = $('#thub-porzioni-label');    // etichetta testuale porzioni (es. "2")
  const porzDyn    = $('#thub-porz-dyn');          // eventuale etichetta extra (hero/meta)
  const kcalPorzEl = $('#thub-kcal-porz');         // kcal per porzione (statiche)
  const kcalTotEl  = $('#thub-kcal-tot');          // kcal totali (ricalcolate)

  function recalc(){
    if(!porzInput || !porzUI) return; // UI assente → esci

    // Base porzioni dall’attributo data (fallback 1)
    const base = parseInt(porzUI.dataset.basePorzioni || '1', 10) || 1;

    // Valore richiesto (>=1) dal campo input
    const wantRaw = parseInt(porzInput.value || base, 10);
    const want    = Math.max(1, isNaN(wantRaw) ? base : wantRaw);

    // Fattore di scala
    const factor = want / base;

    // Aggiorna etichette porzioni
    if (porzLabel) porzLabel.textContent = String(want);
    if (porzDyn)   porzDyn.textContent   = String(want);

    // Aggiorna ingredienti riga per riga
    $$('#thub-ingredienti-list .thub-ing').forEach(li => {
      const qtaEl  = li.querySelector('.thub-ing__qta');
      const unitEl = li.querySelector('.thub-ing__unit');
      if(!qtaEl || !unitEl) return; // guard-rail markup

      const q0   = parseFloat(li.dataset.baseQta || '0');
      const unit = li.dataset.unit || '';

      if (isNaN(q0)) {
        qtaEl.textContent  = '—';
        unitEl.textContent = unit;
        return;
      }

      const qNew = q0 * factor;
      qtaEl.textContent  = (Math.round(qNew * 100) / 100).toString(); // max 2 decimali
      unitEl.textContent = unit;
    });

    // Kcal (singola porzione + totale arrotondato)
    const kcalPorz = parseFloat(porzUI.dataset.kcalPorz || '0');
    if (kcalPorzEl) kcalPorzEl.textContent = (kcalPorz > 0) ? String(Math.round(kcalPorz))      : '—';
    if (kcalTotEl)  kcalTotEl.textContent  = (kcalPorz > 0) ? String(Math.round(kcalPorz*want)) : '—';
  }

  // Bind UI porzioni
  if (porzInput && porzUI){
    const clamp = v => Math.max(1, parseInt(v||'1',10) || 1);
    recalc();

    /* [THUB_PORZIONI_BTN] evita 0 con il tasto “-” */
    $('.thub-porz-minus')?.addEventListener('click', ()=>{
      porzInput.value = String(Math.max(1, clamp(porzInput.value) - 1));
      recalc();
    });
    $('.thub-porz-plus')?.addEventListener('click', ()=>{
      porzInput.value = String(clamp(porzInput.value) + 1);
      recalc();
    });

    /* [THUB_PORZIONI_QUICK] preset rapidi */
    $$('.thub-porz-quick button').forEach(b=>{
      b.addEventListener('click', ()=>{
        porzInput.value = b.dataset.q || '1';
        recalc();
      });
    });

    /* [THUB_PORZIONI_INPUT] UX migliore durante digitazione */
    porzInput.addEventListener('change', recalc);
    porzInput.addEventListener('input',  recalc);
  }

  /* ============================================================
   * [THUB_JS_RECIPE_VARIANTS_SCOPE] Carousel Varianti (scoped)
   * - Frecce mostrate solo se >1 card (gestione classe .is-hidden)
   * - Scope limitato al blocco .thub-variants per evitare collisioni
   * ============================================================ */
  (function(){
    const car = document.getElementById('thub-variants-carousel'); // container carousel
    if(!car) return;

    // Scope locale al blocco varianti
    const scope = car.closest('.thub-variants') || document;

    const prev  = scope.querySelector('.thub-carousel__prev'); // freccia prev
    const next  = scope.querySelector('.thub-carousel__next'); // freccia next
    const cards = car.querySelectorAll('.thub-variant');        // card item

    // Se c’è 0/1 card, nascondi frecce (e rendile non focusabili)
    const toggleArrows = () => {
      const hide = (cards.length <= 1);
      [prev, next].forEach(btn => {
        if(!btn) return;
        btn.classList.toggle('is-hidden', hide);
        btn.setAttribute('aria-hidden', hide ? 'true' : 'false');
        btn.setAttribute('aria-disabled', hide ? 'true' : 'false');
        btn.tabIndex = hide ? -1 : 0;
        if ('disabled' in btn) btn.disabled = !!hide; // per <button>
      });
    };

    // Larghezza di una card (fallback 280px) + gap 16px
    const cardW = () => {
      const el = car.querySelector('.thub-variant');
      return el ? el.getBoundingClientRect().width + 16 : 280 + 16;
    };

    // Bind frecce (smooth scroll)
    prev && prev.addEventListener('click', () => {
      car.scrollBy({ left: -cardW(), behavior: 'smooth' });
    });
    next && next.addEventListener('click', () => {
      car.scrollBy({ left:  cardW(), behavior: 'smooth' });
    });

    toggleArrows();
  })();

  /* ============================================================
   * [THUB_GATING_ACTIONS_SELECTORS] supporta markup legacy (.ricetta-actions, .btn-print/.btn-share)
   * ============================================================ */
  $$('.thub-actions .thub-btn, .ricetta-actions .thub-btn, .thub-actions .btn-print, .thub-actions .btn-share, .ricetta-actions .btn-print, .ricetta-actions .btn-share').forEach(btn=>{
    const locked  = btn.classList.contains('is-locked');
    const isPrint = btn.classList.contains('thub-btn--print') || btn.classList.contains('btn-print');

    if (!locked && isPrint) {
      btn.addEventListener('click', ()=> window.print());
    } else if (locked) {
      btn.addEventListener('click', (e)=>{
        e.preventDefault();
        const msg = btn.dataset.lockMsg || 'Disponibile con Pro';
        btn.setAttribute('data-thub-tooltip', msg);
        btn.classList.add('show-tip');
        setTimeout(()=>btn.classList.remove('show-tip'), 1500);
      });
    }
  });
})();

/* ============================================================
   [THUB_TTS_PLAY] — Lettura vocale ricetta (rev-safe)
   - Bottone: .thub-tts-btn
   - Testo:   [data-ricetta-tts]
   - Note: onend legato all’ULTIMO utterance; nessun listener globale
   ============================================================ */
(function(){
  const btn = document.querySelector('.thub-tts-btn');
  const src = document.querySelector('[data-ricetta-tts]');
  if(!btn || !src) return;
  if(!('speechSynthesis' in window)) return;

  let speaking = false;

  // [THUB_TTS_STATE] bottone icon-only: aria-pressed + title + classe
  const setState = (isOn) => {
    btn.setAttribute('aria-pressed', isOn ? 'true' : 'false');
    btn.title = isOn ? 'Interrompi lettura' : 'Leggi la ricetta';
    btn.classList.toggle('is-on', !!isOn);
  };

  // [THUB_TTS_SPLIT] separa titolo/ingredienti/passaggi da [data-ricetta-tts]
  const splitSections = (txt) => {
    const t = (txt || '').trim();
    const parts = { title: '', ing: '', steps: '' };
    const mIng = t.indexOf('Ingredienti:');
    const mPas = t.indexOf('Passaggi:');

    if(mIng === -1 && mPas === -1){
      parts.title = t;
      return parts;
    }
    if(mIng > -1){
      parts.title = t.substring(0, mIng).trim();
      if(mPas > -1){
        parts.ing   = t.substring(mIng + 'Ingredienti:'.length, mPas).trim();
        parts.steps = t.substring(mPas + 'Passaggi:'.length).trim();
      } else {
        parts.ing = t.substring(mIng + 'Ingredienti:'.length).trim();
      }
    } else {
      parts.title = t.substring(0, mPas).trim();
      parts.steps = t.substring(mPas + 'Passaggi:'.length).trim();
    }
    return parts;
  };

  // [THUB_TTS_END] chiusura stato al termine o alla cancellazione
  const onEndOrStop = () => {
    speaking = false;
    setState(false);
  };

  // Uscendo dalla pagina fermiamo eventuale coda vocale
  window.addEventListener('beforeunload', () => window.speechSynthesis.cancel());

  // [THUB_TTS_SPEAK] parla in coda; onend sull’ultimo utterance
  const speakQueue = (texts) => {
    const { speechSynthesis: synth } = window;
    synth.cancel(); // pulizia coda precedente

    const queue = [];
    if (texts.title) queue.push({ t: texts.title, r: 0.98 });
    if (texts.ing)   queue.push({ t: 'Ingredienti: ' + texts.ing, r: 0.96 });
    if (texts.steps) queue.push({ t: 'Passaggi: ' + texts.steps,  r: 0.98 });

    queue.forEach((item, i) => {
      const u = new SpeechSynthesisUtterance(item.t);
      u.lang = 'it-IT';
      u.rate = item.r;
      if (i === queue.length - 1) { // ultimo pezzo → ripristina stato al termine
        u.onend = onEndOrStop; // [THUB_TTS_ONEND_FIX]
      }
      synth.speak(u);
    });
  };

  // [THUB_TTS_CLICK] toggle lettura
  btn.addEventListener('click', () => {
    const text = (src.textContent || '').trim();
    if(!text) return;

    if(!speaking){
      speaking = true;
      setState(true);
      const sections = splitSections(text);
      speakQueue(sections);
    } else {
      window.speechSynthesis.cancel();
      onEndOrStop();
    }
  });
})();

/* ============================================================
 * [THUB_FAV_TOGGLE_JS] Gestione click “Preferiti” (AJAX)
 * ============================================================ */
(function(){
  'use strict';
  const on = (el, ev, fn)=> el && el.addEventListener(ev, fn, false);
  const qs = (s, c)=> (c||document).querySelector(s);

  on(document, 'click', function(ev){
    const btn = ev.target.closest && ev.target.closest('.thub-btn--fav');
    if(!btn) return;

    const ajax  = btn.getAttribute('data-ajax');
    const nonce = btn.getAttribute('data-nonce');
    const pid   = btn.getAttribute('data-post');
    const login = btn.getAttribute('data-login-url') || '/login';
    const now   = window.location.href;

    // Disabilita momentaneamente per evitare doppio click
    btn.disabled = true;

    // Chiamata AJAX
    const fd = new FormData();
    fd.append('action','thub_toggle_favorite');
    fd.append('nonce', nonce);
    fd.append('post_id', pid);
    fd.append('redirect_to', now);

    fetch(ajax, { method:'POST', credentials:'same-origin', body: fd })
      .then(r => r.json().catch(()=>({success:false})).then(data => ({ok:r.ok, status:r.status, data})))
      .then(({ok, status, data})=>{
        if(!ok){
          // Non loggato → redirect al login
          if (data && data.data && data.data.code === 'not_logged' && data.data.login_url){
            window.location.href = data.data.login_url;
            return;
          }
          // Altro errore: ripristina e esci
          btn.disabled = false;
          return;
        }
        // Successo: toggle UI
        const added = data.data && data.data.status === 'added';
        btn.setAttribute('aria-pressed', added ? 'true' : 'false');
        btn.classList.toggle('is-on', added);
        const lab = qs('.thub-fav-label', btn);
        if(lab) lab.textContent = added ? 'Salvata' : 'Salva';
        btn.disabled = false;
      })
      .catch(()=>{
        btn.disabled = false;
      });
  });
})();