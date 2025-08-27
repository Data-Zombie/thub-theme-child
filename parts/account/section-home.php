<?php
/**
 * =====================================================================
 *  [THUB_SECTION_HOME] — Dashboard Home (Canvas Account)
 *  Percorso: wp-content/themes/hello-theme-child/parts/account/section-home.php
 * ---------------------------------------------------------------------
 *  Caratteristiche:
 *   - Griglia 2×2 (Box 1-2 in riga 1, Box 3-4 in riga 2) con allineamento
 *     verticale uniforme (separatori e link allineati tra le card).
 *   - Avatar centrale cliccabile: riutilizza la stessa logica di
 *     section-informazioni-personali.php (classe .thub-profile-avatar-trigger).
 *     Al click: dispatch evento 'thub:openAccountDropdown' + click su
 *     #thub-acc-avatar-wrap all'interno del dropdown per avviare l'upload.
 *   - Sicurezza: riuso controller condiviso thub_get_security_summary($user_id)
 *     se presente; fallback “protetto” se assente.
 *   - CSS e JS unificati all'interno del file (come richiesto).
 *
 *  Dipendenze/funzionalità correlate:
 *   - Helper avatar: thub_get_user_avatar_url($user_id) (già nel tema).
 *   - Dropdown Account/Modal Light: deve esporre l’elemento
 *     #thub-acc-avatar-wrap e reagire a 'thub:openAccountDropdown'.
 *   - Router Canvas: page-thub-canvas.php carica questo partial quando
 *     ?section=home (o slug equivalente).
 * =====================================================================
 */

if ( ! defined('ABSPATH') ) { exit; }

/* ============================================================
   [THUB_USER] — Recupero utente e dati base nome/cognome
   ============================================================ */
$current_user = wp_get_current_user();
$user_id      = $current_user ? intval($current_user->ID) : 0;
$first_name   = trim( get_user_meta($user_id, 'first_name', true) );
$last_name    = trim( get_user_meta($user_id, 'last_name',  true) );
$display_name = trim( $first_name . ' ' . $last_name );
if ( $display_name === '' ) {
  $display_name = $current_user ? $current_user->display_name : __('utente', 'thub');
}

/* ============================================================
   [THUB_AVATAR] — URL immagine profilo (helper → ACF/user_meta → Gravatar)
   ============================================================ */
$avatar_url = function_exists('thub_get_user_avatar_url')
  ? thub_get_user_avatar_url($user_id)
  : ( get_avatar_url($user_id) ?: '' );

/* ============================================================
   [THUB_SECURITY] — Riepilogo sicurezza (controller condiviso)
   - Se esiste thub_get_security_summary($user_id) lo usiamo.
   - Altrimenti fallback “protetto”.
   ============================================================ */
$sec_status = 'ok';
$sec_title  = __('Il tuo account è protetto', 'thub');
$sec_text   = __('Lo strumento di controllo della sicurezza ha esaminato il tuo account e non ha trovato azioni consigliate.', 'thub');

if ( function_exists('thub_get_security_summary') ) {
  $sec = thub_get_security_summary($user_id);
  if ( is_array($sec) ) {
    $sec_status = isset($sec['status']) ? $sec['status'] : $sec_status;     // 'ok' | 'attention'
    $sec_title  = isset($sec['title'])  ? $sec['title']  : $sec_title;
    $sec_text   = isset($sec['text'])   ? $sec['text']   : $sec_text;
  }
}

?>
<!-- ===================================================================
     [THUB_HOME_STYLES] — CSS unificato (griglia 2×2 + allineamento verticale)
     =================================================================== -->
<style>
  /* ================================
     [THUB_HOME] Token locali (solo per questo file)
     ================================ */
  :root{
    --thub-home-gap: 16px;                 /* gap griglia principale               */
    --thub-home-radius: .75rem;            /* raggio card                          */
    --thub-home-border: #e6e6ea;           /* colore bordo card                    */
    --thub-home-sep: #efeff2;              /* colore separatore card               */
  }

  /* ================================
     [THUB_HOME_GRID] Griglia 2×2
     - 1 col sotto 700px
     - 2 col da 700px in su
     ================================ */
  .thub-home__grid{
    display: grid !important;
    grid-template-columns: 1fr;           /* mobile: una colonna */
    gap: var(--thub-home-gap);
    align-items: stretch;                 /* elementi allineati per riga */
  }
  @media (min-width: 700px){
    .thub-home__grid{
      grid-template-columns: repeat(2, minmax(0, 1fr)) !important; /* desktop: 2 colonne */
    }
  }

  /* ================================
     [THUB_HOME_CARD] Card con contenuto allineato
     - layout interno: 1fr auto (testo | media)
     - titoli e testi in alto, separatore, link sempre in basso
     ================================ */
  .thub-home__card{
    display: grid;
    grid-template-columns: 1fr auto;      /* contenuto a sx | illustrazione a dx */
    gap: 14px;
    border: 1px solid var(--thub-home-border);
    border-radius: var(--thub-home-radius);
    padding: 1rem;
    background: #fff;
    min-width: 0;                          /* evita overflow in grid */
    align-items: stretch;
  }

  .thub-home__content{
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  .thub-home__content h3{
    margin: 0 0 .4rem;
    font-size: 1.05rem;
    line-height: 1.35;
  }

  .thub-home__content p{
    margin: 0;
    color: #444;
  }

  .thub-home__sep{
    height: 1px;
    background: var(--thub-home-sep);
    margin: .75rem 0 .75rem;              /* separatori allineati tra card */
  }

  .thub-home__link{
    margin-top: auto;                     /* spinge il link in basso (allineamento verticale coerente) */
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    text-decoration: none;
    font-weight: 600;
  }
  .thub-home__link svg{ width: 18px; height: 18px; }

  .thub-home__media{
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 0;
  }
  .thub-home__media svg{
    width: 100%;
    max-width: 160px;
    height: auto;
    display: block;
  }

  /* ================================
     [THUB_HOME_HERO] — Avatar e titoli
     ================================ */
  .thub-home__hero{
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .75rem;
    margin: 0 auto 1.25rem;
    max-width: 820px;
    text-align: center;
  }

  .thub-home__avatar{
    position: relative;
    width: 104px; height: 104px;
    border-radius: 999px;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border: 1px solid var(--thub-home-border);
  }

  .thub-home__title{
    margin: .25rem 0 0;
    font-size: clamp(1.25rem, 2.2vw, 1.75rem);
    line-height: 1.2;
    font-weight: 700;
  }

  .thub-home__intro{
    margin: .5rem 0 0;
    max-width: 62ch;
    color: #555;
  }

  /* ================================
     [THUB_HOME_AVATAR_TRIGGER] — hint e accessibilità
     ================================ */
  .thub-profile-avatar-trigger:hover{ outline: 0; }
  .thub-profile-avatar-trigger:focus-visible{
    outline: 2px solid var(--violet, #7249a4);
    outline-offset: 2px;
  }

  /* ================================
     [THUB_HOME_ORDER] Ordine esplicito (1-2 | 3-4)
     ================================ */
  .thub-home__card:nth-child(1){ order: 1; }
  .thub-home__card:nth-child(2){ order: 2; }
  .thub-home__card:nth-child(3){ order: 3; }
  .thub-home__card:nth-child(4){ order: 4; }
</style>

<section class="thub-home">
  <!-- ======================================================
       [THUB_HOME_HERO] — Avatar cliccabile + titolo + intro
       - Avatar usa la stessa trigger usata in section-informazioni-personali.php
       ====================================================== -->
  <div class="thub-home__hero">
    <!-- [THUB_HOME_AVATAR] Avatar centrale con trigger -->
    <div class="thub-home__avatar">
      <button type="button"
              class="thub-profile-avatar-trigger"
              aria-label="<?php echo esc_attr__('Gestisci immagine del profilo', 'thub'); ?>"
              style="display:inline-flex;align-items:center;justify-content:center;width:100%;height:100%;background:transparent;border:0;padding:0;cursor:pointer;">
        <?php if ( ! empty($avatar_url) ) : ?>
          <img src="<?php echo esc_url($avatar_url); ?>" alt=""
               width="104" height="104"
               style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
        <?php else: ?>
          <!-- Fallback icona generica (senza testo per non alterare il layout hero) -->
          <svg width="64" height="64" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="4" fill="#d1d5db"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6" fill="#d1d5db"/>
          </svg>
        <?php endif; ?>
      </button>
    </div>

    <!-- [THUB_HOME_TITLE] Benvenuto Nome Cognome -->
    <h1 class="thub-home__title">
      <?php echo esc_html( sprintf( __('Benvenuto %s', 'thub'), $display_name ) ); ?>
    </h1>

    <!-- [THUB_HOME_INTRO] Testo guida -->
    <p class="thub-home__intro">
      <?php echo esc_html__('Gestisci le tue informazioni, la privacy e la sicurezza per adattare meglio T-Hub alle tue esigenze.', 'thub'); ?>
    </p>
  </div>

  <!-- ======================================================
       [THUB_HOME_GRID] — 2×2: Box 1-2 (riga 1), Box 3-4 (riga 2)
       ====================================================== -->
  <div class="thub-home__grid">
    <!-- ==================================================
         [THUB_HOME_CARD_1] — Informazioni Personali (sx riga 1)
         ================================================== -->
    <article class="thub-home__card thub-home__card--personal" aria-labelledby="thub-card-personal-title">
      <div class="thub-home__content">
        <h3 id="thub-card-personal-title">Informazioni Personali</h3>
        <p>Visualizza le informazioni nel tuo Account T-Hub e scegli quali salvare per personalizzare la tua esperienza su T-Hub.</p>
        <div class="thub-home__sep" role="separator" aria-hidden="true"></div>
        <a class="thub-home__link" href="/account/informazioni-personali/">
          <span>Gestisci le tue informazioni personali</span>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8 5l8 7l-8 7" fill="none" stroke="currentColor" stroke-width="2"/>
          </svg>
        </a>
      </div>

      <div class="thub-home__media" aria-hidden="true">
        <!-- [THUB_SVG_PERSONAL] — illustrazione coerente con section-informazioni-personali.php -->
        <svg viewBox="0 0 48 48" role="img" aria-label="Informazioni personali">
          <rect x="5" y="6" width="38" height="28" rx="4" fill="#eaf2ff"/>
          <circle cx="17" cy="18" r="5" fill="#9bbcf7"/>
          <rect x="26" y="14" width="14" height="2.6" rx="1.3" fill="#9bbcf7"/>
          <rect x="26" y="19" width="12" height="2.6" rx="1.3" fill="#bdd1fb"/>
          <rect x="5" y="36" width="28" height="6" rx="3" fill="#dfe8ff"/>
        </svg>
      </div>
    </article>

    <!-- ==================================================
         [THUB_HOME_CARD_2] — Privacy e personalizzazione (dx riga 1)
         ================================================== -->
    <article class="thub-home__card thub-home__card--privacy" aria-labelledby="thub-card-privacy-title">
      <div class="thub-home__content">
        <h3 id="thub-card-privacy-title">Privacy e personalizzazione</h3>
        <p>Visualizza i dati nel tuo Account T-Hub e scegli quali salvare per personalizzare la tua esperienza su T-Hub.</p>
        <div class="thub-home__sep" role="separator" aria-hidden="true"></div>
        <a class="thub-home__link" href="/account/dati-privacy/">
          <span>Gestisci i tuoi dati e la tua privacy</span>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8 5l8 7l-8 7" fill="none" stroke="currentColor" stroke-width="2"/>
          </svg>
        </a>
      </div>

      <div class="thub-home__media" aria-hidden="true">
        <!-- [THUB_SVG_PRIVACY] — scudo/privacy -->
        <svg viewBox="0 0 48 48" role="img" aria-label="Privacy">
          <path d="M24 4l16 6v10c0 8.8-6.3 16.8-16 18c-9.7-1.2-16-9.2-16-18V10l16-6z" fill="#eaf7ef"/>
          <path d="M24 14c5.5 0 10 4.5 10 10c0 5.6-4.5 10.2-10 10.2S14 29.6 14 24c0-5.5 4.5-10 10-10z" fill="#99d6ad"/>
          <path d="M22 24l2 2l4-4" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
    </article>

    <!-- ==================================================
         [THUB_HOME_CARD_3] — Sicurezza (sx riga 2) — dinamico da controller
         ================================================== -->
    <article class="thub-home__card thub-home__card--security" aria-labelledby="thub-card-security-title">
      <div class="thub-home__content">
        <h3 id="thub-card-security-title"><?php echo esc_html($sec_title); ?></h3>
        <p><?php echo esc_html($sec_text); ?></p>
        <div class="thub-home__sep" role="separator" aria-hidden="true"></div>
        <a class="thub-home__link" href="/account/sicurezza/">
          <span>Visualizza dettagli</span>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8 5l8 7l-8 7" fill="none" stroke="currentColor" stroke-width="2"/>
          </svg>
        </a>
      </div>

      <div class="thub-home__media" aria-hidden="true">
        <?php if ($sec_status === 'ok'): ?>
          <!-- [THUB_SVG_SECURITY_OK] -->
          <svg viewBox="0 0 48 48" role="img" aria-label="Sicurezza OK">
            <path d="M24 4l16 6v10c0 8.8-6.3 16.8-16 18c-9.7-1.2-16-9.2-16-18V10l16-6z" fill="#e8f7ff"/>
            <path d="M18 24l4 4l8-8" fill="none" stroke="#71c6ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        <?php else: ?>
          <!-- [THUB_SVG_SECURITY_ATTENTION] -->
          <svg viewBox="0 0 48 48" role="img" aria-label="Sicurezza: attenzione">
            <path d="M24 4l16 6v10c0 8.8-6.3 16.8-16 18c-9.7-1.2-16-9.2-16-18V10l16-6z" fill="#fff3e6"/>
            <path d="M24 16v10" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
            <circle cx="24" cy="30" r="2" fill="#f59e0b"/>
          </svg>
        <?php endif; ?>
      </div>
    </article>

    <!-- ==================================================
         [THUB_HOME_CARD_4] — Maggiori informazioni (dx riga 2)
         ================================================== -->
    <article class="thub-home__card thub-home__card--info" aria-labelledby="thub-card-info-title">
      <div class="thub-home__content">
        <h3 id="thub-card-info-title">Cerchi qualcos'altro?</h3>
        <p>Trova risorse pratiche, suggerimenti e indicazioni per ottenere risposte rapide e approfondire le funzioni che ti interessano. Un percorso guidato per chiarire dubbi, scoprire strumenti utili e migliorare l’esperienza con T-Hub.</p>
        <div class="thub-home__sep" role="separator" aria-hidden="true"></div>
        <a class="thub-home__link" href="/account/informazioni/">
          <span>Ottieni maggiori informazioni</span>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8 5l8 7l-8 7" fill="none" stroke="currentColor" stroke-width="2"/>
          </svg>
        </a>
      </div>

      <div class="thub-home__media" aria-hidden="true">
        <!-- [THUB_SVG_INFO] — icona “i” informazioni -->
        <svg viewBox="0 0 48 48" role="img" aria-label="Informazioni">
          <circle cx="24" cy="24" r="20" fill="#eef2ff"/>
          <circle cx="24" cy="16" r="2.8" fill="#7c8cf8"/>
          <rect x="22.6" y="20" width="2.8" height="12" rx="1.4" fill="#7c8cf8"/>
        </svg>
      </div>
    </article>
  </div>
</section>

<!-- ===================================================================
     [THUB_HOME_JS_AVATAR] — Apri dropdown account e avvia upload dal modal
     - Riutilizza la stessa identica logica di section-informazioni-personali.php
     - Requisiti:
       * esistenza del dropdown/modal account con id="thub-dropdown-account"
       * evento custom 'thub:openAccountDropdown' gestito dal sistema modal
       * wrapper avatar nel dropdown con id="thub-acc-avatar-wrap"
     =================================================================== -->
<script>
(function(){
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.thub-profile-avatar-trigger');
    if(!btn) return;

    const dropdown = document.getElementById('thub-dropdown-account');
    if (dropdown){
      // 1) Chiedo al sistema di aprire il dropdown account
      window.dispatchEvent(new CustomEvent('thub:openAccountDropdown'));
      // 2) Dopo un breve delay, simulo il click sul wrapper avatar del modal
      setTimeout(function(){
        const wrap = document.getElementById('thub-acc-avatar-wrap');
        if(wrap) wrap.click();
      }, 120);
    } else {
      // Fallback estremo: porta il profilo su /wp-admin/profile.php
      window.location.href = "<?php echo esc_url( admin_url('profile.php') ); ?>";
    }
  }, true);
})();
</script>