<?php
/**
 * [THUB_PRIVACY_SECTION] Sezione: Dati e privacy (Canvas Account)
 * Percorso: parts/account/section-dati-privacy.php
 *
 * Funzioni:
 * - Titolo e descrizione centrati (coerenti con layout Canvas)
 * - Box in griglia 2 colonne, allineamento verticale coerente
 * - Toggle (thub-toggle) a destra del testo
 * - Salvataggio immediato via AJAX al cambio dei toggle (senza pulsanti "Salva")
 * - Box finale "Scarica o elimina i tuoi dati": testo + piccolo pulsante con icona accanto
 *
 * Requisiti:
 * - CSS base del tema (.thub-box, layout Canvas, ecc.)
 * - CSS [THUB_TOGGLE] già presente nel tuo style.css per gli switch
 * - Handler AJAX in functions.php: [THUB_PRIVACY_AJAX_SAVE] (wp_ajax_thub_toggle_privacy_save)
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! is_user_logged_in() ){
  echo '<div class="thub-box">Devi essere loggato per gestire le impostazioni di privacy.</div>';
  return;
}

$current_user_id = get_current_user_id();

/** Helper: legge ON/OFF come '1'/'0' */
function thub_privacy_get_meta( $user_id, $key ){
  $val = get_user_meta( $user_id, $key, true );
  return $val === '1' ? '1' : '0';
}

// Valori iniziali meta utente
$meta = [
  'thub_priv_web_activity'   => thub_privacy_get_meta($current_user_id,'thub_priv_web_activity'),
  'thub_priv_history'        => thub_privacy_get_meta($current_user_id,'thub_priv_history'),
  'thub_ads_personalized'    => thub_privacy_get_meta($current_user_id,'thub_ads_personalized'),
  'thub_ads_partners'        => thub_privacy_get_meta($current_user_id,'thub_ads_partners'),
  'thub_search_personalized' => thub_privacy_get_meta($current_user_id,'thub_search_personalized'),
  'thub_share_birthdate'     => thub_privacy_get_meta($current_user_id,'thub_share_birthdate'),
  'thub_share_gender'        => thub_privacy_get_meta($current_user_id,'thub_share_gender'),
  'thub_share_email'         => thub_privacy_get_meta($current_user_id,'thub_share_email'),
  'thub_share_phone'         => thub_privacy_get_meta($current_user_id,'thub_share_phone'),
  'thub_share_social'        => thub_privacy_get_meta($current_user_id,'thub_share_social'),
  'thub_share_geoloc'        => thub_privacy_get_meta($current_user_id,'thub_share_geoloc'),
];

// Nonce per AJAX
$thub_privacy_ajax_nonce = wp_create_nonce('thub_privacy_ajax');
?>

<!-- =========================
     [THUB_PRIVACY_STYLES] Stili locali di impaginazione (griglia + righe inline)
     Nota: se preferisci, puoi spostare queste regole nel tuo style.css con tag [THUB_PRIVACY_GRID]
     ========================= -->
<style>
  /* Titolo + intro centrati */
  #thub-canvas .thub-account__title,
  #thub-canvas .thub-account__intro{ text-align:center; }

  /* Griglia a 2 colonne per i box */
  #thub-canvas .thub-privacy-grid{
    display:grid; grid-template-columns:1fr 1fr; gap:1rem;
  }
  #thub-canvas .thub-privacy-grid .thub-box{
    display:flex; flex-direction:column; justify-content:space-between;
    min-height:220px;
  }

  /* Righe “testo + toggle” (testo a sx, switch a dx) */
  .thub-toggle__row{
    display:flex; align-items:center; justify-content:space-between;
    gap:.75rem; padding:.45rem .6rem;
    border:1px solid var(--border, #e6e6ea); border-radius:.6rem; background:#fff;
  }
  .thub-box__title{ margin:.2rem 0 .35rem; }
  .thub-box__desc{ margin:0 0 .6rem; color:#555; }

  /* Riga inline testo + pulsante icona (Box 5) */
  .thub-inline-row{
    display:flex; align-items:center; justify-content:space-between;
    gap:.75rem; padding:.45rem .6rem;
    border:1px solid var(--border, #e6e6ea); border-radius:.6rem; background:#fff;
    margin:.45rem 0;
  }
  .thub-inline-row__label{ font-size:.95rem; color:#222; }

  /* Pulsante icona compatto (Box 5) */
  .thub-btn--icon{
    display:inline-flex; align-items:center; justify-content:center;
    width:32px; height:32px; padding:0; border:1px solid var(--border, #e6e6ea);
    border-radius:.6rem; background:#f7f7f9; cursor:pointer;
  }
  .thub-btn--icon:hover{ background:#efeff4; }

  /* Badge di stato salvataggio (success/error) */
  .thub-save-msg{ display:inline-block; margin-top:.6rem; font-size:.88rem; }
  .thub-save-ok{ color:#2c7a4b; }
  .thub-save-err{ color:#c0392b; }

  @media (max-width: 900px){
    #thub-canvas .thub-privacy-grid{ grid-template-columns:1fr; }
  }
</style>

<section id="thub-privacy" class="thub-account__section" data-nonce="<?php echo esc_attr($thub_privacy_ajax_nonce); ?>"><!-- [THUB_PRIVACY_ROOT] -->
  <header class="thub-account__header" style="max-width:760px;margin:0 auto 1.25rem;">
    <h2 class="thub-account__title">Dati e privacy</h2>
    <p class="thub-account__intro">
      Opzioni principali relative alla privacy che ti aiutano a scegliere i dati da salvare nel tuo account,
      gli annunci che ti vengono mostrati, le informazioni da condividere con altre persone e altro ancora.
    </p>
  </header>

  <div class="thub-privacy-grid"><!-- [THUB_PRIVACY_GRID_WRAP] -->

    <!-- =========================
         Box 1 — Impostazioni cronologia
         ========================= -->
    <div class="thub-box" aria-labelledby="thub-pv-box1-title"><!-- [THUB_BOX_PRIVACY_1] -->
      <div>
        <h3 id="thub-pv-box1-title" class="thub-box__title">Impostazioni cronologia</h3>
        <p class="thub-box__desc">Scegli se salvare le attività che svolgi per ricevere risultati più pertinenti, consigli e non solo.</p>

        <!-- Attività web -->
        <div class="thub-toggle__row">
          <span class="thub-toggle-label">Attività web</span>
          <label class="thub-toggle thub-justify" for="thub_priv_web_activity">
            <input
              type="checkbox"
              id="thub_priv_web_activity"
              data-meta-key="thub_priv_web_activity"
              value="1" <?php checked($meta['thub_priv_web_activity'],'1'); ?>
            />
            <span class="thub-toggle-slider" aria-hidden="true"></span>
          </label>
        </div>

        <!-- Cronologia -->
        <div class="thub-toggle__row" style="margin-top:.5rem;">
          <span class="thub-toggle-label">Cronologia</span>
          <label class="thub-toggle thub-justify" for="thub_priv_history">
            <input
              type="checkbox"
              id="thub_priv_history"
              data-meta-key="thub_priv_history"
              value="1" <?php checked($meta['thub_priv_history'],'1'); ?>
            />
            <span class="thub-toggle-slider" aria-hidden="true"></span>
          </label>
        </div>

        <!-- Messaggio salvataggio -->
        <small class="thub-save-msg" hidden></small>
      </div>
    </div>

    <!-- =========================
         Box 2 — Annunci personalizzati
         ========================= -->
    <div class="thub-box" aria-labelledby="thub-pv-box2-title"><!-- [THUB_BOX_PRIVACY_2] -->
      <div>
        <h3 id="thub-pv-box2-title" class="thub-box__title">Annunci personalizzati</h3>
        <p class="thub-box__desc">Puoi scegliere se vedere annunci personalizzati sui servizi T‑Hub.</p>

        <!-- Annunci personalizzati -->
        <div class="thub-toggle__row">
          <span class="thub-toggle-label">Annunci personalizzati</span>
          <label class="thub-toggle thub-justify" for="thub_ads_personalized">
            <input
              type="checkbox"
              id="thub_ads_personalized"
              data-meta-key="thub_ads_personalized"
              value="1" <?php checked($meta['thub_ads_personalized'],'1'); ?>
            />
            <span class="thub-toggle-slider" aria-hidden="true"></span>
          </label>
        </div>

        <!-- Annunci partner -->
        <div class="thub-toggle__row" style="margin-top:.5rem;">
          <span class="thub-toggle-label">Annunci personalizzati partner T‑Hub</span>
          <label class="thub-toggle thub-justify" for="thub_ads_partners">
            <input
              type="checkbox"
              id="thub_ads_partners"
              data-meta-key="thub_ads_partners"
              value="1" <?php checked($meta['thub_ads_partners'],'1'); ?>
            />
            <span class="thub-toggle-slider" aria-hidden="true"></span>
          </label>
        </div>

        <p style="margin:.6rem 0 0; color:#555; font-size:.92rem;">
          <strong>Nota:</strong> T‑Hub tutela la tua privacy. I tuoi contenuti non vengono mai usati per finalità pubblicitarie.
        </p>

        <!-- Messaggio salvataggio -->
        <small class="thub-save-msg" hidden></small>
      </div>
    </div>

    <!-- =========================
         Box 3 — Personalizzazione della Ricerca
         ========================= -->
    <div class="thub-box" aria-labelledby="thub-pv-box3-title"><!-- [THUB_BOX_PRIVACY_3] -->
      <div>
        <h3 id="thub-pv-box3-title" class="thub-box__title">Personalizzazione della Ricerca</h3>
        <p class="thub-box__desc">Scegli se la Ricerca può mostrarti esperienze personalizzate in base ai dati salvati nel tuo Account T‑Hub.</p>

        <div class="thub-toggle__row">
          <span class="thub-toggle-label">Ricerca personalizzata</span>
          <label class="thub-toggle thub-justify" for="thub_search_personalized">
            <input
              type="checkbox"
              id="thub_search_personalized"
              data-meta-key="thub_search_personalized"
              value="1" <?php checked($meta['thub_search_personalized'],'1'); ?>
            />
            <span class="thub-toggle-slider" aria-hidden="true"></span>
          </label>
        </div>

        <!-- Messaggio salvataggio -->
        <small class="thub-save-msg" hidden></small>
      </div>
    </div>

    <!-- =========================
         Box 4 — Informazioni condivisibili
         ========================= -->
    <div class="thub-box" aria-labelledby="thub-pv-box4-title"><!-- [THUB_BOX_PRIVACY_4] -->
      <div>
        <h3 id="thub-pv-box4-title" class="thub-box__title">Informazioni che puoi condividere con altre persone</h3>
        <p class="thub-box__desc">
          Le informazioni personali che hai salvato nel tuo account, come il tuo compleanno o il tuo indirizzo email.
          Queste informazioni sono private, ma puoi renderle visibili ad altre persone.
        </p>

        <h4 style="margin:.6rem 0 .4rem;">Informazioni personali</h4>

        <div class="thub-toggle__row">
          <span class="thub-toggle-label">Data di nascita</span>
          <label class="thub-toggle thub-justify" for="thub_share_birthdate">
            <input
              type="checkbox"
              id="thub_share_birthdate"
              data-meta-key="thub_share_birthdate"
              value="1" <?php checked($meta['thub_share_birthdate'],'1'); ?>
            />
            <span class="thub-toggle-slider" aria-hidden="true"></span>
          </label>
        </div>

        <div class="thub-toggle__row" style="margin-top:.5rem;">
          <span class="thub-toggle-label">Genere</span>
          <label class="thub-toggle thub-justify" for="thub_share_gender">
            <input
              type="checkbox"
              id="thub_share_gender"
              data-meta-key="thub_share_gender"
              value="1" <?php checked($meta['thub_share_gender'],'1'); ?>
            />
            <span class="thub-toggle-slider" aria-hidden="true"></span>
          </label>
        </div>

        <h4 style="margin:.8rem 0 .4rem;">Dati di contatto</h4>

        <div class="thub-toggle__row">
          <span class="thub-toggle-label">Email di contatto</span>
          <label class="thub-toggle thub-justify" for="thub_share_email">
            <input
              type="checkbox"
              id="thub_share_email"
              data-meta-key="thub_share_email"
              value="1" <?php checked($meta['thub_share_email'],'1'); ?>
            />
            <span class="thub-toggle-slider" aria-hidden="true"></span>
          </label>
        </div>

        <div class="thub-toggle__row" style="margin-top:.5rem;">
          <span class="thub-toggle-label">Numero di telefono</span>
          <label class="thub-toggle thub-justify" for="thub_share_phone">
            <input
              type="checkbox"
              id="thub_share_phone"
              data-meta-key="thub_share_phone"
              value="1" <?php checked($meta['thub_share_phone'],'1'); ?>
            />
            <span class="thub-toggle-slider" aria-hidden="true"></span>
          </label>
        </div>

        <h4 style="margin:.8rem 0 .4rem;">I tuoi profili social</h4>

        <div class="thub-toggle__row">
          <span class="thub-toggle-label">Profili social</span>
          <label class="thub-toggle thub-justify" for="thub_share_social">
            <input
              type="checkbox"
              id="thub_share_social"
              data-meta-key="thub_share_social"
              value="1" <?php checked($meta['thub_share_social'],'1'); ?>
            />
            <span class="thub-toggle-slider" aria-hidden="true"></span>
          </label>
        </div>

        <h4 style="margin:.8rem 0 .4rem;">Condivisione della posizione</h4>

        <div class="thub-toggle__row">
          <span class="thub-toggle-label">Geo‑localizzazione</span>
          <label class="thub-toggle thub-justify" for="thub_share_geoloc">
            <input
              type="checkbox"
              id="thub_share_geoloc"
              data-meta-key="thub_share_geoloc"
              value="1" <?php checked($meta['thub_share_geoloc'],'1'); ?>
            />
            <span class="thub-toggle-slider" aria-hidden="true"></span>
          </label>
        </div>

        <!-- Messaggio salvataggio -->
        <small class="thub-save-msg" hidden></small>
      </div>
    </div>

    <!-- =========================
         Box 5 — Scarica o elimina i tuoi dati
         (testo + piccolo pulsante icona accanto)
         ========================= -->
    <div class="thub-box" aria-labelledby="thub-pv-box5-title"><!-- [THUB_PRIVACY_BOX5_DATA] -->
      <div>
        <h3 id="thub-pv-box5-title" class="thub-box__title">Scarica o elimina i tuoi dati</h3>

        <!-- Riga 1: Scarica i tuoi dati + icona -->
        <div class="thub-inline-row">
          <span class="thub-inline-row__label">Scarica i tuoi dati</span>
          <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" style="margin:0;">
            <?php wp_nonce_field( 'thub_privacy_export_nonce', '_thub_privacy_export_nonce' ); ?>
            <input type="hidden" name="action" value="thub_download_data" />
            <button type="submit" class="thub-btn--icon" aria-label="Scarica i tuoi dati">
              <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M12 3v10m0 0l4-4m-4 4L8 9" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M4 14v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>
              </svg>
            </button>
          </form>
        </div>

        <!-- Riga 2: Elimina i tuoi dati + icona -->
        <div class="thub-inline-row">
          <span class="thub-inline-row__label">Elimina i tuoi dati</span>
          <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" style="margin:0;" onsubmit="return confirm('Confermi di voler richiedere l’eliminazione dei tuoi dati? Riceverai una email per completare la procedura.');">
            <?php wp_nonce_field( 'thub_privacy_erase_nonce', '_thub_privacy_erase_nonce' ); ?>
            <input type="hidden" name="action" value="thub_erase_data" />
            <button type="submit" class="thub-btn--icon" aria-label="Elimina i tuoi dati">
              <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M3 6h18M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M6 6l1 14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>
                <path d="M10 11v6m4-6v6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>
              </svg>
            </button>
          </form>
        </div>
      </div>
    </div>

  </div><!-- /thub-privacy-grid -->
</section>