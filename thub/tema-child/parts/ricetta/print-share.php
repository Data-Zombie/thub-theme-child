<?php
/* [THUB_PRINT_SHARE] Pulsanti con gating JS */
$is_pro = function_exists('thub_is_pro_user') ? thub_is_pro_user() : false;
?>
<div class="thub-actions">
  <button class="thub-btn thub-btn--print <?php echo $is_pro?'':'is-locked'; ?>" data-lock-msg="Disponibile con Pro">Stampa</button>
  <button class="thub-btn thub-btn--share <?php echo $is_pro?'':'is-locked'; ?>" data-lock-msg="Disponibile con Pro">Condividi</button>
</div>