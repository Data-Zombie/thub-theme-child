<?php
/**
 * ============================================================
 * [THUB_CANVAS_SECTION: nuova-ricetta]
 * Editor creazione ricetta — Canonico-Only
 * Rev: 2025-09-14 (patch stile account + fix repeater + validazione)
 * ============================================================
 */
if (!defined('ABSPATH')) exit;

$ajaxurl  = admin_url('admin-ajax.php');
$nr_nonce = wp_create_nonce('thub_save_user_recipe');
$user_id  = get_current_user_id();
$is_pro   = function_exists('thub_is_pro') ? thub_is_pro($user_id) : false;
?>

<?php
/* [THUB_NR_EDIT_MODE] — Riconosce ?post_id=&mode=edit e valida autore */
$edit_post_id = 0;
if ( isset($_GET['post_id'], $_GET['mode']) && $_GET['mode'] === 'edit' ) {
  $pid = absint($_GET['post_id']);
  if ( $pid && get_post_type($pid) === 'ricetta' && (int) get_post_field('post_author', $pid) === (int) $user_id ) {
    $edit_post_id = $pid;
  }
}
?>
<script>
  /* [THUB_NR_EDIT_BOOT] — espone al JS l’eventuale post da caricare */
  window.THUB_EDIT_RECIPE = {
    post_id: <?php echo (int) $edit_post_id; ?> 
  };
</script>

<section class="thub-canvas-section thub-nr">

  <header class="thub-nr__head">
    <h1 class="thub-nr__title">Inserisci la tua ricetta</h1>
    <p class="thub-nr__subtitle">Ti diamo il benvenuto nell'editor di creazione delle tue ricette</p>
  </header>

  <form id="thub-nr-form"
        class="thub-nr__form"
        method="post"
        action="<?php echo esc_url($ajaxurl); ?>"
        enctype="multipart/form-data"
        data-ajaxurl="<?php echo esc_attr($ajaxurl); ?>"
        data-nonce="<?php echo esc_attr($nr_nonce); ?>"
        data-is-pro="<?php echo $is_pro ? '1':'0'; ?>">

    <input type="hidden" name="action" value="thub_save_user_recipe">
    <input type="hidden" name="thub_nr_nonce" value="<?php echo esc_attr($nr_nonce); ?>">
    <input type="hidden" name="thub_submit_type" id="thub_submit_type" value="draft"><!-- draft|publish -->
    <input type="hidden" name="edit_post_id" id="thub_edit_post_id" value="<?php echo esc_attr($edit_post_id); ?>"><!-- [THUB_NR_EDIT_HIDDEN] -->

    <!-- ======================= BOX 1 ======================= -->
    <div class="thub-box thub-box--full">
      <div class="thub-grid thub-grid--2">
        <div class="thub-col thub-col--center">
          <div class="thub-chef-hat" aria-hidden="true">
            <svg viewBox="0 0 120 120" role="img" aria-label="Crea ricetta">
              <title>Crea ricetta</title>
              <circle cx="60" cy="60" r="50" fill="#fff3e6"></circle>
              <path d="M40 60c-8 0-14-6-14-14s6-14 14-14c4 0 7 1 10 4 3-3 6-4 10-4s7 1 10 4c3-3 6-4 10-4 8 0 14 6 14 14s-6 14-14 14H40z" fill="#eaeaea"/>
              <rect x="42" y="60" width="36" height="22" rx="6" ry="6" fill="#ffd6a1"/>
              <rect x="46" y="82" width="28" height="8" rx="4" ry="4" fill="#ffbd6b"/>
            </svg>
          </div>
        </div>

        <div class="thub-col">
          <!-- Titolo -->
          <div class="thub-field">
            <input type="text" name="post_title" id="thub_title" class="thub-input"
                   placeholder="Titolo ricetta" maxlength="120" required>
            <div class="thub-row-helpers">
              <p class="thub-help">(max ~50 caratteri consigliati)</p>
              <span class="thub-count" data-for="thub_title" data-rec="50">0/50</span>
            </div>
          </div>

          <div class="thub-spacer"></div>

          <!-- Intro -->
          <div class="thub-field">
            <input type="text" name="intro_breve" id="thub_intro" class="thub-input"
                   placeholder="Breve descrizione" maxlength="180" required>
            <div class="thub-row-helpers">
              <p class="thub-help">(max ~150 caratteri consigliati)</p>
              <span class="thub-count" data-for="thub_intro" data-rec="150">0/150</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ======================= BOX 2 ======================= -->
    <div class="thub-box thub-box--full">
      <div class="thub-grid thub-grid--4">
        <div class="thub-col">
          <div class="thub-label">Porzioni base</div>
          <!-- input hidden inviato al server -->
          <input type="hidden" name="porzioni_base" value="1">
          <!-- input UI non cliccabile (no focus, no selection) -->
          <input type="text" class="thub-input thub-input--ro thub-input--static" value="1"
                 disabled tabindex="-1" aria-disabled="true"><!-- [THUB_RO_STATIC] -->
        </div>
        <div class="thub-col">
          <div class="thub-label">Kcal per porzione</div>
          <input type="number" name="kcal_per_porz" class="thub-input" placeholder="Es. 350" required>
        </div>
        <div class="thub-col">
          <div class="thub-label">Tempo di preparazione</div>
          <input type="text" name="tempo_di_preparazione" class="thub-input" placeholder="Es. 20 min" required>
        </div>
        <div class="thub-col">
          <div class="thub-label">Tempo di cottura</div>
          <input type="text" name="tempo_di_cottura" class="thub-input" placeholder="Es. 30 min" required>
        </div>
      </div>
    </div>

    <!-- ======================= BOX 3 (2 sub-box affiancati) ======================= -->
    <div class="thub-grid thub-grid--2"><!-- due “box” sulla stessa riga -->
      <div class="thub-subbox">
        <div class="thub-label">Link Video</div>
        <input type="url" name="video_url" class="thub-input" placeholder="https://...">
      </div>
      <div class="thub-subbox">
        <div class="thub-nr__image-upload">
          <div id="thub-nr-image-preview" class="thub-nr__image-preview is-empty" data-has-image="0">
            <span class="thub-nr__image-placeholder">Nessuna immagine selezionata</span>
            <img src="" alt="" loading="lazy" decoding="async">
          </div>
          <input type="file" name="thub_recipe_image" class="thub-input thub-input--file" accept="image/*" required>
          <input type="hidden" name="thub_existing_image_id" id="thub_existing_image_id" value="">
        </div>
      </div>
    </div>

    <!-- ======================= BOX 4 — Ingredienti (Repeater) ======================= -->
    <div class="thub-box thub-box--full">
      <h3 class="thub-box__title">Ingredienti strutturati</h3>

      <div id="thub-ing-repeater" class="thub-repeater" data-next-index="1">
        <div class="thub-repeater-row thub-ing-row">
          <input type="text" class="thub-input" name="ingredienti[0][nome]" placeholder="Nome ingrediente" required>
          <input type="text" class="thub-input" name="ingredienti[0][qta]"  placeholder="Quantità">
          <div class="thub-grid thub-grid--2xs thub-ing-unitwrap">
            <select class="thub-input thub-unit" name="ingredienti[0][unita]">
              <option value="">Unità</option>
              <option value="g">g</option>
              <option value="ml">ml</option>
              <option value="pz">pezzo</option>
              <option value="altro">Altro…</option>
            </select>
            <input type="text" class="thub-input thub-unit-other" name="ingredienti[0][unita_altro]" placeholder="Specifica" />
          </div>
          <button type="button" class="thub-btn thub-repeater-remove thub-btn--x" aria-label="Rimuovi ingrediente">×</button>
        </div>

        <template id="tpl-ing-row">
          <div class="thub-repeater-row thub-ing-row">
            <input type="text" class="thub-input" name="ingredienti[__i__][nome]" placeholder="Nome ingrediente" required>
            <input type="text" class="thub-input" name="ingredienti[__i__][qta]"  placeholder="Quantità">
            <div class="thub-grid thub-grid--2xs thub-ing-unitwrap">
              <select class="thub-input thub-unit" name="ingredienti[__i__][unita]">
                <option value="">Unità</option>
                <option value="g">g</option>
                <option value="ml">ml</option>
                <option value="pz">pezzo</option>
                <option value="altro">Altro…</option>
              </select>
              <input type="text" class="thub-input thub-unit-other" name="ingredienti[__i__][unita_altro]" placeholder="Specifica" />
            </div>
            <button type="button" class="thub-btn thub-repeater-remove thub-btn--x" aria-label="Rimuovi ingrediente">×</button>
          </div>
        </template>

        <div class="thub-repeater-ctrl">
          <button type="button" id="thub-ing-add" class="thub-btn thub-btn--primary">+ Aggiungi ingrediente</button>
        </div>
      </div>
    </div>

    <!-- ======================= BOX 5 — Attrezzature (Repeater) ======================= -->
    <div class="thub-box thub-box--full">
      <h3 class="thub-box__title">Attrezzature</h3>

      <div id="thub-tool-repeater" class="thub-repeater" data-next-index="1">
        <div class="thub-repeater-row thub-tool-row">
          <select name="attrezzature[0][key]" class="thub-input">
            <option value="">— Attrezzatura utilizzata —</option>
            <option value="forno">Forno</option>
            <option value="pentola">Pentola</option>
            <option value="padella">Padella</option>
            <option value="frusta">Frusta</option>
            <option value="teglia">Teglia</option>
            <option value="coltello">Coltello</option>
            <option value="custom_svg">SVG personalizzato</option>
          </select>
          <input type="text" name="attrezzature[0][testo]" class="thub-input" placeholder="Descrizione breve (facoltativa)">
          <button type="button" class="thub-btn thub-repeater-remove thub-btn--x" aria-label="Rimuovi attrezzatura">×</button>
        </div>

        <template id="tpl-tool-row">
          <div class="thub-repeater-row thub-tool-row">
            <select name="attrezzature[__i__][key]" class="thub-input">
              <option value="">— Attrezzatura utilizzata —</option>
              <option value="forno">Forno</option>
              <option value="pentola">Pentola</option>
              <option value="padella">Padella</option>
              <option value="frusta">Frusta</option>
              <option value="teglia">Teglia</option>
              <option value="coltello">Coltello</option>
              <option value="custom_svg">SVG personalizzato</option>
            </select>
            <input type="text" name="attrezzature[__i__][testo]" class="thub-input" placeholder="Descrizione breve (facoltativa)">
            <button type="button" class="thub-btn thub-repeater-remove thub-btn--x" aria-label="Rimuovi attrezzatura">×</button>
          </div>
        </template>

        <div class="thub-repeater-ctrl">
          <button type="button" id="thub-tool-add" class="thub-btn thub-btn--primary">+ Aggiungi attrezzatura</button>
        </div>
      </div>
    </div>

    <!-- ======================= BOX 6 — Passaggi (Repeater) ======================= -->
    <div class="thub-box thub-box--full">
      <h3 class="thub-box__title">Passaggi preparatori</h3>

      <div id="thub-steps-repeater" class="thub-repeater" data-next-index="2">
        <div class="thub-repeater-row thub-step-row">
          <span class="thub-step-idx">#1</span>
          <input type="text" class="thub-input" name="passaggi[1]" placeholder="Descrizione passaggio 1" required>
          <button type="button" class="thub-btn thub-repeater-remove thub-btn--x" aria-label="Rimuovi passaggio">×</button>
        </div>

        <template id="tpl-step-row">
          <div class="thub-repeater-row thub-step-row">
            <span class="thub-step-idx">#__i__</span>
            <input type="text" class="thub-input" name="passaggi[__i__]" placeholder="Descrizione passaggio __i__" required>
            <button type="button" class="thub-btn thub-repeater-remove thub-btn--x" aria-label="Rimuovi passaggio">×</button>
          </div>
        </template>

        <div class="thub-repeater-ctrl">
          <button type="button" id="thub-step-add" class="thub-btn thub-btn--primary">+ Aggiungi passaggio</button>
        </div>
      </div>
    </div>

    <!-- ======================= BOX 7 — Note ======================= -->
    <div class="thub-box thub-box--full">
      <h3 class="thub-box__title">Eventuali note tecniche</h3>
      <textarea name="eventuali_note_tecniche" class="thub-input thub-input--area" rows="6"
                placeholder="Eventuali note tecniche, consigli, avvertenze."></textarea>
    </div>

    <!-- ======================= BOX 8 — Vino ======================= -->
    <div class="thub-box thub-box--full">
      <h3 class="thub-box__title">Vino di accompagnamento</h3>
      <div class="thub-grid thub-grid--2">
        <div class="thub-col">
          <input type="text" class="thub-input" name="vino_nome" placeholder="Nome e anno di produzione">
        </div>
        <div class="thub-col">
          <input type="text" class="thub-input" name="vino_denominazione" placeholder="Cantina di produzione">
        </div>
      </div>
    </div>

    <!-- ======================= BOX 9 — Destinazione ======================= -->
    <div class="thub-box thub-box--full">
      <h3 class="thub-box__title">Salva in bozza o scegli dove salvare</h3>
      <div class="thub-grid thub-grid--2">
        <div class="thub-col">
          <label class="thub-radio">
            <input type="radio" name="ricetta_dest" value="nonna" checked>
            <span>Salva in: Ricette della Nonna</span>
            <span class="thub-i" data-tip="Pubblica in Ricette della Nonna e scegli se la visualizzazione è pubblica o privata">i</span>
          </label>
          <div id="thub-nonna-sub" class="thub-nested">
            <label class="thub-radio">
              <input type="radio" name="nonna_vis" value="pubblica" checked>
              <span>Ricetta Pubblica (richiede approvazione)</span>
            </label>
            <label class="thub-radio">
              <input type="radio" name="nonna_vis" value="privata">
              <span>Ricetta Privata (visibile solo a te)</span>
            </label>
          </div>
        </div>

        <div class="thub-col">
          <label class="thub-radio <?php echo $is_pro ? '' : 'is-disabled'; ?>">
            <input type="radio" name="ricetta_dest" value="chef" <?php echo $is_pro ? '' : 'disabled'; ?>>
            <span>Salva in: Ricette dello Chef (Pro)</span>
            <span class="thub-i" data-tip="Pubblica in Ricette dello Chef, la sezione dedicata ai creatori di ricette pro e monetizza la tua ricetta nel T-Hub market.">i</span>
          </label>
          <?php if(!$is_pro): ?>
            <p class="thub-help">Questa opzione è riservata agli utenti Pro.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ======================= BOX 10 — Azioni (destra, senza box) ======================= -->
    <div class="thub-actions thub-actions--footer">
      <div id="thub-nr-msg" class="thub-msg" aria-live="polite"></div>
      <button type="button" id="thub_btn_draft"   class="thub-btn thub-btn--outline">Salva in bozze</button><!-- unico “outline” -->
      <button type="button" id="thub_btn_publish" class="thub-btn thub-btn--primary">Pubblica</button>
    </div>
  </form>

  <style>
    /* ================== STILI IN LINEA CON ACCOUNT ================== */
    .thub-box{
      background:#fff;
      border:1px solid var(--border, #e7e7e7);
      border-radius:.9rem;
      padding:16px; margin:16px 0;
    }
    .thub-subbox{
      background:#fff;
      border:1px solid var(--border, #e7e7e7);
      border-radius:.9rem;
      padding:16px;
    }

    /* [THUB_INPUT_STYLE] input radius .6 + bordo #e6e6ea */
    .thub-input{
      width:100%;
      padding:10px 12px;
      border:1px solid #e6e6ea;              /* richiesto */
      border-radius:.6rem;                    /* richiesto */
      background:#fff;
    }
    .thub-input--file{ border:0 !important; padding-left:0 !important; }
    .thub-nr__image-upload{ display:flex; flex-direction:column; gap:12px; }
    .thub-nr__image-preview{
      position:relative;
      display:flex;
      align-items:center;
      justify-content:center;
      min-height:160px;
      padding:12px;
      border:1px dashed var(--border, #dcdce3);
      border-radius:.9rem;
      background:#f9f9fc;
      overflow:hidden;
    }
    .thub-nr__image-preview img{
      max-width:100%;
      max-height:240px;
      object-fit:contain;
      display:none;
      border-radius:.6rem;
    }
    .thub-nr__image-preview .thub-nr__image-placeholder{
      color:#666;
      text-align:center;
      font-size:.95rem;
      display:none;
    }
    .thub-nr__image-preview.is-empty .thub-nr__image-placeholder{ display:block; }
    .thub-nr__image-preview.is-empty img{ display:none; }
    .thub-nr__image-preview:not(.is-empty) img{ display:block; }
    .thub-input--ro{ border:0 !important; background:#f7f7f9 !important; color:#777; }
    .thub-input--static{ pointer-events:none; user-select:none; caret-color:transparent; }
    .thub-input--area{ border:0 !important; background:#fff !important; } /* richiesto */

    .thub-nr__title{ text-align:center; margin:.2rem 0 0; }
    .thub-nr__subtitle{ text-align:center; color:#666; margin:.25rem 0 1.1rem; }
    .thub-label{ font-weight:600; margin-bottom:4px; }
    .thub-help{ color:#888; font-size:.9em; margin:.35rem 0 0; }
    .thub-spacer{ height:.6rem; }
    .thub-field .thub-row-helpers{ display:flex; align-items:center; justify-content:space-between; margin-top:.25rem; }
    .thub-count{ color:#666; font-size:.9em; }

    .thub-grid{ display:grid; gap:12px; align-items:start; }
    .thub-grid--2{ grid-template-columns:1fr 1fr; }
    .thub-grid--4{ grid-template-columns:repeat(4, 1fr); }
    .thub-grid--2xs{ grid-template-columns:1fr 1fr; gap:8px; }
    .thub-col--center{ display:flex; justify-content:center; align-items:center; min-height:220px; }
    .thub-chef-hat svg{ width:150px; height:150px; display:block; }

    /* ========== REPEATER ========== */
    .thub-repeater-ctrl{ margin-top:10px; }
    .thub-repeater-row{ padding-top:5px; } /* richiesto */

    /* Bottoni (eccetto bozza = outline) */
    .thub-btn{
      border:1px solid transparent;
      border-radius:.6rem;                           /* richiesto */
      padding:.45rem .8rem;                          /* richiesto */
      cursor:pointer; width:auto; align-self:flex-start; /* richiesto */
      transition:background .15s ease, border-color .15s ease, color .15s ease;
    }
    .thub-btn--primary, .thub-btn--solid{
      border:1px solid #7249a4;                      /* richiesto */
      background:#7249a4; color:#fff;                /* richiesto */
    }
    .thub-btn--primary:hover, .thub-btn--solid:hover{
      background:#7249a4; color:#fff; border-color:#7249a4; /* hover coerente */
    }
    .thub-btn--outline{
      border:1px solid #7249a4; color:#7249a4; background:#fff;
    }
    .thub-btn--outline:hover{
      background:#f7f5fb; color:#7249a4; border-color:#7249a4;
    }
    .thub-btn--x{ width:42px; min-width:42px; text-align:center; }

    /* Allineamento colonne repeater:
       - usiamo minmax per stabilizzare le larghezze (fix safari/overflow) */
    .thub-ing-row{
      display:grid;
      grid-template-columns:
        minmax(220px, 1.4fr)   /* nome */
        minmax(120px, .8fr)    /* qta  */
        minmax(180px, 1fr)     /* unit wrap */
        42px;                  /* X    */
      gap:12px; align-items:center;
    }
    .thub-ing-unitwrap{ min-width:180px; } /* assicura larghezza coerente */
    .thub-tool-row{
      display:grid;
      grid-template-columns:
        minmax(220px, 1fr)     /* select */
        minmax(220px, 1fr)     /* testo  */
        42px;
      gap:12px; align-items:center;
    }
    .thub-step-row{
      display:grid;
      grid-template-columns:
        auto
        minmax(280px, 1fr)
        42px;
      gap:12px; align-items:center;
    }
    .thub-step-idx{
      width:34px; height:34px; border-radius:50%;
      border:1px solid var(--border, #e7e7e7);
      display:inline-grid; place-items:center; background:#fff; font-weight:600;
    }

    /* Radio & nested */
    .thub-radio{ display:flex; align-items:center; gap:.5rem; margin:.25rem 0; }
    .thub-radio.is-disabled{ opacity:.6; }
    .thub-nested{ margin:.5rem 0 0 1.6rem; display:block; }

    /* Tooltip */
    .thub-i{ display:inline-grid; place-items:center; width:18px; height:18px; border:1px solid var(--border, #e7e7e7); border-radius:50%; font-size:.8rem; color:#555; cursor:help; position:relative; }
    .thub-i:hover::after{
      content: attr(data-tip);
      position:absolute; left:0; transform: translate(-6px, -120%);
      background:#111; color:#fff; font-size:.8rem; padding:6px 8px; border-radius:6px; max-width:320px; white-space:normal; z-index:10;
    }

    /* Azioni a destra, senza box */
    .thub-actions--footer{ display:flex; gap:10px; align-items:center; justify-content:flex-end; margin:16px 0; }
    .thub-msg{ margin-right:auto; font-weight:600; }

    @media (max-width: 980px){
      .thub-grid--2, .thub-grid--4{ grid-template-columns: 1fr; }
      .thub-col--center{ min-height:160px; }
      .thub-actions--footer{ justify-content:stretch; }
      .thub-msg{ margin:0 0 6px 0; order:-1; }
    }


    /*Sovrascritto*/
    /* === INPUT: bordo e radius in linea con account === */
    .thub-input{
      width:100%;
      padding:10px 12px;
      border:1px solid #e6e6ea;      /* baseline */
      border-radius:.6rem;            /* baseline */
      background:#fff;
      min-width:0;                    /* evita overflow in grid/flex */
    }
    /* richiesta specifica: per i text usa #e1e1e6 */
    .thub-input[type="text"]{ border-color:#e1e1e6; }
    .thub-input[type="number"]{ border-color:#e1e1e6; }
    .thub-input[type="url"]{ border-color:#e1e1e6; }

    /* Porzioni base “non cliccabile” già ok; confermo */
    .thub-input--ro{ border:0 !important; background:#f7f7f9 !important; color:#777; }
    .thub-input--static{ pointer-events:none; user-select:none; caret-color:transparent; }

    /* Textarea note: senza bordo e bg bianco (richiesto) */
    .thub-input--area{ border:0 !important; background:#fff !important; }

    /* === BOTTONI === */
    /* Base (anche per +Aggiungi) */
    .thub-btn{
      border:1px solid transparent;
      border-radius:.6rem;
      padding:.45rem .8rem;
      cursor:pointer;
      width:auto; align-self:flex-start;
      transition:background .15s ease, border-color .15s ease, color .15s ease;
    }
    /* Solido (per Pubblica e i 3 +Aggiungi) */
    .thub-btn--primary{
      border:1px solid #7249a4;
      background:#7249a4;
      color:#fff;
    }
    .thub-btn--primary:hover{ background:#7249a4; color:#fff; border-color:#7249a4; }

    /* Outline (solo “Salva in bozze”) */
    .thub-btn--outline{ border:1px solid #7249a4; color:#7249a4; background:#fff; }
    .thub-btn--outline:hover{ background:#f7f5fb; color:#7249a4; border-color:#7249a4; }

    /* X dei repeater: eccezione (leggera, NON solida) */
    .thub-btn--x{
      border:1px solid #e6e6ea;      /* guscio leggero */
      background:#fff;
      color:#7249a4;
      width:42px; min-width:42px; text-align:center;
    }
    .thub-btn--x:hover{ background:#f7f5fb; color:#7249a4; border-color:#e6e6ea; }

    /* Focus uniforme su tutti i bottoni */
    .thub-btn:focus-visible{ outline:2px solid #7249a4; outline-offset:2px; }

    /* === REPEATER: padding top riga e allineamenti === */
    .thub-repeater-row{ padding-top:5px; }

    /* Fix: nascondi “Specifica” di default per evitare ricalcoli tra righe */
    .thub-unit-other{ display:none; }

    /* IMPORTANTISSIMO: prevenire overflow che altera i calcoli delle colonne */
    .thub-ing-row > *, .thub-tool-row > *, .thub-step-row > * { min-width:0; }

    /* INGREDIENTI — alternativa stabile in % (allinea anche le righe clonate)  */
    .thub-ing-row{
      display:grid;
      grid-template-columns: 44% 18% 38% 42px;  /* nome | qta | unit-wrap | X */
      gap:12px; align-items:center;
    }
    .thub-ing-unitwrap{ display:grid; grid-template-columns: 1fr 1fr; gap:8px; min-width:0; }

    /* ATTREZZATURE — 50% | 50% | X */
    .thub-tool-row{
      display:grid;
      grid-template-columns: 1fr 1fr 42px;
      gap:12px; align-items:center;
    }

    /* PASSAGGI — # | testo | X */
    .thub-step-row{
      display:grid;
      grid-template-columns: auto 1fr 42px;
      gap:12px; align-items:center;
    }
  </style>

  <script>
  (function(){
    const form = document.getElementById('thub-nr-form');
    if (!form) { return; }

    const ajax  = (form.dataset && form.dataset.ajaxurl) ? form.dataset.ajaxurl : '';
    const nonce = (form.dataset && form.dataset.nonce) ? form.dataset.nonce : '';
    const isPro = !!(form.dataset && form.dataset.isPro === '1');
    const msg   = document.getElementById('thub-nr-msg');
    const submitTypeField = document.getElementById('thub_submit_type');
    const hid   = document.getElementById('thub_edit_post_id');

    const $  = (s, c)=> (c || document).querySelector(s);
    const $$ = (s, c)=> Array.from((c || document).querySelectorAll(s));
    const fillTpl = (tpl, index)=> tpl ? tpl.replace(/__i__/g, String(index)) : '';

    const ingWrap   = document.getElementById('thub-ing-repeater');
    const ingTplEl  = document.getElementById('tpl-ing-row');
    const ingTpl    = ingTplEl ? ingTplEl.innerHTML : '';
    const toolWrap  = document.getElementById('thub-tool-repeater');
    const toolTplEl = document.getElementById('tpl-tool-row');
    const toolTpl   = toolTplEl ? toolTplEl.innerHTML : '';
    const stWrap    = document.getElementById('thub-steps-repeater');
    const stTplEl   = document.getElementById('tpl-step-row');
    const stTpl     = stTplEl ? stTplEl.innerHTML : '';
    const imageInput         = $('input[name="thub_recipe_image"]');
    const existingImageField = document.getElementById('thub_existing_image_id');
    const imagePreviewWrap   = document.getElementById('thub-nr-image-preview');
    const imagePreviewImg    = imagePreviewWrap ? imagePreviewWrap.querySelector('img') : null;
    const imagePlaceholder   = imagePreviewWrap ? imagePreviewWrap.querySelector('.thub-nr__image-placeholder') : null;
    let previewObjectUrl     = '';

    const markImagePreview = function(has){
      if (!imagePreviewWrap) { return; }
      imagePreviewWrap.dataset.hasImage = has ? '1' : '0';
      imagePreviewWrap.classList.toggle('is-empty', !has);
      if (imagePlaceholder) { imagePlaceholder.setAttribute('aria-hidden', has ? 'true' : 'false'); }
    };

    const resetPreviewDimensions = function(){
      if (!imagePreviewImg) { return; }
      imagePreviewImg.style.width = '';
      imagePreviewImg.style.height = '';
      imagePreviewImg.removeAttribute('width');
      imagePreviewImg.removeAttribute('height');
    };

    const applyPreviewDimensions = function(width, height){
      if (!imagePreviewImg) { return; }
      const w = parseInt(width || 0, 10);
      const h = parseInt(height || 0, 10);
      if (!w || !h) {
        resetPreviewDimensions();
        return;
      }
      const maxW = 360;
      const maxH = 240;
      const ratio = Math.min(maxW / w, maxH / h, 1);
      const finalW = Math.max(1, Math.round(w * ratio));
      const finalH = Math.max(1, Math.round(h * ratio));
      imagePreviewImg.style.width = finalW + 'px';
      imagePreviewImg.style.height = finalH + 'px';
      imagePreviewImg.setAttribute('width', String(finalW));
      imagePreviewImg.setAttribute('height', String(finalH));
    };

    const clearImagePreview = function(){
      if (!imagePreviewWrap || !imagePreviewImg) { return; }
      resetPreviewDimensions();
      imagePreviewImg.removeAttribute('src');
      imagePreviewImg.alt = '';
      markImagePreview(false);
    };

    const showImagePreview = function(opts){
      if (!imagePreviewWrap || !imagePreviewImg) { return; }
      const src = (opts && opts.src) ? opts.src : '';
      if (!src) {
        clearImagePreview();
        return;
      }
      const altText = (opts && opts.alt) ? opts.alt : '';
      imagePreviewImg.src = src;
      imagePreviewImg.alt = altText !== '' ? altText : 'Anteprima immagine ricetta';
      resetPreviewDimensions();
      const w = opts && opts.width ? opts.width : 0;
      const h = opts && opts.height ? opts.height : 0;
      if (w && h) {
        applyPreviewDimensions(w, h);
      } else {
        const handle = function(){
          applyPreviewDimensions(imagePreviewImg.naturalWidth, imagePreviewImg.naturalHeight);
          imagePreviewImg.removeEventListener('load', handle);
        };
        imagePreviewImg.addEventListener('load', handle);
      }
      markImagePreview(true);
    };

    const revokePreviewObjectUrl = function(){
      if (previewObjectUrl) {
        URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = '';
      }
    };

    if (imagePreviewWrap) {
      const initialHasImage = imagePreviewWrap.dataset && imagePreviewWrap.dataset.hasImage === '1';
      markImagePreview(initialHasImage);
    }

    if (imageInput) {
      imageInput.addEventListener('change', function(){
        revokePreviewObjectUrl();
        const file = imageInput.files && imageInput.files[0];
        if (!file) {
          if (existingImageField && existingImageField.value) {
            markImagePreview(true);
          } else {
            clearImagePreview();
          }
          return;
        }
        previewObjectUrl = URL.createObjectURL(file);
        showImagePreview({
          src: previewObjectUrl,
          alt: file.name || '',
          width: file.width || 0,
          height: file.height || 0
        });
        if (existingImageField) { existingImageField.value = ''; }
        imageInput.classList.remove('is-error');
      });
    }

    /* ============================================================
      [THUB_NR_EDIT_LOAD_JS] — Carica i dati in edit e precompila
      ============================================================ */
    const EDIT = window.THUB_EDIT_RECIPE || {};
    if (EDIT.post_id) {
      // segna l’ID in hidden
      if (hid) { hid.value = String(EDIT.post_id); }

      if (ajax) {
        const fd = new FormData();
        fd.append('action', 'thub_get_recipe_data');
        fd.append('thub_nr_nonce', nonce);
        fd.append('post_id', String(EDIT.post_id));

        fetch(ajax, { method:'POST', credentials:'same-origin', body: fd })
          .then(function(r){ return r.json(); })
          .then(function(j){
            if (!j || !j.success || !j.data) {
              const errMsg = (j && j.data && j.data.message) ? j.data.message : 'Errore caricamento dati.';
              throw new Error(errMsg);
            }
            const d = j.data || {};

            const titleInput = $('#thub_title');
            if (titleInput) { titleInput.value = d.post_title || ''; }
            const introInput = $('#thub_intro');
            if (introInput) { introInput.value = d.intro_breve || ''; }
            const kcal = document.querySelector('input[name="kcal_per_porz"]');
            if (kcal) { kcal.value = d.kcal_per_porz || ''; }
            const tp = document.querySelector('input[name="tempo_di_preparazione"]');
            if (tp) { tp.value = d.tempo_di_preparazione || ''; }
            const tc = document.querySelector('input[name="tempo_di_cottura"]');
            if (tc) { tc.value = d.tempo_di_cottura || ''; }
            const vu = document.querySelector('input[name="video_url"]');
            if (vu) { vu.value = d.video_url || ''; }
            const no = document.querySelector('textarea[name="eventuali_note_tecniche"]');
            if (no) { no.value = d.eventuali_note_tecniche || ''; }
            if (existingImageField) {
              const fid = (d.featured_image && d.featured_image.id) ? d.featured_image.id : '';
              existingImageField.value = fid ? String(fid) : '';
            }
            if (d.featured_image && d.featured_image.url) {
              revokePreviewObjectUrl();
              showImagePreview({
                src: d.featured_image.url,
                alt: d.featured_image.alt || d.post_title || '',
                width: d.featured_image.width || 0,
                height: d.featured_image.height || 0
              });
              if (imageInput) { imageInput.classList.remove('is-error'); }
            } else {
              clearImagePreview();
            }

            const rNonna = document.querySelector('input[name="ricetta_dest"][value="nonna"]');
            if (rNonna) { rNonna.checked = true; }
            const visPub = document.querySelector('input[name="nonna_vis"][value="pubblica"]');
            const visPrv = document.querySelector('input[name="nonna_vis"][value="privata"]');
            if (d.nonna_vis === 'privata' && visPrv) {
              visPrv.checked = true;
            } else if (visPub) {
              visPub.checked = true;
            }

            if (ingWrap && ingTpl) {
              $$('.thub-ing-row', ingWrap).forEach(function(node){ node.remove(); });
              let next = 0;
              (d.ingredienti || []).forEach(function(rawRow){
                const row = rawRow || {};
                const html = fillTpl(ingTpl, next);
                const div  = document.createElement('div');
                div.className = 'thub-repeater-row thub-ing-row';
                div.innerHTML = html;
                ingWrap.insertBefore(div, ingWrap.querySelector('.thub-repeater-ctrl'));
                const nameInput = div.querySelector('input[name="ingredienti['+next+'][nome]"]');
                if (nameInput) { nameInput.setAttribute('value', row.nome || ''); }
                const qtyInput = div.querySelector('input[name="ingredienti['+next+'][qta]"]');
                if (qtyInput) { qtyInput.setAttribute('value', row.qta || ''); }
                const unitSelect = div.querySelector('select[name="ingredienti['+next+'][unita]"]');
                if (unitSelect) { unitSelect.value = row.unita || ''; }
                const otherInput = div.querySelector('input[name="ingredienti['+next+'][unita_altro]"]');
                if (otherInput) { otherInput.setAttribute('value', row.unita_altro || ''); }
                next++;
              });
              ingWrap.setAttribute('data-next-index', String(next || 1));
            }

            if (toolWrap && toolTpl) {
              $$('.thub-tool-row', toolWrap).forEach(function(node){ node.remove(); });
              let next = 0;
              (d.attrezzature || []).forEach(function(rawRow){
                const row = rawRow || {};
                const html = fillTpl(toolTpl, next);
                const div  = document.createElement('div');
                div.className = 'thub-repeater-row thub-tool-row';
                div.innerHTML = html;
                toolWrap.insertBefore(div, toolWrap.querySelector('.thub-repeater-ctrl'));
                const sel = div.querySelector('select[name="attrezzature['+next+'][key]"]');
                if (sel) { sel.value = row.key || ''; }
                const textInput = div.querySelector('input[name="attrezzature['+next+'][testo]"]');
                if (textInput) { textInput.setAttribute('value', row.testo || ''); }
                next++;
              });
              toolWrap.setAttribute('data-next-index', String(next || 1));
            }

            if (stWrap && stTpl) {
              $$('.thub-step-row', stWrap).forEach(function(node){ node.remove(); });
              let next = 1;
              (d.passaggi || []).forEach(function(txt){
                const html = fillTpl(stTpl, next);
                const div  = document.createElement('div');
                div.className = 'thub-repeater-row thub-step-row';
                div.innerHTML = html;
                stWrap.insertBefore(div, stWrap.querySelector('.thub-repeater-ctrl'));
                const input = div.querySelector('input[name="passaggi['+next+']"]');
                if (input) { input.setAttribute('value', txt || ''); }
                next++;
              });
              stWrap.setAttribute('data-next-index', String(next || 2));
              $$('.thub-step-idx', stWrap).forEach(function(el, idx){ el.textContent = '#'+(idx+1); });
            }

            const vn = document.querySelector('input[name="vino_nome"]');
            if (vn) { vn.value = d.vino_nome || ''; }
            const vd = document.querySelector('input[name="vino_denominazione"]');
            if (vd) { vd.value = d.vino_denominazione || ''; }
          })
          .catch(function(err){
            const msgBox = document.getElementById('thub-nr-msg');
            if (msgBox) {
              msgBox.textContent = (err && err.message) ? err.message : 'Impossibile caricare i dati della ricetta.';
              msgBox.style.color = '#a33';
            }
          });
      }
    }

    /* ---- Contatori caratteri ---- */
    function bindCounter(id){
      const el = document.getElementById(id);
      if (!el) { return; }
      const cnt = document.querySelector('.thub-count[data-for="'+id+'"]');
      const recAttr = (cnt && cnt.dataset) ? cnt.dataset.rec : '0';
      const rec = parseInt(recAttr || '0', 10) || 0;
      const upd = function(){ if (cnt) { cnt.textContent = el.value.length + '/' + rec; } };
      el.addEventListener('input', upd);
      upd();
    }
    bindCounter('thub_title');
    bindCounter('thub_intro');

    /* ---- Ingredienti repeater ---- */
    const addIngBtn = document.getElementById('thub-ing-add');
    if (addIngBtn && ingWrap && ingTpl) {
      addIngBtn.addEventListener('click', function(){
        const i = parseInt(ingWrap.getAttribute('data-next-index') || '1', 10);
        const html = fillTpl(ingTpl, i);
        if (!html) { return; }
        const div = document.createElement('div');
        div.className = 'thub-repeater-row thub-ing-row';
        div.innerHTML = html;
        ingWrap.insertBefore(div, ingWrap.querySelector('.thub-repeater-ctrl'));
        ingWrap.setAttribute('data-next-index', String(i + 1));
      });
    }
    if (ingWrap) {
      ingWrap.addEventListener('click', function(e){
        const removeBtn = e.target.closest('.thub-repeater-remove');
        if (removeBtn) {
          const row = removeBtn.closest('.thub-repeater-row');
          if (row) { row.remove(); }
        }
      });
      ingWrap.addEventListener('change', function(e){
        const sel = e.target.closest('select.thub-unit');
        if (!sel) { return; }
        const wrap = sel.closest('.thub-ing-unitwrap');
        const other = wrap ? wrap.querySelector('.thub-unit-other') : null;
        const isAltro = (sel.value || '').toLowerCase() === 'altro';
        if (other) {
          other.style.display = isAltro ? 'block' : 'none';
          if (!isAltro) { other.value = ''; }
        }
      });
      $$('.thub-ing-row select.thub-unit', ingWrap).forEach(function(sel){
        const wrap = sel.closest('.thub-ing-unitwrap');
        const other = wrap ? wrap.querySelector('.thub-unit-other') : null;
        const isAltro = (sel.value || '').toLowerCase() === 'altro';
        if (other) {
          other.style.display = isAltro ? 'block' : 'none';
        }
      });
    }

    /* ---- Attrezzature repeater ---- */
    const addToolBtn = document.getElementById('thub-tool-add');
    if (addToolBtn && toolWrap && toolTpl) {
      addToolBtn.addEventListener('click', function(){
        const i = parseInt(toolWrap.getAttribute('data-next-index') || '1', 10);
        const html = fillTpl(toolTpl, i);
        if (!html) { return; }
        const div = document.createElement('div');
        div.className = 'thub-repeater-row thub-tool-row';
        div.innerHTML = html;
        toolWrap.insertBefore(div, toolWrap.querySelector('.thub-repeater-ctrl'));
        toolWrap.setAttribute('data-next-index', String(i + 1));
      });
    }
    if (toolWrap) {
      toolWrap.addEventListener('click', function(e){
        const removeBtn = e.target.closest('.thub-repeater-remove');
        if (removeBtn) {
          const row = removeBtn.closest('.thub-repeater-row');
          if (row) { row.remove(); }
        }
      });
    }

    /* ---- Passaggi repeater ---- */
    const addStepBtn = document.getElementById('thub-step-add');
    if (addStepBtn && stWrap && stTpl) {
      addStepBtn.addEventListener('click', function(){
        const i = parseInt(stWrap.getAttribute('data-next-index') || '2', 10);
        const html = fillTpl(stTpl, i);
        if (!html) { return; }
        const div = document.createElement('div');
        div.className = 'thub-repeater-row thub-step-row';
        div.innerHTML = html;
        stWrap.insertBefore(div, stWrap.querySelector('.thub-repeater-ctrl'));
        stWrap.setAttribute('data-next-index', String(i + 1));
        $$('.thub-step-idx', stWrap).forEach(function(el, idx){ el.textContent = '#'+(idx+1); });
      });
    }
    if (stWrap) {
      stWrap.addEventListener('click', function(e){
        const removeBtn = e.target.closest('.thub-repeater-remove');
        if (removeBtn) {
          const row = removeBtn.closest('.thub-repeater-row');
          if (row) { row.remove(); }
          $$('.thub-step-idx', stWrap).forEach(function(el, idx){ el.textContent = '#'+(idx+1); });
        }
      });
    }

    /* ---- Toggle Nonna ---- */
    form.addEventListener('change', function(e){
      if (e.target && e.target.name === 'ricetta_dest') {
        const box = document.getElementById('thub-nonna-sub');
        if (box) {
          box.style.display = (e.target.value === 'nonna') ? 'block' : 'none';
        }
      }
    });

    /* ---- Validazione client ---- */
    function validate(){
      let ok = true;
      const errs = [];
      const req = function(sel, label){
        const el = $(sel);
        if (!el) { return; }
        const v = (el.value || '').trim();
        if (!v) {
          el.classList.add('is-error');
          ok = false;
          errs.push(label + ' mancante');
        } else {
          el.classList.remove('is-error');
        }
      };
      req('#thub_title', 'Titolo');
      req('#thub_intro', 'Descrizione');
      req('input[name="tempo_di_preparazione"]', 'Tempo di preparazione');
      req('input[name="tempo_di_cottura"]', 'Tempo di cottura');

      if (imageInput) {
        const hasFile = imageInput.files && imageInput.files.length > 0;
        const hasExistingImage = (!hasFile) && (
          (existingImageField && existingImageField.value && existingImageField.value !== '') ||
          (imagePreviewWrap && imagePreviewWrap.dataset && imagePreviewWrap.dataset.hasImage === '1')
        );
        if (!hasFile && !hasExistingImage) {
          ok = false;
          errs.push('Immagine della ricetta mancante');
          imageInput.classList.add('is-error');
        } else {
          imageInput.classList.remove('is-error');
        }
      }

      const ingRows = $$('.thub-ing-row');
      const ingFilled = ingRows.filter(function(r){
        const input = r.querySelector('input[name*="[nome]"]');
        const val = input && input.value ? input.value.trim() : '';
        return val !== '';
      });
      if (ingFilled.length < 1) {
        ok = false;
        errs.push('Inserisci almeno 1 ingrediente');
      }

      const toolRows = $$('.thub-tool-row');
      const toolFilled = toolRows.filter(function(r){
        const sel = r.querySelector('select[name*="[key]"]');
        const val = sel && sel.value ? sel.value : '';
        return val !== '';
      });
      if (toolFilled.length < 1) {
        ok = false;
        errs.push('Inserisci almeno 1 attrezzatura');
      }

      const stepRows = $$('.thub-step-row');
      const stepFilled = stepRows.filter(function(r){
        const input = r.querySelector('input[name^="passaggi"]');
        const val = input && input.value ? input.value.trim() : '';
        return val !== '';
      });
      if (stepFilled.length < 3) {
        ok = false;
        errs.push('Inserisci almeno 3 passaggi');
      }

      if (!ok && msg) {
        msg.textContent = errs.join(' • ');
        msg.style.color = '#a33';
      }
      return ok;
    }

    /* ---- Submit ---- */
    const btnDraft = document.getElementById('thub_btn_draft');
    if (btnDraft) {
      btnDraft.addEventListener('click', function(){ submit('draft'); });
    }
    const btnPublish = document.getElementById('thub_btn_publish');
    if (btnPublish) {
      btnPublish.addEventListener('click', function(){ submit('publish'); });
    }

    function submit(type){
      if (!validate()) { return; }

      if (submitTypeField) {
        submitTypeField.value = (type === 'publish') ? 'publish' : 'draft';
      }
      if (msg) {
        msg.textContent = 'Salvataggio in corso...';
        msg.style.color = '#444';
      }

      const fd = new FormData(form);
      fd.set('thub_nr_nonce', nonce);
      fd.set('action', 'thub_save_user_recipe');

      if (!isPro && fd.get('ricetta_dest') === 'chef') {
        fd.set('ricetta_dest', 'nonna');
      }

      if (!ajax) {
        if (msg) {
          msg.textContent = 'Endpoint non disponibile.';
          msg.style.color = '#a33';
        }
        return;
      }

      fetch(ajax, { method:'POST', credentials:'same-origin', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(j){
          if (j && j.success) {
            if (msg) {
              msg.textContent = j.message || 'Salvato con successo.';
              msg.style.color = '#2a7a2a';
            }
            if (j && j.redirect) {
              window.location.href = j.redirect;
            }
          } else {
            if (msg) {
              msg.textContent = (j && j.message) ? j.message : 'Si è verificato un errore.';
              msg.style.color = '#a33';
            }
          }
        })
        .catch(function(){
          if (msg) {
            msg.textContent = 'Errore di rete.';
            msg.style.color = '#a33';
          }
        });
    }
  })();
  </script>
</section>