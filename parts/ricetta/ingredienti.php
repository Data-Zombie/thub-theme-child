<?php
/* [THUB_INGREDIENTI] Lista ingredienti (repeater) con data-* per scaling */
$rows = function_exists('get_field') ? get_field('ingredienti_rep') : [];
$porz_base = (int) (get_field('porzioni_base') ?: 1);
?>
<section class="thub-box thub-ingredients">
  <h2>Ingredienti (per <span id="thub-porz-dyn"><?php echo $porz_base; ?></span> porzione<?php echo $porz_base>1?'i':''; ?>)</h2>
  <ul id="thub-ingredienti-list">
    <?php if($rows): foreach($rows as $r):
      $nome = $r['ing_nome'] ?? '';
      $qta  = (float) ($r['ing_qta'] ?? 0);
      $unit = $r['ing_unita'] ?? '';
      if (strcasecmp($unit, 'altro') === 0) {
        $unit = $r['ing_unita_altro'] ?? '';
      }
      ?>
      <li class="thub-ing"
          data-base-qta="<?php echo esc_attr($qta); ?>"
          data-unit="<?php echo esc_attr($unit); ?>">
        <span class="thub-ing__qta"><?php echo $qta; ?></span>
        <span class="thub-ing__unit"><?php echo esc_html($unit); ?></span>
        <span class="thub-ing__nome"><?php echo esc_html($nome); ?></span>
      </li>
    <?php endforeach; else: ?>
      <li>Nessun ingrediente inserito.</li>
    <?php endif; ?>
  </ul>
</section>