<?php
/**
 * [THUB_SECTION] Lingua e regione dei risultati di ricerca
 * Route Canvas: /lingua-e-regione/lingua-e-regione-dei-risultati/
 * NOTE:
 * - Rimosso "Lingua attuale: …" (non richiesto)
 * - Fix duplicazione label nel dropdown (niente thub_format_locale('after') nelle <option>)
 * - Pulsante Salva spostato in Colonna 2 sotto la select
 * - Colonna 2 = 60% su desktop, responsive su mobile
 */
if ( ! defined('ABSPATH') ) exit;

$user_id      = get_current_user_id();
$locales      = function_exists('thub_results_get_locales') ? thub_results_get_locales() : [];
$current_lang = function_exists('thub_get_user_results_lang') ? thub_get_user_results_lang($user_id) : 'it';
?>
<section id="thub-results-lang" class="thub-section thub-results-lang-section">
  <style>
    /* ===========================
       [THUB_RESULTS_LANG_CSS] Stili locali alla section
       =========================== */
    .thub-results-lang-section{ padding: 1rem 0 2rem; }
    .thub-section__title{
      text-align:center; font-size:1.35rem; font-weight:700; margin:0 0 1rem;
    }
    /* Grid 40% | 60% su schermi grandi */
    .thub-box{
      display:grid; grid-template-columns: 40% 60%; gap: 18px;
      padding: 1rem; border:1px solid #ececf3; border-radius:.8rem; background:#fff;
    }
    .thub-col h3{ margin:.25rem 0 .35rem; font-size:1.05rem; }
    .thub-col p{ margin:.1rem 0 .8rem; color:#444; line-height:1.45; }

    /* Colonna 2: select full-width (la colonna è al 60%) */
    .thub-select-wrap{ display:flex; flex-direction:column; align-items:flex-start; }
    .thub-select{
      width:100%; /* riempi la colonna (che è al 60% del box) */
      max-width: 640px;
      padding:.5rem .6rem; border:1px solid #cfcfe8; border-radius:.6rem; background:#fff;
    }
    .thub-actions{
      margin-top:.8rem; display:flex; gap:.6rem; justify-content:flex-start;
    }
    .thub-btn-primary{
      border:1px solid #7249a4; border-radius:.6rem; padding:.45rem .8rem; background:#7249a4; color:#fff; cursor:pointer;
    }

    /* Responsive: su schermi piccoli impila a 1 colonna */
    @media (max-width: 900px){
      .thub-box{ grid-template-columns: 1fr; }
      .thub-select{ width:100%; max-width:100%; }
    }
  </style>

  <!-- [THUB_TITLE] Titolo -->
  <h2 class="thub-section__title">Lingua dei risultati di ricerca</h2>
  <p style="color:#555; text-align:center; margin: .4rem 0 1rem;">
      Utilizza i controlli nella barra di ricerca per attivare questo filtro
  </p>

  <!-- [THUB_BOX] 2 colonne: 40% testo | 60% select+salva -->
  <div class="thub-box" id="thub-box-results-lang">
    <!-- Colonna 1: testo -->
    <div class="thub-col">
      <h3>Lingua di visualizzazione dei risultati di ricerca</h3>
      <p>Seleziona la tua lingua preferita per i risultati di ricerca.</p>
      <!-- [THUB_NOTE_REMOVED] RIMOSSA la riga “Lingua attuale: …” come richiesto -->
    </div>

    <!-- Colonna 2: select + pulsante Salva -->
    <div class="thub-col thub-select-wrap">
      <!-- [THUB_RESULTS_SELECT] Elenco lingue UE. Fix duplicazione: NO thub_format_locale('after') -->
      <select id="thub-results-lang-select" class="thub-select" aria-label="Seleziona lingua risultati">
        <?php foreach( $locales as $code => $label ): ?>
          <option value="<?php echo esc_attr($code); ?>" <?php selected($code, $current_lang); ?>>
            <?php echo esc_html($label); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="thub-actions">
        <!-- [THUB_SAVE] Pulsante Salva spostato sotto la select -->
        <button type="button" class="thub-btn-primary" id="thub-save-results-lang">Salva</button>
      </div>
    </div>
  </div>

  <script>
  /* ===========================================================
     [THUB_RESULTS_LANG_JS] Salvataggio via AJAX
     - Loggati: update_user_meta
     - Ospiti: cookie
     - Rimosso aggiornamento label “Lingua attuale” (non più in UI)
     =========================================================== */
  (function(){
    const $ = (s,c=document)=>c.querySelector(s);
    const btn = $('#thub-save-results-lang');
    const sel = $('#thub-results-lang-select');
    if(!btn || !sel) return;

    btn.addEventListener('click', function(){
      const lang = sel.value;
      const fd = new FormData();
      fd.append('action', 'thub_save_results_lang');
      fd.append('lang', lang);
      fd.append('_ajax_nonce', (window.THUB_RESULTS_LANG && window.THUB_RESULTS_LANG.nonce) || '');

      fetch( (window.THUB_RESULTS_LANG && window.THUB_RESULTS_LANG.ajax) || '/wp-admin/admin-ajax.php', {
        method:'POST', credentials:'same-origin', body:fd
      })
      .then(r=>r.json())
      .then(json=>{
        if(json && json.success){
          alert('Lingua dei risultati salvata.');
        }else{
          alert((json && json.data && json.data.message) || 'Errore durante il salvataggio.');
        }
      })
      .catch(()=> alert('Errore di rete. Riprova.'));
    });
  })();
  </script>

    <!-- =========================================================
       [THUB_RESULTS_REGION_BOX2] — Box 2: Regione dei risultati (cucina)
       - Col 1 (40%): titolo + testo
       - Col 2 (60%): select cucine europee + pulsante Salva
       ========================================================= -->
  <?php
    // Helpers dal core (Passo A)
    $regions = function_exists('thub_get_results_region_choices') ? thub_get_results_region_choices() : [];
    $cur_reg = function_exists('thub_get_user_results_region') ? thub_get_user_results_region( get_current_user_id() ) : '';
  ?>

  <div class="thub-box" id="thub-box-results-region">
    <!-- Colonna 1 (40%) -->
    <div class="thub-col">
      <h3>Regione dei risultati di ricerca</h3>
      <p>Seleziona la regione preferita per i risultati di ricerca.</p>
    </div>

    <!-- Colonna 2 (60%) -->
    <div class="thub-col thub-select-wrap">
      <!-- [THUB_RESULTS_REGION_SELECT] Elenco cucine europee -->
      <label for="thub-results-region-select" class="screen-reader-text">Regione dei risultati</label>
      <select id="thub-results-region-select" class="thub-select" aria-label="Seleziona la regione/cucina">
        <?php foreach( $regions as $slug => $label ): ?>
          <option value="<?php echo esc_attr($slug); ?>" <?php selected($slug, $cur_reg); ?>>
            <?php echo esc_html($label); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="thub-actions">
        <!-- [THUB_RESULTS_REGION_SAVE_BTN] stile coerente al resto -->
        <button type="button" class="thub-btn-primary" id="thub-save-results-region">Salva</button>
      </div>
      <span id="thub-results-region-msg" style="margin-left:.6rem; font-size:.92rem; color:#2f7a3e;"></span>
    </div>
  </div>

  <script>
  /* ===========================================================
     [THUB_RESULTS_REGION_JS] Salvataggio via AJAX
     - Loggati: update_user_meta + cookie
     - Ospiti: solo cookie
     =========================================================== */
  (function(){
    const $   = (s,c=document)=>c.querySelector(s);
    const sel = $('#thub-results-region-select');
    const btn = $('#thub-save-results-region');
    const msg = $('#thub-results-region-msg');
    if(!sel || !btn) return;

    function say(t, ok=true){
      if(!msg) return;
      msg.textContent = t || (ok ? 'Salvato ✓' : 'Errore');
      msg.style.color = ok ? '#2f7a3e' : '#a13333';
      setTimeout(()=>{ msg.textContent=''; }, 2800);
    }

    btn.addEventListener('click', function(){
      const region = sel.value || '';
      if(!region){ say('Seleziona una regione valida', false); return; }

      const fd = new FormData();
      fd.append('action', 'thub_save_results_region');
      fd.append('value', region);
      fd.append('_ajax_nonce', (window.THUB_RESULTS_REGION && window.THUB_RESULTS_REGION.nonce) || '');

      fetch( (window.THUB_RESULTS_REGION && window.THUB_RESULTS_REGION.ajax) || '/wp-admin/admin-ajax.php', {
        method:'POST', credentials:'same-origin', body:fd
      })
      .then(r=>r.json())
      .then(json=>{
        if(json && (json.success || json.ok)){ say('Preferenza salvata.'); }
        else{ say((json && json.data && json.data.message) || 'Errore durante il salvataggio.', false); }
      })
      .catch(()=> say('Errore di rete. Riprova.', false));
    });
  })();
  </script>
</section>