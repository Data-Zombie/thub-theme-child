<?php
/**
 * Template Name: T-Hub • Password dimenticata (desktop orizzontale, mobile/tablet verticale)
 * Description: Avvia il reset password. Accetta telefono o email; se telefono risale all’email e reindirizza al form nativo.
 *
 * ISTRUZIONI (Editor classico):
 * 1) Salva come `page-password-dimenticata.php` in /wp-content/themes/hello-elementor-child/
 * 2) Crea/Modifica pagina "Password dimenticata" (slug: /password-dimenticata)
 * 3) Attributi pagina → Template: questo → Pubblica.
 */

if ( ! defined('ABSPATH') ) { exit; } // [THUB_TEMPLATE_GUARD]

/* ==========================================================================================
   [THUB_PD_SLUGS] — Slug pagine correlate (modifica se usi slug diversi)
   ========================================================================================== */
$PAGE_PRIVACY = '/privacy';
$PAGE_TERMINI = '/termini';

/* ==========================================================================================
   [THUB_PD_HELPERS] — Utility per normalizzare/risolvere identificativo
   ========================================================================================== */

/** Normalizza telefono: tiene solo cifre e converte 0039xxxx → 39xxxx */
if ( ! function_exists('thub_pd_norm_phone') ) {
  function thub_pd_norm_phone( $raw ) {
    $digits = preg_replace( '/\D+/', '', $raw ?? '' );
    if ( ! $digits ) return '';
    if ( strpos( $digits, '0039' ) === 0 ) { $digits = substr( $digits, 2 ); } // 0039 → 39
    return $digits;
  }
}

/**
 * Risolve l'input in un'EMAIL valida per il reset:
 * - Se è email → la restituisce
 * - Se è telefono → cerca meta 'phone_number' in tre varianti: 39..., senza 39, 0039...
 */
if ( ! function_exists('thub_pd_resolve_identifier_to_email') ) {
  function thub_pd_resolve_identifier_to_email( $input ) {
    $input = trim( (string) $input );

    // Caso email diretto
    if ( is_email( $input ) ) {
      return $input;
    }

    // Caso telefono
    $norm = thub_pd_norm_phone( $input );
    if ( $norm ) {
      // a) così com'è
      $u = get_users( [
        'meta_key'   => 'phone_number',
        'meta_value' => $norm,
        'number'     => 1,
        'fields'     => 'all',
      ] );
      if ( ! empty( $u ) && $u[0] instanceof WP_User ) return $u[0]->user_email;

      // b) senza 39
      if ( strpos( $norm, '39' ) === 0 ) {
        $alt = substr( $norm, 2 );
        if ( $alt ) {
          $u = get_users( [
            'meta_key'   => 'phone_number',
            'meta_value' => $alt,
            'number'     => 1,
            'fields'     => 'all',
          ] );
          if ( ! empty( $u ) && $u[0] instanceof WP_User ) return $u[0]->user_email;
        }

        // c) vecchi salvataggi con 0039
        $with0039 = '00' . $norm; // 39xxxxxxxx → 0039xxxxxxxx
        $u = get_users( [
          'meta_key'   => 'phone_number',
          'meta_value' => $with0039,
          'number'     => 1,
          'fields'     => 'all',
        ] );
        if ( ! empty( $u ) && $u[0] instanceof WP_User ) return $u[0]->user_email;
      }
    }

    // Nessuna corrispondenza
    return '';
  }
}

/* ==========================================================================================
   [THUB_PD_SUBMIT] — Gestione invio form (con honeypot)
   ========================================================================================== */
$msg_err = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

  // [THUB_HP_CHECK] — blocco bot: se il campo nascosto è valorizzato, fermo tutto
  if ( ! empty( $_POST['thub_hp'] ?? '' ) ) {
    $msg_err = 'Si è verificato un problema. Riprova.';
  }
  // Verifica nonce
  elseif ( ! isset( $_POST['_thub_pd_nonce'] ) || ! wp_verify_nonce( $_POST['_thub_pd_nonce'], 'thub_pd' ) ) {
    $msg_err = 'Sessione non valida. Riprova.';
  } else {
    $identifier = isset( $_POST['thub_identifier'] ) ? sanitize_text_field( wp_unslash( $_POST['thub_identifier'] ) ) : '';

    if ( ! $identifier ) {
      $msg_err = 'Inserisci email o telefono.';
    } else {
      $for_email = thub_pd_resolve_identifier_to_email( $identifier );

      if ( ! $for_email ) {
        $msg_err = 'Nessun account trovato con i dati inseriti.';
      } else {
        // Reindirizza al form nativo di WordPress con user_login precompilato
        $lost_url = wp_lostpassword_url();
        wp_safe_redirect( add_query_arg( 'user_login', rawurlencode( $for_email ), $lost_url ) );
        exit;
      }
    }
  }
}

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <!-- [THUB_HTML_HEAD] Metadati + hook WP -->
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>

  <!-- [THUB_AUTH_LAYOUT_CSS] v. desktop top-left, input sottili, 100vh, legali sotto card -->
  <style>
    :root{ --violet:#7249a4; --ink:#111; --muted:#555; --border:#eee; --bg:#fff; }

    html, body { height:100%; }
    body{ margin:0; background:#fafafa; color:var(--ink); font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; }

    .auth-hero{
      height:100vh;
      display:flex; flex-direction:column; align-items:center; justify-content:center;
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
      display:flex; flex-direction:column; justify-content:center; /* mobile centrato */
      background:linear-gradient(180deg,#f8f7fb 0%,#ffffff 100%);
      border-bottom:1px solid var(--border);
    }
    .auth-left .auth-logo{ margin-bottom:1rem; }
    .auth-left .auth-logo img{ display:block; height:36px; width:auto; }
    .auth-left h1{ margin:.2rem 0 .35rem; font-size:1.6rem; font-weight:800; }
    .auth-left p{ margin:0; color:#555; line-height:1.45; }

    .auth-right{ padding:2rem 1.4rem; display:flex; flex-direction:column; }

    .auth-field{ margin:.6rem 0; }
    .auth-label{ display:block; font-weight:600; margin:0 0 .25rem; }
    .auth-input{
      width:100%; border:1px solid #efeff3; border-radius:12px;
      padding:.65rem .8rem; font-size:1rem; background:#fff; outline:none;
    }
    .auth-input:focus{ border-color:#dcd7ee; box-shadow:0 0 0 2px rgba(114,73,164,.08); }

    .auth-link{ text-decoration:none; color:#444; }
    .auth-link:hover{ color:var(--violet); text-decoration:underline; }

    .auth-error{
      background:#fff2f2; color:#9a1c1c; border:1px solid #f1c9c9;
      padding:.6rem .7rem; border-radius:.6rem; margin:.4rem 0 .6rem;
    }

    .actions-right{ margin-top:.8rem; display:flex; justify-content:flex-end; }
    .auth-submit{
      border:0; border-radius:9999px; padding:.7rem 1.2rem; font-size:1rem;
      background:#7249a4; color:#fff; font-weight:700; cursor:pointer;
      width:100%; /* mobile full */
    }
    .auth-submit:hover{ opacity:.95; }

    .auth-legal-out{
      width:min(960px,96vw); font-size:.9rem; display:flex; justify-content:center; gap:1rem;
    }

    @media (min-width: 1025px){
      .auth-card{ grid-template-columns: 1fr 1fr; }
      .auth-left{
        border-bottom:0; border-right:1px solid var(--border);
        justify-content:flex-start; align-items:flex-start; /* desktop top-left */
      }
      .auth-submit{ width:auto; }             /* desktop compatto */
      .auth-legal-out{ justify-content:flex-end; }
    }
  </style>
</head>
<body class="thub-auth thub-no-header">
  <!-- [THUB_HTML_BODY] -->
  <main class="auth-hero">
    <section class="auth-card" role="form" aria-label="Recupero password">

      <div class="auth-left">
        <div class="auth-logo">
          <?php if ( function_exists('the_custom_logo') && has_custom_logo() ) : the_custom_logo(); endif; ?>
        </div>
        <h1>Recupero password</h1>
        <p>Inserisci il tuo numero di telefono o l’indirizzo email</p>
      </div>

      <div class="auth-right">
        <?php if ( $msg_err ) : ?>
          <div class="auth-error" role="alert" aria-live="assertive"><?php echo esc_html( $msg_err ); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( get_permalink() ); ?>">
          <?php /* Nonce di sicurezza */ wp_nonce_field( 'thub_pd', '_thub_pd_nonce' ); ?>

          <?php /* [THUB_HP] campo honeypot anti-bot */ ?>
          <input type="text" name="thub_hp" value="" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">

          <!-- [THUB_FORM_FIELDS] Campo unico: telefono o email -->
          <div class="auth-field">
            <label class="auth-label" for="thub_identifier">Numero di telefono o email</label>
            <input class="auth-input" type="text" id="thub_identifier" name="thub_identifier" required autocomplete="username">
          </div>

          <div class="actions-right">
            <button class="auth-submit" type="submit">Avanti</button>
          </div>
        </form>
      </div>
    </section>

    <div class="auth-legal-out">
      <a class="auth-link" href="<?php echo esc_url( home_url( $PAGE_PRIVACY ) ); ?>">Privacy</a>
      <a class="auth-link" href="<?php echo esc_url( home_url( $PAGE_TERMINI ) ); ?>">Termini</a>
    </div>
  </main>

  <?php wp_footer(); ?>
</body>
</html>