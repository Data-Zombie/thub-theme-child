<?php
/* ============================================================
 * [THUB_PASSAGGI] Step con gating Free/Pro — CANONICO
 * - Usa repeater 'passaggi_rep' (sub: passo_testo)
 * - Nessun uso di 'passaggi' legacy
 * ============================================================ */
$rows = function_exists('get_field') ? (array) get_field('passaggi_rep') : [];
$is_pro = function_exists('thub_is_pro_user') ? thub_is_pro_user() : false;
$limit  = function_exists('thub_free_steps_limit') ? thub_free_steps_limit() : 3;
$total  = count($rows);
$show   = $is_pro ? $total : min($limit, $total);
?>
<section class="thub-box thub-steps"><!-- [THUB_PASSAGGI_BOX] -->
  <h2>Preparazione</h2>
  <?php if ($total): ?>
    <ol>
      <?php for ($i=0; $i<$show; $i++):
        $t = trim((string)($rows[$i]['passo_testo'] ?? ''));
      ?>
        <li><?php echo wp_kses_post($t); ?></li>
      <?php endfor; ?>
    </ol>
    <?php if (!$is_pro && $total > $limit): ?>
      <?php get_template_part('parts/ricetta/cta-pro'); ?>
    <?php endif; ?>
  <?php else: ?>
    <p>Nessun passaggio inserito.</p>
  <?php endif; ?>
</section>