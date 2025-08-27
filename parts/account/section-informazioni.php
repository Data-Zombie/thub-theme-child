<?php
/**
 * [THUB_ASSISTENZA_INFO] Centro assistenza — section-informazioni.php (rev finale+edit)
 * - Titoli centrati (H titolo in grassetto)
 * - Box 1: tendina CHIUSA di default; form a destra width 60% (full width su schermi piccoli)
 *   Pulsante "continua": chiude Box 1, lo BLOCCA, e apre Box 2
 * - Box 2: tendina NON cliccabile (niente freccia), apertura solo programmatica via JS
 *   Riepilogo: sfondo bianco, senza bordi; checkbox risposta email pre-selezionata; honeypot & nonce lato form
 *   Pulsanti: "modifica richiesta" (riapre Box 1 e chiude Box 2) + "Invia richiesta"
 * - Stile pulsanti UNIFICATO: background:#7249a4;color:#fff;border:1px solid #7249a4;border-radius:.6rem;padding:.55rem .9rem;cursor:pointer;
 *
 * Requisiti: handler in functions.php [THUB_SUPPORT_HANDLER] già presente (wp_mail() a info@t-hub.it)
 */
?>

<!-- ===========================
     [THUB_ASSISTENZA_TITLE] Titolo e testo centrati
     =========================== -->
<div class="thub-account__title" aria-level="1" role="heading" style="text-align:center;">
  <b>Centro assistenza</b>
</div>
<div style="height:.25rem;"></div>
<p style="margin:.2rem 0 1rem; color:#444; text-align:center;">Come possiamo aiutarti?</p>

<?php
// [THUB_SUPPORT_NOTICE] — Messaggi di esito (?thub_support=ok|invalid|spam|err)
$notice = isset($_GET['thub_support']) ? sanitize_key($_GET['thub_support']) : '';
if ($notice === 'ok'){
  echo '<div class="thub-box" style="border-color:#c9e7d6;background:#f2fbf6;color:#1f5134;">
          <strong>Richiesta inviata.</strong> Ti risponderemo via email il prima possibile.
        </div>';
} elseif ($notice === 'invalid'){
  echo '<div class="thub-box" style="border-color:#f5c2c7;background:#fff5f5;color:#7a1a1a;">
          <strong>Controlla i campi.</strong> La sintesi deve avere tra 10 e 100 caratteri e il messaggio non può essere vuoto.
        </div>';
} elseif ($notice === 'spam'){
  echo '<div class="thub-box" style="border-color:#f5c2c7;background:#fff5f5;color:#7a1a1a;">
          <strong>Si è verificato un problema.</strong> Riprova tra qualche istante.
        </div>';
} elseif ($notice === 'err'){
  echo '<div class="thub-box" style="border-color:#f5c2c7;background:#fff5f5;color:#7a1a1a;">
          <strong>Errore di invio.</strong> Non siamo riusciti a spedire la richiesta. Riprova.
        </div>';
}
?>

<!-- ===========================
     [THUB_ASSISTENZA_BOX1] Box 1 — tendina CHIUSA (nessun attributo open)
     =========================== -->
<div class="thub-box">
  <details id="thub-support-step1">
    <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.2rem 0;">
      <div>
        <div style="font-weight:700;">Hai bisogno di assistenza?</div>
        <div style="color:#666;font-size:.92rem; margin-top:.2rem;">
          Segui i passaggi indicati di seguito
        </div>
      </div>
      <!-- Freccia a destra leggermente più grande -->
      <span aria-hidden="true" style="display:inline-block;font-size:1.25rem;transform:translateY(2px)">▾</span>
    </summary>

    <!-- [THUB_ASSISTENZA_ROW] Wrapper: form a destra (60%), full width su small -->
    <div class="thub-assistenza__row" style="display:flex;justify-content:flex-end;margin-top:1rem;">
      <div class="thub-assistenza__pane" style="width:60%;min-width:320px;max-width:840px;">
        <!-- [THUB_SUPPORT_FORM] Form unico (gestito da handler in functions.php) -->
        <form id="thub-support-form" method="post" action="" novalidate>
          <?php /* [THUB_NONCE] Nonce sicurezza */ wp_nonce_field('thub_support', '_thub_support_nonce'); ?>
          <input type="hidden" name="thub_support_action" value="create" />

          <!-- [THUB_HP] HONEYPOT anti-bot (invisibile) -->
          <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
            <label for="thub_hp">Lascia vuoto</label>
            <input type="text" id="thub_hp" name="thub_hp" value="" autocomplete="off" />
          </div>

          <!-- [THUB_SUPPORT_SUBJECT] Sintesi 10..100 -->
          <div style="margin:.8rem 0;">
            <label for="thub_support_subject"><strong>Breve sintesi del problema</strong></label>
            <input
              type="text"
              id="thub_support_subject"
              name="thub_support_subject"
              aria-label="Breve sintesi del problema"
              placeholder="Non riesco a ..."
              minlength="10"
              maxlength="100"
              required
              style="width:100%;padding:.6rem;border:1px solid #e6e6ea;border-radius:.6rem;"
            />
            <div style="font-size:.85rem;color:#666;margin-top:.2rem;">Min 10, max 100 caratteri.</div>
          </div>

          <!-- [THUB_SUPPORT_MESSAGE] Messaggio dettagliato -->
          <div style="margin:.8rem 0;">
            <label for="thub_support_message"><strong>Spiega il problema riscontrato ed i passaggi per la risoluzione provati</strong></label>
            <textarea
              id="thub_support_message"
              name="thub_support_message"
              aria-label="Descrizione dettagliata del problema"
              placeholder="Non includere informazioni private o riservate come ad esempio i dati della carta di credito."
              rows="6"
              required
              style="width:100%;padding:.6rem;border:1px solid #e6e6ea;border-radius:.6rem;resize:vertical;"
            ></textarea>
          </div>

          <!-- [THUB_BTN_CONTINUA] Stile pulsanti unificato -->
          <div style="margin-top:.8rem;">
            <button type="button" id="thub-support-next"
              style="background:#7249a4;color:#fff;border:1px solid #7249a4;border-radius:.6rem;padding:.55rem .9rem;cursor:pointer;">
              continua
            </button>
          </div>
        </form>
      </div>
    </div>
  </details>
</div>

<!-- ===========================
     [THUB_ASSISTENZA_BOX2] Box 2 — tendina bloccata (no freccia, no toggle)
     =========================== -->
<div class="thub-box">
  <!-- Aggiungiamo .thub-locked per disabilitare il toggle a click -->
  <details id="thub-support-step2" class="thub-locked">
    <!-- Summary senza freccia; solo titolo -->
    <summary class="thub-summary--locked" style="padding:.2rem 0; display:flex; align-items:center;">
      <strong>Conferma e invia</strong>
    </summary>

    <div class="thub-assistenza__row" style="display:flex;justify-content:flex-end;margin-top:1rem;">
      <div class="thub-assistenza__pane" style="width:60%;min-width:320px;max-width:840px;">
        <!-- Testo riepilogo -->
        <div style="margin:.2rem 0 .6rem; color:#444;">Riepilogo dati</div>

        <!-- [THUB_SUPPORT_PREVIEW] Sfondo bianco, nessun bordo -->
        <div style="background:#fff;border:0;border-radius:.6rem;padding:.75rem;margin:.25rem 0;">
          <div style="margin:.25rem 0;">
            <span style="color:#666;">Sintesi: </span>
            <span id="thub-prev-subject">—</span>
          </div>
          <div style="margin:.25rem 0;">
            <div style="color:#666;">Messaggio:</div>
            <pre id="thub-prev-message" style="white-space:pre-wrap;margin:.25rem 0 0; font-family:inherit;"></pre>
          </div>
        </div>

        <!-- Checkbox risposta (pre-selezionata) -->
        <div style="margin:.8rem 0;">
          <label style="display:flex;gap:.5rem;align-items:flex-start;cursor:pointer;">
            <input type="checkbox" id="thub_support_reply_ok" name="thub_support_reply_ok" value="1" checked />
            <span>Ricevi risposta per email</span>
          </label>
          <div style="font-size:.85rem;color:#666;margin-top:.2rem;">
            Useremo l’indirizzo del tuo account per risponderti (se sei loggato).
          </div>
        </div>

        <!-- [THUB_BTN_BAR] Pulsanti: modifica richiesta + invio -->
        <div style="margin-top:.6rem; display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
          <!-- [THUB_BTN_EDIT] Nuovo pulsante: “modifica richiesta” -->
          <button type="button" id="thub-support-edit"
            style="background:#7249a4;color:#fff;border:1px solid #7249a4;border-radius:.6rem;padding:.55rem .9rem;cursor:pointer;">
            modifica richiesta
          </button>

          <!-- [THUB_BTN_SEND] Invia lo stesso form del Box 1 via JS -->
          <button type="button" id="thub-support-send"
            style="background:#7249a4;color:#fff;border:1px solid #7249a4;border-radius:.6rem;padding:.55rem .9rem;cursor:pointer;">
            Invia richiesta
          </button>
        </div>
      </div>
    </div>
  </details>
</div>

<!-- ===========================
     [THUB_SUPPORT_RESPONSIVE] Responsività: full width su schermi piccoli
     =========================== -->
<style>
  /* [THUB_DETAILS_LOCK] Disattiva toggle e nascondi marker del Box 2 */
  details.thub-locked > summary {
    cursor: default !important;
    pointer-events: none;      /* evita click */
    user-select: text;
  }
  details.thub-locked > summary::-webkit-details-marker { display: none; }
  details.thub-locked > summary::marker { content: ""; }

  /* Layout responsive del pannello a destra */
  @media (max-width: 920px){
    .thub-assistenza__row{ justify-content: flex-start !important; }
    .thub-assistenza__pane{ width: 100% !important; min-width: 0 !important; }
  }
</style>

<!-- ===========================
     [THUB_SUPPORT_JS] Logica step:
     - Chiudi Box1 al load (guard-rail)
     - "continua": valida → anteprima → chiudi & BLOCca Box1 → apri Box2
     - "modifica richiesta": chiudi Box2 → SBLOCca & apri Box1
     - "Invia richiesta": submit del form Box1
     =========================== -->
<script>
/* [THUB_SUPPORT_JS] Gestione step + lock/unlock details */
(function(){
  // Helper query
  const $ = (s, c=document)=>c.querySelector(s);

  // Riferimenti DOM
  const subj    = $('#thub_support_subject');   // input sintesi
  const msg     = $('#thub_support_message');   // textarea messaggio
  const next    = $('#thub-support-next');      // btn "continua"
  const send    = $('#thub-support-send');      // btn "Invia richiesta"
  const editBtn = $('#thub-support-edit');      // btn "modifica richiesta"
  const prevS   = $('#thub-prev-subject');      // anteprima sintesi
  const prevM   = $('#thub-prev-message');      // anteprima messaggio
  const step1   = $('#thub-support-step1');     // <details> Box 1
  const step2   = $('#thub-support-step2');     // <details> Box 2 (locked)
  const form    = $('#thub-support-form');      // form unico (Box 1)
  const replyOk = $('#thub_support_reply_ok');  // checkbox risposta email

  /* [THUB_GUARD] forza Box 1 chiuso al load */
  if (step1) { step1.open = false; }

  /* [THUB_LOCK_SUMMARY] Impedisci toggle del summary Box 2 (cintura+spallacci) */
  const s2summary = step2?.querySelector('summary');
  if (s2summary) {
    s2summary.addEventListener('click', (e)=>{ e.preventDefault(); e.stopImmediatePropagation(); }, true);
  }

  // Se manca qualcosa, esco senza errori
  if (!form || !subj || !msg || !next || !send || !prevS || !prevM || !step1 || !step2) return;

  /* [THUB_DETAILS_UTILS] Lock/Unlock dei <details> (disabilita toggle e marker via .thub-locked) */
  function lockDetails(detailsEl){
    if(!detailsEl) return;
    detailsEl.classList.add('thub-locked');
  }
  function unlockDetails(detailsEl){
    if(!detailsEl) return;
    detailsEl.classList.remove('thub-locked');
  }

  // Validazione campi
  function isValid(){
    const s = (subj.value||'').trim();
    const m = (msg.value||'').trim();
    return (s.length >= 10 && s.length <= 100 && m.length > 0);
  }

  // Costruisci anteprima riepilogo
  function buildPreview(){
    prevS.textContent = (subj.value||'').trim() || '—';
    prevM.textContent = (msg.value||'').trim() || '—';
  }

  // [THUB_STEP_CONTINUA] valida → anteprima → chiudi & BLOCca Box1 → apri Box2
  next.addEventListener('click', function(){
    if(!isValid()){
      alert('Controlla i campi: la sintesi deve avere tra 10 e 100 caratteri e il messaggio non può essere vuoto.');
      return;
    }
    buildPreview();

    // Chiudi e blocca Box 1 (niente riapertura a click)
    step1.open = false;
    lockDetails(step1);

    // Apri Box 2 (già bloccato a click dal markup/CSS)
    step2.open = true;
    step2.scrollIntoView({behavior:'smooth', block:'start'});
  });

  // [THUB_STEP_EDIT] Torna alla modifica: chiudi Box2 → SBLOCca & apri Box1
  if (editBtn){
    editBtn.addEventListener('click', function(){
      // Chiudi Box 2
      step2.open = false;

      // Sblocca e apri Box 1 (riabilitiamo il summary)
      unlockDetails(step1);
      step1.open = true;

      // Focus soft sull'input sintesi
      step1.scrollIntoView({behavior:'smooth', block:'start'});
      setTimeout(()=> subj?.focus(), 250);
    });
  }

  // [THUB_STEP_SEND] submit del form del Box 1
  send.addEventListener('click', function(){
    if(!isValid()){
      alert('Controlla i campi prima di inviare.');
      return;
    }
    // Se la checkbox fosse fuori dal form, replica come hidden (rete di sicurezza)
    if (replyOk && !replyOk.closest('form')) {
      let hidden = form.querySelector('input[name="thub_support_reply_ok"][type="hidden"]');
      if(!hidden){
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'thub_support_reply_ok';
        form.appendChild(hidden);
      }
      hidden.value = replyOk.checked ? '1' : '';
    }
    form.submit();
  });

  // Aggiorna anteprima in tempo reale
  subj.addEventListener('input', buildPreview);
  msg .addEventListener('input', buildPreview);
})();
</script>