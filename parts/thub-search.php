<?php
/**
 * parts/thub-search.php
 * Component riutilizzabile della barra di ricerca THUB.
 * Varianti:
 *  - 'stacked' (homepage): barra + bottone sotto "Cerca con T-Hub", lente decorativa a sinistra
 *  - 'inline'  (header interno): barra con microfono + lente (submit) dentro
 *
 * Uso:
 *  get_template_part('parts/thub-search', null, ['variant' => 'stacked']);
 *  get_template_part('parts/thub-search', null, ['variant' => 'inline']);
 */
$variant = $args['variant'] ?? 'stacked';
$is_inline = ($variant === 'inline');
?>
<form role="search" method="get" class="thub-search thub-search--<?php echo esc_attr($is_inline ? 'inline' : 'stacked'); ?>" action="<?php echo esc_url( home_url('/') ); ?>">
  <input type="hidden" name="post_type" value="ricetta"> <!-- limita la ricerca alle ricette -->
  <div class="thub-search-field <?php echo $is_inline ? 'thub-search-field--inline' : ''; ?>">
    <?php if (!$is_inline): ?>
      <!-- Lente decorativa a sinistra (homepage) -->
      <svg class="thub-search-icon" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2" />
        <line x1="16.5" y1="16.5" x2="22" y2="22" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
      </svg>
    <?php endif; ?>

    <!-- Campo (senza placeholder) -->
    <input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" aria-label="Cerca">

    <!-- Microfono (dettatura) -->
    <button class="thub-mic-btn" type="button" aria-label="Dettatura vocale" title="Dettatura vocale">
      <svg class="thub-mic-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <!-- capsula microfono -->
        <path d="M12 14a3 3 0 0 0 3-3V7a3 3 0 1 0-6 0v4a3 3 0 0 0 3 3z" fill="currentColor"/>
        <!-- arco -->
        <path d="M5 11a7 7 0 0 0 14 0" fill="none" stroke="currentColor" stroke-width="2"/>
        <!-- stelo + base -->
        <line x1="12" y1="18" x2="12" y2="22" stroke="currentColor" stroke-width="2"/>
        <line x1="8" y1="22" x2="16" y2="22" stroke="currentColor" stroke-width="2"/>
      </svg>
    </button>

    <?php if ($is_inline): ?>
      <!-- Lente (submit) dentro la barra (header interno) -->
      <button class="thub-search-submit thub-search-submit--icon" type="submit" aria-label="Cerca">
        <svg class="thub-search-icon" viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2" />
          <line x1="16.5" y1="16.5" x2="22" y2="22" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
      </button>
    <?php endif; ?>
  </div>

  <?php if (!$is_inline): ?>
    <!-- Bottone sotto la barra (homepage) -->
    <button class="thub-search-submit" type="submit">Cerca con T-Hub</button>
  <?php endif; ?>
</form>