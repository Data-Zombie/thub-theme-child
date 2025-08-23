<?php
/**
 * parts/thub-result-sponsored.php
 * Card "Sponsorizzata" (ADV • CPC) da mostrare come primo blocco.
 *
 * Args ($args):
 *  - cpc_click_url (string|null) URL click di destinazione ADV; se vuoto → non-linka o fallback
 *  - cpc_logo      (string|null) URL logo sponsor
 *  - cpc_name      (string|null) Nome sponsor (es. "Nome Azienda")
 *  - cpc_note      (string|null) Nota breve (es. "Promo attiva")
 */
$args = isset($args) && is_array($args) ? $args : [];
$click  = !empty($args['cpc_click_url']) ? esc_url($args['cpc_click_url']) : '';
$logo   = !empty($args['cpc_logo']) ? esc_url($args['cpc_logo']) : '';
$name   = !empty($args['cpc_name']) ? esc_html($args['cpc_name']) : 'Sponsorizzata';
$note   = !empty($args['cpc_note']) ? esc_html($args['cpc_note']) : '';

$tag_open  = $click ? '<a class="thub-cpc-wrap" href="'.$click.'">' : '<div class="thub-cpc-wrap">';
$tag_close = $click ? '</a>' : '</div>';
?>
<article class="thub-result thub-result--cpc"><!-- // card CPC distinta -->
  <div class="thub-cpc-badge">Sponsorizzata</div><!-- // badge in alto a destra -->

  <?php echo $tag_open; ?><!-- // rende tutta la card cliccabile se $click -->
    <div class="thub-cpc-inner">
      <div class="thub-cpc-left">
        <div class="thub-cpc-adv">
          <?php if ($logo): ?>
            <img class="thub-cpc-logo" src="<?php echo $logo; ?>" alt="<?php echo esc_attr($name); ?>"><!-- // logo tondo -->
          <?php endif; ?>
          <div class="thub-cpc-meta">
            <strong class="thub-cpc-name"><?php echo $name; ?></strong>
            <?php if ($note): ?><span class="thub-cpc-note"><?php echo $note; ?></span><?php endif; ?>
          </div>
        </div>

        <!-- // Titolo/descr/URL dummy (puoi sostituirli con una ricetta in evidenza o un contenuto CPC dedicato) -->
        <h2 class="thub-cpc-title"><!-- // titolo -->
          Scopri la ricetta in evidenza
        </h2>
        <p class="thub-cpc-desc"><!-- // descrizione -->
          Una proposta speciale consigliata dal nostro partner. Ingredienti e passaggi curati dagli chef.
        </p>
        <span class="thub-cpc-link">partner.it/landing</span><!-- // link visibile -->
      </div>

      <div class="thub-cpc-right">
        <div class="thub-cpc-thumb" aria-hidden="true"></div><!-- // box immagine full-bleed (background via CSS) -->
      </div>
    </div>
  <?php echo $tag_close; ?>
</article>