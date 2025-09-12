<?php
/**
 * [THUB_MODAL_APPS] Partial: modal Tastierino (griglia scorciatoie)
 * - 6 riquadri: Account, Amministrazione, Gestione profilo attività, Ads, Analytics, Classroom
 * - L’icona Account clona l’avatar dell’header via JS (slot data-thub-avatar-clone)
 * - Si appoggia a CSS .thub-dropdown e blocco [THUB_MODAL_STYLES] in style.css
 * - L’apertura/chiusura è gestita dallo script in footer [THUB_MODAL_JS]
 */
?>
<nav class="thub-dropdown thub-apps-dropdown" id="thub-dropdown-apps" role="menu" aria-hidden="true">
  <div class="thub-apps-grid">
  <!-- Account — avatar pieno nel cerchio se loggato con foto, altrimenti icona -->
  <a class="thub-apps-item is-acc" href="<?php echo esc_url( home_url('/account') ); ?>" aria-label="Account">
    <div class="thub-apps-icon">
      <?php if ( is_user_logged_in() ) :
        $uid = get_current_user_id();
        $avatar_url = function_exists('thub_get_user_avatar_url') ? thub_get_user_avatar_url($uid, 'thumbnail') : '';
        if ($avatar_url): ?>
          <!-- Foto profilo: riempi tutto il cerchio -->
          <img class="thub-apps-avatar-img" src="<?php echo esc_url($avatar_url); ?>" alt="Il tuo account">
        <?php else: ?>
          <!-- Nessuna foto: icona generica utente -->
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="4"></circle>
            <path d="M4 20c0-4 4-6 8-6s8 2 8 6" fill="none" stroke="currentColor" stroke-width="2"></path>
          </svg>
        <?php endif; ?>
      <?php else: ?>
        <!-- Utente NON loggato: icona generica utente -->
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="12" cy="8" r="4"></circle>
          <path d="M4 20c0-4 4-6 8-6s8 2 8 6" fill="none" stroke="currentColor" stroke-width="2"></path>
        </svg>
      <?php endif; ?>
    </div>
    <span class="thub-apps-label" data-label-full="Account">Account</span>
  </a>

  <!-- Amministrazione -->
  <a class="thub-apps-item is-admin" href="<?php echo esc_url( home_url('/console') ); ?>" aria-label="Amministrazione">
    <div class="thub-apps-icon">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 3l9 6H3l9-6Z" />
        <path d="M5 10v8M19 10v8M3 18h18" />
        <path d="M9 14h6" />
      </svg>
    </div>
    <span class="thub-apps-label" data-label-full="Amministrazione">Amministrazione</span>
  </a>

  <!-- Attività -->
<a class="thub-apps-item is-profile" href="<?php echo esc_url( home_url('/gestione-profilo-attivita') ); ?>" aria-label="Attività">
  <div class="thub-apps-icon">
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M3 7h18l-1 4H4L3 7Z" />
      <path d="M5 11v7h14v-7" />
      <path d="M9 18v-4h6v4" />
    </svg>
  </div>
  <span class="thub-apps-label" data-label-full="Attività">Attività</span>
</a>

  <!-- Ads -->
  <a class="thub-apps-item is-ads" href="<?php echo esc_url( home_url('/ads') ); ?>" aria-label="Ads">
    <div class="thub-apps-icon">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <rect x="3" y="4" width="18" height="14" rx="2" ry="2"></rect>
        <path d="M7 8h7M7 12h10M7 16h5" />
        <circle cx="19" cy="18.5" r="2.5"></circle>
      </svg>
    </div>
    <span class="thub-apps-label" data-label-full="Ads">Ads</span>
  </a>

  <!-- Analytics -->
  <a class="thub-apps-item is-analytics" href="<?php echo esc_url( home_url('/analytics') ); ?>" aria-label="Analytics">
    <div class="thub-apps-icon">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <polyline points="3,17 9,11 13,14 21,6" />
        <circle cx="9" cy="11" r="1.6"></circle>
        <circle cx="13" cy="14" r="1.6"></circle>
        <circle cx="21" cy="6" r="1.6"></circle>
      </svg>
    </div>
    <span class="thub-apps-label" data-label-full="Analytics">Analytics</span>
  </a>

  <!-- Classroom -->
  <a class="thub-apps-item is-classroom" href="<?php echo esc_url( home_url('/classroom') ); ?>" aria-label="Classroom">
    <div class="thub-apps-icon">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <rect x="3" y="5" width="18" height="12" rx="2" ry="2"></rect>
        <path d="M8 11a4 4 0 0 1 8 0" />
        <path d="M6 17c1.5-2 4-3 6-3s4.4 1 6 3" />
      </svg>
    </div>
    <span class="thub-apps-label" data-label-full="Classroom">Classroom</span>
  </a>
</div>
</nav>