<?php
/**
 * [THUB_CANVAS_SECTION: nuova-ricetta]
 * Section: Crea nuova ricetta (frontend editor)
 * Layout coerente con le altre section Canvas.
 * Salvataggio via AJAX → handler in functions.php [THUB_SAVE_USER_RECIPE_AJAX]
 */
if ( ! defined('ABSPATH') ) exit;

// -- [THUB_NR_NONCE] Nonce per sicurezza
$thub_nr_nonce = wp_create_nonce('thub_save_user_recipe');
$ajaxurl = admin_url('admin-ajax.php');
$current_user_can_publish = is_user_logged_in(); // controllo base; publish è validato anche server-side
?>

<section class="thub-canvas-section thub-nuova-ricetta"><!-- [THUB_NR_SECTION_WRAPPER] -->
  <!-- Titolo + sottotitolo -->
  <header class="thub-nr-header">
    <h1 class="thub-title">Inserisci la tua ricetta</h1>
    <p class="thub-subtitle">Ti diamo il benvenuto nell'editor di creazione delle tue ricette</p>
  </header>

  <!-- ===========================
       [THUB_NR_FORM] Form principale
       =========================== -->
  <form id="thub-nr-form"
        class="thub-nr-form"
        method="post"
        action="<?php echo esc_url($ajaxurl); ?>"
        novalidate
        data-ajaxurl="<?php echo esc_url($ajaxurl); ?>"
        data-nonce="<?php echo esc_attr($thub_nr_nonce); ?>">

    <input type="hidden" name="action" value="thub_save_user_recipe"><!-- [THUB_NR_ACTION] -->
    <input type="hidden" name="thub_nr_nonce" value="<?php echo esc_attr($thub_nr_nonce); ?>">
    <input type="hidden" name="thub_submit_type" id="thub_submit_type" value="draft"><!-- draft|publish -->

    <!-- ================== BOX 1 — full width ================== -->
    <div class="thub-box"><!-- [THUB_NR_BOX1] -->
      <div class="thub-grid thub-grid--2">
        <!-- Colonna 1: SVG cappello -->
        <div class="thub-col thub-col--center">
          <!-- [THUB_NR_CHEF_HAT] SVG cappello da chef (centrato) -->
          <div class="thub-chef-hat" aria-hidden="true">
            <svg viewBox="0 0 120 120" role="img" aria-label="Crea ricetta">
              <title>Crea ricetta</title>
              <defs>
                <linearGradient id="thubHatG" x1="0" y1="0" x2="1" y2="1">
                  <stop offset="0" stop-color="#f2f2f2"/>
                  <stop offset="1" stop-color="#e0e0e0"/>
                </linearGradient>
              </defs>
              <circle cx="60" cy="60" r="50" fill="#fff3e6"/>
              <path d="M40 60c-8 0-14-6-14-14s6-14 14-14c4 0 7 1 10 4 3-3 6-4 10-4s7 1 10 4c3-3 6-4 10-4 8 0 14 6 14 14s-6 14-14 14H40z" fill="url(#thubHatG)"/>
              <rect x="42" y="60" width="36" height="22" rx="6" ry="6" fill="#ffd6a1"/>
              <rect x="46" y="82" width="28" height="8" rx="4" ry="4" fill="#ffbd6b"/>
            </svg>
          </div>
        </div>

        <!-- Colonna 2: Titolo + Intro -->
        <div class="thub-col">
          <!-- Titolo ricetta -->
          <label class="thub-label" for="thub_titolo">Titolo ricetta</label>
          <input type="text" id="thub_titolo" name="post_title"
                 placeholder="Titolo ricetta"
                 maxlength="120"
                 class="thub-input"
                 required>
          <p class="thub-help">(max ~50 caratteri consigliati)</p>

          <!-- Breve descrizione -->
          <label class="thub-label mt" for="thub_intro_breve">Breve descrizione</label>
          <input type="text" id="thub_intro_breve" name="intro_breve"
                 placeholder="Breve descrizione"
                 maxlength="180"
                 class="thub-input">
          <p class="thub-help">(max ~150 caratteri consigliati)</p>
        </div>
      </div>
    </div>

    <!-- ================== BOX 2 — full width ================== -->
    <div class="thub-box"><!-- [THUB_NR_BOX2] -->
      <div class="thub-grid thub-grid--4">
        <div class="thub-col">
          <label class="thub-label" for="thub_porzioni">Porzioni base</label>
          <input type="number" min="1" step="1" id="thub_porzioni" name="porzioni_base" class="thub-input" placeholder="Es. 4" required>
        </div>
        <div class="thub-col">
          <label class="thub-label" for="thub_kcal">Kcal per porzione</label>
          <input type="number" min="0" step="1" id="thub_kcal" name="kcal_per_porz" class="thub-input" placeholder="Es. 350">
        </div>
        <div class="thub-col">
          <label class="thub-label" for="thub_tprep">Tempo di preparazione</label>
          <input type="text" id="thub_tprep" name="tempo_di_preparazione" class="thub-input" placeholder="Es. 20 min">
        </div>
        <div class="thub-col">
          <label class="thub-label" for="thub_tcott">Tempo di cottura</label>
          <input type="text" id="thub_tcott" name="tempo_di_cottura" class="thub-input" placeholder="Es. 30 min">
        </div>
      </div>
    </div>

    <!-- ================== BOX 3 — full width ================== -->
    <div class="thub-box"><!-- [THUB_NR_BOX3] -->
      <label class="thub-label" for="thub_video_url">Link Video</label>
      <input type="url" id="thub_video_url" name="video_url" class="thub-input" placeholder="https://...">
    </div>

    <!-- ================== BOX 4 — full width (Ingredienti strutturati) ================== -->
    <div class="thub-box"><!-- [THUB_NR_BOX4] -->
      <h3 class="thub-box-title">Ingredienti strutturati</h3>
      <div id="thub-ing-repeater" class="thub-repeater" data-next-index="1">
        <!-- Riga template (clonata da JS) -->
        <template id="tpl-ing-row"><!-- [THUB_NR_ING_TEMPLATE] -->
          <div class="thub-grid thub-grid--3 thub-repeater-row">
            <div class="thub-col">
              <label class="thub-sr-only">Nome ingrediente</label>
              <input type="text" name="ingredienti[__i__][nome]" class="thub-input" placeholder="Nome ingrediente" required>
            </div>
            <div class="thub-col">
              <label class="thub-sr-only">Quantità</label>
              <input type="text" name="ingredienti[__i__][qta]" class="thub-input" placeholder="Quantità (es. 200)">
            </div>
            <div class="thub-col thub-grid thub-grid--2xs thub-unit">
              <div>
                <label class="thub-sr-only">Unità</label>
                <select name="ingredienti[__i__][unita]" class="thub-input">
                  <option value="">Unità</option>
                  <option value="g">g</option>
                  <option value="ml">ml</option>
                  <option value="pz">pezzo</option>
                  <option value="altro">Altro…</option>
                </select>
              </div>
              <div>
                <label class="thub-sr-only">Altro (unità)</label>
                <input type="text" name="ingredienti[__i__][unita_altro]" class="thub-input" placeholder="Specifica" />
              </div>
              <button type="button" class="thub-btn thub-btn--ghost thub-repeater-remove" aria-label="Rimuovi ingrediente">&times;</button>
            </div>
          </div>
        </template>

        <!-- Prima riga iniziale -->
        <div class="thub-grid thub-grid--3 thub-repeater-row">
          <div class="thub-col">
            <label class="thub-sr-only">Nome ingrediente</label>
            <input type="text" name="ingredienti[0][nome]" class="thub-input" placeholder="Nome ingrediente" required>
          </div>
          <div class="thub-col">
            <label class="thub-sr-only">Quantità</label>
            <input type="text" name="ingredienti[0][qta]" class="thub-input" placeholder="Quantità (es. 200)">
          </div>
          <div class="thub-col thub-grid thub-grid--2xs thub-unit">
            <div>
              <label class="thub-sr-only">Unità</label>
              <select name="ingredienti[0][unita]" class="thub-input">
                <option value="">Unità</option>
                <option value="g">g</option>
                <option value="ml">ml</option>
                <option value="pz">pezzo</option>
                <option value="altro">Altro…</option>
              </select>
            </div>
            <div>
              <label class="thub-sr-only">Altro (unità)</label>
              <input type="text" name="ingredienti[0][unita_altro]" class="thub-input" placeholder="Specifica" />
            </div>
            <button type="button" class="thub-btn thub-btn--ghost thub-repeater-remove" aria-label="Rimuovi ingrediente">&times;</button>
          </div>
        </div>

        <div class="thub-repeater-ctrl">
          <button type="button" id="thub-ing-add" class="thub-btn thub-btn--secondary">+ Aggiungi ingrediente</button>
        </div>
      </div>
    </div>

    <!-- ================== BOX 5 — full width (Icona ricetta) ================== -->
    <div class="thub-box"><!-- [THUB_NR_BOX5] -->
      <div class="thub-grid thub-grid--2">
        <div class="thub-col">
          <label class="thub-label">Icona</label>
          <!-- [THUB_NR_ICON_SELECT] Lista icone; coerente con nomenclatura “attrezzature” -->
          <select name="ricetta_icona" class="thub-input">
            <option value="">— Seleziona icona —</option>
            <option value="forno">Forno</option>
            <option value="pentola">Pentola</option>
            <option value="padella">Padella</option>
            <option value="frusta">Frusta</option>
            <option value="teglia">Teglia</option>
            <option value="coltello">Coltello</option>
          </select>
        </div>
        <div class="thub-col">
          <label class="thub-label">Descrizione icona</label>
          <input type="text" name="ricetta_icona_desc" class="thub-input" placeholder="Breve descrizione (facoltativa)">
        </div>
      </div>
    </div>

    <!-- ================== BOX 6 — full width (Passaggi preparatori) ================== -->
    <div class="thub-box"><!-- [THUB_NR_BOX6] -->
      <h3 class="thub-box-title">Passaggi preparatori</h3>

      <div id="thub-steps-repeater" class="thub-repeater" data-next-index="2">
        <template id="tpl-step-row"><!-- [THUB_NR_STEP_TEMPLATE] -->
          <div class="thub-repeater-row thub-grid thub-grid--1">
            <div class="thub-col thub-grid thub-grid--step">
              <span class="thub-step-idx">#<span class="n">__i__</span></span>
              <input type="text" name="passaggi[__i__]" class="thub-input" placeholder="Descrizione passaggio __i__" required>
              <button type="button" class="thub-btn thub-btn--ghost thub-repeater-remove" aria-label="Rimuovi passaggio">&times;</button>
            </div>
          </div>
        </template>

        <!-- Passaggio 1 iniziale -->
        <div class="thub-repeater-row thub-grid thub-grid--1">
          <div class="thub-col thub-grid thub-grid--step">
            <span class="thub-step-idx">#<span class="n">1</span></span>
            <input type="text" name="passaggi[1]" class="thub-input" placeholder="Descrizione passaggio 1" required>
            <button type="button" class="thub-btn thub-btn--ghost thub-repeater-remove" aria-label="Rimuovi passaggio">&times;</button>
          </div>
        </div>

        <div class="thub-repeater-ctrl">
          <button type="button" id="thub-step-add" class="thub-btn thub-btn--secondary">+ Aggiungi passaggio</button>
        </div>
      </div>
    </div>

    <!-- ================== BOX 7 — full width (Note tecniche) ================== -->
    <div class="thub-box"><!-- [THUB_NR_BOX7] -->
      <label class="thub-label" for="thub_note">Eventuali note tecniche</label>
      <textarea id="thub_note" name="eventuali_note_tecniche" class="thub-input" rows="6" placeholder="Note tecniche, consigli, avvertenze..."></textarea>
    </div>

    <!-- ================== BOX 8 — full width (Vino di accompagnamento) ================== -->
    <div class="thub-box"><!-- [THUB_NR_BOX8] -->
      <h3 class="thub-box-title">Vino di accompagnamento</h3>
      <div class="thub-grid thub-grid--2">
        <div class="thub-col">
          <label class="thub-label" for="thub_vino_nome">Nome e anno di produzione</label>
          <input type="text" id="thub_vino_nome" name="vino_nome" class="thub-input" placeholder="Es. Barolo 2018">
        </div>
        <div class="thub-col">
          <label class="thub-label" for="thub_vino_cantina">Cantina di produzione</label>
          <input type="text" id="thub_vino_cantina" name="vino_denominazione" class="thub-input" placeholder="Es. Cantina Marchesi...">
        </div>
      </div>
    </div>

    <!-- ================== BOX 9 — full width (Completamento) ================== -->
    <div class="thub-box"><!-- [THUB_NR_BOX9] -->
      <h3 class="thub-box-title">Completa o salva per dopo</h3>

      <div class="thub-grid thub-grid--4 thub-align-center">
        <!-- Radio: Ricette della Nonna -->
        <div class="thub-col">
          <label class="thub-radio">
            <input type="radio" name="ricetta_visibilita" value="nonna" checked>
            <span>Salva in “Ricette della Nonna”</span>
          </label>
          <span class="thub-tooltip" aria-label="Visibili solo a te" title="Visibili solo a te">ℹ︎</span>
        </div>

        <!-- Radio: Ricette dello Chef (Pro) -->
        <div class="thub-col">
          <label class="thub-radio">
            <input type="radio" name="ricetta_visibilita" value="chef">
            <span>Salva in “Ricette dello Chef” (Pro)</span>
          </label>
          <span class="thub-tooltip" aria-label="Pubblicata nel T-Hub market" title="Pubblicata nel T-Hub market">ℹ︎</span>
        </div>

        <!-- Salva in bozze -->
        <div class="thub-col">
          <button type="button" class="thub-btn thub-btn--outline" id="thub_btn_draft">Salva in bozze</button>
        </div>

        <!-- Pubblica -->
        <div class="thub-col">
          <button type="button" class="thub-btn thub-btn--primary" id="thub_btn_publish" <?php disabled( ! $current_user_can_publish ); ?>>Pubblica</button>
        </div>
      </div>

      <!-- Messaggi -->
      <div id="thub-nr-msg" class="thub-msg" aria-live="polite"></div>
    </div>
  </form>

  <!-- ========== CSS minimo (scoped) per allineare al Canvas esistente ========== -->
  <style>
    /* [THUB_NR_CSS] Regole minime e non invasive (scoped su .thub-nuova-ricetta) */
    .thub-nuova-ricetta .thub-title { text-align:center; margin: .2rem 0 0; }
    .thub-nuova-ricetta .thub-subtitle { text-align:center; color:#666; margin: .25rem 0 1.25rem; }
    .thub-nuova-ricetta .thub-box { background:#fff; border:1px solid #eee; border-radius:12px; padding:16px; margin:16px 0; }
    .thub-nuova-ricetta .thub-box--half { max-width:980px; margin-inline:auto; }
    .thub-nuova-ricetta .thub-grid { display:grid; gap:12px; align-items:start; }
    .thub-nuova-ricetta .thub-grid--2 { grid-template-columns: 1fr 1fr; }
    .thub-nuova-ricetta .thub-grid--3 { grid-template-columns: 1.2fr .8fr 1.2fr; }
    .thub-nuova-ricetta .thub-grid--4 { grid-template-columns: repeat(4, 1fr); }
    .thub-nuova-ricetta .thub-grid--2xs { grid-template-columns: 1fr 1fr; gap:8px; }
    .thub-nuova-ricetta .thub-grid--step { grid-template-columns: auto 1fr auto; gap:10px; align-items:center; }
    .thub-nuova-ricetta .thub-col--center { display:flex; justify-content:center; align-items:center; min-height:240px; }
    .thub-nuova-ricetta .thub-chef-hat svg { width:160px; height:160px; display:block; }
    .thub-nuova-ricetta .thub-label { font-weight:600; display:block; margin-bottom:4px; }
    .thub-nuova-ricetta .thub-input { width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:10px; }
    .thub-nuova-ricetta .thub-help { color:#888; font-size:.9em; margin:.35rem 0 0; }
    .thub-nuova-ricetta .thub-box-title { margin:.2rem 0 .75rem; font-weight:700; }
    .thub-nuova-ricetta .thub-repeater-row { background:#fafafa; border:1px dashed #e3e3e3; padding:10px; border-radius:10px; }
    .thub-nuova-ricetta .thub-repeater-ctrl { margin-top:10px; }
    .thub-nuova-ricetta .thub-step-idx { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; border:1px solid #ddd; background:#fff; font-weight:600; }
    .thub-nuova-ricetta .thub-btn { padding:10px 14px; border-radius:10px; border:1px solid transparent; cursor:pointer; }
    .thub-nuova-ricetta .thub-btn--secondary { background:#f5f7ff; border-color:#dfe6ff; }
    .thub-nuova-ricetta .thub-btn--ghost { background:transparent; border-color:#ddd; }
    .thub-nuova-ricetta .thub-btn--outline { background:#fff; border-color:#7249a4; color:#7249a4; }
    .thub-nuova-ricetta .thub-btn--primary { background:#7249a4; color:#fff; }
    .thub-nuova-ricetta .thub-tooltip { margin-left:6px; cursor:help; color:#999; }
    .thub-nuova-ricetta .thub-align-center { align-items:center; }
    .thub-nuova-ricetta .mt { margin-top:10px; }
    .thub-nuova-ricetta .thub-sr-only { position:absolute; left:-10000px; top:auto; width:1px; height:1px; overflow:hidden; }
    .thub-nuova-ricetta .thub-msg { margin-top:12px; font-weight:600; }
    @media (max-width: 920px){
      .thub-nuova-ricetta .thub-grid--2,
      .thub-nuova-ricetta .thub-grid--3,
      .thub-nuova-ricetta .thub-grid--4 { grid-template-columns: 1fr; }
      .thub-nuova-ricetta .thub-col--center { min-height:160px; }
    }
  </style>

  <!-- ========== JS: Repeater + Submit AJAX ========== -->
  <script>
  // [THUB_NR_JS] — Vanilla JS, no deps. Gestisce repeater e submit AJAX.
  (function(){
    const form = document.getElementById('thub-nr-form');
    const msg  = document.getElementById('thub-nr-msg');
    const ajaxurl = form?.dataset?.ajaxurl || '<?php echo esc_js($ajaxurl); ?>';
    const nonce   = form?.dataset?.nonce || '<?php echo esc_js($thub_nr_nonce); ?>';

    // ===== Repeater Ingredienti =====
    const ingWrap = document.getElementById('thub-ing-repeater');
    const ingAdd  = document.getElementById('thub-ing-add');
    const ingTpl  = document.getElementById('tpl-ing-row').innerHTML;

    ingAdd?.addEventListener('click', function(){
      const i = parseInt(ingWrap.getAttribute('data-next-index') || '1', 10);
      const html = ingTpl.replaceAll('__i__', String(i));
      const div = document.createElement('div');
      div.className = 'thub-grid thub-grid--3 thub-repeater-row';
      div.innerHTML = html;
      ingWrap.insertBefore(div, ingWrap.querySelector('.thub-repeater-ctrl'));
      ingWrap.setAttribute('data-next-index', String(i+1));
    });

    ingWrap?.addEventListener('click', function(e){
      if(e.target.closest('.thub-repeater-remove')){
        const row = e.target.closest('.thub-repeater-row');
        row?.remove();
      }
    });

    // ===== Repeater Passaggi =====
    const stWrap = document.getElementById('thub-steps-repeater');
    const stAdd  = document.getElementById('thub-step-add');
    const stTpl  = document.getElementById('tpl-step-row').innerHTML;

    stAdd?.addEventListener('click', function(){
      const i = parseInt(stWrap.getAttribute('data-next-index') || '2', 10);
      const html = stTpl.replaceAll('__i__', String(i));
      const div = document.createElement('div');
      div.className = 'thub-repeater-row thub-grid thub-grid--1';
      div.innerHTML = html;
      stWrap.insertBefore(div, stWrap.querySelector('.thub-repeater-ctrl'));
      stWrap.setAttribute('data-next-index', String(i+1));
    });

    stWrap?.addEventListener('click', function(e){
      if(e.target.closest('.thub-repeater-remove')){
        const row = e.target.closest('.thub-repeater-row');
        row?.remove();
        // Re-index visuale (#n)
        Array.from(stWrap.querySelectorAll('.thub-step-idx .n')).forEach((el, idx) => el.textContent = String(idx+1));
      }
    });

    // ===== Bottoni submit (bozza/pubblica) =====
    document.getElementById('thub_btn_draft')?.addEventListener('click', () => submitForm('draft'));
    document.getElementById('thub_btn_publish')?.addEventListener('click', () => submitForm('publish'));

    function submitForm(type){
      if(!form) return;
      document.getElementById('thub_submit_type').value = type === 'publish' ? 'publish' : 'draft';

      msg.textContent = 'Salvataggio in corso...';
      msg.style.color = '#444';

      const formData = new FormData(form);
      formData.set('thub_nr_nonce', nonce);
      formData.set('action', 'thub_save_user_recipe');

      fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
      })
      .then(r => r.json())
      .then(json => {
        if(json?.success){
          msg.textContent = json.message || 'Salvato con successo.';
          msg.style.color = '#2a7a2a';
          if(json.redirect){
            window.location.href = json.redirect;
          }
        } else {
          msg.textContent = json?.message || 'Si è verificato un errore.';
          msg.style.color = '#a33';
        }
      })
      .catch(() => {
        msg.textContent = 'Errore di rete. Riprova.';
        msg.style.color = '#a33';
      });
    }
  })();
  </script>
</section>