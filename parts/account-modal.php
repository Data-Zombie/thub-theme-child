<?php
/**
 * [THUB_MODAL_ACCOUNT] Partial: modal Account utente
 * - Mostra email, link console/account/cronologia/servizi, lingua, guida, privacy/termini
 * - Avatar: foto profilo (ACF 'thub_profile_photo' — ID/URL/Array) oppure iniziale con bg
 * - Si appoggia a CSS .thub-dropdown e blocco [THUB_MODAL_STYLES] in style.css
 * - L’apertura/chiusura è gestita dallo script in footer [THUB_MODAL_JS]
 */

if ( ! is_user_logged_in() ) {
  // Utente non loggato: non stampiamo il modal Account
  return;
}

$u   = wp_get_current_user();
$nm  = $u->display_name ?: $u->user_login;
$ini = strtoupper(mb_substr($nm, 0, 1, 'UTF-8'));

/* [THUB_palette] Pastello deterministico su ID utente (coerente con header) */
$palette = ['#E0F2FE','#DCFCE7','#FAE8FF','#FFE4E6','#FEF9C3','#EDE9FE','#E2E8F0','#FCE7F3'];
$bg = $palette[ $u->ID % count($palette) ];

/* -----------------------------------------------------------
 * [THUB_avatar_resolve] Ricava URL immagine profilo da ACF o meta
 * - ACF 'thub_profile_photo' può essere: ID, URL, oppure Array
 * - Usiamo get_field(..., false) per valore RAW ignorando il formato di ritorno
 * - Se non c’è ACF, proviamo user_meta 'thub_profile_photo' (URL)
 * ----------------------------------------------------------- */
$avatar_url = '';
if ( function_exists('get_field') ) {
  $raw = get_field('thub_profile_photo', 'user_'.$u->ID, false); // RAW (ignora return format)
  if ( is_numeric($raw) ) {
    // ID allegato → thumbnail o URL pieno
    $avatar_url = wp_get_attachment_image_url( intval($raw), 'thumbnail' );
    if ( ! $avatar_url ) {
      $avatar_url = wp_get_attachment_url( intval($raw) );
    }
  } elseif ( is_array($raw) ) {
    // Array (es. ['url'=>...])
    if ( !empty($raw['url']) && is_string($raw['url']) ) {
      $avatar_url = esc_url_raw($raw['url']);
    } elseif ( !empty($raw['ID']) && is_numeric($raw['ID']) ) {
      $avatar_url = wp_get_attachment_image_url( intval($raw['ID']), 'thumbnail' )
                 ?: wp_get_attachment_url( intval($raw['ID']) );
    }
  } elseif ( is_string($raw) && filter_var($raw, FILTER_VALIDATE_URL) ) {
    // URL
    $avatar_url = esc_url_raw($raw);
  }
} else {
  // Fallback: meta utente semplice (URL)
  $meta_url = get_user_meta( $u->ID, 'thub_profile_photo', true );
  if ( $meta_url && is_string($meta_url) && filter_var($meta_url, FILTER_VALIDATE_URL) ) {
    $avatar_url = esc_url_raw($meta_url);
  }
}

/* [THUB_avatar_flag] È una URL valida? Se no, forziamo fallback (niente <img src="123">) */
$has_photo = (bool) ( $avatar_url && filter_var($avatar_url, FILTER_VALIDATE_URL) );
?>
<nav class="thub-dropdown thub-account-dropdown" id="thub-dropdown-account" role="menu" aria-hidden="true">
  <!-- [THUB_ACC_TOP] Email + chiudi -->
  <div class="thub-acc__top">
    <span class="thub-acc__email"><?php echo esc_html( $u->user_email ); ?></span>
    <button type="button" class="thub-acc__close" data-thub-close="#thub-dropdown-account" aria-label="Chiudi">×</button>
  </div>

  <!-- [THUB_ACC_CONSOLE] Link console -->
  <div class="thub-acc__adm">
    <a href="<?php echo esc_url( home_url('/console') ); ?>" class="thub-acc__adm-link">Console di amministrazione</a>
  </div>

  <!-- [THUB_ACC_AVATAR] Foto o iniziale + saluto + trigger upload -->
  <div class="thub-acc__avatar-wrap" id="thub-acc-avatar-wrap" title="Clicca per cambiare foto">
    <?php if ($has_photo): ?>
      <img class="thub-acc__avatar-img" src="<?php echo esc_url($avatar_url); ?>" alt="Foto profilo">
    <?php else: ?>
      <div class="thub-acc__avatar-fallback" style="background:<?php echo esc_attr($bg); ?>;color:#111;display:grid;place-items:center;width:56px;height:56px;border-radius:50%;font-weight:800;">
        <?php echo esc_html($ini); ?>
      </div>
    <?php endif; ?>
    <div class="thub-acc__hello">Ciao <strong><?php echo esc_html($nm); ?></strong></div>

    <!-- [THUB_PROFILE_UPLOAD_INPUT] input file nascosto, scatenato cliccando l’avatar -->
    <input type="file" id="thub-upload-profile" name="profile_photo" accept="image/*" hidden>
  </div>

  <!-- [THUB_PROFILE_UPLOAD_MSG] messaggi stato upload -->
  <div class="thub-acc__upload-msg" id="thub-profile-msg" aria-live="polite"></div>

  <!-- [THUB_ACC_ACTIONS] Pulsanti principali -->
  <div class="thub-acc__actions">
    <a class="thub-btn thub-btn--full"  href="<?php echo esc_url( home_url('/account') ); ?>">Gestisci il tuo account T-Hub</a>
    <a class="thub-btn thub-btn--ghost" href="<?php echo esc_url( home_url('/cronologia') ); ?>">Cronologia delle ricerche</a>
    <a class="thub-btn thub-btn--ghost" href="<?php echo esc_url( home_url('/servizi') ); ?>">Ricette salvate e raccolte</a>
  </div>

  <!-- [THUB_ACC_PREFS] Lingua + impostazioni/guida -->
  <div class="thub-acc__prefs">
    <a href="<?php echo esc_url( home_url('/lingua-e-regione') ); ?>" class="thub-acc__pref">
      <span>Lingua</span> <strong>Italiano</strong>
    </a>
    <div class="thub-acc__pref-pair">
      <a href="<?php echo esc_url( home_url('/impostazioni') ); ?>">Altre impostazioni</a>
      <a href="<?php echo esc_url( home_url('/assistenza') ); ?>">Guida</a>
    </div>
  </div>

  <!-- [THUB_ACC_LEGAL] Privacy/Termini -->
  <div class="thub-acc__legal">
    <a href="<?php echo esc_url( home_url('/privacy') ); ?>">Privacy</a>
    <span class="sep">·</span>
    <a href="<?php echo esc_url( home_url('/termini') ); ?>">Termini</a>
  </div>
</nav>