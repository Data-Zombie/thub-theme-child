<?php
/* ==========================================================================
 * [THUB_CHECKLIST_SECTION] section-checklist-account-THUB.php
 * Elenco di controllo Account T-Hub — Layout stile Canvas
 * Requisiti:
 *  - Hello Child Theme, Editor classico, niente Elementor
 *  - Router Canvas già attivo (page-thub-canvas.php + helpers in functions.php)
 *  - Solo visualizzazione stato (read-only), nessuna scrittura
 *
 * Meta chiavi utilizzate (presenti in functions.php):
 *  - Telefono: thub_phone_cc (prefisso, es. +39) + thub_phone_number (solo cifre)
 *  - Email recupero: thub_email_recovery
 *  - Email contatto: thub_email_contact
 *  - Immagine profilo: ACF user field thub_profile_photo (helper: thub_get_user_avatar_url)
 *  - Indirizzo di casa (strutturato/libero):
 *      thub_home_country, thub_home_address, thub_home_number, thub_home_interno,
 *      thub_home_cap, thub_home_city, thub_home_province  |  oppure thub_home_free
 *  - Indirizzo di lavoro (strutturato/libero):
 *      thub_work_country, thub_work_address, thub_work_number, thub_work_interno,
 *      thub_work_cap, thub_work_city, thub_work_province  |  oppure thub_work_free
 *  - Metodo di pagamento: thub_has_payment_method (string '1' = presente) [opzionale al momento]
 *
 * NOTE DI STILE:
 *  - Box 1 e 2 fullwidth (una riga ciascuno, 2 colonne: icona + contenuto)
 *  - Box 3 e 4 halfwidth affiancati su desktop, con allineamento verticale "medio"
 *  - Linee di separazione di Box 3 e 4 alla stessa altezza per coerenza
 *  - Checkbox disabilitate (solo stato), link barrato (line-through) se completato
 * ========================================================================== */

// [THUB_CHECKLIST_USER] — Recupero utente
$user_id = get_current_user_id();

// Sicurezza: se non loggato, messaggio e stop (Canvas protetto ha già redirect, ma teniamo il guard)
if ( ! $user_id ) {
  echo '<p>Devi effettuare l\'accesso per visualizzare questa pagina.</p>';
  return;
}

// Helper: meta get safe
function thub_meta_get($uid, $key){
  $v = get_user_meta($uid, $key, true);
  return is_string($v) ? trim($v) : $v;
}

// [THUB_CHECKLIST_STATE] — Stato completamento voci
$phone_cc     = thub_meta_get($user_id, 'thub_phone_cc');
$phone_num    = thub_meta_get($user_id, 'thub_phone_number');
$has_phone    = ($phone_cc !== '' && $phone_num !== '');

$recovery_em  = thub_meta_get($user_id, 'thub_email_recovery');
$has_recovery = ($recovery_em !== '');

$contact_em   = thub_meta_get($user_id, 'thub_email_contact');
$has_contact  = ($contact_em !== '');

// Avatar (via helper) → spunta se URL valido
$avatar_url = '';
if ( function_exists('thub_get_user_avatar_url') ) {
  $avatar_url = thub_get_user_avatar_url($user_id);
} else {
  // fallback grezzo su meta diretta, se esistesse
  $avatar_url = thub_meta_get($user_id, 'thub_profile_photo');
}
$has_avatar = !empty($avatar_url);

// Indirizzo di casa: consideriamo completato se ha almeno un campo strutturato rilevante
$home_struct = [
  'thub_home_country','thub_home_address','thub_home_number','thub_home_cap',
  'thub_home_city','thub_home_province'
];
$home_free = thub_meta_get($user_id, 'thub_home_free');
$has_home  = false;
foreach($home_struct as $k){
  if( thub_meta_get($user_id, $k) !== '' ){ $has_home = true; break; }
}
if( ! $has_home && $home_free !== '' ){ $has_home = true; }

// Indirizzo di lavoro
$work_struct = [
  'thub_work_country','thub_work_address','thub_work_number','thub_work_cap',
  'thub_work_city','thub_work_province'
];
$work_free = thub_meta_get($user_id, 'thub_work_free');
$has_work  = false;
foreach($work_struct as $k){
  if( thub_meta_get($user_id, $k) !== '' ){ $has_work = true; break; }
}
if( ! $has_work && $work_free !== '' ){ $has_work = true; }

// Metodo di pagamento (opzionale, se non ancora presente la meta rimane non spuntato)
$has_pay = ( thub_meta_get($user_id, 'thub_has_payment_method') === '1' );

// [THUB_CHECKLIST_UI_HELPER] — Renderer riga voce con checkbox + link
function thub_checklist_row($is_done, $label_html, $id){
  // $label_html contiene già l'anchor <a>…</a>
  $cls = $is_done ? ' is-done' : '';
  ?>
  <div class="thub-checkrow<?php echo esc_attr($cls); ?>" id="<?php echo esc_attr($id); ?>">
    <input type="checkbox" disabled <?php checked($is_done); ?> aria-checked="<?php echo $is_done ? 'true':'false'; ?>" />
    <span class="thub-checkrow__label"><?php echo $label_html; // phpcs:ignore ?></span>
  </div>
  <?php
}

?>
<!-- ================================
     [THUB_CHECKLIST_STYLES] CSS inline
     ================================ -->
<style>
  /* Wrapper generale */
  .thub-checklist { margin: 0 auto; max-width: 1100px; padding: 12px 16px 24px; }

  /* Titolo e sottotitolo centrati */
  .thub-checklist__head { text-align: center; margin-bottom: 18px; }
  .thub-checklist__title { font-size: clamp(22px, 3.4vw, 30px); font-weight: 700; margin: 6px 0 4px; }
  .thub-checklist__subtitle { color: #666; font-size: 15px; }

  /* Box generici (icone a sx, contenuti a dx) */
  .thub-box {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 16px;
    padding: 16px;
    border: 1px solid #ececf1;
    border-radius: 12px;
    background: #fff;
    margin-bottom: 14px;
  }

  /* Fullwidth: di base 2 colonne (icona|contenuto) già impostate */
  .thub-box--full {}

  /* Halfwidth: due box affiancati in riga su desktop */
  .thub-two { display: grid; grid-template-columns: 1fr; gap: 14px; }
  @media (min-width: 980px){
    .thub-two { grid-template-columns: 1fr 1fr; }
  }

  /* Colonna icona: centrata orizz/vert */
  .thub-col--icon {
    display: flex; align-items: center; justify-content: center;
  }
  .thub-iconwrap {
    width: clamp(120px, 26vw, 160px); height: clamp(120px, 26vw, 160px);
    display: flex; align-items: center; justify-content: center;
  }
  .thub-iconwrap svg { width: 100%; height: 100%; }

  /* Colonna contenuto */
  .thub-col--content { display: flex; flex-direction: column; justify-content: center; }
  .thub-col--content h3 { margin: 0 0 10px; font-size: 18px; }

  /* Righe checkbox */
  .thub-checkrow {
    display: grid; grid-template-columns: auto 1fr; align-items: center;
    gap: 10px; padding: 6px 0;
  }
  .thub-checkrow input[type="checkbox"] {
    width: 18px; height: 18px; accent-color: #7249a4; /* colore tema */
  }
  .thub-checkrow__label a { color: #1a1a1a; text-decoration: none; }
  .thub-checkrow__label a:hover { text-decoration: underline; }

  /* Stato completato: barrato + colore attenuato */
  .thub-checkrow.is-done .thub-checkrow__label a {
    text-decoration: line-through;
    color: #8c8c92;
  }

  /* Box 3 e 4: allineamento verticale “medio” + linea alla stessa altezza */
  .thub-box--mid { min-height: 280px; } /* altezza coerente per allineare le linee */
  .thub-sepline {
    height: 1px; background: #ececf1; margin: 10px 0 12px;
  }

  /* Sottotitoli */
  .thub-sub { color: #555; font-size: 14px; margin: -4px 0 10px; }

  /* Responsive piccole */
  @media (max-width: 600px){
    .thub-box { grid-template-columns: 1fr; }
    .thub-col--content { justify-content: flex-start; }
    .thub-box--mid { min-height: unset; }
  }
</style>

<!-- ================================
     [THUB_CHECKLIST_HTML] Markup
     ================================ -->
<section class="thub-checklist" aria-labelledby="thubChecklistTitle">
  <!-- Header -->
  <div class="thub-checklist__head">
    <h2 id="thubChecklistTitle" class="thub-checklist__title">
      Elenco di controllo dell'Account T-Hub
    </h2>
    <div class="thub-checklist__subtitle">
      Ti diamo il benvenuto nell'elenco di controllo del tuo Account T-Hub
    </div>
  </div>

  <!-- ==========================
       [THUB_CHECKLIST_BOX1] Box 1 — FULLWIDTH
       Titolo: Evita di non riuscire ad accedere all'account
       ========================== -->
  <div class="thub-box thub-box--full" role="group" aria-label="Recupero e contatti">
    <!-- Col 1: Icona Account -->
    <div class="thub-col--icon">
      <div class="thub-iconwrap" aria-hidden="true">
        <!-- [THUB_ICON_ACCOUNT] SVG Account -->
        <svg viewBox="0 0 120 120" role="img" aria-label="Account">
          <rect x="10" y="8" width="100" height="104" rx="16" ry="16" fill="#f1f0ff"></rect>
          <circle cx="60" cy="46" r="20" fill="#c7bef3"></circle>
          <path d="M24 98c8-18 26-24 36-24s28 6 36 24" fill="none" stroke="#9f90ec" stroke-width="6" stroke-linecap="round"/>
        </svg>
      </div>
    </div>

    <!-- Col 2: Contenuto -->
    <div class="thub-col--content">
      <h3>Evita di non riuscire ad accedere all'account</h3>

      <?php
        // Riga: Aggiungi il tuo numero di telefono
        thub_checklist_row(
          $has_phone,
          '<a href="/account/informazioni-personali/">Aggiungi il tuo numero di telefono</a>',
          'THUB_CHECKLIST_ROW_PHONE'
        );

        // Riga: Email di recupero
        thub_checklist_row(
          $has_recovery,
          '<a href="/account/informazioni-personali/">Aggiungi l\'indirizzo email di recupero</a>',
          'THUB_CHECKLIST_ROW_RECOVERY'
        );

        // Riga: Email di contatto
        thub_checklist_row(
          $has_contact,
          '<a href="/account/informazioni-personali/">Aggiungi l\'indirizzo email di contatto</a>',
          'THUB_CHECKLIST_ROW_CONTACT'
        );
      ?>
    </div>
  </div>

  <!-- ==========================
       [THUB_CHECKLIST_BOX2] Box 2 — FULLWIDTH
       Titolo: Semplificati la vita con T-Hub
       ========================== -->
  <div class="thub-box thub-box--full" role="group" aria-label="Profilo e indirizzi">
    <!-- Col 1: Icona Address Book -->
    <div class="thub-col--icon">
      <div class="thub-iconwrap" aria-hidden="true">
        <!-- [THUB_ICON_ADDRESSBOOK] SVG Address Book -->
        <svg viewBox="0 0 120 120" role="img" aria-label="Rubrica">
          <rect x="14" y="10" width="80" height="100" rx="12" ry="12" fill="#eaf7ff"></rect>
          <rect x="30" y="22" width="48" height="10" rx="4" fill="#9bd0f7"></rect>
          <rect x="30" y="40" width="48" height="10" rx="4" fill="#b3dcfb"></rect>
          <rect x="30" y="58" width="48" height="10" rx="4" fill="#c9e7fd"></rect>
          <rect x="98" y="18" width="10" height="84" rx="4" fill="#7fc3f3"></rect>
        </svg>
      </div>
    </div>

    <!-- Col 2: Contenuto -->
    <div class="thub-col--content">
      <h3>Semplificati la vita con T-Hub</h3>

      <?php
        // Riga: Immagine profilo
        thub_checklist_row(
          $has_avatar,
          '<a href="/account/informazioni-personali/">Aggiungi un\'immagine del profilo</a>',
          'THUB_CHECKLIST_ROW_AVATAR'
        );

        // Riga: Indirizzo di casa
        thub_checklist_row(
          $has_home,
          '<a href="/account/informazioni-personali/">Indirizzo di casa</a>',
          'THUB_CHECKLIST_ROW_HOME'
        );

        // Riga: Indirizzo di lavoro
        thub_checklist_row(
          $has_work,
          '<a href="/account/informazioni-personali/">Indirizzo di lavoro</a>',
          'THUB_CHECKLIST_ROW_WORK'
        );

        // Riga: Metodo di pagamento
        thub_checklist_row(
          $has_pay,
          '<a href="/account/pagamenti-abbonamenti/">Aggiungi un metodo di pagamento</a>',
          'THUB_CHECKLIST_ROW_PAYMENT'
        );
      ?>
    </div>
  </div>

  <!-- ==========================
       [THUB_CHECKLIST_BOX3_4] Box 3 e 4 — HALFWIDTH affiancati
       ========================== -->
  <div class="thub-two">
    <!-- Box 3 -->
    <div class="thub-box thub-box--mid" role="region" aria-label="Dati e privacy">
      <!-- Col 1: Icona Privacy -->
      <div class="thub-col--icon">
        <div class="thub-iconwrap" aria-hidden="true">
          <!-- [THUB_ICON_PRIVACY] SVG Privacy -->
          <svg viewBox="0 0 120 120" role="img" aria-label="Privacy">
            <path d="M60 10 L100 25 V50 C100 82 78.2 114 60 116 C41.8 114 20 82 20 50 V25 L60 10 Z" fill="#eaf7ef"></path>
            <circle cx="60" cy="60" r="25" fill="#99d6ad"></circle>
            <path d="M52 60 L58 66 L70 54" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </div>
      </div>

      <!-- Col 2: Contenuti -->
      <div class="thub-col--content">
        <h3>Scegli quali dati da salvare nel tuo account</h3>
        <div class="thub-sub">
          Visualizza i dati nel tuo Account T-Hub e scegli quali salvare per personalizzare la tua esperienza su T-Hub.
        </div>
        <div class="thub-sepline" aria-hidden="true"></div>
        <div>
          <a href="/account/dati-privacy/">Gestisci i tuoi dati e la tua privacy</a>
        </div>
      </div>
    </div>

    <!-- Box 4 -->
    <div class="thub-box thub-box--mid" role="region" aria-label="Lingua di visualizzazione">
      <!-- Col 1: Icona Lingua -->
      <div class="thub-col--icon">
        <div class="thub-iconwrap" aria-hidden="true">
          <!-- [THUB_ICON_LANGUAGE] SVG Lingua (fornito) -->
          <svg viewBox="0 0 120 120" role="img" aria-label="Lingua">
            <rect x="8" y="12" rx="16" ry="16" width="104" height="96" fill="#eaf2ff"></rect>
            <circle cx="60" cy="60" r="28" fill="#9bbcf7"></circle>
            <path d="M42 60h36M60 42v36" stroke="#fff" stroke-width="6" stroke-linecap="round"></path>
          </svg>
        </div>
      </div>

      <!-- Col 2: Contenuti -->
      <div class="thub-col--content">
        <h3>Scegli la lingua di visualizzazione</h3>
        <div class="thub-sub">
          Gestisci la lingua di visualizzazione della Ricerca T-Hub e seleziona le preferenze per i risultati.
        </div>
        <div class="thub-sepline" aria-hidden="true"></div>
        <div>
          <a href="/lingua-e-regione/">Imposta la tua lingua madre</a>
        </div>
      </div>
    </div>
  </div>
</section>