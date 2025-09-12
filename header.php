<?php
/**
 * [THUB_HEADER] Header personalizzato THUB
 * - Home:    sinistra link "Chi siamo" • destra icone
 * - Pagine:  sinistra logo piccolo • centro barra ricerca (mic + lente) • destra icone
 * - I modali (Account/Tastierino) sono separati in parts/ e inclusi qui.
 * - JS dei modali SOLO nel footer ([THUB_MODAL_JS]).
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>"><!-- [THUB_meta] charset -->
  <meta name="viewport" content="width=device-width, initial-scale=1"><!-- [THUB_meta] viewport -->
  <?php wp_head(); ?><!-- [THUB_wp] hook head -->
</head>
<body <?php body_class(); ?>>
<?php if (function_exists('wp_body_open')) wp_body_open(); ?><!-- [THUB_wp] body open -->

<?php
// [THUB_logic] Header sdoppiato (home vs pagine interne) — coerente con front-page.php
$is_home = is_front_page();
?>
<header class="site-header thub-header <?php echo $is_home ? 'thub-header--home' : 'thub-header--inner'; ?>"><!-- [THUB_hdr] wrapper -->
  <div class="thub-header-inner"><!-- [THUB_hdr_inner] 3 colonne: left / center / right -->

    <!-- =======================
         COLONNA SINISTRA
         ======================= -->
    <div class="thub-header-left">
      <?php if ($is_home): ?>
        <!-- [THUB_hdr_left_home] Link “Chi siamo” SOLO in home -->
        <a href="<?php echo esc_url( home_url('/chi-siamo/') ); ?>" class="thub-link-chi-siamo" title="Chi siamo">Chi siamo</a>
      <?php else: ?>
        <!-- [THUB_hdr_left_inner] Logo pagine interne (NO link-nel-link) -->
        <div class="thub-logo"><!-- wrapper neutro per layout -->
          <?php if ( function_exists('the_custom_logo') && has_custom_logo() ) : ?>
            <?php the_custom_logo(); ?><!-- stampa <a class="custom-logo-link"><img class="custom-logo"></a> -->
          <?php else : ?>
            <!-- [THUB_logo_fallback] Testuale se manca un logo caricato -->
            <a href="<?php echo esc_url( home_url('/') ); ?>" class="site-name" aria-label="Home">
              <?php bloginfo('name'); ?>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- =======================
         COLONNA CENTRALE (solo pagine interne)
         ======================= -->
    <?php if ( ! $is_home ): ?>
      <div class="thub-header-center">
        <?php
        // [THUB_hdr_search_inline] Barra ricerca inline con mic + lente
        get_template_part('parts/thub-search', null, ['variant' => 'inline']);
        ?>
      </div>
    <?php endif; ?>

    <!-- =======================
         COLONNA DESTRA
         ======================= -->
    <div class="thub-header-right">

      <!-- [THUB_APPS] Tastierino (menu scorciatoie) -->
      <div class="thub-apps" style="position:relative;"><!-- pos:relative per dropdown ancorato -->
        <button class="thub-apps-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Apri menu scorciatoie">
          <!-- [THUB_svg_grid] 9 quadratini -->
          <svg class="thub-grid-icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="3"  width="5" height="5" rx="1"></rect>
            <rect x="10" y="3" width="5" height="5" rx="1"></rect>
            <rect x="17" y="3" width="5" height="5" rx="1"></rect>
            <rect x="3" y="10" width="5" height="5" rx="1"></rect>
            <rect x="10" y="10" width="5" height="5" rx="1"></rect>
            <rect x="17" y="10" width="5" height="5" rx="1"></rect>
            <rect x="3" y="17" width="5" height="5" rx="1"></rect>
            <rect x="10" y="17" width="5" height="5" rx="1"></rect>
            <rect x="17" y="17" width="5" height="5" rx="1"></rect>
          </svg>
        </button>

        <?php
          // [THUB_INCLUDE] Modal Tastierino (griglia 2x3)
          get_template_part('parts/apps-modal');
        ?>
      </div>
      <!-- /[THUB_APPS] -->

      <!-- [THUB_USER] Utente (Accedi / Avatar con foto o iniziale) -->
      <div class="thub-user" style="position:relative;"><!-- pos:relative per dropdown ancorato -->
        <?php if ( ! is_user_logged_in() ): ?>
          <!-- [THUB_login_btn] Utente NON loggato -->
          <a class="thub-btn-login"
             href="<?php echo esc_url( home_url('/login') ); ?>"
             aria-label="Accedi al tuo account">Accedi</a>

        <?php else:
          // [THUB_user_data] Dati utente loggato
          $u   = wp_get_current_user();
          $nm  = $u->display_name ?: $u->user_login;
          $ini = strtoupper(mb_substr($nm, 0, 1, 'UTF-8'));

          // [THUB_palette] Pastello deterministico su ID utente (coerente con CSS)
          $palette = ['#E0F2FE','#DCFCE7','#FAE8FF','#FFE4E6','#FEF9C3','#EDE9FE','#E2E8F0','#FCE7F3'];
          $bg = $palette[ $u->ID % count($palette) ];

          // [THUB_photo] Foto profilo personalizzata (preferita) — ACF "thub_profile_photo" o user_meta omonimo
          $custom_avatar_url = '';
          if ( function_exists('get_field') ) {
            $acf_val = get_field('thub_profile_photo', 'user_'.$u->ID);
            if (is_array($acf_val) && !empty($acf_val['url'])) {
              $custom_avatar_url = esc_url($acf_val['url']);
            } elseif (is_string($acf_val) && filter_var($acf_val, FILTER_VALIDATE_URL)) {
              $custom_avatar_url = esc_url($acf_val);
            } elseif (is_numeric($acf_val)) {
              $custom_avatar_url = wp_get_attachment_image_url(intval($acf_val), 'thumbnail');
            }
          } else {
            $meta_url = get_user_meta($u->ID, 'thub_profile_photo', true);
            if ($meta_url && filter_var($meta_url, FILTER_VALIDATE_URL)) {
              $custom_avatar_url = esc_url($meta_url);
            }
          }
          $has_photo = !empty($custom_avatar_url);
        ?>

          <!-- [THUB_avatar_btn] Bottone avatar header -->
          <button class="thub-avatar"
                  type="button"
                  aria-haspopup="true" aria-expanded="false"
                  aria-label="Apri menu utente"
                  style="background: <?php echo esc_attr($bg); ?>;">
            <?php if ($has_photo): ?>
              <!-- Foto profilo -->
              <img src="<?php echo esc_url($custom_avatar_url); ?>" alt="Foto profilo" style="width:100%;height:100%;object-fit:cover;border-radius:9999px;">
            <?php else: ?>
              <!-- Iniziale con palette -->
              <span class="thub-avatar-initial" aria-hidden="true" style="font-weight:800;color:#111;"><?php echo esc_html($ini); ?></span>
            <?php endif; ?>
          </button>

          <?php
            // [THUB_INCLUDE] Modal Account (email, saluto, link)
            get_template_part('parts/account-modal');
          ?>
        <?php endif; ?>
      </div>
      <!-- /[THUB_USER] -->
    </div>
  </div>
</header>