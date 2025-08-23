<?php
/* ==========================================================================
 * search.php — Pagina risultati THUB
 * Build 2.1.4 – 2025-08-20
 * Requisiti:
 *  - Layout a 2 colonne (risultati sx / sidebar dx)
 *  - SLOT #0 CPC sempre in cima
 *  - Ricerca CPT 'ricetta': Q1 (core) + Q2 (ingredienti indicizzati) fuse
 *  - Sidebar "Le ricette Pro" parametrica da Opzioni (tassonomia/slug)
 *  - Tutte le stringhe sanificate; tag [THUB_*] per modifiche future
 * ========================================================================== */

get_header(); ?>
<main class="container thub-results-layout"><!-- // wrapper 2 colonne -->

  <!-- // COLONNA SINISTRA: RISULTATI -->
  <section class="thub-results-main"><!-- // [LEFT] -->

    <?php
    /* [THUB_TERM] Leggi termine ricerca */
    $term = trim( get_search_query() );
    if ($term === '') {
      echo '<p>Inserisci un termine di ricerca.</p>';
      echo '</section></main>'; get_footer(); return;
    }

    /* [THUB_POST_TYPE] Filtra per post_type (ricetta = ricerca avanzata) */
    $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';

    /* [THUB_CPC_VARS] Lettura variabili CPC da Opzioni (con fallback sicuri) */
    $cpc = [
      'cpc_click_url' => (string) ( get_field('cpc_click_url','option') ?: '' ),
      'cpc_logo'      => (string) ( get_field('cpc_logo','option')      ?: get_stylesheet_directory_uri().'/assets/img/sponsor-cpc.png' ),
      'cpc_name'      => (string) ( get_field('cpc_name','option')      ?: 'Sponsorizzata' ),
      'cpc_note'      => (string) ( get_field('cpc_note','option')      ?: '' ),
    ];

    // ===== Caso CPT "ricetta" con doppia ricerca (core + ACF indicizzata) =====
    if ($post_type === 'ricetta') {

      $ids = [];

      /* [THUB_Q1] Core search (titolo/contenuto) — solo ID */
      $q1 = new WP_Query([
        'post_type'      => 'ricetta',
        's'              => $term,
        'fields'         => 'ids',
        'posts_per_page' => 200,
        'no_found_rows'  => true,
      ]);
      $ids = array_merge($ids, (array) $q1->posts);

      /* [THUB_Q2] Ricerca su 'ingredienti_search' (tokenizzata & normalizzata) */
      $term_raw  = trim( get_search_query() );
      $term_norm = function_exists('thub_normalizza_testo')
        ? thub_normalizza_testo($term_raw)
        : mb_strtolower($term_raw, 'UTF-8');

      $tokens = preg_split('/\s+/u', $term_norm);
      $tokens = array_slice(array_filter(array_unique((array) $tokens)), 0, 5); // max 5 token

      // Costruisci OR su ingredienti_search
      $meta_or = ['relation' => 'OR'];
      foreach ($tokens as $tk) {
        $meta_or[] = [
          'key'     => 'ingredienti_search',
          'value'   => $tk,
          'compare' => 'LIKE',
        ];
      }

      // Query Q2 solo su ricette, ritorna solo gli ID
      $args_q2 = [
        'post_type'      => 'ricetta',
        'post_status'    => 'publish',
        'posts_per_page' => 200,
        'fields'         => 'ids',
        'no_found_rows'  => true,
      ];

      if (count($meta_or) > 1) {
        $args_q2['meta_query'] = $meta_or;
      } else {
        $args_q2['post__in'] = [0]; // nessun token ⇒ forziamo 0 risultati (evitiamo all-match)
      }

      $q2 = new WP_Query($args_q2);

      /* [THUB_MERGE] Unisci Q1+Q2 e deduplica */
      if (!empty($q2->posts)) {
        $ids = array_merge($ids, (array) $q2->posts);
      }
      $ids = array_values( array_unique( array_map('intval', $ids) ) );

      /* [THUB_RENDER] Rendering risultati + paginazione */
      if ($ids) {
        // Paginazione manuale sugli ID
        $paged = max(1, (int) get_query_var('paged'));
        $per   = 12;
        $total = (int) ceil( count($ids) / $per );
        $slice = array_slice($ids, ($paged - 1) * $per, $per);

        // Query finale pagina corrente (manteniamo l'ordine dell'array)
        $q = new WP_Query([
          'post_type'      => 'ricetta',
          'post__in'       => $slice,
          'orderby'        => 'post__in',
          'posts_per_page' => $per,
          'paged'          => $paged,
        ]);

        /* SLOT #0: card "Sponsorizzata" (ADV • CPC) sempre in cima */
        get_template_part('parts/thub-result-sponsored', null, $cpc);
        echo '<hr class="thub-results-separator" />';

        // LOOP risultati (la card per-ricetta gestirà eventuale CPM via partial)
        if ($q->have_posts()) {
          while ($q->have_posts()) { 
            $q->the_post();
            get_template_part('parts/thub-result'); // partial usa dati del post corrente
          }
          wp_reset_postdata();
        }

        // Paginazione
        if ($total > 1) {
          echo '<nav class="pagination">';
          echo paginate_links(['total'=>$total, 'current'=>$paged]);
          echo '</nav>';
        }

      } else {
        // Nessun risultato nel CPT ricetta: mostriamo comunque lo slot CPC
        get_template_part('parts/thub-result-sponsored', null, $cpc);
        echo '<hr class="thub-results-separator" />';
        echo '<p>Nessuna ricetta trovata.</p>';
      }

    // ===== Ricerca “normale” (altri post type) =====
    } else {

      // SLOT #0: CPC sempre in cima anche qui
      get_template_part('parts/thub-result-sponsored', null, $cpc);
      echo '<hr class="thub-results-separator" />';

      if (have_posts()) {
        while (have_posts()) { 
          the_post();
          get_template_part('parts/thub-result', null, []); // post generici, no CPM
        }

        echo '<nav class="pagination">';
        the_posts_pagination([
          'mid_size'  => 1,
          'prev_text' => __('« Precedenti', 'hello-elementor-child'),
          'next_text' => __('Successivi »', 'hello-elementor-child'),
        ]);
        echo '</nav>';

      } else {
        echo '<p>Nessun contenuto trovato.</p>';
      }
    }
    ?>

  </section><!-- // /thub-results-main -->


  <!-- // COLONNA DESTRA: SIDEBAR "Le ricette Pro" -->
  <aside class="thub-results-aside"><!-- // [RIGHT] -->
    <div class="thub-pro-box">
      <h3 class="thub-pro-title">Le ricette Pro</h3>
      <?php
      /* [THUB_PRO_SIDEBAR] Lettura tassonomia/slug da Opzioni (fallback: category/pro) */
      $pro_tax  = (string) ( get_field('pro_term_tax','option')  ?: 'category' );
      if (!in_array($pro_tax, ['category','post_tag'], true)) { $pro_tax = 'category'; }
      $pro_slug = (string) ( get_field('pro_term_slug','option') ?: 'pro' );

      $pro_q = new WP_Query([
        'post_type'      => 'ricetta',
        'posts_per_page' => 5,
        'tax_query'      => [[
          'taxonomy' => $pro_tax,
          'field'    => 'slug',
          'terms'    => [$pro_slug],
        ]],
        'no_found_rows'  => true,
      ]);

      if ($pro_q->have_posts()){
        echo '<ul class="thub-pro-list">';
        while ($pro_q->have_posts()){ $pro_q->the_post();
          echo '<li><a href="'.esc_url(get_permalink()).'">'.esc_html(get_the_title()).'</a></li>';
        }
        echo '</ul>';
        wp_reset_postdata();
      } else {
        echo '<p class="thub-pro-empty">In arrivo le ricette Pro dei ristoratori.</p>';
      }
      ?>
    </div>
  </aside><!-- // /thub-results-aside -->

</main><!-- // /thub-results-layout -->
<?php get_footer(); ?>