<?php
/**
 * parts/thub-result-sponsored.php
 * [THUB_RESULT_SPONSORED] Card Sponsorizzata (ADV • CPC) — slot #0
 *
 * Args ($args) accettati:
 *  - cpc_click_url    (string|null) URL di destinazione ADV
 *  - cpc_logo         (string|null) URL logo sponsor
 *  - cpc_name         (string|null) Nome sponsor (es. "Nome Azienda")
 *  - cpc_note         (string|null) Nota breve (es. "Promo attiva")
 *  - cpc_title        (string|null) Titolo card
 *  - cpc_desc         (string|null) Descrizione card
 *  - cpc_display_link (string|null) Link “visibile” (es. dominio)
 *  - cpc_thumb        (string|null) URL immagine destra (background)
 */

if ( ! defined('ABSPATH') ) exit;

// -------------------------------
// [THUB_ARGS] Parsing argomenti
// -------------------------------
$args   = isset($args) && is_array($args) ? $args : [];
$click  = !empty($args['cpc_click_url'])    ? esc_url($args['cpc_click_url'])    : '';
$logo   = !empty($args['cpc_logo'])         ? esc_url($args['cpc_logo'])         : '';
$name   = !empty($args['cpc_name'])         ? wp_kses_post($args['cpc_name'])     : 'Sponsorizzata';
$note   = !empty($args['cpc_note'])         ? wp_kses_post($args['cpc_note'])     : '';

$title  = !empty($args['cpc_title'])        ? wp_kses_post($args['cpc_title'])    : 'Scopri la ricetta in evidenza';
$desc   = !empty($args['cpc_desc'])         ? wp_kses_post($args['cpc_desc'])     : 'Una proposta speciale consigliata dal nostro partner. Ingredienti e passaggi curati dagli chef.';
$disp   = !empty($args['cpc_display_link']) ? esc_html($args['cpc_display_link']) : '';
$thumb  = !empty($args['cpc_thumb'])        ? esc_url($args['cpc_thumb'])         : '';

// Se non passato, prova a ricavare il dominio dall’URL di click
if (!$disp && $click) {
  $host = parse_url($click, PHP_URL_HOST);
  if ($host) $disp = $host;
}

// --------------------------------------------
// [THUB_RESULTS_NEWWIN_LOCAL] Preferenza newwin
// --------------------------------------------
$thub_newwin = function_exists('thub_results_open_in_new_window') && thub_results_open_in_new_window();

// --------------------------------------------
// [THUB_RESULT_SPONSORED_WRAP] <a> o <div> + target
// --------------------------------------------
$wrap_class = 'thub-cpc-wrap' . ( $click ? ' thub-result-link' : '' );

if ($click) {
  $extra     = $thub_newwin ? ' target="_blank" rel="noopener"' : '';
  $tag_open  = '<a class="'.esc_attr($wrap_class).'"'.$extra.' href="'.$click.'">';
  $tag_close = '</a>';
} else {
  $tag_open  = '<div class="'.esc_attr($wrap_class).'">';
  $tag_close = '</div>';
}
?>

<article <?php post_class('thub-result thub-result--cpc'); ?>><!-- [THUB_RESULT_SPONSORED_CARD] -->
  <span class="thub-cpc-badge" aria-label="Sponsorizzato">ADV • CPC</span>

  <?php echo $tag_open; ?>
    <div class="thub-cpc-inner"><!-- [THUB_CPC_INNER] layout -->
      <div class="thub-cpc-left">
        <!-- [THUB_CPC_ADV_BAR] Sponsor + nota -->
        <div class="thub-cpc-adv">
          <?php if ($logo): ?>
            <img class="thub-cpc-logo"
                 src="<?php echo $logo; ?>"
                 alt="<?php echo esc_attr( wp_strip_all_tags($name) ); ?>"
                 loading="lazy" decoding="async" />
          <?php else: ?>
            <span class="thub-cpc-logo" aria-hidden="true" style="background:#fff;"></span>
          <?php endif; ?>

          <strong class="thub-cpc-name"><?php echo esc_html( wp_strip_all_tags($name) ); ?></strong>

          <?php if ($note): ?>
            <span class="thub-cpc-note"><?php echo esc_html( wp_strip_all_tags($note) ); ?></span>
          <?php endif; ?>
        </div>

        <!-- [THUB_CPC_CONTENT] Testi -->
        <h2 class="thub-cpc-title"><?php echo $title; ?></h2>

        <?php if ($desc): ?>
          <p class="thub-cpc-desc"><?php echo $desc; ?></p>
        <?php endif; ?>

        <?php if ($disp): ?>
          <span class="thub-cpc-link"><?php echo $disp; ?></span>
        <?php endif; ?>
      </div>

      <div class="thub-cpc-right">
        <!-- [THUB_CPC_THUMB] fondo immagine (opzionale) -->
        <div class="thub-cpc-thumb" aria-hidden="true"
             <?php if($thumb) echo 'style="background-image:url(\''.$thumb.'\');background-size:cover;background-position:center;"'; ?>>
        </div>
      </div>
    </div>
  <?php echo $tag_close; ?>
</article>