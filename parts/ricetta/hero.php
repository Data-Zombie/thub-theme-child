<?php
/* [THUB_HERO] Hero singola ricetta: titolo, sponsor, intro, cover */
$id = get_the_ID();
$intro = get_field('intro_breve') ?: '';
$title_50  = function_exists('thub_trim_chars') ? thub_trim_chars(get_the_title(),50) : get_the_title();
$intro_150 = function_exists('thub_trim_chars') ? thub_trim_chars($intro,150) : $intro;

$spons = function_exists('thub_get_sponsor_data') ? thub_get_sponsor_data($id) : null;
$cover = get_the_post_thumbnail_url($id,'large');
?>
<section class="thub-hero">
  <div class="thub-hero__text">
    <h1 class="thub-hero__title"><?php echo esc_html($title_50); ?></h1>

    <?php if($spons): 
      $claim = sprintf($spons['claim'], esc_html($spons['nome']));
      $logo_id = $spons['logo']; $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id,'thumbnail') : '';
    ?>
      <div class="thub-sponsor-claim">
        <?php if($logo_url): ?>
          <img class="thub-sponsor-claim__logo" src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($spons['nome']); ?>">
        <?php endif; ?>
        <span class="thub-sponsor-claim__text"><?php echo esc_html($claim); ?></span>
        <?php if(!empty($spons['url'])): ?>
          <a class="thub-sponsor-claim__link" href="<?php echo esc_url($spons['url']); ?>" rel="sponsored noopener">Scopri</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if($intro_150): ?>
      <p class="thub-hero__intro"><?php echo esc_html($intro_150); ?></p>
    <?php endif; ?>

    <ul class="thub-meta">
      <li><strong>Prep:</strong> <?php echo esc_html(get_field('tempo_di_preparazione')); ?></li>
      <li><strong>Cottura:</strong> <?php echo esc_html(get_field('tempo_di_cottura')); ?></li>
      <li><strong>Porzioni:</strong> <span id="thub-porzioni-label"><?php echo (int) (get_field('porzioni_base') ?: 1); ?></span></li>
      <li><strong>Kcal/porz:</strong> <span id="thub-kcal-porz"><?php echo esc_html(get_field('kcal_per_porz') ?: '—'); ?></span></li>
      <li><strong>Kcal tot:</strong> <span id="thub-kcal-tot">—</span></li>
    </ul>

    <!-- [THUB_PORZIONI_UI] UI scaler porzioni -->
    <div class="thub-porzioni-ui" 
         data-base-porzioni="<?php echo (int) (get_field('porzioni_base') ?: 1); ?>" 
         data-kcal-porz="<?php echo (float) (get_field('kcal_per_porz') ?: 0); ?>">
      <button type="button" class="thub-porz-minus" aria-label="Diminuisci porzioni">–</button>
      <input type="number" id="thub-porzioni" value="<?php echo (int) (get_field('porzioni_base') ?: 1); ?>" min="1" step="1" />
      <button type="button" class="thub-porz-plus" aria-label="Aumenta porzioni">+</button>
      <div class="thub-porz-quick">
        <button type="button" data-q="1">1</button>
        <button type="button" data-q="2">2</button>
        <button type="button" data-q="4">4</button>
        <button type="button" data-q="6">6</button>
      </div>
    </div>
  </div>

  <?php if($cover): ?>
    <div class="thub-hero__media">
      <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
    </div>
  <?php endif; ?>
</section>