<?php
/* ==========================================================================
 * search.php — Pagina risultati THUB
 * Build 2.1.6 – 2025-09-02
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
    /* =========================================================
       [THUB_TERM] Leggi termine ricerca
       ========================================================= */
    $term = trim( get_search_query() );
    if ($term === '') {
      echo '<p>Inserisci un termine di ricerca.</p>';
      echo '</section></main>';
      get_footer();
      return;
    }

    /* =========================================================
       [THUB_POST_TYPE] Filtra per post_type (ricetta = ricerca avanzata)
       ========================================================= */
    $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';

    /* =========================================================
       [THUB_CPC_VARS] Lettura variabili CPC da Opzioni (fallback sicuri)
       ========================================================= */
    $cpc = [
      'cpc_click_url' => (string) ( get_field('cpc_click_url','option') ?: '' ),
      'cpc_logo'      => (string) ( get_field('cpc_logo','option')      ?: get_stylesheet_directory_uri().'/assets/img/sponsor-cpc.png' ),
      'cpc_name'      => (string) ( get_field('cpc_name','option')      ?: 'Sponsorizzata' ),
      'cpc_note'      => (string) ( get_field('cpc_note','option')      ?: '' ),
    ];

    // ==========================================================
    // Caso CPT "ricetta" con doppia ricerca (core + ACF indicizzata)
    // ==========================================================
    if ($post_type === 'ricetta') {

      $ids = [];

      /* ---------------------------------------------
         [THUB_Q1] Core search (titolo/contenuto) — solo ID
         --------------------------------------------- */
      $q1 = new WP_Query([
        'post_type'      => 'ricetta',
        's'              => $term,
        'fields'         => 'ids',
        'posts_per_page' => 200,
        'no_found_rows'  => true,
      ]);
      $ids = array_merge($ids, (array) $q1->posts);

      /* ---------------------------------------------
         [THUB_Q2] Ricerca su 'ingredienti_search' (tokenizzata & normalizzata)
         --------------------------------------------- */
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

      /* ---------------------------------------------
         [THUB_MERGE] Unisci Q1+Q2 e deduplica
         --------------------------------------------- */
      if (!empty($q2->posts)) {
        $ids = array_merge($ids, (array) $q2->posts);
      }
      $ids = array_values( array_unique( array_map('intval', $ids) ) );

      /* ---------------------------------------------
         [THUB_RENDER] Rendering risultati + paginazione
         --------------------------------------------- */
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

        /* ======================================================================
           [THUB_SEARCH_TOPINFO] Info risultati + lingua (ramo ricetta)
           Regola lingua:
           - ?results_lang=xx → prioritaria (override volatile)
           - altrimenti thub_get_user_results_lang()
           ====================================================================== */
        $locales       = function_exists('thub_results_get_locales') ? thub_results_get_locales() : [];
        $override      = isset($_GET['results_lang']) ? strtolower(sanitize_text_field($_GET['results_lang'])) : '';
        $current_lang  = ( $override && isset($locales[$override]) )
          ? $override
          : ( function_exists('thub_get_user_results_lang') ? thub_get_user_results_lang( get_current_user_id() ) : 'it' );
        $current_label = $locales[$current_lang] ?? strtoupper($current_lang);
        $results_count = (int) $q->post_count; // numero reale mostrato nella pagina corrente

        /* [THUB_RESULTS_REGION_VARS] Prepara variabili Regione (cucina) */
        $regions        = function_exists('thub_get_results_region_choices') ? thub_get_results_region_choices() : [];
        $override_reg   = isset($_GET['results_region']) ? sanitize_key($_GET['results_region']) : '';
        $current_region = ( $override_reg && isset($regions[$override_reg]) )
          ? $override_reg
          : ( function_exists('thub_get_user_results_region') ? thub_get_user_results_region( get_current_user_id() ) : '' );
        $current_region_label = $current_region && isset($regions[$current_region]) ? $regions[$current_region] : 'Cucina';

        /* [THUB_RESULTS_CUISINE_LABEL] Rimuove "Cucina " e converte in Title Case (Italiana, Francese, ...) */
        $current_cuisine_label = $current_region_label
          ? mb_convert_case( preg_replace('/^Cucina\s+/i', '', $current_region_label), MB_CASE_TITLE, 'UTF-8')
          : 'Cucina';
        ?>

        <!-- [THUB_SEARCH_TOPINFO] Riga info sotto header (allineata a sx) -->
        <div class="thub-search-topinfo"
            style="width:100%; padding:.5rem .75rem; display:flex; gap:1rem; align-items:center; font-size:small;">
          <div><strong>N. di risultati:</strong> <?php echo esc_html($results_count); ?></div>

          <div>
            <strong>Lingua dei risultati:</strong>
            <!-- [THUB_LANG_CTRL_WRAP] wrapper ancoraggio laterale -->
            <span id="thub-lang-ctrl"
                  style="position:relative; display:inline-flex; align-items:center; gap:.35rem;">
              <button id="thub-results-lang-btn"
                      class="thub-link"
                      type="button"
                      style="background:none;border:0;padding:0;cursor:pointer;"
                      aria-haspopup="listbox"
                      aria-expanded="false"
                      aria-controls="thub-results-lang-dd">
                <?php echo esc_html($current_label); ?>
              </button>

              <!-- [THUB_RESULTS_LANG_DROPDOWN] Pannello dropdown a lato -->
              <div id="thub-results-lang-dd"
                  role="listbox"
                  aria-label="Seleziona lingua risultati"
                  style="position:absolute; top:0; left:100%; margin-left:.5rem; min-width:260px; max-height:240px; overflow:auto; border:1px solid #cfcfe8; border-radius:.6rem; background:#fff; box-shadow:0 10px 28px rgba(0,0,0,.14); padding:.35rem; display:none; z-index:1200;">
                <?php foreach( $locales as $code => $label ): ?>
                  <button type="button"
                          role="option"
                          data-lang="<?php echo esc_attr($code); ?>"
                          aria-selected="<?php echo $code === $current_lang ? 'true' : 'false'; ?>"
                          style="display:block; width:100%; text-align:left; padding:.35rem .5rem; border:none; background:<?php echo $code === $current_lang ? '#f2eef7' : 'transparent'; ?>; border-radius:.35rem; cursor:pointer;">
                    <?php echo esc_html($label); ?>
                  </button>
                <?php endforeach; ?>
              </div>
            </span>
          </div>

          <!-- ============================
               [THUB_RESULTS_REGION_CTRL] Controllo "Cucina"
               ============================ -->
          <div>
            <strong>Cucina:</strong>
            <span id="thub-region-ctrl"
                  style="position:relative; display:inline-flex; align-items:center; gap:.35rem;">
              <button id="thub-results-region-btn"
                      class="thub-link"
                      type="button"
                      style="background:none;border:0;padding:0;cursor:pointer;"
                      aria-haspopup="listbox"
                      aria-expanded="false"
                      aria-controls="thub-results-region-dd">
                <?php echo esc_html($current_cuisine_label); ?>
              </button>

              <div id="thub-results-region-dd"
                   role="listbox"
                   aria-label="Seleziona cucina"
                   style="position:absolute; top:0; left:100%; margin-left:.5rem; min-width:260px; max-height:240px; overflow:auto; border:1px solid #cfcfe8; border-radius:.6rem; background:#fff; box-shadow:0 10px 28px rgba(0,0,0,.14); padding:.35rem; display:none; z-index:1200;">
                <?php foreach( $regions as $slug => $label ): ?>
                  <?php
                    // [THUB_RESULTS_CUISINE_ITEM] Solo nome cucina (Title Case), senza prefisso "Cucina "
                    $cname = mb_convert_case( preg_replace('/^Cucina\s+/i', '', $label), MB_CASE_TITLE, 'UTF-8');
                  ?>
                  <button type="button"
                          role="option"
                          data-region="<?php echo esc_attr($slug); ?>"
                          aria-selected="<?php echo $slug === $current_region ? 'true' : 'false'; ?>"
                          style="display:block; width:100%; text-align:left; padding:.35rem .5rem; border:none; background:<?php echo $slug === $current_region ? '#f2eef7' : 'transparent'; ?>; border-radius:.35rem; cursor:pointer;">
                    <?php echo esc_html($cname); ?>
                  </button>
                <?php endforeach; ?>
              </div>
            </span>
          </div>
        </div>

        <script>
        /* ===========================================================
           [THUB_RESULTS_DROPDOWN_JS] Dropdown accessibile lingua risultati
           =========================================================== */
        (function(){
          const $     = (s,c=document)=>c.querySelector(s);
          const btn   = $('#thub-results-lang-btn');
          const panel = $('#thub-results-lang-dd');
          if(!btn || !panel) return;

          const getOpts = () => Array.from(panel.querySelectorAll('[role="option"][data-lang]'));

          function toggle(show){
            panel.style.display = show ? 'block' : 'none';
            btn.setAttribute('aria-expanded', show ? 'true' : 'false');
            if(show){
              const sel = panel.querySelector('[aria-selected="true"]') || panel.querySelector('[role="option"]');
              sel && sel.focus();
            }
          }

          btn.addEventListener('click', ()=> {
            const open = panel.style.display !== 'none' && panel.style.display !== '';
            toggle(!open);
          });

          document.addEventListener('click', (e)=>{
            if( !panel.contains(e.target) && e.target !== btn ){
              toggle(false);
            }
          });

          btn.addEventListener('keydown', (e)=>{
            if(e.key === 'ArrowDown'){ e.preventDefault(); toggle(true); }
            if(e.key === 'Escape'){ toggle(false); }
          });

          panel.addEventListener('keydown', (e)=>{
            const opts = getOpts();
            const i    = opts.indexOf(document.activeElement);
            if(e.key === 'ArrowDown'){ e.preventDefault(); (opts[i+1] || opts[0]).focus(); }
            if(e.key === 'ArrowUp'){   e.preventDefault(); (opts[i-1] || opts[opts.length-1]).focus(); }
            if(e.key === 'Escape'){    e.preventDefault(); toggle(false); btn.focus(); }
            if(e.key === 'Enter' || e.key === ' '){
              e.preventDefault();
              document.activeElement?.click();
            }
          });

          panel.addEventListener('click', (e)=>{
            const opt = e.target.closest('[role="option"][data-lang]');
            if(!opt) return;
            const lang = opt.getAttribute('data-lang') || 'it';
            try{
              document.cookie = "thub_results_lang="+lang+"; path=/; max-age="+(60*60*24*365)+"; SameSite=Lax";
            }catch(_){}
            const url = new URL(window.location.href);
            url.searchParams.set('results_lang', lang);
            window.location.href = url.toString();
          });
        })();
        </script>

        <!-- [THUB_RESULTS_REGION_DROPDOWN_JS] Dropdown accessibile Cucina -->
        <script>
        (function(){
          const $     = (s,c=document)=>c.querySelector(s);
          const btn   = $('#thub-results-region-btn');
          const panel = $('#thub-results-region-dd');
          if(!btn || !panel) return;

          const getOpts = () => Array.from(panel.querySelectorAll('[role="option"][data-region]'));

          function toggle(show){
            panel.style.display = show ? 'block' : 'none';
            btn.setAttribute('aria-expanded', show ? 'true' : 'false');
            if(show){
              const sel = panel.querySelector('[aria-selected="true"]') || panel.querySelector('[role="option"]');
              sel && sel.focus();
            }
          }

          btn.addEventListener('click', ()=> {
            const open = panel.style.display !== 'none' && panel.style.display !== '';
            toggle(!open);
          });

          document.addEventListener('click', (e)=>{
            if( !panel.contains(e.target) && e.target !== btn ){
              toggle(false);
            }
          });

          btn.addEventListener('keydown', (e)=>{
            if(e.key === 'ArrowDown'){ e.preventDefault(); toggle(true); }
            if(e.key === 'Escape'){ toggle(false); }
          });

          panel.addEventListener('keydown', (e)=>{
            const opts = getOpts();
            const i    = opts.indexOf(document.activeElement);
            if(e.key === 'ArrowDown'){ e.preventDefault(); (opts[i+1] || opts[0]).focus(); }
            if(e.key === 'ArrowUp'){   e.preventDefault(); (opts[i-1] || opts[opts.length-1]).focus(); }
            if(e.key === 'Escape'){    e.preventDefault(); toggle(false); btn.focus(); }
            if(e.key === 'Enter' || e.key === ' '){
              e.preventDefault();
              document.activeElement?.click();
            }
          });

          panel.addEventListener('click', (e)=>{
            const opt = e.target.closest('[role="option"][data-region]');
            if(!opt) return;
            const region = opt.getAttribute('data-region') || '';
            try{
              document.cookie = "thub_results_region="+region+"; path=/; max-age="+(60*60*24*365)+"; SameSite=Lax";
            }catch(_){}
            const url = new URL(window.location.href);
            url.searchParams.set('results_region', region);
            window.location.href = url.toString();
          });
        })();
        </script>

        <?php /* SLOT #0: card "Sponsorizzata" (ADV • CPC) sempre in cima */ ?>
        <?php
          get_template_part('parts/thub-result-sponsored', null, $cpc);
          echo '<hr class="thub-results-separator" />';
        ?>

        <?php
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
        ?>

      <?php } else { ?>
        <?php
        // Nessun risultato nel CPT ricetta: mostriamo comunque lo slot CPC
        get_template_part('parts/thub-result-sponsored', null, $cpc);
        echo '<hr class="thub-results-separator" />';
        echo '<p>Nessuna ricetta trovata.</p>';
        ?>
      <?php } ?>

    <?php
    // ==========================================================
    // Ricerca “normale” (altri post type)
    // ==========================================================
    } else {
      ?>

      <?php
      /* [THUB_SEARCH_TOPINFO] Info risultati + lingua (ramo core) */
      $locales       = function_exists('thub_results_get_locales') ? thub_results_get_locales() : [];
      $override      = isset($_GET['results_lang']) ? strtolower(sanitize_text_field($_GET['results_lang'])) : '';
      $current_lang  = ( $override && isset($locales[$override]) )
        ? $override
        : ( function_exists('thub_get_user_results_lang') ? thub_get_user_results_lang( get_current_user_id() ) : 'it' );
      $current_label = $locales[$current_lang] ?? strtoupper($current_lang);
      global $wp_query;
      $results_count = isset($wp_query->post_count) ? (int) $wp_query->post_count : 0;

      /* [THUB_RESULTS_REGION_VARS] Prepara variabili Regione (cucina) */
      $regions        = function_exists('thub_get_results_region_choices') ? thub_get_results_region_choices() : [];
      $override_reg   = isset($_GET['results_region']) ? sanitize_key($_GET['results_region']) : '';
      $current_region = ( $override_reg && isset($regions[$override_reg]) )
        ? $override_reg
        : ( function_exists('thub_get_user_results_region') ? thub_get_user_results_region( get_current_user_id() ) : '' );
      $current_region_label = $current_region && isset($regions[$current_region]) ? $regions[$current_region] : 'Cucina';

      /* [THUB_RESULTS_CUISINE_LABEL] Rimuove "Cucina " e converte in Title Case */
      $current_cuisine_label = $current_region_label
        ? mb_convert_case( preg_replace('/^Cucina\s+/i', '', $current_region_label), MB_CASE_TITLE, 'UTF-8')
        : 'Cucina';
      ?>

      <div class="thub-search-topinfo"
          style="width:100%; padding:.5rem .75rem; display:flex; gap:1rem; align-items:center; font-size:small;">
        <div><strong>N. di risultati:</strong> <?php echo esc_html($results_count); ?></div>

        <div>
          <strong>Lingua dei risultati:</strong>
          <span id="thub-lang-ctrl"
                style="position:relative; display:inline-flex; align-items:center; gap:.35rem;">
            <button id="thub-results-lang-btn"
                    class="thub-link"
                    type="button"
                    style="background:none;border:0;padding:0;cursor:pointer;"
                    aria-haspopup="listbox"
                    aria-expanded="false"
                    aria-controls="thub-results-lang-dd">
              <?php echo esc_html($current_label); ?>
            </button>

            <div id="thub-results-lang-dd"
                role="listbox"
                aria-label="Seleziona lingua risultati"
                style="position:absolute; top:0; left:100%; margin-left:.5rem; min-width:260px; max-height:240px; overflow:auto; border:1px solid #cfcfe8; border-radius:.6rem; background:#fff; box-shadow:0 10px 28px rgba(0,0,0,.14); padding:.35rem; display:none; z-index:1200;">
              <?php foreach( $locales as $code => $label ): ?>
                <button type="button"
                        role="option"
                        data-lang="<?php echo esc_attr($code); ?>"
                        aria-selected="<?php echo $code === $current_lang ? 'true' : 'false'; ?>"
                        style="display:block; width:100%; text-align:left; padding:.35rem .5rem; border:none; background:<?php echo $code === $current_lang ? '#f2eef7' : 'transparent'; ?>; border-radius:.35rem; cursor:pointer;">
                  <?php echo esc_html($label); ?>
                </button>
              <?php endforeach; ?>
            </div>
          </span>
        </div>

        <!-- ============================
             [THUB_RESULTS_REGION_CTRL] Controllo "Cucina"
             ============================ -->
        <div>
          <strong>Cucina:</strong>
          <span id="thub-region-ctrl"
                style="position:relative; display:inline-flex; align-items:center; gap:.35rem;">
            <button id="thub-results-region-btn"
                    class="thub-link"
                    type="button"
                    style="background:none;border:0;padding:0;cursor:pointer;"
                    aria-haspopup="listbox"
                    aria-expanded="false"
                    aria-controls="thub-results-region-dd">
              <?php echo esc_html($current_cuisine_label); ?>
            </button>

            <div id="thub-results-region-dd"
                 role="listbox"
                 aria-label="Seleziona cucina"
                 style="position:absolute; top:0; left:100%; margin-left:.5rem; min-width:260px; max-height:240px; overflow:auto; border:1px solid #cfcfe8; border-radius:.6rem; background:#fff; box-shadow:0 10px 28px rgba(0,0,0,.14); padding:.35rem; display:none; z-index:1200;">
              <?php foreach( $regions as $slug => $label ): ?>
                <?php
                  // [THUB_RESULTS_CUISINE_ITEM] Solo nome cucina (Title Case)
                  $cname = mb_convert_case( preg_replace('/^Cucina\s+/i', '', $label), MB_CASE_TITLE, 'UTF-8');
                ?>
                <button type="button"
                        role="option"
                        data-region="<?php echo esc_attr($slug); ?>"
                        aria-selected="<?php echo $slug === $current_region ? 'true' : 'false'; ?>"
                        style="display:block; width:100%; text-align:left; padding:.35rem .5rem; border:none; background:<?php echo $slug === $current_region ? '#f2eef7' : 'transparent'; ?>; border-radius:.35rem; cursor:pointer;">
                  <?php echo esc_html($cname); ?>
                </button>
              <?php endforeach; ?>
            </div>
          </span>
        </div>
      </div>

      <script>
      (function(){
        const $     = (s,c=document)=>c.querySelector(s);
        const btn   = $('#thub-results-lang-btn');
        const panel = $('#thub-results-lang-dd');
        if(!btn || !panel) return;

        const getOpts = () => Array.from(panel.querySelectorAll('[role="option"][data-lang]'));

        function toggle(show){
          panel.style.display = show ? 'block' : 'none';
          btn.setAttribute('aria-expanded', show ? 'true' : 'false');
          if(show){
            const sel = panel.querySelector('[aria-selected="true"]') || panel.querySelector('[role="option"]');
            sel && sel.focus();
          }
        }

        btn.addEventListener('click', ()=> {
          const open = panel.style.display !== 'none' && panel.style.display !== '';
          toggle(!open);
        });

        document.addEventListener('click', (e)=>{
          if( !panel.contains(e.target) && e.target !== btn ){
            toggle(false);
          }
        });

        btn.addEventListener('keydown', (e)=>{
          if(e.key === 'ArrowDown'){ e.preventDefault(); toggle(true); }
          if(e.key === 'Escape'){ toggle(false); }
        });

        panel.addEventListener('keydown', (e)=>{
          const opts = getOpts();
          const i    = opts.indexOf(document.activeElement);
          if(e.key === 'ArrowDown'){ e.preventDefault(); (opts[i+1] || opts[0]).focus(); }
          if(e.key === 'ArrowUp'){   e.preventDefault(); (opts[i-1] || opts[opts.length-1]).focus(); }
          if(e.key === 'Escape'){    e.preventDefault(); toggle(false); btn.focus(); }
          if(e.key === 'Enter' || e.key === ' '){
            e.preventDefault();
            document.activeElement?.click();
          }
        });

        panel.addEventListener('click', (e)=>{
          const opt = e.target.closest('[role="option"][data-lang]');
          if(!opt) return;
          const lang = opt.getAttribute('data-lang') || 'it';
          try{
            document.cookie = "thub_results_lang="+lang+"; path=/; max-age="+(60*60*24*365)+"; SameSite=Lax";
          }catch(_){}
          const url = new URL(window.location.href);
          url.searchParams.set('results_lang', lang);
          window.location.href = url.toString();
        });
      })();
      </script>

      <!-- [THUB_RESULTS_REGION_DROPDOWN_JS] Dropdown accessibile Cucina -->
      <script>
      (function(){
        const $     = (s,c=document)=>c.querySelector(s);
        const btn   = $('#thub-results-region-btn');
        const panel = $('#thub-results-region-dd');
        if(!btn || !panel) return;

        const getOpts = () => Array.from(panel.querySelectorAll('[role="option"][data-region]'));

        function toggle(show){
          panel.style.display = show ? 'block' : 'none';
          btn.setAttribute('aria-expanded', show ? 'true' : 'false');
          if(show){
            const sel = panel.querySelector('[aria-selected="true"]') || panel.querySelector('[role="option"]');
            sel && sel.focus();
          }
        }

        btn.addEventListener('click', ()=> {
          const open = panel.style.display !== 'none' && panel.style.display !== '';
          toggle(!open);
        });

        document.addEventListener('click', (e)=>{
          if( !panel.contains(e.target) && e.target !== btn ){
            toggle(false);
          }
        });

        btn.addEventListener('keydown', (e)=>{
          if(e.key === 'ArrowDown'){ e.preventDefault(); toggle(true); }
          if(e.key === 'Escape'){ toggle(false); }
        });

        panel.addEventListener('keydown', (e)=>{
          const opts = getOpts();
          const i    = opts.indexOf(document.activeElement);
          if(e.key === 'ArrowDown'){ e.preventDefault(); (opts[i+1] || opts[0]).focus(); }
          if(e.key === 'ArrowUp'){   e.preventDefault(); (opts[i-1] || opts[opts.length-1]).focus(); }
          if(e.key === 'Escape'){    e.preventDefault(); toggle(false); btn.focus(); }
          if(e.key === 'Enter' || e.key === ' '){
            e.preventDefault();
            document.activeElement?.click();
          }
        });

        panel.addEventListener('click', (e)=>{
          const opt = e.target.closest('[role="option"][data-region]');
          if(!opt) return;
          const region = opt.getAttribute('data-region') || '';
          try{
            document.cookie = "thub_results_region="+region+"; path=/; max-age="+(60*60*24*365)+"; SameSite=Lax";
          }catch(_){}
          const url = new URL(window.location.href);
          url.searchParams.set('results_region', region);
          window.location.href = url.toString();
        });
      })();
      </script>

      <?php /* SLOT #0: CPC sempre in cima anche qui */ ?>
      <?php
        get_template_part('parts/thub-result-sponsored', null, $cpc);
        echo '<hr class="thub-results-separator" />';
      ?>

      <?php
      if (have_posts()) {
        while (have_posts()) {
          the_post();
          get_template_part('parts/thub-result', null, []); // post generici, no CPM
        }

        // the_posts_pagination() stampa già il <nav> esterno
        the_posts_pagination([
          'mid_size'  => 1,
          'prev_text' => __('« Precedenti', 'hello-elementor-child'),
          'next_text' => __('Successivi »', 'hello-elementor-child'),
        ]);

      } else {
        echo '<p>Nessun contenuto trovato.</p>';
      }
      ?>

    <?php } // fine else (ricerca core) ?>

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