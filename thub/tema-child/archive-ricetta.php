<?php
/* ===========================
 * [THUB_ARCHIVE] Archivio CPT "ricetta" con filtri tassonomici
 * File: archive-ricetta.php
 * =========================== */
get_header();
?>
<main class="container thub-archive-ricetta">
  <h1>Ricette</h1>

  <?php
  /* ============================================
   * [THUB_FILTERS] Form filtri Portata / Cucina / Dieta
   * - Legge i valori correnti da query vars o GET
   * - Mostra solo i termini non vuoti
   * - Mantiene eventuale ricerca testo (?s=) e post_type
   * ============================================ */

  // Valori correnti (query_var → fallback GET)
  $sel_portata = get_query_var('portata') ?: ( isset($_GET['portata']) ? sanitize_text_field(wp_unslash($_GET['portata'])) : '' );
  $sel_cucina  = get_query_var('cucina')  ?: ( isset($_GET['cucina'])  ? sanitize_text_field(wp_unslash($_GET['cucina']))  : '' );
  $sel_dieta   = get_query_var('dieta')   ?: ( isset($_GET['dieta'])   ? sanitize_text_field(wp_unslash($_GET['dieta']))   : '' );

  // Helper termini (ordinati per nome, solo non-vuoti)
  $terms_portata = get_terms(['taxonomy'=>'portata','hide_empty'=>true,'orderby'=>'name','order'=>'ASC']);
  $terms_cucina  = get_terms(['taxonomy'=>'cucina','hide_empty'=>true,'orderby'=>'name','order'=>'ASC']);
  $terms_dieta   = get_terms(['taxonomy'=>'dieta','hide_empty'=>true,'orderby'=>'name','order'=>'ASC']);

  if ( is_wp_error($terms_portata) ) $terms_portata = [];
  if ( is_wp_error($terms_cucina)  ) $terms_cucina  = [];
  if ( is_wp_error($terms_dieta)   ) $terms_dieta   = [];
  ?>

  <!-- [THUB_FILTERS] Barra filtri -->
  <form class="thub-filters" method="get" action="<?php echo esc_url( get_post_type_archive_link('ricetta') ); ?>">
    <!-- Portata -->
    <label for="f-portata" class="screen-reader-text">Portata</label>
    <select id="f-portata" name="portata">
      <option value="">Tutte le portate</option>
      <?php foreach($terms_portata as $t): ?>
        <option value="<?php echo esc_attr($t->slug); ?>" <?php selected($t->slug, $sel_portata); ?>>
          <?php echo esc_html($t->name); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Cucina -->
    <label for="f-cucina" class="screen-reader-text">Cucina</label>
    <select id="f-cucina" name="cucina">
      <option value="">Tutte le cucine</option>
      <?php foreach($terms_cucina as $t): ?>
        <option value="<?php echo esc_attr($t->slug); ?>" <?php selected($t->slug, $sel_cucina); ?>>
          <?php echo esc_html($t->name); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Dieta -->
    <label for="f-dieta" class="screen-reader-text">Dieta</label>
    <select id="f-dieta" name="dieta">
      <option value="">Tutte le diete</option>
      <?php foreach($terms_dieta as $t): ?>
        <option value="<?php echo esc_attr($t->slug); ?>" <?php selected($t->slug, $sel_dieta); ?>>
          <?php echo esc_html($t->name); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Pulsanti -->
    <button type="submit" class="button"><?php esc_html_e('Filtra','thub'); ?></button>
    <a class="button thub-filters__reset" href="<?php echo esc_url( get_post_type_archive_link('ricetta') ); ?>">Reset</a>

    <?php
    // Mantieni eventuale ricerca testo ?s= e post_type se presenti
    if (!empty($_GET['s']))         echo '<input type="hidden" name="s" value="'.esc_attr($_GET['s']).'">';
    if (!empty($_GET['post_type'])) echo '<input type="hidden" name="post_type" value="'.esc_attr($_GET['post_type']).'">';
    ?>
  </form>

  <?php if (have_posts()): ?>
    <ul class="ricette-archive">
      <?php while (have_posts()): the_post(); ?>
        <li class="ricetta-card">
          <a href="<?php the_permalink(); ?>">
            <?php if (has_post_thumbnail()) the_post_thumbnail('medium'); ?>
            <h2><?php the_title(); ?></h2>
            <p><?php echo esc_html( get_the_excerpt() ); ?></p>
          </a>
        </li>
      <?php endwhile; ?>
    </ul>

    <div class="pagination"><?php the_posts_pagination(); ?></div>
  <?php else: ?>
    <p>Nessuna ricetta disponibile.</p>
  <?php endif; ?>
</main>
<?php get_footer(); ?>