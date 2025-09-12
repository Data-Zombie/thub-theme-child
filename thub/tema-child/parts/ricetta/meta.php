<?php
/* [THUB_META] Meta tassonomie + vini suggeriti */
$id = get_the_ID();
$cucina = wp_get_post_terms($id,'cucina',['fields'=>'names']);
$portata= wp_get_post_terms($id,'portata',['fields'=>'names']);
$dieta  = wp_get_post_terms($id,'dieta',['fields'=>'names']);
$vini   = function_exists('get_field') ? get_field('vini',$id) : [];
?>
<aside class="thub-box thub-meta-block">
  <ul class="thub-tags">
    <?php if($cucina): ?><li><strong>Cucina:</strong> <?php echo esc_html(implode(', ',$cucina)); ?></li><?php endif; ?>
    <?php if($portata): ?><li><strong>Portata:</strong> <?php echo esc_html(implode(', ',$portata)); ?></li><?php endif; ?>
    <?php if($dieta): ?><li><strong>Ricetta:</strong> <?php echo esc_html(implode(', ',$dieta)); ?></li><?php endif; ?>
  </ul>

  <?php if($vini): ?>
    <div class="thub-wines">
      <h3>Vini suggeriti</h3>
      <ul>
        <?php foreach($vini as $w):
          $nome = $w['vino_nome'] ?? '';
          $den  = $w['vino_denominazione'] ?? '';
          $url  = $w['vino_link'] ?? '';
          ?>
          <li><?php echo esc_html($nome); ?><?php echo $den? ' – '.esc_html($den):''; ?><?php if($url): ?> <a href="<?php echo esc_url($url); ?>" rel="nofollow">scheda</a><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</aside>