<?php
/**
 * Template Name: T-Hub • Trova email (desktop orizzontale, mobile/tablet verticale)
 * Description: Recupero dell’indirizzo email associato all’account (ricerca per telefono o Nome/Cognome).
 *
 * ISTRUZIONI (Editor classico):
 * 1) Salva come `page-trova-email.php` in /wp-content/themes/hello-elementor-child/
 * 2) Crea/Modifica pagina "Trova email" (slug: /trova-email) → Attributi pagina → Template: questo.
 */

if ( ! defined('ABSPATH') ) { exit; } // [THUB_TEMPLATE_GUARD]

/* ==========================================================================================
   [THUB_TE_SLUGS] — Slug delle pagine correlate (modifica se usi slug diversi)
   ========================================================================================== */
$PAGE_PRIVACY = '/privacy';
$PAGE_TERMINI = '/termini';

/* ==========================================================================================
   [THUB_TE_HELPERS] — Funzioni di supporto (telefono/email)
   ========================================================================================== */
/** Normalizza il numero: tiene solo cifre e converte 0039xxxx → 39xxxx */
if ( ! function_exists('thub_te_norm_phone') ) {
  function thub_te_norm_phone( $raw ) {
    $digits = preg_replace( '/\D+/', '', $raw ?? '' );
    if ( ! $digits ) return '';
    if ( strpos( $digits, '0039' ) === 0 ) { $digits = substr( $digits, 2 ); } // 0039 → 39
    return $digits;
  }
}

/** Maschera email per mostrare solo la prima lettera della parte locale */
if ( ! function_exists('thub_te_mask_email') ) {
  function thub_te_mask_email( $email ) {
    if ( ! is_email( $email ) ) return '';
    list( $u, $d ) = explode( '@', $email, 2 );
    if ( strlen( $u ) <= 1 ) return '*@' . $d;
    return substr( $u, 0, 1 ) . str_repeat( '*', max( 1, strlen( $u ) - 1 ) ) . '@' . $d;
  }
}

/**
 * Ricerca utente:
 * 1) per telefono (meta_key 'phone_number'): prova formati 39..., senza 39, e 0039...
 * 2) altrimenti per Nome/Cognome (meta_query) con fallback su display_name
 */
if ( ! function_exists('thub_te_find_user') ) {
  function thub_te_find_user( $phone_raw, $first_name, $last_name ) {

    // (1) Telefono
    $norm = thub_te_norm_phone( $phone_raw );
    if ( $norm ) {
      // a) così com'è
      $u = get_users( [ 'meta_key'=>'phone_number', 'meta_value'=>$norm, 'number'=>1, 'fields'=>'all' ] );
      if ( ! empty( $u ) && $u[0] instanceof WP_User ) return $u[0];

      // b) senza 39
      if ( strpos( $norm, '39' ) === 0 ) {
        $alt = substr( $norm, 2 );
        if ( $alt ) {
          $u = get_users( [ 'meta_key'=>'phone_number', 'meta_value'=>$alt, 'number'=>1, 'fields'=>'all' ] );
          if ( ! empty( $u ) && $u[0] instanceof WP_User ) return $u[0];
        }

        // c) vecchi salvataggi con 0039
        $with0039 = '00' . $norm; // 39xxxxxxxx → 0039xxxxxxxx
        $u = get_users( [ 'meta_key'=>'phone_number', 'meta_value'=>$with0039, 'number'=>1, 'fields'=>'all' ] );
        if ( ! empty( $u ) && $u[0] instanceof WP_User ) return $u[0];
      }
    }

    // (2) Nome/Cognome
    $first = trim( (string) $first_name );
    $last  = trim( (string) $last_name );

    if ( $first || $last ) {
      // meta_query su first_name / last_name
      $meta_query = [ 'relation' => 'AND' ];
      if ( $first ) $meta_query[] = [ 'key'=>'first_name', 'value'=>$first, 'compare'=>'LIKE' ];
      if ( $last )  $meta_query[] = [ 'key'=>'last_name',  'value'=>$last,  'compare'=>'LIKE' ];

      $maybe = get_users( [ 'meta_query'=>$meta_query, 'number'=>1, 'fields'=>'all' ] );
      if ( ! empty( $maybe ) && $maybe[0] instanceof WP_User ) return $maybe[0];

      // fallback display_name
      $maybe = get_users( [
        'search'         => '*' . esc_attr( trim( $first . ' ' . $last ) ) . '*',
        'search_columns' => [ 'display_name' ],
        'number'         => 1,
        'fields'         => 'all',
      ] );
      if ( ! empty( $maybe ) && $maybe[0] instanceof WP_User ) return $maybe[0];
    }

    return null;
  }
}

/* ==========================================================================================
   [THUB_TE_SUBMIT] — Gestione form (con honeypot)
   ========================================================================================== */
$msg_ok  = '';
$msg_err = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

  // [THUB_HP_CHECK] — blocco bot: se il campo nascosto è valorizzato, fermo tutto
  if ( ! empty( $_POST['thub_hp'] ?? '' ) ) {
    $msg_err = 'Si è verificato un problema. Riprova.';
  }
  // Nonce
  elseif ( ! isset( $_POST['_thub_te_nonce'] ) || ! wp_verify_nonce( $_POST['_thub_te_nonce'], 'thub_te' ) ) {
    $msg_err = 'Sessione non valida. Riprova.';
  } else {
    $telefono = isset($_POST['thub_phone'])      ? sanitize_text_field( wp_unslash($_POST['thub_phone']) )      : '';
    $nome     = isset($_POST['thub_first_name']) ? sanitize_text_field( wp_unslash($_POST['thub_first_name']) ) : '';
    $cognome  = isset($_POST['thub_last_name'])  ? sanitize_text_field( wp_unslash($_POST['thub_last_name']) )  : '';

    if ( ! $telefono && ! $nome && ! $cognome ) {
      $msg_err = 'Inserisci almeno il telefono oppure Nome e Cognome.';
    } else {
      $user = thub_te_find_user( $telefono, $nome, $cognome );
      if ( $user instanceof WP_User ) {
        $msg_ok = 'Abbiamo trovato questo indirizzo associato: ' . esc_html( thub_te_mask_email( $user->user_email ) );
      } else {
        $msg_err = 'Nessun account trovato con i dati inseriti.';
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
    .auth-ok{ background:#f2fff5; color:#0f6d2c; border-color:#cbe9d3; }

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
  <main class="auth-hero">

    <!-- CARD -->
    <section class="auth-card" role="form" aria-label="Trova la tua email">

      <div class="auth-left">
        <div class="auth-logo">
          <?php if ( function_exists('the_custom_logo') && has_custom_logo() ) : the_custom_logo(); endif; ?>
        </div>
        <h1>Trova la tua email</h1>
        <p>Inserisci il tuo numero di telefono o il tuo nome</p>
      </div>

      <div class="auth-right">
        <?php if ( $msg_ok ) : ?>
          <div class="auth-error auth-ok" role="status" aria-live="polite"><?php echo esc_html( $msg_ok ); ?></div>
        <?php endif; ?>

        <?php if ( $msg_err ) : ?>
          <div class="auth-error" role="alert" aria-live="assertive"><?php echo esc_html( $msg_err ); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( get_permalink() ); ?>">
          <?php wp_nonce_field( 'thub_te', '_thub_te_nonce' ); ?>

          <?php /* [THUB_HP] campo honeypot anti-bot */ ?>
          <input type="text" name="thub_hp" value="" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">

          <!-- [THUB_FORM_FIELDS] -->
          <div class="auth-field">
            <label class="auth-label" for="thub_phone">Numero di telefono</label>
            <input class="auth-input" type="text" id="thub_phone" name="thub_phone" autocomplete="tel">
          </div>

          <div class="auth-row" style="display:flex; gap:.6rem; flex-wrap:wrap; margin-top:.2rem;">
            <div class="auth-field" style="flex:1; min-width:220px;">
              <label class="auth-label" for="thub_first_name">Nome</label>
              <input class="auth-input" type="text" id="thub_first_name" name="thub_first_name" autocomplete="given-name">
            </div>
            <div class="auth-field" style="flex:1; min-width:220px;">
              <label class="auth-label" for="thub_last_name">Cognome</label>
              <input class="auth-input" type="text" id="thub_last_name" name="thub_last_name" autocomplete="family-name">
            </div>
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