<?php
/* 
 * Template Name: THUB Canvas Layout
 * Description: Layout canvas con header fisso (logo sx, NESSUNA search centrale, icone tastierino+account dx), menu canvas fisso a sinistra e senza footer visibile.
 * Note: questo template gestisce tutte le pagine “Canvas” con sezioni dinamiche via ?section=... o URL riscritte /pagina/<section>/
 * [THUB_CANVAS_TPL]
 */

defined('ABSPATH') || exit;

/* ---------------------------------------------------------
 * [THUB_CANVAS_TPL_BODYCLASS]
 * Aggiunge body class per nascondere search e footer nelle pagine Canvas,
 * e per attivare gli stili del layout canvas (sidebar sinistra).
 * --------------------------------------------------------- */
add_filter('body_class', function($classes){
  $classes[] = 'thub-no-search'; // nasconde la colonna centrale dell’header (search) su questo template
  $classes[] = 'thub-no-footer'; // nasconde il footer via CSS su questo template
  $classes[] = 'thub-has-canvas'; // abilita la griglia canvas (aside+content)
  return $classes;
});

// [THUB_CANVAS_TPL] Header del tema
get_header();
?>

<main id="thub-canvas" class="thub-canvas-layout"><!-- [THUB_CANVAS_MARKUP] wrapper principale -->
  <div class="thub-account__layout"><!-- [THUB_CANVAS_GRID] griglia 2 colonne: aside + main -->

    <!-- =====================================================
         [THUB_CANVAS_MENU_LOC] — MENU LATERALE (per-pagina)
         Priorità:
         1) ACF "thub_canvas_menu" (Select → ID menu)
         2) Theme location dinamica "thub-canvas-<slug_pagina>" (se esiste)
         3) Fallback finale "thub-account-menu"
         ===================================================== -->
    <aside class="thub-account__aside" aria-label="Menu di sezione">
      <nav class="thub-account__nav">
        <?php
          // Slug pagina corrente (es. account, console, servizi...)
          $qo   = get_queried_object();
          $slug = !empty($qo->post_name) ? sanitize_title($qo->post_name) : '';

          // Lettura robusta del campo ACF (ID “value” o array con ['value'])
          $raw = function_exists('get_field') ? get_field('thub_canvas_menu') : 0;
          $menu_id = is_array($raw) ? (int) ($raw['value'] ?? 0) : (int) $raw;

          $args = [
            'container'  => false,
            'menu_class' => 'thub-account__list', // <ul> class coerente col CSS
          ];

          if ($menu_id) {
            // 1) Menu scelto nella pagina (ACF)
            $args['menu'] = $menu_id;
          } else {
            // 2) Theme location dinamica: thub-canvas-<slug> (es. thub-canvas-console)
            $loc_dynamic = 'thub-canvas-' . $slug;
            if (has_nav_menu($loc_dynamic)) {
              $args['theme_location'] = $loc_dynamic;
            } else {
              // 3) Fallback finale: thub-account-menu
              $args['theme_location'] = 'thub-account-menu';
            }
          }

          wp_nav_menu($args);
        ?>
      </nav>

      <!-- [THUB_CANVAS_LEGAL] Riga legale sotto al contenuto del menu (non sticky) -->
      <div class="thub-account__legal">
        <!-- [THUB_LEGAL_BRAND] riga 1: brand su linea dedicata -->
        <div class="thub-legal__row thub-legal__brand">
          © <?php echo date('Y'); ?> T-Hub Ltd.
        </div>
        <!-- [THUB_LEGAL_LINKS] riga 2: link Privacy/Termini su nuova riga -->
        <div class="thub-legal__row thub-legal__links">
          <a href="/privacy/">Privacy</a> – <a href="/termini/">Termini</a>
        </div>
      </div>
    </aside>

    <!-- =====================================================
         [THUB_CANVAS_CONTENT] — CONTENUTI
         - Se la pagina è tra quelle “sezionate”: router → include parts/<slug>/section-<section>.php
         - Altrimenti: normale contenuto Editor classico
         ===================================================== -->
    <section class="thub-account__main" role="region" aria-label="Contenuti pagina">
      <?php
        // Slug pagina e lista pagine “sezionate” (fornite via functions.php)
        $pages = function_exists('thub_canvas_section_slugs') ? thub_canvas_section_slugs() : [];
        $is_sectioned = in_array($slug, $pages, true);

        if ($is_sectioned) :
          /* ------------------------------------------
           * [THUB_CANVAS_ROUTER] Router sezioni multi-pagina
           * - URL supportati:
           *     /pagina/?section=<slug>
           *     /pagina/<slug>/  (rewrite → query var "section")
           * - Include: parts/<slug_pagina>/section-<slug_sezione>.php
           * ------------------------------------------ */

          // Sezione corrente (query var > GET > default per quella pagina)
          $current = function_exists('thub_canvas_current_section') ? thub_canvas_current_section() : '';
          if (!$current && function_exists('thub_canvas_default_section')) {
            $current = thub_canvas_default_section($slug);
          }
          if (!$current) { $current = 'home'; }

          // H1: humanize della section (es. lingua-di-visualizzazione → Lingua Di Visualizzazione)
          $title = ucwords(str_replace('-', ' ', $current));

          // Toggle ACF per nascondere H1 (opzionale)
          $hide_title = function_exists('get_field') ? (bool) get_field('thub_hide_title') : false;
          if (!$hide_title) {
            echo '<h1 class="thub-account__title">'. esc_html($title) .'</h1>';
          }

          // Include del partial della sezione
          $partial = get_stylesheet_directory() . '/parts/' . $slug . '/section-' . $current . '.php';
          if (file_exists($partial)) {
            include $partial; // [THUB_INCLUDE_SECTION] include il file della sezione
          } else {
            // Fallback: contenuto Editor classico
            if ( have_posts() ) : while ( have_posts() ) : the_post(); the_content(); endwhile; endif;
          }

        else :
          /* ------------------------------------------
           * [THUB_CANVAS_DEFAULT_CONTENT]
           * Pagine Canvas NON- “sezionate” → contenuto Editor classico
           * ------------------------------------------ */
          if ( have_posts() ) : 
            while ( have_posts() ) : the_post();
              // Stampa H1 solo se non nascosto via ACF (preferenza Canvas)
              $hide_title = function_exists('get_field') ? (bool) get_field('thub_hide_title') : false;
              if (!$hide_title) {
                the_title('<h1 class="thub-canvas-title">','</h1>');
              }
              the_content();
            endwhile;
          endif;

        endif;
      ?>
    </section>

  </div><!-- /[THUB_CANVAS_GRID] -->
</main>

<?php
/* ---------------------------------------------------------
 * [THUB_CANVAS_TPL_FOOTER]
 * Non mostriamo il footer (UI) su questo template: è nascosto via CSS (.thub-no-footer).
 * Manteniamo comunque get_footer() per garantire wp_footer() e il corretto enqueue degli script.
 * --------------------------------------------------------- */
get_footer();