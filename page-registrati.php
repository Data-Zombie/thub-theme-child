<?php
/**
 * Template Name: T-Hub • Registrazione (4 step, desktop orizzontale, mobile/tablet verticale)
 * Description: Registrazione guidata: (1) Nome/Cognome → (2) Data di nascita & Genere (+ modal) → (3) Email & Telefono (prefisso UE) → (4) Password.
 *
 * ISTRUZIONI (Editor classico):
 * 1) Salva come `page-registrati.php` in /wp-content/themes/hello-elementor-child/
 * 2) Pagina "Registrati" (slug: /registrati) → Attributi pagina → Template: questo → Pubblica.
 *
 * NOTE SEO: pagina utility → noindex gestito via functions.php.
 */

if ( ! defined('ABSPATH') ) { exit; } // [THUB_TEMPLATE_GUARD]

/* ==========================================================================================
   [THUB_REG_SLUGS]
   ========================================================================================== */
$PAGE_PRIVACY = '/privacy';    // Modifica se hai slug diversi
$PAGE_TERMINI = '/termini';
$REDIRECT_OK  = home_url('/'); // Destinazione dopo registrazione

/* ==========================================================================================
   [THUB_REG_HELPERS] — Funzioni di supporto (wrappate per evitare redeclare)
   ========================================================================================== */
/** Normalizza telefono: tiene solo cifre e converte 0039xxxx → 39xxxx (fallback storico) */
if ( ! function_exists('thub_reg_norm_phone') ) {
  function thub_reg_norm_phone( $raw ) {
    $digits = preg_replace( '/\D+/', '', $raw ?? '' );
    if ( ! $digits ) return '';
    if ( strpos( $digits, '0039' ) === 0 ) { $digits = substr( $digits, 2 ); } // 0039 → 39
    return $digits;
  }
}

/** Verifica esistenza telefono (meta_key: phone_number), prova variante senza 39 per vecchi salvataggi */
if ( ! function_exists('thub_reg_phone_exists') ) {
  function thub_reg_phone_exists( $norm ) {
    if ( ! $norm ) return false;

    // a) match esatto
    $u = get_users( [ 'meta_key'=>'phone_number', 'meta_value'=>$norm, 'number'=>1, 'fields'=>'ids' ] );
    if ( ! empty( $u ) ) return true;

    // b) se inizia con 39, prova senza 39 (vecchi salvataggi nazionali)
    if ( strpos( $norm, '39' ) === 0 ) {
      $alt = substr( $norm, 2 );
      if ( $alt ) {
        $u2 = get_users( [ 'meta_key'=>'phone_number', 'meta_value'=>$alt, 'number'=>1, 'fields'=>'ids' ] );
        if ( ! empty( $u2 ) ) return true;
      }
    }
    return false;
  }
}

/** Genera uno username univoco a partire dal prefisso dell'email */
if ( ! function_exists('thub_reg_make_username') ) {
  function thub_reg_make_username( $email ) {
    $base = sanitize_user( current( explode( '@', (string) $email ) ), true );
    if ( ! $base ) $base = 'user';
    $candidate = $base; $i = 1;
    while ( username_exists( $candidate ) ) {
      $candidate = $base . $i;
      $i++;
      if ( $i > 9999 ) break;
    }
    return $candidate;
  }
}

/** Login immediato dopo creazione utente */
if ( ! function_exists('thub_reg_login_user') ) {
  function thub_reg_login_user( $user_id ) {
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );
  }
}

/* ==========================================================================================
   [THUB_REG_SUBMIT] — Validazione e creazione utente
   ========================================================================================== */
$err = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['thub_reg_action'] ) && $_POST['thub_reg_action'] === 'create' ) {

  // [THUB_HP_CHECK] — blocco bot: se il campo nascosto è valorizzato, fermo tutto
  if ( ! empty( $_POST['thub_hp'] ?? '' ) ) {
    $err = 'Si è verificato un problema. Riprova.';
  } else {

    // 1) Nonce
    if ( ! isset( $_POST['_thub_reg_nonce'] ) || ! wp_verify_nonce( $_POST['_thub_reg_nonce'], 'thub_reg' ) ) {
      $err = 'Sessione non valida. Ricarica la pagina e riprova.';
    } else {

      // 2) Input (step 1-4)
      $first_name   = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
      $last_name    = sanitize_text_field( wp_unslash( $_POST['last_name']  ?? '' ) );

      $birth_day    = intval( $_POST['birth_day']   ?? 0 );
      $birth_month  = intval( $_POST['birth_month'] ?? 0 );
      $birth_year   = intval( $_POST['birth_year']  ?? 0 );
      $gender       = sanitize_text_field( wp_unslash( $_POST['gender'] ?? '' ) );

      $email        = sanitize_email( $_POST['email'] ?? '' );

      // Telefono (step 3): prefisso paese + numero locale
      $phone_cc_raw    = sanitize_text_field( wp_unslash( $_POST['phone_cc'] ?? '39' ) );
      $phone_local_raw = sanitize_text_field( wp_unslash( $_POST['phone_local'] ?? '' ) );
      $phone_cc        = preg_replace('/\D+/', '', $phone_cc_raw );
      $phone_local     = preg_replace('/\D+/', '', $phone_local_raw );
      $phone_raw       = $phone_cc . $phone_local; // es. 39 + 3291234567

      // Password (step 4)
      $password    = $_POST['password']  ?? '';
      $password2   = $_POST['password2'] ?? '';

      // 3) Requisiti
      if ( ! $first_name || ! $last_name ) {
        $err = 'Inserisci nome e cognome (Step 1).';
      } elseif ( ! $birth_day || ! $birth_month || ! $birth_year ) {
        $err = 'Inserisci la data di nascita (Step 2).';
      } elseif ( ! is_email( $email ) ) {
        $err = 'Inserisci un indirizzo email valido (Step 3).';
      } elseif ( ! $phone_cc || ! $phone_local ) {
        $err = 'Inserisci il numero di telefono con prefisso (Step 3).';
      } elseif ( ! $password || ( $password !== $password2 ) ) {
        $err = 'Le password non coincidono (Step 4).';
      }

      // 4) Data valida
      if ( ! $err && ! checkdate( $birth_month, $birth_day, $birth_year ) ) {
        $err = 'La data di nascita non è valida.';
      }

      // 5) Unicità email/telefono
      $phone_norm = thub_reg_norm_phone( $phone_raw );
      if ( ! $err && email_exists( $email ) ) {
        $err = 'Esiste già un account con questa email.';
      }
      if ( ! $err && thub_reg_phone_exists( $phone_norm ) ) {
        $err = 'Esiste già un account con questo numero di telefono.';
      }

      // 6) Creazione utente
      if ( ! $err ) {
        $username = thub_reg_make_username( $email );
        $user_id  = wp_insert_user( [
          'user_login'   => $username,
          'user_email'   => $email,
          'user_pass'    => $password,
          'first_name'   => $first_name,
          'last_name'    => $last_name,
          'display_name' => trim( $first_name . ' ' . $last_name ),
          'role'         => get_option( 'default_role', 'subscriber' ),
        ] );

        if ( is_wp_error( $user_id ) ) {
          $err = 'Errore durante la creazione dell’account: ' . $user_id->get_error_message();
        } else {
          // Metadati profilo (legacy base, come nel tuo file)
          update_user_meta( $user_id, 'phone_number',       $phone_norm );
          update_user_meta( $user_id, 'phone_country_code', $phone_cc ); // opzionale, utile per analisi
          $birthdate = sprintf( '%04d-%02d-%02d', $birth_year, $birth_month, $birth_day );
          update_user_meta( $user_id, 'birthdate', $birthdate );
          if ( $gender ) update_user_meta( $user_id, 'gender', $gender );

          /* ===========================
             [THUB_REG_MIRROR] Mirror meta per compatibilità Canvas
             - Telefoni → thub_phone_cc / thub_phone_number
             - Nascita  → thub_dob_day|month|year
             - Ultima modifica password → inizializzata all’atto della registrazione
             - (OPZ. C): NON copiare più email di contatto da user_email
             =========================== */
          if ( ! empty($phone_cc) )   update_user_meta( $user_id, 'thub_phone_cc', $phone_cc );         // CC
          if ( ! empty($phone_norm) ) update_user_meta( $user_id, 'thub_phone_number', $phone_norm );   // NUM
          if ( $birth_year )  update_user_meta( $user_id, 'thub_dob_year',  (int)$birth_year );
          if ( $birth_month ) update_user_meta( $user_id, 'thub_dob_month', (int)$birth_month );
          if ( $birth_day )   update_user_meta( $user_id, 'thub_dob_day',   (int)$birth_day );
          if ( ! get_user_meta( $user_id, 'thub_last_password_change', true ) ) {
            update_user_meta( $user_id, 'thub_last_password_change', time() );
          }

          /* ===========================
            [THUB_RESULTS_REGION_ON_REGISTER]
            Inizializza "Regione dei risultati" (cucina) una sola volta
            Sorgenti: Accept-Language → fallback prefisso telefonico
            =========================== */
          {
            // 0) Meta-key sicura (fallback se la costante non esiste)
            $meta_key = defined('THUB_RESULTS_REGION_META') ? THUB_RESULTS_REGION_META : 'thub_results_region';

            // 1) Non sovrascrivere se già presente (idempotenza)
            $already = get_user_meta( $user_id, $meta_key, true );
            if ( ! $already ) {

              $initial_region = '';

              // 2) Hint dalla lingua del browser (riusa i tuoi helper già presenti)
              $lang_hint = '';
              if ( function_exists('thub_results_detect_browser_lang') ) {
                $lang_hint = thub_results_detect_browser_lang(); // es. "it-IT", "fr-FR"
              } elseif ( function_exists('thub_detect_browser_lang') ) {
                $lang_hint = thub_detect_browser_lang();
              }
              if ( $lang_hint && function_exists('thub_results_region_from_lang') ) {
                $initial_region = thub_results_region_from_lang( $lang_hint );
              }

              // 3) Fallback dal prefisso telefonico
              if ( ! $initial_region && ! empty($phone_cc) && function_exists('thub_results_region_from_phone') ) {
                $initial_region = thub_results_region_from_phone( $phone_cc );
              }

              // 4) Salva meta (+ cookie facoltativo per coerenza client)
              if ( $initial_region ) {
                update_user_meta( $user_id, $meta_key, $initial_region );

                // (Opzionale) Cookie lato client, utile subito dopo il redirect
                setcookie('thub_results_region', $initial_region, time()+YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
                $_COOKIE['thub_results_region'] = $initial_region;
              }
            }
          }

          /* ===========================
            [THUB_RESULTS_LANG_ON_REGISTER]
            Browser (80–90%) → hint da prefisso tel. → fallback 'it'
            =========================== */
          $lang_from_browser = function_exists('thub_results_detect_browser_lang')
            ? thub_results_detect_browser_lang()
            : ( function_exists('thub_detect_browser_lang') ? thub_detect_browser_lang() : 'it' );

          $phone_cc_for_hint = isset($phone_cc) ? $phone_cc : ''; // già popolato nelle tue variabili sopra
          $hint_lang = function_exists('thub_results_lang_from_phone')
            ? thub_results_lang_from_phone($phone_cc_for_hint)
            : ( function_exists('thub_guess_lang_from_phone') ? thub_guess_lang_from_phone($phone_cc_for_hint) : '' );

          $initial_results_lang = $lang_from_browser ?: ( $hint_lang ?: 'it' );

          // Chiave dedicata per "Lingua dei risultati" (separata dalla lingua UI)
          if ( ! defined('THUB_RESULTS_LANG_META') ) {
            define('THUB_RESULTS_LANG_META', 'thub_results_lang');
          }
          update_user_meta( $user_id, THUB_RESULTS_LANG_META, $initial_results_lang );

          /* ===========================
             [THUB_REG_LOGIN] Login immediato
             =========================== */
          if ( function_exists('thub_reg_login_user') ) {
            thub_reg_login_user( $user_id ); // helper del tema, se presente
          } else {
            // Fallback: wp_signon
            wp_logout(); // sicurezza
            $creds = [
              'user_login'    => $email,
              'user_password' => $password,
              'remember'      => true,
            ];
            $signed = wp_signon( $creds, is_ssl() );
            if ( is_wp_error($signed) ) {
              error_log('[THUB_REG] Login immediato fallito: '.$signed->get_error_message());
            }
          }

          /* [THUB_FORCE_WP_LOGIN_ON_REGISTER] forza wp_login se non è già scattato
             - Garantisce il popolamento del box "Dispositivi" anche se l'auto-login non ha chiamato wp_login */
          if ( did_action('wp_login') === 0 ) {
            $uobj = get_userdata( $user_id );
            if ( $uobj && $uobj->user_login ) {
              do_action( 'wp_login', $uobj->user_login, $uobj );
            }
          }

          // (Opzionale) Se usi un "touch" custom per dispositivi, puoi aggiungerlo qui:
          // if ( function_exists('thub_login_devices_touch') ) thub_login_devices_touch( $user_id );

          // Redirect finale
          wp_safe_redirect( $REDIRECT_OK );
          exit;
        }
      }

      // ⛔️ NOTA: nel file originale c'era una duplicazione dei "Metadati profilo" + [THUB_REG_MIRROR] DOPO il redirect.
      // Va rimossa: dopo il redirect non verrebbe mai eseguita e può usare $user_id non definito.
    } // nonce OK
  }   // honeypot ok
}     // submit

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <!-- [THUB_HTML_HEAD] -->
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>

  <!--
    [THUB_AUTH_LAYOUT_CSS]
    - 100vh, card mobile-first; desktop 2 colonne con sinistra in alto
    - Input e SELECT con stile uniforme (Safari/iOS fix)
    - Link Privacy/Termini sotto la card (desktop a destra)
    - Pulsanti: mobile full width, desktop compatti
    - Modal informativo (step 2) per "Perché ti chiediamo..."
  -->
  <style>
    :root{ --violet:#7249a4; --ink:#111; --muted:#555; --border:#eee; --bg:#fff; }

    html, body { height:100%; }
    body{
      margin:0; background:#fafafa; color:var(--ink);
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    }
    .hide{ display:none; } /* [THUB_HELPER_CLASS] */

    .auth-hero{
      height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center;
      padding:2rem 1rem; gap:.6rem;
    }

    .auth-card{
      width:min(960px,96vw);
      background:#fff; border:1px solid var(--border); border-radius:16px;
      box-shadow:0 10px 30px rgba(0,0,0,.04);
      display:grid; grid-template-columns: 1fr; overflow:hidden;
    }

    .auth-left{
      padding:2rem 1.4rem;
      display:flex; flex-direction:column; justify-content:center; /* mobile: centrata */
      background:linear-gradient(180deg,#f8f7fb 0%,#ffffff 100%);
      border-bottom:1px solid var(--border);
    }
    .auth-left .auth-logo{ margin-bottom:1rem; }
    .auth-left .auth-logo img{ display:block; height:36px; width:auto; }
    .auth-left h1{ margin:.2rem 0 .35rem; font-size:1.6rem; font-weight:800; }
    .auth-left p{ margin:0; color:#555; line-height:1.45; }

    .auth-right{ padding:2rem 1.4rem; display:flex; flex-direction:column; }

    /* Input & Select uniformi */
    .auth-field{ margin:.6rem 0; }
    .auth-label{ display:block; font-weight:600; margin:0 0 .25rem; }
    .auth-input{
      width:100%; border:1px solid #efeff3; border-radius:12px;
      padding:.65rem .9rem; font-size:1rem; background:#fff; outline:none;
    }
    .auth-input:focus{ border-color:#dcd7ee; box-shadow:0 0 0 2px rgba(114,73,164,.08); }

    /* [THUB_SELECT_FIX] — Rendi la select identica agli input, anche su Safari/iOS */
    select.auth-input{
      -webkit-appearance: none !important;
      -moz-appearance: none !important;
      appearance: none !important;
      background-color: #fff !important;
      border: 1px solid #efeff3 !important;
      border-radius: 12px !important;
      padding: .65rem 2.2rem .65rem .9rem !important; /* spazio per la freccia */
      font-size: 1rem !important;
      line-height: 1.25 !important;
      outline: none !important;
      background-image:
        linear-gradient(45deg, transparent 50%, #777 50%),
        linear-gradient(135deg, #777 50%, transparent 50%);
      background-position:
        calc(100% - 18px) calc(50% - 3px),
        calc(100% - 12px) calc(50% - 3px);
      background-size: 6px 6px, 6px 6px;
      background-repeat: no-repeat;
    }
    select.auth-input:focus{
      border-color:#dcd7ee !important;
      box-shadow:0 0 0 2px rgba(114,73,164,.08) !important;
    }
    select.auth-input::-ms-expand{ display:none; } /* IE/Edge legacy */

    .auth-link{ text-decoration:none; color:#444; background:none; border:0; padding:0; cursor:pointer; }
    .auth-link:hover{ color:var(--violet); text-decoration:underline; }

    .auth-error{
      background:#fff2f2; color:#9a1c1c; border:1px solid #f1c9c9;
      padding:.6rem .7rem; border-radius:.6rem; margin:.4rem 0 .6rem;
    }

    /* Pulsanti */
    .actions-right{ margin-top:.8rem; display:flex; justify-content:flex-end; }
    .nav-row{
      margin-top:.8rem; display:flex; align-items:center; justify-content:space-between; gap:.8rem;
      flex-wrap:wrap;
    }
    .btn{
      border:0; border-radius:9999px; padding:.7rem 1.2rem; font-size:1rem; cursor:pointer; font-weight:700;
    }
    .btn-primary{ background:#7249a4; color:#fff; }
    .btn-primary:hover{ opacity:.95; }
    .btn-secondary{ background:#999; color:#fff; }
    .btn-secondary:hover{ opacity:.92; }
    .btn-primary{ width:100%; } /* mobile full */
    .btn-secondary{ width:auto; }

    /* Riga telefono (prefisso + numero) */
    .phone-row{ display:flex; gap:.6rem; flex-wrap:wrap; }
    .phone-row .cc{ flex:0 0 180px; min-width:180px; } /* select prefissi */
    .phone-row .local{ flex:1 1 220px; min-width:220px; }

    /* Legali sotto la card */
    .auth-legal-out{
      width:min(960px,96vw); font-size:.9rem; display:flex; justify-content:center; gap:1rem;
    }

    /* Modal generico */
    .modal-backdrop{
      position:fixed; inset:0; background:rgba(0,0,0,.45);
      display:none; align-items:center; justify-content:center; padding:1rem; z-index:1000;
    }
    .modal-backdrop.is-open{ display:flex; }
    .modal{
      position:relative;
      width:min(640px,96vw); background:#fff; border-radius:14px; border:1px solid #eee;
      box-shadow:0 20px 60px rgba(0,0,0,.25); padding:1.2rem 1.2rem 1rem;
    }
    .modal h2{ margin:.2rem 0 .6rem; font-size:1.25rem; }
    .modal p{ margin:0; color:#444; line-height:1.55; }
    .modal-close{
      background:transparent; border:0; font-size:1.4rem; line-height:1; cursor:pointer; color:#666;
      position:absolute; right:1rem; top:.8rem;
    }

    /* Desktop */
    @media (min-width: 1025px){
      .auth-card{ grid-template-columns: 1fr 1fr; }
      .auth-left{
        border-bottom:0; border-right:1px solid var(--border);
        justify-content:flex-start; align-items:flex-start; /* sinistra in alto */
      }
      .btn-primary{ width:auto; } /* desktop compatto */
      .auth-legal-out{ justify-content:flex-end; }
    }
  </style>
</head>
<body class="thub-auth thub-no-header">
  <main class="auth-hero">
    <section class="auth-card" aria-label="Registrazione T-Hub">

      <!-- Colonna sinistra: logo + copy (in alto su desktop) -->
      <div class="auth-left">
        <div class="auth-logo">
          <?php if ( function_exists('the_custom_logo') && has_custom_logo() ) : the_custom_logo(); endif; ?>
        </div>
        <h1>Crea un account T-Hub</h1>
        <p>Completa i 4 step per iniziare</p>
      </div>

      <!-- Colonna destra: wizard 4 step -->
      <div class="auth-right">
        <?php if ( ! empty( $err ) ) : ?>
          <div class="auth-error" role="alert" aria-live="assertive"><?php echo esc_html( $err ); ?></div>
        <?php endif; ?>

        <form id="thubRegForm" method="post" action="<?php echo esc_url( get_permalink() ); ?>">
          <?php /* Nonce di sicurezza */ wp_nonce_field( 'thub_reg', '_thub_reg_nonce' ); ?>
          <input type="hidden" name="thub_reg_action" value="create">
          <?php /* [THUB_HP] campo honeypot anti-bot */ ?>
          <input type="text" name="thub_hp" value="" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">

          <!-- ================================
               [THUB_FORM_STEPS] — STEP 1/4
               ================================= -->
          <div id="step1">
            <div class="auth-field">
              <label class="auth-label" for="first_name">Nome</label>
              <input class="auth-input" type="text" id="first_name" name="first_name" required autocomplete="given-name">
            </div>
            <div class="auth-field">
              <label class="auth-label" for="last_name">Cognome</label>
              <input class="auth-input" type="text" id="last_name" name="last_name" required autocomplete="family-name">
            </div>

            <div class="actions-right">
              <button type="button" class="btn btn-primary" id="toStep2">Avanti</button>
            </div>
          </div>

          <!-- ================================
               [THUB_FORM_STEPS] — STEP 2/4
               ================================= -->
          <div id="step2" class="hide">
            <div class="auth-row" style="display:flex; gap:.6rem; flex-wrap:wrap;">
              <div class="auth-field" style="flex:1; min-width:120px;">
                <label class="auth-label" for="birth_day">Giorno</label>
                <input class="auth-input" type="number" id="birth_day" name="birth_day" min="1" max="31" required inputmode="numeric">
              </div>
              <div class="auth-field" style="flex:1; min-width:120px;">
                <label class="auth-label" for="birth_month">Mese</label>
                <input class="auth-input" type="number" id="birth_month" name="birth_month" min="1" max="12" required inputmode="numeric">
              </div>
              <div class="auth-field" style="flex:1; min-width:140px;">
                <label class="auth-label" for="birth_year">Anno</label>
                <input class="auth-input" type="number" id="birth_year" name="birth_year" min="1900" max="<?php echo esc_attr( date('Y') ); ?>" required inputmode="numeric">
              </div>
            </div>

            <div class="auth-field">
              <label class="auth-label" for="gender">Genere (opzionale)</label>
              <select class="auth-input" id="gender" name="gender">
                <option value="">Preferisco non dirlo</option>
                <option value="Donna">Donna</option>
                <option value="Uomo">Uomo</option>
              </select>
            </div>

            <!-- Link info con modal -->
            <p class="hint-row" style="margin:.35rem 0 0;">
              <button type="button" class="auth-link" id="openWhy" aria-haspopup="dialog" aria-controls="whyModal" aria-expanded="false">
                Perché ti chiediamo la data di nascita e il genere
              </button>
            </p>

            <div class="nav-row">
              <button type="button" class="btn btn-secondary" id="back1">Indietro</button>
              <button type="button" class="btn btn-primary" id="toStep3">Avanti</button>
            </div>
          </div>

          <!-- ================================
               [THUB_FORM_STEPS] — STEP 3/4 (Email + Telefono con prefisso UE)
               ================================= -->
          <div id="step3" class="hide">
            <div class="auth-field">
              <label class="auth-label" for="email">Indirizzo email</label>
              <input class="auth-input" type="email" id="email" name="email" required autocomplete="email">
            </div>

            <div class="auth-field">
              <label class="auth-label" for="phone_local">Numero di telefono</label>
              <div class="phone-row">
                <div class="cc">
                  <select class="auth-input" id="phone_cc" name="phone_cc" aria-label="Prefisso internazionale">
                    <!-- Opzioni inserite via JS da THUB_CALLING_CODES -->
                  </select>
                </div>
                <div class="local">
                  <input class="auth-input" type="tel" id="phone_local" name="phone_local" required autocomplete="tel" inputmode="tel" placeholder="es. 3291234567">
                </div>
              </div>
            </div>

            <div class="nav-row">
              <button type="button" class="btn btn-secondary" id="back2">Indietro</button>
              <button type="button" class="btn btn-primary" id="toStep4">Avanti</button>
            </div>
          </div>

          <!-- ================================
               [THUB_FORM_STEPS] — STEP 4/4 (Password)
               ================================= -->
          <div id="step4" class="hide">
            <div class="auth-field">
              <label class="auth-label" for="password">Password</label>
              <input class="auth-input" type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            <div class="auth-field">
              <label class="auth-label" for="password2">Conferma password</label>
              <input class="auth-input" type="password" id="password2" name="password2" required autocomplete="new-password">
            </div>

            <div class="nav-row">
              <button type="button" class="btn btn-secondary" id="back3">Indietro</button>
              <button type="submit" class="btn btn-primary">Crea account</button>
            </div>
          </div>
        </form>
      </div>
    </section>

    <!-- [THUB_LEGAL_OUT] Link Privacy/Termini sotto la card, a destra (su desktop) -->
    <div class="auth-legal-out">
      <a class="auth-link" href="<?php echo esc_url( home_url( $PAGE_PRIVACY ) ); ?>">Privacy</a>
      <a class="auth-link" href="<?php echo esc_url( home_url( $PAGE_TERMINI ) ); ?>">Termini</a>
    </div>
  </main>

  <!-- [THUB_MODAL] — Dialog "Perché chiediamo data di nascita e genere" -->
  <div class="modal-backdrop" id="whyBackdrop" hidden>
    <div class="modal" id="whyModal" role="dialog" aria-modal="true" aria-labelledby="whyTitle" tabindex="-1">
      <button class="modal-close" type="button" id="closeWhy" aria-label="Chiudi">×</button>
      <h2 id="whyTitle">Perché chiediamo data di nascita e genere?</h2>
      <p>
        La <strong>data di nascita</strong> è obbligatoria: T-Hub la usa per verificare l’idoneità a suggerire ricette alcoliche
        e per offrirti un’esperienza più personalizzata. Il <strong>genere</strong> è facoltativo e viene usato solo per
        personalizzazione. <strong>Nota privacy</strong>: per impostazione predefinita, queste informazioni non vengono
        condivise con altri utenti T-Hub.
      </p>
    </div>
  </div>

  <!-- [THUB_WIZARD_JS] — Navigazione step + prefissi UE + modal -->
  <script>
    (function(){
      /* ------------------------------
         Wizard (4 step)
      ------------------------------ */
      const step1 = document.getElementById('step1');
      const step2 = document.getElementById('step2');
      const step3 = document.getElementById('step3');
      const step4 = document.getElementById('step4');

      function go(step){
        [step1, step2, step3, step4].forEach(el => el.classList.add('hide'));
        if (step === 1) step1.classList.remove('hide');
        if (step === 2) step2.classList.remove('hide');
        if (step === 3) step3.classList.remove('hide');
        if (step === 4) step4.classList.remove('hide');
      }

      const toStep2 = document.getElementById('toStep2');
      const toStep3 = document.getElementById('toStep3');
      const toStep4 = document.getElementById('toStep4');
      const back1   = document.getElementById('back1');
      const back2   = document.getElementById('back2');
      const back3   = document.getElementById('back3');

      // Validazioni minime client-side (server-side già complete)
      toStep2.addEventListener('click', function(){
        const fn = (document.getElementById('first_name').value || '').trim();
        const ln = (document.getElementById('last_name').value  || '').trim();
        if (!fn || !ln) { alert('Inserisci nome e cognome.'); return; }
        go(2);
      });

      toStep3.addEventListener('click', function(){
        const d = +document.getElementById('birth_day').value;
        const m = +document.getElementById('birth_month').value;
        const y = +document.getElementById('birth_year').value;
        if (!d || !m || !y) { alert('Inserisci la data di nascita.'); return; }
        go(3);
      });

      toStep4.addEventListener('click', function(){
        const email = (document.getElementById('email').value || '').trim();
        const cc    = (document.getElementById('phone_cc').value || '').trim();
        const loc   = (document.getElementById('phone_local').value || '').trim();
        if (!email) { alert('Inserisci l’email.'); return; }
        if (!cc || !loc) { alert('Inserisci il telefono con prefisso.'); return; }
        go(4);
      });

      back1.addEventListener('click', function(){ go(1); });
      back2.addEventListener('click', function(){ go(2); });
      back3.addEventListener('click', function(){ go(3); });

      // Avvio sul primo step
      go(1);

      /* ------------------------------
         Prefissi internazionali (EU only) → select #phone_cc
      ------------------------------ */
      const THUB_CALLING_CODES = [
        {cc:'39',  label:'Italia (+39)'},
        {cc:'44',  label:'Regno Unito (+44)'},
        {cc:'33',  label:'Francia (+33)'},
        {cc:'49',  label:'Germania (+49)'},
        {cc:'34',  label:'Spagna (+34)'},
        {cc:'43',  label:'Austria (+43)'},
        {cc:'32',  label:'Belgio (+32)'},
        {cc:'31',  label:'Paesi Bassi (+31)'},
        {cc:'351', label:'Portogallo (+351)'},
        {cc:'41',  label:'Svizzera (+41)'},
        {cc:'46',  label:'Svezia (+46)'},
        {cc:'45',  label:'Danimarca (+45)'},
        {cc:'47',  label:'Norvegia (+47)'},
        {cc:'358', label:'Finlandia (+358)'},
        {cc:'30',  label:'Grecia (+30)'},
        {cc:'353', label:'Irlanda (+353)'},
        {cc:'48',  label:'Polonia (+48)'},
        {cc:'420', label:'Cechia (+420)'},
        {cc:'421', label:'Slovacchia (+421)'},
        {cc:'36',  label:'Ungheria (+36)'},
        {cc:'386', label:'Slovenia (+386)'},
        {cc:'385', label:'Croazia (+385)'},
        {cc:'387', label:'Bosnia ed Erzegovina (+387)'},
        {cc:'381', label:'Serbia (+381)'},
        {cc:'382', label:'Montenegro (+382)'},
        {cc:'355', label:'Albania (+355)'},
        {cc:'389', label:'Macedonia del Nord (+389)'},
        {cc:'40',  label:'Romania (+40)'},
        {cc:'359', label:'Bulgaria (+359)'},
        {cc:'380', label:'Ucraina (+380)'},
        {cc:'375', label:'Bielorussia (+375)'},
        {cc:'7',   label:'Russia (+7)'},
        {cc:'90',  label:'Turchia (+90)'},
        {cc:'357', label:'Cipro (+357)'},
        {cc:'356', label:'Malta (+356)'},
        {cc:'354', label:'Islanda (+354)'},
        {cc:'372', label:'Estonia (+372)'},
        {cc:'371', label:'Lettonia (+371)'},
        {cc:'370', label:'Lituania (+370)'},
        {cc:'352', label:'Lussemburgo (+352)'},
        {cc:'377', label:'Monaco (+377)'},
        {cc:'376', label:'Andorra (+376)'},
        {cc:'423', label:'Liechtenstein (+423)'},
        {cc:'383', label:'Kosovo (+383)'},
        {cc:'373', label:'Moldavia (+373)'},
        {cc:'378', label:'San Marino (+378)'}
      ];

      const ccSelect = document.getElementById('phone_cc');
      THUB_CALLING_CODES.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.cc.replace(/\D+/g,'');
        opt.textContent = item.label;
        ccSelect.appendChild(opt);
      });
      // Default Italia se possibile
      try {
        const lang = (navigator.language || '').toLowerCase();
        if (lang.startsWith('it')) ccSelect.value = '39';
      } catch(e){ ccSelect.value = '39'; }
      if (!ccSelect.value) ccSelect.value = '39';

      /* ------------------------------
         Modal info (Step 2)
      ------------------------------ */
      const openWhy  = document.getElementById('openWhy');
      const backdrop = document.getElementById('whyBackdrop');
      const modal    = document.getElementById('whyModal');
      const closeWhy = document.getElementById('closeWhy');

      function openModal(){
        backdrop.hidden = false;
        backdrop.classList.add('is-open');
        modal.focus();
        openWhy.setAttribute('aria-expanded','true');
        document.addEventListener('keydown', onEsc);
        backdrop.addEventListener('click', onBackdrop);
      }
      function closeModal(){
        backdrop.classList.remove('is-open');
        backdrop.hidden = true;
        openWhy.setAttribute('aria-expanded','false');
        document.removeEventListener('keydown', onEsc);
        backdrop.removeEventListener('click', onBackdrop);
        openWhy.focus();
      }
      function onEsc(e){ if (e.key === 'Escape') closeModal(); }
      function onBackdrop(e){ if (e.target === backdrop) closeModal(); }

      openWhy.addEventListener('click', openModal);
      closeWhy.addEventListener('click', closeModal);
    })();
  </script>

  <?php wp_footer(); ?>
</body>
</html>