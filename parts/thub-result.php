<?php
/**
 * parts/thub-result.php
 * [THUB_RESULT] Card risultato standard
 * - ADV CPM sopra il titolo → "Nome Azienda suggerisce questa ricetta"
 * - Testo a sinistra: Titolo (50c), Descrizione (150c), Link (52c)
 * - Immagine a destra (featured)
 * - Tutto il clic va alla RICETTA (richiesta CPM)
 */

// ── [THUB_RESULT_ARGS] Lettura ARG (fallback manuale da search.php)
$args     = isset($args) && is_array($args) ? $args : [];
$arg_name = !empty($args['cpm_name']) ? (string) $args['cpm_name'] : '';
$arg_logo = !empty($args['cpm_logo']) ? $args['cpm_logo'] : '';

/* ============================================================
 * [THUB_RESULT_SPONSOR_RESOLVER]
 * Preferisci Sponsor unificato (thub_get_sponsor_data) + fallback ad $args
 * ============================================================ */
$spons = function_exists('thub_get_sponsor_data') ? thub_get_sponsor_data(get_the_ID()) : null;

$cpm_name = $arg_name;
$cpm_logo = $arg_logo;

if ($spons) {
  if (!empty($spons['nome'])) {
    $cpm_name = (string) $spons['nome'];
  }
  if (!empty($spons['logo'])) {
    if (is_numeric($spons['logo'])) {
      $img = wp_get_attachment_image_src((int)$spons['logo'], 'thub_sponsor_logo')
         ?: wp_get_attachment_image_src((int)$spons['logo'], 'thumbnail');
      if ($img) $cpm_logo = $img[0];
    } elseif (is_string($spons['logo'])) {
      $cpm_logo = esc_url($spons['logo']);
    }
  }
}

/* ============================================================
 * Helpers trunc e link
 * ============================================================ */
if (!function_exists('thub_mb_strim')) {
  function thub_mb_strim($text, $limit, $suffix='...'){
    $text = wp_strip_all_tags((string)$text);
    if (function_exists('mb_strimwidth')) return mb_strimwidth($text, 0, $limit, $suffix, 'UTF-8');
    return (strlen($text) > $limit) ? substr($text, 0, $limit).$suffix : $text;
  }
}

/** Link visibile (max 52c): host+path senza schema */
function thub_visible_link($url){
  $url = preg_replace('#^https?://#','',$url);
  $url = preg_replace('#^www\.#','',$url);
  return $url;
}

/* ============================================================
 * Dati card (troncature)
 * ============================================================ */
$post_link = get_permalink();
$title_50  = thub_mb_strim( get_the_title(), 50, '...' );
$excerpt   = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content() ), 30, '' );
$desc_150  = thub_mb_strim( $excerpt, 150, '...' );
$link_txt  = thub_mb_strim( thub_visible_link($post_link), 52, '...' );

/* Preferenza “Apri in nuova finestra” (server-side) */
$thub_newwin = function_exists('thub_results_open_in_new_window') && thub_results_open_in_new_window();
?>

<article <?php post_class('thub-result'); ?>><!-- [THUB_RESULT_CARD] -->

  <!-- // ADV CPM (opzionale): "Nome Azienda suggerisce questa ricetta" -->
  <?php if ($cpm_name || $cpm_logo): ?>
    <a class="thub-adv-cpm thub-result-link"
       <?php if($thub_newwin) echo 'target="_blank" rel="noopener"'; ?>
       href="<?php echo esc_url($post_link); ?>"><!-- CPM: redirect alla ricetta -->
      <div class="thub-adv-cpm-left">
        <?php if ($cpm_logo): ?>
          <img class="thub-adv-cpm-logo"
               src="<?php echo esc_url($cpm_logo); ?>"
               alt="<?php echo esc_attr($cpm_name); ?>"><!-- logo tondo -->
        <?php endif; ?>
      </div>
      <div class="thub-adv-cpm-right">
        <span class="thub-adv-cpm-text">
          <strong><?php echo $cpm_name ? esc_html($cpm_name) : 'Sponsor'; ?></strong>
          suggerisce questa ricetta
        </span>
      </div>
    </a>
  <?php endif; ?>

  <!-- // Corpo card: tutto cliccabile alla ricetta -->
  <a class="thub-result-body thub-result-link"
     <?php if($thub_newwin) echo 'target="_blank" rel="noopener"'; ?>
     href="<?php echo esc_url($post_link); ?>">
    <div class="thub-result-text">
      <h2 class="thub-result-title"><?php echo esc_html($title_50); ?></h2><!-- 50c -->
      <p class="thub-result-desc"><?php echo esc_html($desc_150); ?></p><!-- 150c -->
      <span class="thub-result-link"><?php echo esc_html($link_txt); ?></span><!-- 52c -->
    </div>
    <div class="thub-result-thumb">
      <?php if (has_post_thumbnail()): ?>
        <?php the_post_thumbnail('thumbnail', ['loading'=>'lazy','decoding'=>'async']); ?>
      <?php else: ?>
        <img src="data:image/svg+xml;utf8,<?php echo rawurlencode(
          "<svg xmlns='http://www.w3.org/2000/svg' width='96' height='96'>
             <rect width='100%' height='100%' fill='#f0f0f3'/>
             <text x='50%' y='54%' dominant-baseline='middle' text-anchor='middle'
                   font-family='Arial' font-size='10' fill='#999'>No Image</text>
           </svg>"
        ); ?>" alt="">
      <?php endif; ?>
    </div>
  </a>
</article>