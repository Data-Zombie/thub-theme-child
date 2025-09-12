<?php
/**
 * [THUB_CANVAS_SECTION] section-impostazioni.php — Build 2.1.7
 * - Icone SVG a palette chiara (coerenti con section-home.php)
 * - Persistenza toggle SOLO per utenti loggati (AJAX → user_meta)
 * - Ospiti: nessun cookie; tema sempre light (forzato da body_class in functions.php)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$user_id   = get_current_user_id();
$is_logged = $user_id > 0;

// Chiavi meta
$KEY_THEME  = 'thub_theme_mode';         // 'light' | 'dark'
$KEY_NEWWIN = 'thub_results_new_window'; // 'on' | 'off'
$KEY_TTS    = 'thub_tts_recipes';        // 'on' | 'off'

// Valori iniziali (loggati da user_meta; ospiti default light/off)
$theme_mode  = $is_logged ? ( get_user_meta($user_id, $KEY_THEME, true) ?: 'light' ) : 'light';
$new_window  = $is_logged ? ( get_user_meta($user_id, $KEY_NEWWIN, true) ?: 'off' )  : 'off';
$tts_enabled = $is_logged ? ( get_user_meta($user_id, $KEY_TTS, true) ?: 'off' )     : 'off';

?>
<section id="thub-section-impostazioni" class="thub-canvas-section thub-section-impostazioni" aria-labelledby="thub-settings-title">

  <style>
    /* =========================================================
       [THUB_SETTINGS_CSS] — Layout + palette chiara
       ========================================================= */
    .thub-section-impostazioni{
      --thub-box-gap: 18px;
      --thub-col-gap: 16px;
      --thub-box-pad: 18px;
      --thub-sep-color:#e6e6ea;
      --thub-sep-h:1px;
    }
    .thub-settings__title{
      text-align:center;
      font-size:clamp(20px, 2.2vw, 26px);
      margin: 8px 0 18px 0;
      font-weight:600;
    }
    .thub-settings__grid{
      display:grid;
      grid-template-columns: 1fr 1fr; /* sx | dx */
      gap: var(--thub-box-gap);
    }
    @media (max-width: 960px){
      .thub-settings__grid{ grid-template-columns: 1fr; }
    }
    .thub-box{
      border:1px solid #ececf1;
      border-radius:12px;
      background:#fff;
      padding:var(--thub-box-pad);
    }
    .thub-box + .thub-box{ margin-top: var(--thub-box-gap); }

    /* Box interno (col1 testo | col2 icona) allineato verticalmente */
    .thub-box__inner{
      display:grid;
      grid-template-columns: 1.4fr 0.6fr;
      gap: var(--thub-col-gap);
      align-items:center;
    }
    @media (max-width: 640px){
      .thub-box__inner{ grid-template-columns: 1fr; }
    }

    .thub-box__title{
      font-weight:600;
      margin: 2px 0 8px 0;
    }
    .thub-box__desc{
      margin: 0 0 14px 0;
      line-height:1.45;
      color:#555;
    }
    .thub-sep{
      height:var(--thub-sep-h);
      width:100%;
      background:var(--thub-sep-color);
      margin: 10px 0 12px 0; /* uniforme su tutti i box */
    }
    .thub-box__link a{
      color:#7249a4;
      text-decoration:none;
      font-weight:500;
    }
    .thub-box__link a:hover{ text-decoration:underline; }

    /* Colonna icona: centrata orizzontalmente */
    .thub-box__icon{
      display:flex;
      justify-content:center;
      align-items:center;
      padding: 6px 0;
    }
    .thub-box__icon svg{
      width: clamp(76px, 10vw, 120px);
      height:auto;
      opacity: .98;
    }

    /* ==========================
       [THUB_SETTINGS_TOGGLES]
       ========================== */
    .thub-toggle-row{
      display:grid;
      grid-template-columns: 1fr auto; /* label | toggle */
      gap: 12px;
      align-items:center;
      padding: 10px 0;
    }
    .thub-switch{
      --w: 54px; --h: 30px;
      position:relative;
      width:var(--w); height:var(--h);
    }
    .thub-switch input{ display:none; }
    .thub-slider{
      position:absolute; inset:0;
      background:#cfcfd6;
      border-radius:999px;
      transition:.18s ease-in-out;
    }
    .thub-slider::before{
      content:"";
      position:absolute;
      width: calc(var(--h) - 6px);
      height: calc(var(--h) - 6px);
      border-radius:50%;
      background:#fff;
      top: 3px; left: 3px;
      transition:.18s ease-in-out;
      box-shadow: 0 1px 2px rgba(0,0,0,.15);
    }
    .thub-switch input:checked + .thub-slider{
      background:#7249a4;
    }
    .thub-switch input:checked + .thub-slider::before{
      transform: translateX( calc(var(--w) - var(--h)) );
    }
    .thub-toggle-label small{
      color:#777; font-weight:500; margin-left:6px;
    }
  </style>

  <h2 id="thub-settings-title" class="thub-settings__title">Impostazioni</h2>

  <div class="thub-settings__grid">
    <!-- =========================
         COLONNA SINISTRA
         ========================= -->
    <div>
      <!-- Box 1: Cronologia ricerche -->
      <div class="thub-box" id="thub-box-history" aria-labelledby="thub-box-history-title">
        <div class="thub-box__inner">
          <div>
            <div class="thub-box__title" id="thub-box-history-title">Cronologia delle ricerche</div>
            <div class="thub-box__desc">
              Se salvi la cronologia nel tuo account, T-Hub può usarla per personalizzare la tua esperienza.
            </div>
            <div class="thub-sep" aria-hidden="true"></div>
            <div class="thub-box__link">
              <a href="<?php echo esc_url( home_url('/cronologia/') ); ?>">Gestisci la tua cronologia</a>
            </div>
          </div>
          <div class="thub-box__icon" aria-hidden="true">
            <!-- [THUB_ICON_HISTORY_LIGHT] palette chiara -->
            <svg viewBox="0 0 120 120" role="img" aria-label="Cronologia">
              <rect x="8" y="12" rx="16" ry="16" width="104" height="96" fill="#eef2ff"/>
              <circle cx="60" cy="60" r="30" fill="#dfe8ff"/>
              <path d="M60 34a26 26 0 1 0 0 52 26 26 0 0 0 0-52Zm-3 10h6v16l13 7-2.5 4.3L57 62V44Z" fill="#7c8cf8"/>
              <path d="M92 92l12 12" stroke="#7c8cf8" stroke-width="6" stroke-linecap="round"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Box 2: Impostazioni account -->
      <div class="thub-box" id="thub-box-account" aria-labelledby="thub-box-account-title">
        <div class="thub-box__inner">
          <div>
            <div class="thub-box__title" id="thub-box-account-title">Impostazioni dell'account T-Hub</div>
            <div class="thub-box__desc">
              Qui puoi gestire dati personali, sicurezza, privacy e preferenze del tuo profilo T-Hub.
            </div>
            <div class="thub-sep" aria-hidden="true"></div>
            <div class="thub-box__link">
              <a href="<?php echo esc_url( home_url('/account/') ); ?>">Gestisci il tuo account</a>
            </div>
          </div>
          <div class="thub-box__icon" aria-hidden="true">
            <!-- [THUB_ICON_ACCOUNT_LIGHT] palette chiara -->
            <svg viewBox="0 0 120 120" role="img" aria-label="Account">
              <rect x="8" y="12" rx="16" ry="16" width="104" height="96" fill="#eaf7ef"/>
              <circle cx="60" cy="46" r="18" fill="#99d6ad"/>
              <path d="M24 94c0-16.5 16.4-30 36-30s36 13.5 36 30" fill="#eaf7ef" stroke="#99d6ad" stroke-width="6" stroke-linecap="round"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- =========================
         COLONNA DESTRA
         ========================= -->
    <div>
      <!-- Box 3: Lingua -->
      <div class="thub-box" id="thub-box-language" aria-labelledby="thub-box-language-title">
        <div class="thub-box__inner">
          <div>
            <div class="thub-box__title" id="thub-box-language-title">Lingua</div>
            <div class="thub-box__desc">
              Gestisci la lingua di visualizzazione della Ricerca T-Hub e seleziona le preferenze per i risultati.
            </div>
            <div class="thub-sep" aria-hidden="true"></div>
            <div class="thub-box__link">
              <a href="<?php echo esc_url( home_url('/lingua-e-regione/') ); ?>">Visualizza dettagli</a>
            </div>
          </div>
          <div class="thub-box__icon" aria-hidden="true">
            <!-- [THUB_ICON_LANGUAGE_LIGHT] palette chiara -->
            <svg viewBox="0 0 120 120" role="img" aria-label="Lingua">
              <rect x="8" y="12" rx="16" ry="16" width="104" height="96" fill="#eaf2ff"/>
              <circle cx="60" cy="60" r="28" fill="#9bbcf7"/>
              <path d="M42 60h36M60 42v36" stroke="#fff" stroke-width="6" stroke-linecap="round"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Box 4: Toggles -->
      <div class="thub-box" id="thub-box-toggles" aria-labelledby="thub-box-toggles-title"
           data-thub-ajax="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>"
           data-logged="<?php echo $is_logged ? '1' : '0'; ?>"
           data-nonce="<?php echo esc_attr( wp_create_nonce('thub_settings_nonce') ); ?>">
        <div class="thub-box__inner" style="grid-template-columns:1fr;">
          <div>
            <div class="thub-box__title" id="thub-box-toggles-title">Preferenze avanzate</div>

            <!-- Riga: Tema -->
            <div class="thub-toggle-row">
              <div class="thub-toggle-label">
                <strong>Tema</strong>
                <small id="thub-theme-state">
                  <?php echo ($theme_mode === 'light') ? 'chiaro' : 'scuro'; ?>
                </small>
              </div>
              <label class="thub-switch" title="Tema chiaro/scuro">
                <!-- ON=chiaro, OFF=scuro -->
                <input type="checkbox" id="thub-toggle-theme" <?php checked($theme_mode === 'light'); ?> />
                <span class="thub-slider" aria-hidden="true"></span>
              </label>
            </div>
            <div class="thub-sep" aria-hidden="true"></div>

            <!-- Riga: Nuova finestra -->
            <div class="thub-toggle-row">
              <div class="thub-toggle-label"><strong>Risultati in una nuova finestra</strong></div>
              <label class="thub-switch" title="Apri risultati in una nuova scheda">
                <input type="checkbox" id="thub-toggle-newwindow" <?php checked($new_window === 'on'); ?> />
                <span class="thub-slider" aria-hidden="true"></span>
              </label>
            </div>
            <div class="thub-sep" aria-hidden="true"></div>

            <!-- Riga: TTS Ricette -->
            <div class="thub-toggle-row">
              <div class="thub-toggle-label"><strong>Ricette lette a voce alta</strong></div>
              <label class="thub-switch" title="Abilita sintesi vocale ricette">
                <input type="checkbox" id="thub-toggle-tts" <?php checked($tts_enabled === 'on'); ?> />
                <span class="thub-slider" aria-hidden="true"></span>
              </label>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script>
  /* ============================================================
     [THUB_SETTINGS_JS] — Gestione toggle (persistenza: SOLO loggati)
     - Nessun cookie per ospiti (tema resta light tra le pagine)
     - Endpoint PHP: action 'thub_save_settings'
     ============================================================ */
  (function(){
    const box      = document.querySelector('#thub-box-toggles');
    if(!box) return;

    const ajax     = box.dataset.thubAjax || '/wp-admin/admin-ajax.php';
    const isLogged = box.dataset.logged === '1';
    const nonce    = box.dataset.nonce || '';

    const $ = s => document.querySelector(s);
    const themeToggle = $('#thub-toggle-theme');
    const themeState  = $('#thub-theme-state');
    const winToggle   = $('#thub-toggle-newwindow');
    const ttsToggle   = $('#thub-toggle-tts');

    // Applica classe tema al volo (solo effetto visivo immediato)
    const applyTheme = (mode) => {
      const b = document.body;
      b.classList.toggle('thub-theme-light', mode === 'light');
      b.classList.toggle('thub-theme-dark',  mode === 'dark');
      if(themeState) themeState.textContent = (mode === 'light') ? 'chiaro' : 'scuro';
    };
    applyTheme( themeToggle?.checked ? 'light' : 'dark' );

    // Salva su user_meta se loggato
    const saveIfLogged = (payload) => {
      if(!isLogged) return; // ospite: niente persistenza
      const form = new FormData();
      form.append('action', 'thub_save_settings');
      form.append('_ajax_nonce', nonce);
      Object.keys(payload).forEach(k => form.append(k, payload[k]));
      fetch(ajax, { method:'POST', body: form, credentials: 'same-origin' })
        .then(r => r.json())
        .catch(()=>{});
    };

    // Toggle: Tema
    themeToggle?.addEventListener('change', () => {
      const mode = themeToggle.checked ? 'light' : 'dark';
      applyTheme(mode);
      saveIfLogged({ thub_theme_mode: mode }); // guests: no persist
    });

    // Toggle: Nuova finestra (applica subito ai link presenti)
    winToggle?.addEventListener('change', () => {
      const onoff = winToggle.checked ? 'on' : 'off';
      saveIfLogged({ thub_results_new_window: onoff });

      document.querySelectorAll('.thub-result-link').forEach(a => {
        if(onoff === 'on'){
          a.setAttribute('target','_blank');
          a.setAttribute('rel','noopener');
        } else {
          a.removeAttribute('target');
          a.removeAttribute('rel');
        }
      });
    });

    // Toggle: TTS ricette (solo preferenza globale)
    ttsToggle?.addEventListener('change', () => {
      const onoff = ttsToggle.checked ? 'on' : 'off';
      saveIfLogged({ thub_tts_recipes: onoff });
    });
  })();
  </script>
</section>