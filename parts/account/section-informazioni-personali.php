<?php
/**
 * [THUB_CANVAS_SECTION_PERSONALI]
 * File: section-impostazioni-personali.php
 * Scopo: sezione "Impostazioni personali" per Canvas Account
 *
 * ISTRUZIONI (Editor classico):
 * - Salva questo file in /wp-content/themes/hello-elementor-child/ come section-impostazioni-personali.php
 * - Assicurati che il router di page-thub-canvas.php carichi questa sezione quando ?section=impostazioni-personali
 *
 * NOTE:
 * - Il layout si appoggia a .thub-box e al Canvas CSS già presenti nello style.css del tema child [THUB_CANVAS_LAYOUT]
 * - Gli handler PHP (admin-post.php) per i form sono da aggiungere in functions.php in un secondo momento:
 *   thub_update_name, thub_update_dob, thub_update_emails, thub_update_phone,
 *   thub_update_address_home, thub_update_address_work, thub_update_socials
 */

if ( ! defined('ABSPATH') ) { exit; }

$current_user = wp_get_current_user();
$user_id      = get_current_user_id();

/* ============================================
 * [THUB_CANVAS_DROPDOWNS_EMBED] — include dropdown Account/Apps dentro la section
 * - Scopo: garantire che su Canvas esista l’HTML di #thub-dropdown-account / #thub-dropdown-apps
 * - Guardia anti-duplicati: costante THUB_DROPDOWNS_PRINTED
 * - NB: i file devono esistere nel tema child con i nomi indicati
 * ============================================ */
if ( ! defined('THUB_DROPDOWNS_PRINTED') ) {
  define('THUB_DROPDOWNS_PRINTED', true);

  // Include “silenzioso”: cerca nel tema child e include senza estrazione di variabili
  // Aggiorna i nomi se i file hanno percorsi diversi (es. parts/account-modal.php)
  if ( locate_template( ['account-modal.php'], true, false ) === '' ) {
    // fallback hard-path se serve
    $f = get_stylesheet_directory() . '/account-modal.php';
    if ( file_exists($f) ) include $f;
  }

  if ( locate_template( ['apps-modal.php'], true, false ) === '' ) {
    $f2 = get_stylesheet_directory() . '/apps-modal.php';
    if ( file_exists($f2) ) include $f2;
  }
}

/* [THUB_PROFILE_HELPERS]
 * Helper avatar: se nel tuo tema è definito thub_get_user_avatar_url($user_id), lo usiamo;
 * altrimenti mostriamo il fallback con iniziale e palette (coerente con il modal account).
 */
$avatar_url = function_exists('thub_get_user_avatar_url') ? thub_get_user_avatar_url($user_id) : '';
$display_name = $current_user ? $current_user->display_name : '';
$first_initial = $display_name ? mb_strtoupper(mb_substr($display_name, 0, 1, 'UTF-8')) : 'U';

/* [THUB_USER_DEFAULTS] Dati base */
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name  = get_user_meta($user_id, 'last_name',  true);
$nickname   = $current_user ? $current_user->user_nicename : '';

/* [THUB_DOB] Data di nascita (salvata in meta separati) + Paese per ordine select */
$dob_day    = get_user_meta($user_id, 'thub_dob_day',   true);
$dob_month  = get_user_meta($user_id, 'thub_dob_month', true);
$dob_year   = get_user_meta($user_id, 'thub_dob_year',  true);
$user_country = get_user_meta($user_id, 'billing_country', true);
if (empty($user_country)) { $user_country = 'IT'; } // fallback

/* [THUB_CONTACTS] Email + Telefono */
$account_email  = $current_user ? $current_user->user_email : '';
$recovery_email = get_user_meta($user_id, 'thub_email_recovery', true);
$contact_email  = get_user_meta($user_id, 'thub_email_contact',  true);
$phone_cc       = get_user_meta($user_id, 'thub_phone_cc',       true); // es. +39
$phone_num      = get_user_meta($user_id, 'thub_phone_number',   true);
$phone_pretty   = trim(($phone_cc ? $phone_cc.' ' : '') . ($phone_num ?: ''));
if ($phone_pretty==='') { $phone_pretty = '—'; }

/* [THUB_ADDRESSES] Indirizzi CASA/LAVORO */
$home_country  = get_user_meta($user_id, 'thub_home_country',  true) ?: 'IT';
$home_addr     = get_user_meta($user_id, 'thub_home_address',  true);
$home_number   = get_user_meta($user_id, 'thub_home_number',   true);
$home_interno  = get_user_meta($user_id, 'thub_home_interno',  true);
$home_cap      = get_user_meta($user_id, 'thub_home_cap',      true);
$home_city     = get_user_meta($user_id, 'thub_home_city',     true);
$home_prov     = get_user_meta($user_id, 'thub_home_province', true);
$home_free     = get_user_meta($user_id, 'thub_home_free',     true);

$work_country  = get_user_meta($user_id, 'thub_work_country',  true) ?: 'IT';
$work_addr     = get_user_meta($user_id, 'thub_work_address',  true);
$work_number   = get_user_meta($user_id, 'thub_work_number',   true);
$work_interno  = get_user_meta($user_id, 'thub_work_interno',  true);
$work_cap      = get_user_meta($user_id, 'thub_work_cap',      true);
$work_city     = get_user_meta($user_id, 'thub_work_city',     true);
$work_prov     = get_user_meta($user_id, 'thub_work_province', true);
$work_free     = get_user_meta($user_id, 'thub_work_free',     true);

/* [THUB_ADDR_PRETTY] Rappresentazione “in chiaro” */
$home_pretty = '—';
if ($home_country==='OTHER' && $home_free){
  $home_pretty = trim($home_free);
} else if ($home_addr || $home_city || $home_cap) {
  $home_pretty = trim(sprintf('%s %s%s, %s %s%s%s',
    $home_addr,
    $home_number,
    $home_interno ? (' interno '.$home_interno) : '',
    $home_cap,
    $home_city,
    $home_prov ? (' ('.$home_prov.')') : '',
    $home_country ? (', '.$home_country) : ''
  ));
}
$work_pretty = '—';
if ($work_country==='OTHER' && $work_free){
  $work_pretty = trim($work_free);
} else if ($work_addr || $work_city || $work_cap) {
  $work_pretty = trim(sprintf('%s %s%s, %s %s%s%s',
    $work_addr,
    $work_number,
    $work_interno ? (' interno '.$work_interno) : '',
    $work_cap,
    $work_city,
    $work_prov ? (' ('.$work_prov.')') : '',
    $work_country ? (', '.$work_country) : ''
  ));
}

/* [THUB_COUNTRIES_SELECT] Paesi gestiti dalla UI (strutturato vs libero) */
$countries_opts = [ 'IT' => 'Italia', 'SM' => 'San Marino', 'OTHER' => 'Altro paese' ];

/* [THUB_SOCIALS] Profili social */
$socials = get_user_meta($user_id, 'thub_socials', true);
if (!is_array($socials)) $socials = [];
$social_names = array_map(function($row){ return !empty($row['platform']) ? $row['platform'] : null; }, $socials);
$social_names = array_filter(array_unique($social_names));
$social_summary = $social_names ? implode(', ', $social_names) : '—';
$platforms = [
  'Facebook','Instagram','TikTok','YouTube','X (Twitter)','LinkedIn','Pinterest','Snapchat',
  'Telegram','WhatsApp','Twitch','Discord','Reddit','Threads','Mastodon'
];

/* [THUB_LANG] Lingua utente (lettura semplice) */
$user_lang = get_user_meta($user_id,'thub_lang',true);
?>
<!-- ===========================
     [THUB_PERSONAL_PAGE] Header sezione
     =========================== -->
<section class="thub-account__section thub-impostazioni-personali">
  <header class="thub-account__header" style="text-align:center; margin: .4rem 0 1rem;">
    <h2 class="thub-account__title" style="margin:.2rem 0 .2rem;">Informazioni personali</h2>
    <p style="color:#555; margin:.25rem 0 0;">
      Informazioni su di te e sulle tue preferenze nei servizi T‑Hub
    </p>
  </header>

  <?php if ( isset($_GET['updated']) && $_GET['updated']=='1' ): ?>
    <div class="thub-box" style="border-color:#d7f0df;background:#f6fffa;">
      <p style="margin:0;color:#155724;"><strong>Informazioni salvate.</strong></p>
    </div>
  <?php endif; ?>

  <!-- ===========================
       [THUB_PERSONAL_INTRO] Intro a 2 colonne (testo + illustrazione)
       =========================== -->
  <div class="thub-box" style="border-color:#eee;">
    <div class="thub-intro-grid" style="display:grid; grid-template-columns:2.2fr 1fr; gap:16px; align-items:center;">
      <!-- Colonna 1: testo -->
      <div>
        <h3 style="margin:.1rem 0 .35rem; font-size:1.05rem;">
          Le informazioni del tuo profilo nei servizi T‑Hub
        </h3>
        <p style="color:#555; margin:0;">
          Informazioni personali e opzioni per gestirle. Puoi rendere visibili ad altre persone alcune di queste informazioni.
        </p>
      </div>
      <!-- Colonna 2: illustrazione (inline SVG, SEO-friendly title) -->
      <div aria-hidden="true">
        <svg viewBox="0 0 320 200" role="img" aria-label="Illustrazione impostazioni account T‑Hub"
             style="width:100%; height:auto; display:block; border-radius:10px; background:#f7f7f9; border:1px solid #e6e6ea;">
          <title>Illustrazione impostazioni account T‑Hub</title>
          <rect x="16" y="16" width="288" height="50" rx="8" fill="#eae7f5"></rect>
          <circle cx="48" cy="41" r="16" fill="#7249a4"></circle>
          <rect x="80" y="30" width="140" height="22" rx="6" fill="#d9d5ec"></rect>
          <rect x="16" y="84" width="288" height="32" rx="8" fill="#efeff6"></rect>
          <rect x="16" y="124" width="180" height="32" rx="8" fill="#efeff6"></rect>
          <rect x="204" y="124" width="100" height="32" rx="8" fill="#e1ddf0"></rect>
          <rect x="16" y="164" width="110" height="20" rx="6" fill="#ddd7ef"></rect>
          <rect x="132" y="164" width="70" height="20" rx="6" fill="#ddd7ef"></rect>
          <rect x="206" y="164" width="98" height="20" rx="6" fill="#ddd7ef"></rect>
        </svg>
      </div>
    </div>
  </div>

  <div style="height:12px;"></div>

  <!-- ===========================
       [THUB_PERSONAL_BLOCK1] 1° blocco: Informazioni di base
       =========================== -->
  <div class="thub-box" style="border-color:#eee;">
    <div style="margin-bottom:.5rem;">
      <h3 style="margin:.1rem 0 .3rem; font-size:1.05rem;">Informazioni di base</h3>
      <p style="color:#555; margin:0;">
        Alcune informazioni potrebbero essere visibili ad altre persone che usano i servizi T‑Hub.
      </p>
    </div>

    <!-- [THUB_PROFILE_AVATAR] Avatar (hook al modal header) -->
    <div class="thub-row" style="display:grid; grid-template-columns: 1fr 2fr; gap:14px; align-items:center; padding:.6rem 0;">
      <div><strong>Immagine del profilo</strong></div>
      <div>
        <button type="button"
                class="thub-profile-avatar-trigger"
                aria-label="Gestisci immagine del profilo"
                style="display:flex; align-items:center; gap:10px; background:transparent; border:0; padding:0; cursor:pointer;">
          <?php if (!empty($avatar_url)): ?>
            <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar profilo"
                 width="56" height="56"
                 style="width:56px;height:56px;border-radius:50%;object-fit:cover;display:block;">
          <?php else: ?>
            <div class="thub-acc__avatar-fallback" data-palette="1" aria-hidden="true"
                 style="width:56px;height:56px;border-radius:50%;display:grid;place-items:center;font-weight:800;color:#fff;">
              <?php echo esc_html($first_initial); ?>
            </div>
          <?php endif; ?>
          <span style="color:#666;">Gestisci foto profilo</span>
        </button>
      </div>
    </div>

    <hr style="border:0;border-top:1px solid #eee;margin:.6rem 0;">

    <!-- [THUB_PERSONAL_NAME] Nome (scheda) -->
    <div class="thub-row" style="display:grid; grid-template-columns: 1fr 2fr; gap:14px; align-items:start; padding:.6rem 0;">
      <div><strong>Nome</strong></div>
      <div>
        <details>
          <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.4rem .6rem;border:1px solid #e6e6ea;border-radius:.6rem;background:#f7f7f9;">
            <span>
              <?php
                $full = trim(($first_name ?: '') . ' ' . ($last_name ?: ''));
                echo $full ? esc_html($full) : '—';
              ?>
            </span>
            <span aria-hidden="true">▾</span>
          </summary>
          <div style="padding:.7rem .2rem .2rem;">
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
              <?php wp_nonce_field('thub_update_name','thub_nonce_name'); ?>
              <input type="hidden" name="action" value="thub_update_name">

              <div style="display:grid; grid-template-columns:1fr 1fr; gap:.6rem; margin:.4rem 0;">
                <label style="display:block;">
                  <span class="screen-reader-text">Nome</span>
                  <input type="text" name="first_name" placeholder="Nome"
                         value="<?php echo esc_attr($first_name); ?>"
                         style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                </label>
                <label style="display:block;">
                  <span class="screen-reader-text">Cognome</span>
                  <input type="text" name="last_name" placeholder="Cognome"
                         value="<?php echo esc_attr($last_name); ?>"
                         style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                </label>
              </div>

              <label style="display:block;max-width:420px;">
                <span class="screen-reader-text">Nickname</span>
                <input type="text" name="nickname" placeholder="Nickname"
                       value="<?php echo esc_attr($nickname); ?>"
                       style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
              </label>

              <p style="margin:.6rem 0 0;">
                <b>Chi può vedere o ascoltare il tuo nome?</b><br>
                <span style="color:#555;">
                  Chiunque può vedere queste informazioni quando comunica con te o quando consulta contenuti creati da te nei servizi T‑Hub.
                </span>
              </p>

              <div style="margin:.7rem 0 0;">
                <button type="submit" class="button"
                        style="border:1px solid #7249a4;border-radius:.6rem;padding:.45rem .8rem;background:#7249a4;color:#fff;cursor:pointer;">
                  Salva nome
                </button>
              </div>
            </form>
          </div>
        </details>
      </div>
    </div>

    <hr style="border:0;border-top:1px solid #eee;margin:.6rem 0;">

    <!-- [THUB_PERSONAL_DOB] Data di nascita (scheda) -->
    <div class="thub-row" style="display:grid; grid-template-columns: 1fr 2fr; gap:14px; align-items:start; padding:.6rem 0;">
      <div><strong>Data di nascita</strong></div>
      <div>
        <details>
          <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.4rem .6rem;border:1px solid #e6e6ea;border-radius:.6rem;background:#f7f7f9;">
            <span>
              <?php
                $dob_text = ($dob_day && $dob_month && $dob_year) ? sprintf('%02d/%02d/%04d', $dob_day, $dob_month, $dob_year) : '—';
                echo esc_html($dob_text);
              ?>
            </span>
            <span aria-hidden="true">▾</span>
          </summary>

          <div style="padding:.7rem .2rem .2rem;">
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
              <?php wp_nonce_field('thub_update_dob','thub_nonce_dob'); ?>
              <input type="hidden" name="action" value="thub_update_dob">
              <input type="hidden" id="thub_user_country" value="<?php echo esc_attr($user_country); ?>">

              <div id="thub-dob-selects" style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
                <!-- i 3 select (GG/MM/AAAA) sono montati via JS in ordine corretto per Paese -->
              </div>

              <p style="margin:.6rem 0 0; color:#555;">
                La tua data di nascita potrebbe essere usata per la personalizzazione e la sicurezza dell'account nei servizi T‑Hub.
                Se si tratta di un Account T‑Hub aziendale, specifica la data di nascita della persona che gestisce l'account.
              </p>

              <div style="margin:.7rem 0 0;">
                <button type="submit" class="button"
                        style="border:1px solid #7249a4;border-radius:.6rem;padding:.45rem .8rem;background:#7249a4;color:#fff;cursor:pointer;">
                  Salva data di nascita
                </button>
              </div>
            </form>
          </div>
        </details>
      </div>
    </div>
  </div>

  <!-- ===========================
       [THUB_CONTACTS_BLOCK2] 2° blocco: Dati di contatto
       =========================== -->
  <div class="thub-box" style="border-color:#eee;">
    <div style="margin-bottom:.5rem;">
      <h3 style="margin:.1rem 0 .3rem; font-size:1.05rem;">Dati di contatto</h3>
      <p style="color:#555; margin:0;">Gestisci gli indirizzi email associati al tuo Account T‑Hub.</p>
    </div>

    <!-- Email -->
    <div class="thub-row" style="display:grid; grid-template-columns: 1fr 2fr; gap:14px; align-items:start; padding:.6rem 0;">
      <div><strong>Email</strong></div>
      <div>
        <details>
          <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.4rem .6rem;border:1px solid #e6e6ea;border-radius:.6rem;background:#f7f7f9;">
            <span><?php echo $account_email ? esc_html($account_email) : '—'; ?></span>
            <span aria-hidden="true">▾</span>
          </summary>
          <div style="padding:.7rem .2rem .2rem;">
            <h4 style="margin:.1rem 0 .25rem; font-size:1rem;">Email Account T‑Hub</h4>
            <p style="color:#555; margin:0 0 .35rem;">
              L'indirizzo che consente a te e agli altri utenti di identificare il tuo Account T‑Hub. Non puoi modificare questo indirizzo.
            </p>
            <p style="margin:0 0 .6rem;"><strong><?php echo esc_html($account_email); ?></strong></p>

            <h4 style="margin:.6rem 0 .25rem; font-size:1rem;">Email di recupero</h4>
            <p style="color:#555; margin:0 0 .35rem;">
              L'indirizzo al quale T‑Hub può contattarti se rileva attività insolite nel tuo account o se non riesci più ad accedere.
            </p>

            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="margin:0 0 .9rem;">
              <?php wp_nonce_field('thub_update_emails','thub_nonce_emails'); ?>
              <input type="hidden" name="action" value="thub_update_emails">
              <label style="display:block;max-width:460px;margin:.25rem 0;">
                <span class="screen-reader-text">Email di recupero</span>
                <input type="email" name="thub_email_recovery" placeholder="indirizzo email di recupero"
                       value="<?php echo esc_attr($recovery_email); ?>"
                       style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
              </label>

              <h4 style="margin:.8rem 0 .25rem; font-size:1rem;">Email di contatto</h4>
              <p style="color:#555; margin:0 0 .35rem;">
                L'indirizzo a cui ti vengono inviate le informazioni sulla maggior parte dei prodotti T‑Hub che utilizzi con questo account.
              </p>
              <label style="display:block;max-width:460px;margin:.25rem 0;">
                <span class="screen-reader-text">Email di contatto</span>
                <input type="email" name="thub_email_contact" placeholder="indirizzo email di contatto"
                       value="<?php echo esc_attr($contact_email); ?>"
                       style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
              </label>

              <div style="margin:.7rem 0 0;">
                <button type="submit" class="button"
                        style="border:1px solid #7249a4;border-radius:.6rem;padding:.45rem .8rem;background:#7249a4;color:#fff;cursor:pointer;">
                  Salva email
                </button>
              </div>
            </form>
          </div>
        </details>
      </div>
    </div>

    <hr style="border:0;border-top:1px solid #eee;margin:.6rem 0;">

    <!-- Telefono -->
    <div class="thub-row" style="display:grid; grid-template-columns: 1fr 2fr; gap:14px; align-items:start; padding:.6rem 0;">
      <div><strong>Numero di telefono</strong></div>
      <div>
        <details>
          <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.4rem .6rem;border:1px solid #e6e6ea;border-radius:.6rem;background:#f7f7f9;">
            <span><?php echo esc_html($phone_pretty); ?></span>
            <span aria-hidden="true">▾</span>
          </summary>
          <div style="padding:.7rem .2rem .2rem;">
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
              <?php wp_nonce_field('thub_update_phone','thub_nonce_phone'); ?>
              <input type="hidden" name="action" value="thub_update_phone">

              <div style="display:grid; grid-template-columns: 140px 1fr; gap:.6rem; max-width:560px;">
                <label style="display:block;">
                  <span class="screen-reader-text">Prefisso</span>
                  <input type="text" name="thub_phone_cc" placeholder="Prefisso (es. +39)"
                         value="<?php echo esc_attr($phone_cc); ?>"
                         style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                </label>
                <label style="display:block;">
                  <span class="screen-reader-text">Numero di telefono</span>
                  <input type="tel" name="thub_phone_number" placeholder="Numero di telefono"
                         value="<?php echo esc_attr($phone_num); ?>"
                         style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                </label>
              </div>

              <p style="margin:.6rem 0 0; color:#555;">
                Usa il tuo numero di telefono per il recupero dell'account. Potrai ricevere avvisi relativi alla sicurezza dell'account e reimpostare la password, qualora la dimenticassi.
              </p>

              <div style="margin:.7rem 0 0;">
                <button type="submit" class="button"
                        style="border:1px solid #7249a4;border-radius:.6rem;padding:.45rem .8rem;background:#7249a4;color:#fff;cursor:pointer;">
                  Salva numero di telefono
                </button>
              </div>
            </form>
          </div>
        </details>
      </div>
    </div>
  </div>

  <!-- ===========================
       [THUB_ADDRESS_BLOCK3] 3° blocco: Indirizzi (Casa/Lavoro)
       =========================== -->
  <div class="thub-box" style="border-color:#eee;">
    <div style="margin-bottom:.5rem;">
      <h3 style="margin:.1rem 0 .3rem; font-size:1.05rem;">Indirizzi</h3>
      <p style="color:#555; margin:0;">
        I tuoi indirizzi di casa e di lavoro vengono utilizzati per personalizzare le tue esperienze su tutti i prodotti T‑Hub e per rendere più pertinenti gli annunci.
        Puoi anche aggiungere indirizzi al tuo profilo T‑Hub.
      </p>
    </div>

    <!-- CASA -->
    <div class="thub-row" style="display:grid; grid-template-columns: 1fr 2fr; gap:14px; align-items:start; padding:.6rem 0;">
      <div><strong>Casa</strong></div>
      <div>
        <details>
          <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.4rem .6rem;border:1px solid #e6e6ea;border-radius:.6rem;background:#f7f7f9;">
            <span><?php echo esc_html($home_pretty); ?></span>
            <span aria-hidden="true">▾</span>
          </summary>
          <div style="padding:.7rem .2rem .2rem;">
            <h4 style="margin:.1rem 0 .25rem; font-size:1rem;">Indirizzo di casa</h4>

            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
              <?php wp_nonce_field('thub_update_address_home','thub_nonce_addr_home'); ?>
              <input type="hidden" name="action" value="thub_update_address_home">

              <label style="display:block;max-width:260px;margin:.25rem 0;">
                <span class="screen-reader-text">Paese</span>
                <select name="thub_home_country" id="thub_home_country"
                        style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  <?php foreach($countries_opts as $k=>$lbl): ?>
                    <option value="<?php echo esc_attr($k); ?>" <?php selected($home_country, $k); ?>>
                      <?php echo esc_html($lbl); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>

              <div id="thub_home_struct" style="display:<?php echo ($home_country==='IT'||$home_country==='SM')?'block':'none'; ?>;">
                <div style="display:grid; grid-template-columns: 1fr 160px 160px; gap:.6rem; max-width:920px;">
                  <label style="display:block;">
                    <span class="screen-reader-text">Indirizzo</span>
                    <input type="text" name="thub_home_address" placeholder="Indirizzo"
                           value="<?php echo esc_attr($home_addr); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                  <label style="display:block;">
                    <span class="screen-reader-text">N. civico</span>
                    <input type="text" name="thub_home_number" placeholder="N. civico"
                           value="<?php echo esc_attr($home_number); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                  <label style="display:block;">
                    <span class="screen-reader-text">Interno</span>
                    <input type="text" name="thub_home_interno" placeholder="Interno"
                           value="<?php echo esc_attr($home_interno); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                </div>

                <div style="display:grid; grid-template-columns: 160px 1fr 180px; gap:.6rem; max-width:920px; margin-top:.6rem;">
                  <label style="display:block;">
                    <span class="screen-reader-text">CAP</span>
                    <input type="text" name="thub_home_cap" placeholder="CAP"
                           value="<?php echo esc_attr($home_cap); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                  <label style="display:block;">
                    <span class="screen-reader-text">Città</span>
                    <input type="text" name="thub_home_city" placeholder="Città"
                           value="<?php echo esc_attr($home_city); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                  <label style="display:block;">
                    <span class="screen-reader-text">Provincia</span>
                    <input type="text" name="thub_home_province" placeholder="Provincia"
                           value="<?php echo esc_attr($home_prov); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                </div>
              </div>

              <div id="thub_home_free" style="display:<?php echo ($home_country==='OTHER')?'block':'none'; ?>; margin-top:.6rem;max-width:920px;">
                <label style="display:block;">
                  <span class="screen-reader-text">Indirizzo completo</span>
                  <input type="text" name="thub_home_free" placeholder="Indirizzo completo (come si usa nel tuo Paese)"
                         value="<?php echo esc_attr($home_free); ?>"
                         style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                </label>
              </div>

              <div style="margin:.7rem 0 0;">
                <button type="submit" class="button"
                        style="border:1px solid #7249a4;border-radius:.6rem;padding:.45rem .8rem;background:#7249a4;color:#fff;cursor:pointer;">
                  Salva indirizzo di casa
                </button>
              </div>
            </form>
          </div>
        </details>
      </div>
    </div>

    <hr style="border:0;border-top:1px solid #eee;margin:.6rem 0;">

    <!-- LAVORO -->
    <div class="thub-row" style="display:grid; grid-template-columns: 1fr 2fr; gap:14px; align-items:start; padding:.6rem 0;">
      <div><strong>Lavoro</strong></div>
      <div>
        <details>
          <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.4rem .6rem;border:1px solid #e6e6ea;border-radius:.6rem;background:#f7f7f9;">
            <span><?php echo esc_html($work_pretty); ?></span>
            <span aria-hidden="true">▾</span>
          </summary>
          <div style="padding:.7rem .2rem .2rem;">
            <h4 style="margin:.1rem 0 .25rem; font-size:1rem;">Indirizzo di lavoro</h4>

            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
              <?php wp_nonce_field('thub_update_address_work','thub_nonce_addr_work'); ?>
              <input type="hidden" name="action" value="thub_update_address_work">

              <label style="display:block;max-width:260px;margin:.25rem 0;">
                <span class="screen-reader-text">Paese</span>
                <select name="thub_work_country" id="thub_work_country"
                        style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  <?php foreach($countries_opts as $k=>$lbl): ?>
                    <option value="<?php echo esc_attr($k); ?>" <?php selected($work_country, $k); ?>>
                      <?php echo esc_html($lbl); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>

              <div id="thub_work_struct" style="display:<?php echo ($work_country==='IT'||$work_country==='SM')?'block':'none'; ?>;">
                <div style="display:grid; grid-template-columns: 1fr 160px 160px; gap:.6rem; max-width:920px;">
                  <label style="display:block;">
                    <span class="screen-reader-text">Indirizzo</span>
                    <input type="text" name="thub_work_address" placeholder="Indirizzo"
                           value="<?php echo esc_attr($work_addr); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                  <label style="display:block;">
                    <span class="screen-reader-text">N. civico</span>
                    <input type="text" name="thub_work_number" placeholder="N. civico"
                           value="<?php echo esc_attr($work_number); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                  <label style="display:block;">
                    <span class="screen-reader-text">Interno</span>
                    <input type="text" name="thub_work_interno" placeholder="Interno"
                           value="<?php echo esc_attr($work_interno); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                </div>

                <div style="display:grid; grid-template-columns: 160px 1fr 180px; gap:.6rem; max-width:920px; margin-top:.6rem;">
                  <label style="display:block;">
                    <span class="screen-reader-text">CAP</span>
                    <input type="text" name="thub_work_cap" placeholder="CAP"
                           value="<?php echo esc_attr($work_cap); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                  <label style="display:block;">
                    <span class="screen-reader-text">Città</span>
                    <input type="text" name="thub_work_city" placeholder="Città"
                           value="<?php echo esc_attr($work_city); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                  <label style="display:block;">
                    <span class="screen-reader-text">Provincia</span>
                    <input type="text" name="thub_work_province" placeholder="Provincia"
                           value="<?php echo esc_attr($work_prov); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                  </label>
                </div>
              </div>

              <div id="thub_work_free" style="display:<?php echo ($work_country==='OTHER')?'block':'none'; ?>; margin-top:.6rem;max-width:920px;">
                <label style="display:block;">
                  <span class="screen-reader-text">Indirizzo completo</span>
                  <input type="text" name="thub_work_free" placeholder="Indirizzo completo (come si usa nel tuo Paese)"
                         value="<?php echo esc_attr($work_free); ?>"
                         style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                </label>
              </div>

              <div style="margin:.7rem 0 0;">
                <button type="submit" class="button"
                        style="border:1px solid #7249a4;border-radius:.6rem;padding:.45rem .8rem;background:#7249a4;color:#fff;cursor:pointer;">
                  Salva indirizzo di lavoro
                </button>
              </div>
            </form>
          </div>
        </details>
      </div>
    </div>
  </div>

  <!-- ===========================
       [THUB_SOCIALS_BLOCK4] 4° blocco: I tuoi profili social
       =========================== -->
  <div class="thub-box" style="border-color:#eee;">
    <div style="margin-bottom:.5rem;">
      <h3 style="margin:.1rem 0 .3rem; font-size:1.05rem;">I tuoi profili social</h3>
      <p style="color:#555; margin:0;">I tuoi profili social vengono resi visibili alle altre persone.</p>
    </div>

    <div class="thub-row" style="display:grid; grid-template-columns: 1fr 2fr; gap:14px; align-items:start; padding:.6rem 0;">
      <div><strong>Profili social attivi</strong></div>
      <div>
        <details>
          <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.4rem .6rem;border:1px solid #e6e6ea;border-radius:.6rem;background:#f7f7f9;">
            <span><?php echo esc_html($social_summary); ?></span>
            <span aria-hidden="true">▾</span>
          </summary>

          <div style="padding:.7rem .2rem .2rem;">
            <?php if($socials): ?>
              <ul style="list-style:none;margin:0 0 .6rem 0;padding:0;display:flex;flex-wrap:wrap;gap:.35rem;">
                <?php foreach($socials as $row): if(empty($row['platform']) || empty($row['url'])) continue; ?>
                  <li style="border:1px solid #e6e6ea;border-radius:.6rem;padding:.25rem .5rem;">
                    <a href="<?php echo esc_url($row['url']); ?>" target="_blank" rel="noopener" style="text-decoration:none;color:#333;">
                      <?php echo esc_html($row['platform']); ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p style="color:#777;margin:0 0 .6rem;">Nessun profilo collegato.</p>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" id="thub-socials-form">
              <?php wp_nonce_field('thub_update_socials','thub_nonce_socials'); ?>
              <input type="hidden" name="action" value="thub_update_socials">

              <div id="thub-socials-repeater" style="display:flex; flex-direction:column; gap:.6rem; max-width:720px;">
                <?php $idx=0; foreach($socials as $row){
                  $plat = isset($row['platform']) ? $row['platform'] : '';
                  $url  = isset($row['url'])      ? $row['url']      : '';
                ?>
                  <div class="thub-social-row" style="display:grid; grid-template-columns: 220px 1fr 40px; gap:.5rem; align-items:center;">
                    <select name="thub_socials[<?php echo $idx; ?>][platform]"
                            style="padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                      <option value="">Seleziona profilo</option>
                      <?php foreach($platforms as $pf): ?>
                        <option value="<?php echo esc_attr($pf); ?>" <?php selected($plat, $pf); ?>>
                          <?php echo esc_html($pf); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <input type="url" name="thub_socials[<?php echo $idx; ?>][url]" placeholder="Link alla pagina social"
                           value="<?php echo esc_attr($url); ?>"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                    <button type="button" class="thub-social-remove" aria-label="Rimuovi riga"
                            style="border:1px solid #ddd;border-radius:.6rem;background:#fff;cursor:pointer;height:38px;">✕</button>
                  </div>
                <?php $idx++; } ?>

                <template id="thub-social-row-tpl">
                  <div class="thub-social-row" style="display:grid; grid-template-columns: 220px 1fr 40px; gap:.5rem; align-items:center;">
                    <select name="__IDX__[platform]" style="padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                      <option value="">Seleziona profilo</option>
                      <?php foreach($platforms as $pf): ?>
                        <option value="<?php echo esc_attr($pf); ?>"><?php echo esc_html($pf); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <input type="url" name="__IDX__[url]" placeholder="Link alla pagina social"
                           style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
                    <button type="button" class="thub-social-remove" aria-label="Rimuovi riga"
                            style="border:1px solid #ddd;border-radius:.6rem;background:#fff;cursor:pointer;height:38px;">✕</button>
                  </div>
                </template>
              </div>

              <div style="margin:.6rem 0 0; display:flex; gap:.5rem; align-items:center;">
                <button type="button" id="thub-social-add" class="button"
                        style="border:1px solid #ddd;border-radius:.6rem;padding:.45rem .8rem;background:#f7f7f9;color:#222;cursor:pointer;">
                  Aggiungi un altro profilo
                </button>
                <button type="submit" class="button"
                        style="border:1px solid #7249a4;border-radius:.6rem;padding:.45rem .8rem;background:#7249a4;color:#fff;cursor:pointer;">
                  Salva profili social
                </button>
              </div>
            </form>
          </div>
        </details>
      </div>
    </div>
  </div>

  <!-- ===========================
     [THUB_PREFS_INTRO_2COL] Altre informazioni — intro a 2 colonne senza box
     - Stessa idea di [THUB_PERSONAL_INTRO] ma "naked": niente .thub-box, niente bordo/sfondo
     - Colonna 1: titolo + testo
     - Colonna 2: illustrazione riempitiva (identità + web settings)
     =========================== -->
  <div style="height:12px;"></div>

  <section class="thub-prefs-intro" aria-labelledby="thub-prefs-title"
          style="display:grid;grid-template-columns:1.2fr 1fr;gap:16px;align-items:center;margin:.4rem 0 1rem;">
    <!-- Colonna 1: testo -->
    <div>
      <h3 id="thub-prefs-title" style="margin:.1rem 0rem .35rem; font-size:1.05rem;">
        Altre informazioni e preferenze per i servizi T‑Hub
      </h3>
      <p style="color:#555; margin:0;">
        Modalità di verifica della tua identità e impostazioni relative al Web
      </p>
    </div>

    <!-- Colonna 2: illustrazione (shield+globe) SEO-friendly -->
    <div aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="Verifica identità">
        <circle cx="32" cy="32" r="30" fill="#f2f2f6" stroke="#ddd" stroke-width="2"/>
        <path d="M32 12L16 20v12c0 11 6.8 21 16 24 9.2-3 16-13 16-24V20L32 12z" 
              fill="#7249a4" stroke="#4b2f7a" stroke-width="2"/>
        <path d="M24 32l6 6 10-10" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
      </svg>
    </div>
  </section>

  <!-- [THUB_PREFS_INTRO_2COL_CSS] Stili locali per il blocco “Altre informazioni” -->
<style>
  /* Colonna 2: usa flexbox per centrare l’illustrazione */
  .thub-prefs-intro > div:last-child {
    display: flex;
    justify-content: center;
    align-items: center;
  }

  /* Illustrazione SVG: dimensioni e proporzioni */
  .thub-prefs-intro svg {
    max-width: 140px;
    height: auto;
  }

  /* Mobile: imposta layout a 1 colonna */
  @media (max-width: 900px){
    .thub-prefs-intro{ 
      grid-template-columns:1fr !important;
    }
  }
</style>

  <!-- ===========================
       [THUB_QUICK_BLOCKS_5_6] Card link a due colonne
       =========================== -->
  <div class="thub-quick-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
    <!-- 5) Password -->
    <a class="thub-box" href="<?php echo esc_url( home_url('/account/sicurezza') ); ?>"
       style="display:block;border-color:#eee;text-decoration:none;color:inherit;">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
        <div>
          <h3 style="margin:.1rem 0 .3rem; font-size:1.05rem;">Password</h3>
          <p style="color:#555; margin:0;">Una password efficace contribuisce a proteggere il tuo account T‑Hub.</p>
        </div>
        <div aria-hidden="true" style="font-size:1.2rem;">›</div>
      </div>
    </a>

    <!-- 6) Preferenze generali per il web -->
    <a class="thub-box" href="<?php echo esc_url( home_url('/lingua-e-regione') ); ?>"
       style="display:block;border-color:#eee;text-decoration:none;color:inherit;">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
        <div>
          <h3 style="margin:.1rem 0 .3rem; font-size:1.05rem;">Preferenze generali per il web</h3>
          <p style="color:#555; margin:0;">Gestisci le impostazioni dei servizi T‑Hub sul Web</p>
          <div style="margin-top:.4rem;">
            <strong>Lingua:</strong> <span><?php echo $user_lang ? esc_html($user_lang) : '—'; ?></span>
          </div>
        </div>
        <div aria-hidden="true" style="font-size:1.2rem;">›</div>
      </div>
    </a>
  </div>
</section>

<!-- ===========================
     [THUB_PERSONAL_JS_AVATAR] Apri dropdown account via evento custom
     =========================== -->
<script>
(function(){
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.thub-profile-avatar-trigger');
    if(!btn) return;

    // Canale ufficiale: evento custom (richiede la patch Step 1)
    window.dispatchEvent(new CustomEvent('thub:openAccountDropdown'));

    // Fallback estremo: se il dropdown non esiste, apri profilo WP
    if (!document.getElementById('thub-dropdown-account')){
      window.location.href = "<?php echo esc_url( admin_url('profile.php') ); ?>";
    }
  }, true);
})();
</script>

<!-- ===========================
     [THUB_PERSONAL_JS_DOB] Monta select data (ordine per Paese)
     =========================== -->
<script>
(function(){
  const wraps = document.getElementById('thub-dob-selects');
  if(!wraps) return;
  const country = (document.getElementById('thub_user_country')?.value || 'IT').toUpperCase();
  const el = (tag, attrs={}, children=[]) => {
    const n = document.createElement(tag);
    Object.entries(attrs).forEach(([k,v]) => { if(k==='class') n.className = v; else n.setAttribute(k, v); });
    children.forEach(c => n.appendChild(typeof c==='string' ? document.createTextNode(c) : c));
    return n;
  };
  const phpDay   = <?php echo $dob_day   ? intval($dob_day)   : 'null'; ?>;
  const phpMonth = <?php echo $dob_month ? intval($dob_month) : 'null'; ?>;
  const phpYear  = <?php echo $dob_year  ? intval($dob_year)  : 'null'; ?>;

  const d = el('select',{name:'dob_day','aria-label':'Giorno', style:'padding:.45rem .6rem;border:1px solid #ddd;border-radius:.6rem;'});
  const m = el('select',{name:'dob_month','aria-label':'Mese', style:'padding:.45rem .6rem;border:1px solid #ddd;border-radius:.6rem;'});
  const y = el('select',{name:'dob_year','aria-label':'Anno', style:'padding:.45rem .6rem;border:1px solid #ddd;border-radius:.6rem;'});
  d.appendChild(el('option',{value:''},['Giorno'])); for(let i=1;i<=31;i++) d.appendChild(el('option',{value:String(i)},[String(i).padStart(2,'0')]));
  m.appendChild(el('option',{value:''},['Mese']));   for(let i=1;i<=12;i++) m.appendChild(el('option',{value:String(i)},[String(i).padStart(2,'0')]));
  const now = new Date().getFullYear(); y.appendChild(el('option',{value:''},['Anno'])); for(let i=now;i>=1900;i--) y.appendChild(el('option',{value:String(i)},[String(i)]));

  if(phpDay) d.value   = String(phpDay); if(phpMonth) m.value = String(phpMonth); if(phpYear) y.value = String(phpYear);

  const order = (country==='US') ? ['M','D','Y'] : (country==='JP' ? ['Y','M','D'] : ['D','M','Y']);
  const labelled = (lab, node) => el('label',{style:'display:flex;align-items:center;gap:.35rem;'},[el('span',{style:'min-width:2.2rem;color:#666;'},[lab]), node]);
  order.forEach(k=>{ if(k==='D') wraps.appendChild(labelled('GG',d)); if(k==='M') wraps.appendChild(labelled('MM',m)); if(k==='Y') wraps.appendChild(labelled('AAAA',y)); });
})();
</script>

<!-- ===========================
     [THUB_ADDRESSES_SOCIALS_JS] Toggle IT/SM vs OTHER + repeater social
     =========================== -->
<script>
(function(){
  // Toggle CASA
  const homeSel = document.getElementById('thub_home_country');
  const homeStruct = document.getElementById('thub_home_struct');
  const homeFree = document.getElementById('thub_home_free');
  homeSel && homeSel.addEventListener('change', function(){
    const v = this.value, ok = (v==='IT'||v==='SM');
    if(homeStruct) homeStruct.style.display = ok ? 'block':'none';
    if(homeFree)   homeFree.style.display   = ok ? 'none':'block';
  });

  // Toggle LAVORO
  const workSel = document.getElementById('thub_work_country');
  const workStruct = document.getElementById('thub_work_struct');
  const workFree = document.getElementById('thub_work_free');
  workSel && workSel.addEventListener('change', function(){
    const v = this.value, ok = (v==='IT'||v==='SM');
    if(workStruct) workStruct.style.display = ok ? 'block':'none';
    if(workFree)   workFree.style.display   = ok ? 'none':'block';
  });

  // Repeater Social
  const rep  = document.getElementById('thub-socials-repeater');
  const add  = document.getElementById('thub-social-add');
  const tpl  = document.getElementById('thub-social-row-tpl');
  if(add && rep && tpl){
    let idx = <?php echo isset($idx) ? intval($idx) : 0; ?>;
    add.addEventListener('click', function(){
      const clone = document.importNode(tpl.content, true);
      clone.querySelectorAll('[name]').forEach(function(input){
        const name = input.getAttribute('name');
        input.setAttribute('name', name.replace('__IDX__', 'thub_socials['+idx+']'));
      });
      rep.appendChild(clone);
      idx++;
    });
    rep.addEventListener('click', function(e){
      const btn = e.target.closest('.thub-social-remove');
      if(!btn) return;
      const row = btn.closest('.thub-social-row');
      if(row) row.remove();
    });
  }
})();
</script>