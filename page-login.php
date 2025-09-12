<?php
/**
 * Template Name: T-Hub • Login (desktop orizzontale, mobile/tablet verticale)
 * Description: Pagina login custom. Desktop con 2 colonne, mobile-first verticale.
 *
 * ISTRUZIONI (Editor classico):
 * 1) Salva come `page-login.php` in /wp-content/themes/hello-elementor-child/
 * 2) Crea/Modifica pagina "Login" (slug /login) → Attributi → Template: questo.
 * 3) Pubblica.
 */

if ( ! defined('ABSPATH') ) { exit; }

/* ==========================================================================================
   [THUB_LOGIN_SLUGS]
   ========================================================================================== */
$PAGE_TROVA_EMAIL     = '/trova-email';
$PAGE_PASSWORD_RESET  = '/password-dimenticata';
$PAGE_REGISTRAZIONE   = '/registrati';
$PAGE_PRIVACY         = '/privacy';
$PAGE_TERMINI         = '/termini';
$REDIRECT_AFTER_LOGIN = home_url('/');

/* ==========================================================================================
   [THUB_LOGIN_HELPERS]
   ========================================================================================== */
if ( ! function_exists('thub_normalize_phone_for_match') ) {
  function thub_normalize_phone_for_match( $phone_raw ) {
    $digits = preg_replace('/\D+/', '', $phone_raw ?? '' );
    if ( ! $digits ) return '';
    if ( strpos($digits, '0039') === 0 ) { $digits = substr($digits, 2); } // 0039xxx → 39xxx
    return $digits;
  }
}

if ( ! function_exists('thub_find_user_by_email_or_phone') ) {
  function thub_find_user_by_email_or_phone( $login_field ) {
    $login_field = trim( (string) $login_field );

    // 1) Email
    if ( is_email( $login_field ) ) {
      $u = get_user_by( 'email', $login_field );
      if ( $u instanceof WP_User ) return $u;
    }

    // 2) Telefono (39 / senza 39 / 0039)
    $norm = thub_normalize_phone_for_match( $login_field );
    if ( ! $norm ) return null;

    // a) così com'è
    $users = get_users([ 'meta_key'=>'phone_number','meta_value'=>$norm,'number'=>1,'fields'=>'all' ]);
    if ( ! empty($users) && $users[0] instanceof WP_User ) return $users[0];

    // b) senza 39
    if ( strpos($norm, '39') === 0 ) {
      $alt = substr($norm, 2);
      if ( $alt ) {
        $users = get_users([ 'meta_key'=>'phone_number','meta_value'=>$alt,'number'=>1,'fields'=>'all' ]);
        if ( ! empty($users) && $users[0] instanceof WP_User ) return $users[0];
      }

      // c) vecchi salvataggi 0039
      $with0039 = '00'.$norm; // 39xxxxxxxx → 0039xxxxxxxx
      $users = get_users([ 'meta_key'=>'phone_number','meta_value'=>$with0039,'number'=>1,'fields'=>'all' ]);
      if ( ! empty($users) && $users[0] instanceof WP_User ) return $users[0];
    }

    return null;
  }
}

if ( ! function_exists('thub_try_signon_as_user') ) {
  function thub_try_signon_as_user( $user, $password ) {
    if ( ! ( $user instanceof WP_User ) ) {
      return new WP_Error( 'invalid_user', 'Utente non valido.' );
    }
    $creds = [
      'user_login'    => $user->user_login,
      'user_password' => $password,
      'remember'      => true,
    ];
    return wp_signon( $creds, is_ssl() );
  }
}

/* ==========================================================================================
   [THUB_LOGIN_SUBMIT] — con supporto redirect_to e honeypot anti-bot
   ========================================================================================== */
$error_msg = '';

/* [THUB_LOGIN_REDIRECT_TO] Legge e valida la destinazione di rientro */
$redirect_to_raw = isset($_REQUEST['redirect_to']) ? wp_unslash($_REQUEST['redirect_to']) : '';
$redirect_to     = wp_validate_redirect( $redirect_to_raw, $REDIRECT_AFTER_LOGIN );

/* [THUB_LOGIN_ALREADY] Se l’utente è già loggato → vai subito alla destinazione */
if ( is_user_logged_in() ) {
  wp_safe_redirect( $redirect_to );
  exit;
}

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
  // Honeypot anti-bot (campo nascosto, se pieno blocco)
  if ( ! empty( $_POST['thub_hp'] ?? '' ) ) {
    $error_msg = 'Si è verificato un problema. Riprova.';
  } elseif ( ! isset( $_POST['_thub_login_nonce'] ) || ! wp_verify_nonce( $_POST['_thub_login_nonce'], 'thub_login' ) ) {
    $error_msg = 'Sessione non valida. Riprova.';
  } else {
    $login_field = isset($_POST['thub_login_field']) ? sanitize_text_field( wp_unslash($_POST['thub_login_field']) ) : '';
    $password    = $_POST['thub_password'] ?? '';

    if ( ! $login_field || ! $password ) {
      $error_msg = 'Inserisci email/telefono e password.';
    } else {
      $user = thub_find_user_by_email_or_phone( $login_field );
      if ( ! $user ) {
        $error_msg = 'Credenziali non valide.';
      } else {
        $signed = thub_try_signon_as_user( $user, $password );
        if ( is_wp_error( $signed ) ) {
          $error_msg = 'Password errata o account non valido.';
        } else {
          $dest = $redirect_to ?: $REDIRECT_AFTER_LOGIN;
          wp_safe_redirect( $dest );
          exit;
        }
      }
    }
  }
}

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <!-- [THUB_HTML_HEAD] -->
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>

  <!--
    [THUB_AUTH_LAYOUT_CSS]
    - Desktop: logo/testi a SINISTRA IN ALTO (non centrati) → [THUB_DESKTOP_TOPLEFT]
    - Input con bordo molto sottile → [THUB_INPUT_SKINNY_BORDER]
    - Link Privacy/Termini SOTTO la card, allineati a destra su desktop → [THUB_LEGAL_OUT]
    - Pagina con altezza 100vh → [THUB_100VH]
    - LOGIN: link "Non ricordi email?" sotto campo login, "Password dimenticata?" sotto password → [THUB_LOGIN_HINTS]
             pulsante "Avanti" compatto a destra + link "Crea un account" inline a sinistra → [THUB_ACTIONS_INLINE]
  -->
  <style>
    :root{ --violet:#7249a4; --ink:#111; --muted:#555; --border:#eee; --bg:#fff; }

    html, body { height:100%; } /* [THUB_100VH] */
    body{
      margin:0; background:#fafafa; color:var(--ink);
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    }

    .auth-hero{
      height:100vh; /* [THUB_100VH] */
      display:flex; flex-direction:column; align-items:center; justify-content:center;
      padding:2rem 1rem; gap:.6rem;
    }

    /* Card verticale (mobile-first) */
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

    /* [THUB_INPUT_SKINNY_BORDER] input molto leggeri */
    .auth-field{ margin:.6rem 0; }
    .auth-label{ display:block; font-weight:600; margin:0 0 .25rem; }
    .auth-input{
      width:100%; border:1px solid #efeff3; border-radius:12px;
      padding:.65rem .8rem; font-size:1rem; background:#fff; outline:none;
    }
    .auth-input:focus{
      border-color:#dcd7ee;
      box-shadow:0 0 0 2px rgba(114,73,164,.08);
    }

    /* [THUB_LOGIN_HINTS] link subito sotto i campi */
    .hint-row{ margin:.25rem 0 .6rem; font-size:.9rem; }
    .auth-link{ text-decoration:none; color:#444; }
    .auth-link:hover{ color:var(--violet); text-decoration:underline; }

    /* [THUB_ACTIONS_INLINE] link "Crea un account" a sinistra, bottone compatto a destra */
    .actions-row{
      margin-top:.8rem; display:flex; align-items:center; justify-content:space-between; gap:.8rem;
    }
    .auth-submit{
      border:0; border-radius:9999px; padding:.6rem 1.1rem;
      background:#7249a4; color:#fff; font-weight:700; cursor:pointer; font-size:1rem;
      width:auto; /* compatto anche su mobile (richiesta esplicita) */
    }
    .auth-submit:hover{ opacity:.95; }

    /* [THUB_LEGAL_OUT] blocco privacy/termini esterno alla card */
    .auth-legal-out{
      width:min(960px,96vw); font-size:.9rem; display:flex; justify-content:center; gap:1rem;
    }

    /* Desktop: 2 colonne e allineamento alto a sinistra */
    @media (min-width: 1025px){
      .auth-card{ grid-template-columns: 1fr 1fr; }
      .auth-left{
        border-bottom:0; border-right:1px solid var(--border);
        justify-content:flex-start; align-items:flex-start; /* [THUB_DESKTOP_TOPLEFT] */
      }
      .auth-legal-out{ justify-content:flex-end; } /* allineo a destra sotto card */
    }
  </style>
</head>
<body class="thub-auth thub-no-header">
  <main class="auth-hero">

    <!-- CARD -->
    <section class="auth-card" role="form" aria-label="Login T-Hub">

      <!-- Colonna sinistra (logo + testi) -->
      <div class="auth-left">
        <div class="auth-logo">
          <?php if ( function_exists('the_custom_logo') && has_custom_logo() ) : the_custom_logo(); else: ?>
            <strong class="site-name"><?php bloginfo('name'); ?></strong>
          <?php endif; ?>
        </div>

        <h1>Accedi</h1>
        <p>Utilizza il tuo account T-Hub</p>
      </div>

      <!-- Colonna destra (form) -->
      <div class="auth-right">
        <?php if ( $error_msg ) : ?>
          <div class="auth-error" role="alert" aria-live="assertive"><?php echo esc_html( $error_msg ); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( get_permalink() ); ?>">
          <?php wp_nonce_field( 'thub_login', '_thub_login_nonce' ); ?>
          <!-- [THUB_HP] honeypot -->
          <input type="text" name="thub_hp" value="" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">

          <!-- [THUB_LOGIN_REDIRECT_HIDDEN] preserva redirect_to anche dopo il POST -->
          <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">

          <!-- Campo login -->
          <div class="auth-field">
            <label class="auth-label" for="thub_login_field">Email o telefono</label>
            <input class="auth-input" type="text" id="thub_login_field" name="thub_login_field" required autocomplete="username">
          </div>
          <!-- [THUB_LOGIN_HINTS] Non ricordi email sotto al campo -->
          <p class="hint-row">
            <a class="auth-link" href="<?php echo esc_url( home_url( $PAGE_TROVA_EMAIL ) ); ?>">Non ricordi l’indirizzo mail?</a>
          </p>

          <!-- Campo password -->
          <div class="auth-field">
            <label class="auth-label" for="thub_password">Password</label>
            <input class="auth-input" type="password" id="thub_password" name="thub_password" required autocomplete="current-password">
          </div>
          <!-- [THUB_LOGIN_HINTS] Password dimenticata sotto al campo -->
          <p class="hint-row">
            <a class="auth-link" href="<?php echo esc_url( home_url( $PAGE_PASSWORD_RESET ) ); ?>">Password dimenticata?</a>
          </p>

          <!-- Azioni: link + bottone compatto -->
          <div class="actions-row">
            <a class="auth-link" href="<?php echo esc_url( home_url( $PAGE_REGISTRAZIONE ) ); ?>">Crea un account</a>
            <button type="submit" class="auth-submit">Avanti</button>
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

  <?php wp_footer(); ?>
</body>
</html>