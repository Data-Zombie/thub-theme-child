<?php
/* ============================================================
 * [THUB_VARIANTI] Carousel nativo (scroll-snap) – Pro teaser
 * Build 2.1.4 – 2025-08-20
 * - Frecce mostrate solo se > 1 card
 * - Free: testo troncato; Pro: testo completo
 * - Fallback thub_trim_chars se non definita (rete di sicurezza)
 * Requisiti:
 *  - ACF PRO: repeater 'varianti' con subfield: var_titolo, var_img (ID), var_descrizione
 *  - JS: assets/js/thub-recipe.js gestisce le frecce e lo scroll
 *  - CSS: assets/css/thub-recipe.css stili carousel + .is-hidden
 * ============================================================ */

/* [THUB_HELPERS_GUARD] evita fatal se funzioni assenti in contesti esterni
   Nota: in produzione la funzione reale è definita in functions.php */
if ( ! function_exists('thub_trim_chars') ) {
  function thub_trim_chars($s, $n){ return $s; } // passthrough neutro
}

/* [THUB_VARIANTI_DATA] recupera dati varianti + gating utente */
$vars     = function_exists('get_field') ? get_field('varianti') : [];
$is_pro   = function_exists('thub_is_pro_user') ? thub_is_pro_user() : false;
$has_many = is_array($vars) && count($vars) > 1;
?>

<section class="thub-box thub-variants" role="region" aria-label="Varianti della ricetta">
  <div class="thub-variants__header">
    <h2 class="thub-variants__title">Varianti</h2>
    <?php if ( $has_many ) : ?>
      <div class="thub-carousel__nav">
        <button class="thub-carousel__prev" aria-label="Scorri varianti precedenti" type="button">&lt;</button>
        <button class="thub-carousel__next" aria-label="Scorri varianti successive" type="button">&gt;</button>
      </div>
    <?php endif; ?>
  </div>

  <div class="thub-carousel" id="thub-variants-carousel">
    <?php if ( ! empty($vars) && is_array($vars) ) : ?>
      <?php foreach ( $vars as $v ) :
        $tit    = isset($v['var_titolo']) ? (string) $v['var_titolo'] : '';
        $desc   = isset($v['var_descrizione']) ? (string) $v['var_descrizione'] : '';
        $img_id = ! empty($v['var_img']) ? (int) $v['var_img'] : 0;
      ?>
        <article class="thub-variant">
          <?php
          /* [THUB_VARIANTI_IMG] Immagine (lazy) con helper WP */
          if ( $img_id ) {
            echo wp_get_attachment_image(
              $img_id,
              'medium',
              false,
              [
                'class'         => 'thub-variant__img', // [THUB_css_hook]
                'alt'           => $tit,                // WP esegue l'escaping dell'attributo
                'loading'       => 'lazy',
                'decoding'      => 'async',
                'fetchpriority' => 'low',
              ]
            );
          }
          ?>
          <h3 class="thub-variant__title"><?php echo esc_html( $tit ); ?></h3>

          <?php if ( $is_pro ) : ?>
            <p class="thub-variant__desc"><?php echo esc_html( $desc ); ?></p>
          <?php else : ?>
            <p class="thub-variant__desc">
              <?php echo esc_html( thub_trim_chars( $desc, 100 ) ); ?>
            </p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    <?php else : ?>
      <p class="thub-variants__empty">Nessuna variante disponibile.</p>
    <?php endif; ?>
  </div>

  <?php if ( ! $is_pro && ! empty($vars) ) : ?>
    <?php
    /* [THUB_VARIANTI_CTA] CTA Pro riutilizzabile */
    get_template_part( 'parts/ricetta/cta-pro' );
    ?>
  <?php endif; ?>
</section>