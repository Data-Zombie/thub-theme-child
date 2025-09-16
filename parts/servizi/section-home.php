<?php
/**
 * [THUB_SERVIZI_HOME] Sezione Home Servizi (Canvas)
 * - Header compatto senza box: avatar (non editabile) + nickname + benvenuto + separatore
 * - 5 box azione (2 per riga + 1 in coda), con SVG pastello a destra
 * - CSS inline e tag identificativi [THUB_*] per modifiche mirate
 *
 * Requisiti:
 * - Template pagina: page-thub-canvas.php (router section=home)
 * - ACF user image: thub_profile_photo (return_format=url)  [vedi ACF export]. 
 * - Variabili CSS globali presenti in style.css (es. --violet, --border, ...).
 */

// [THUB_SERV_HOME_USER] — Recupero utente, nickname e avatar (ACF → helper → fallback)
$user_id   = get_current_user_id();
$wp_user   = $user_id ? get_userdata($user_id) : null;
$nickname  = $wp_user ? ( get_user_meta($user_id, 'nickname', true) ?: $wp_user->display_name ) : __('Ospite','hello-elementor-child');

// 1) Se hai un helper centralizzato (da PROMEMORIA 68): lo usiamo
$avatar_url = (function() use ($user_id){
  if(!$user_id) return '';
  if(function_exists('thub_get_user_avatar_url')){
    $u = thub_get_user_avatar_url($user_id);
    if($u) return $u;
  }
  // 2) Fallback ACF utente: campo 'thub_profile_photo' (return_format=url) — vedi acf-export
  if(function_exists('get_field')){
    $u = get_field('thub_profile_photo', 'user_' . $user_id);
    if($u) return $u;
  }
  return '';
})();

// Iniziali per fallback avatar
$initials = '';
if($wp_user){
  $name = trim($wp_user->display_name ?: $wp_user->user_login);
  $parts = preg_split('/\s+/', $name);
  $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
  $initials = $initials ?: strtoupper(mb_substr($name, 0, 1));
}
?>
<section id="thub-servizi-home" class="thub-servizi-home" aria-labelledby="thub-servizi-home-title"><!-- [THUB_SERV_HOME_WRAPPER] -->

  <!-- =========================
       [THUB_SERV_HOME_STYLES] CSS inline locale
       ========================= -->
  <style>
    /* Contenitore locale */
    #thub-servizi-home{ display:block; }

    /* Header “senza box”: avatar + nickname + benvenuto + separatore */
    .thub-sh__intro{
      display:grid; grid-template-columns: 84px 1fr; gap:14px; align-items:center;
      margin: .25rem 0 .75rem 0;
    }
    .thub-sh__photo{
      width:72px; height:72px; border-radius:50%; overflow:hidden; display:grid; place-items:center;
      background:#f2f2f6; box-shadow: inset 0 0 0 1px #e6e6ea;
    }
    .thub-sh__photo img{ width:100%; height:100%; object-fit:cover; display:block; }
    .thub-sh__photo-fallback{
      width:100%; height:100%; display:grid; place-items:center; font-weight:800; color:#fff;
      background: var(--thub-av2, #5561e9); /* palette già usata nel canvas */
    }
    .thub-sh__nick{ font-weight:700; font-size:1.05rem; line-height:1.25; }
    .thub-sh__welcome{ color:#555; margin-top:.15rem; }
    .thub-sh__sep{
      border:0; border-top:1px solid var(--border, #eee); margin:.75rem 0 1rem 0;
    }

    /* Griglia dei box: 2 colonne su desktop, 1 su mobile */
    .thub-sboxes{
      display:grid; grid-template-columns: 1fr 1fr; gap: 14px;
    }
    @media (max-width: 980px){
      .thub-sboxes{ grid-template-columns: 1fr; }
    }

    /* Singolo box */
    .thub-sbox{
      background:#fff; border:1px solid var(--border, #eee); border-radius:.9rem;
      padding: 14px; display:grid; grid-template-columns: 1.2fr 1fr; gap:14px; align-items:center;
    }
    @media (max-width: 560px){
      .thub-sbox{ grid-template-columns:1fr; }
    }

    /* Colonna testo nel box: titolo, descrizione, separatore, link */
    .thub-sbox__text{
      display:grid; grid-template-rows: auto 1fr auto auto; /* allinea separatore */
      min-height: 160px; /* aiuta l’allineamento del divider sui box della stessa riga */
    }
    .thub-sbox__title{ margin:.1rem 0 .35rem; font-size:1.05rem; font-weight:700; line-height:1.3; }
    .thub-sbox__desc{
      color:#444; margin:0 0 .4rem; line-height:1.35;
      display:-webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 5; overflow:hidden; /* clamp 3 righe → divider allineato */
      min-height: calc(1.35em * 5);
    }
    .thub-sbox__divider{
      border:0; border-top:1px solid var(--border, #eee); margin:.4rem 0 .5rem 0;
    }
    .thub-sbox__link{
      align-self:end; display:inline-block; text-decoration:none; color:var(--violet, #7249a4); font-weight:600;
    }
    .thub-sbox__link:hover{ opacity:.95; }

    /* Colonna immagine: SVG 120×120 con palette chiara (coerente con account/home) */
    .thub-sbox__svg{
      display:grid; place-items:center;
    }
    .thub-sbox__svg svg{ width:120px; height:120px; display:block; }

    /* Micro-accessibilità sui link */
    .thub-sbox__link:focus-visible{
      outline: 2px solid var(--violet, #7249a4); outline-offset: 2px; border-radius:.25rem;
    }
  </style>

  <!-- =========================
       [THUB_SERV_HOME_HEADER] Header senza box
       ========================= -->
  <div class="thub-sh__intro">
    <div class="thub-sh__photo" aria-hidden="true">
      <?php if( !empty($avatar_url) ): ?>
        <img src="<?php echo esc_url($avatar_url); ?>" alt="" />
      <?php else: ?>
        <div class="thub-sh__photo-fallback" aria-hidden="true"><?php echo esc_html($initials ?: 'U'); ?></div>
      <?php endif; ?>
    </div>
    <div>
      <div class="thub-sh__nick"><?php echo esc_html($nickname); ?></div>
      <div class="thub-sh__welcome">Ti diamo il benvenuto nel Gestionale ricette di T-Hub Workspace.</div>
    </div>
  </div>
  <hr class="thub-sh__sep" />

  <!-- =========================
       [THUB_SERV_HOME_BOXES] Griglia box azione
       ========================= -->
  <div class="thub-sboxes">

    <!-- Box 1 — Scrivi una ricetta (sx) -->
    <article class="thub-sbox" aria-labelledby="thub-box-write-title">
      <div class="thub-sbox__text">
        <h3 id="thub-box-write-title" class="thub-sbox__title">Scrivi una ricetta</h3>
        <p class="thub-sbox__desc">
          Crea ricette gustose: piatti della tradizione locale o idee gourmet.
          Decidi quali condividere con tutti e quali conservare nel tuo libro privato.
        </p>
        <hr class="thub-sbox__divider" />
        <a class="thub-sbox__link" href="<?php echo esc_url( home_url('/servizi/nuova-ricetta/') ); ?>" aria-label="Crea una ricetta">
          Crea una ricetta →
        </a>
      </div>
      <div class="thub-sbox__svg" aria-hidden="true">
        <!-- [THUB_SERV_HOME_SVG_HAT] Cappello da cuoco (chiaro) -->
        <svg viewBox="0 0 120 120" role="img" aria-label="Cappello da cuoco">
          <title>Cappello da cuoco — THUB Servizi</title>
          <rect x="10" y="20" width="100" height="80" rx="14" fill="#f4f2fb"></rect>
          <path d="M60 28c16 0 28 9 28 20v6H32v-6c0-11 12-20 28-20z" fill="#e9e1fb"></path>
          <rect x="40" y="58" width="40" height="30" rx="8" fill="#d9c9f6"></rect>
          <rect x="30" y="90" width="60" height="6" rx="3" fill="#c9b5f1"></rect>
        </svg>
      </div>
    </article>

    <!-- Box 2 — Ricette della Nonna (dx) -->
    <article class="thub-sbox" aria-labelledby="thub-box-nonna-title">
      <div class="thub-sbox__text">
        <h3 id="thub-box-nonna-title" class="thub-sbox__title">Ricette della Nonna</h3>
        <p class="thub-sbox__desc">
          Il tuo libro di cucina, pubblico e privato: tutte le tue ricette organizzate e facili da ritrovare in un unico posto.
        </p>
        <hr class="thub-sbox__divider" />
        <a class="thub-sbox__link" href="<?php echo esc_url( home_url('/servizi/ricette-della-nonna/') ); ?>" aria-label="Gestisci le Ricette della Nonna">
          Gestisci le Ricette della Nonna →
        </a>
      </div>
      <div class="thub-sbox__svg" aria-hidden="true">
        <!-- [THUB_SERV_HOME_SVG_GRANDMA] Volto anziano + libro (no brand KFC) -->
        <svg viewBox="0 0 120 120" role="img" aria-label="Ricette della nonna">
          <title>Ricette della nonna — THUB Servizi</title>
          <rect x="12" y="18" width="96" height="84" rx="12" fill="#fff3e6"></rect>
          <!-- volto -->
          <circle cx="60" cy="48" r="18" fill="#ffe2c4"></circle>
          <circle cx="52" cy="46" r="3" fill="#8b6b5a"></circle>
          <circle cx="68" cy="46" r="3" fill="#8b6b5a"></circle>
          <path d="M52 55c2.5 3 13.5 3 16 0" stroke="#8b6b5a" stroke-width="2" fill="none" stroke-linecap="round"></path>
          <!-- capelli -->
          <path d="M42 46c0-10 8-16 18-16s18 6 18 16" fill="#f0e6de"></path>
          <!-- libro -->
          <path d="M30 72h60v22H30z" fill="#ffd6a1"></path>
          <path d="M60 72v22" stroke="#e6b26b" stroke-width="2"></path>
          <rect x="36" y="76" width="20" height="4" rx="2" fill="#fff"></rect>
          <rect x="64" y="76" width="20" height="4" rx="2" fill="#fff"></rect>
        </svg>
      </div>
    </article>

    <!-- Box 3 — Ricette dello Chef (sx, riga sotto) -->
    <article class="thub-sbox" aria-labelledby="thub-box-chef-title">
      <div class="thub-sbox__text">
        <h3 id="thub-box-chef-title" class="thub-sbox__title">Ricette dello Chef</h3>
        <p class="thub-sbox__desc">
          Uno spazio professionale per ricette Premium scritte da Chef referenziati. Diventa Pro, pubblica ricette di alto livello e monetizza la tua passione.
        </p>
        <hr class="thub-sbox__divider" />
        <a class="thub-sbox__link" href="<?php echo esc_url( home_url('/servizi/ricette-dello-chef/') ); ?>" aria-label="Gestisci le Ricette dello Chef">
          Gestisci le Ricette dello Chef →
        </a>
      </div>
      <div class="thub-sbox__svg" aria-hidden="true">
        <!-- [THUB_SERV_HOME_SVG_CHEF] Chef con libro -->
        <svg viewBox="0 0 120 120" role="img" aria-label="Ricette dello chef">
          <title>Ricette dello chef — THUB Servizi</title>
          <rect x="10" y="16" width="100" height="88" rx="12" fill="#eef6ff"></rect>
          <!-- cappello -->
          <path d="M60 26c14 0 24 8 24 18v6H36v-6c0-10 10-18 24-18z" fill="#dbeafe"></path>
          <!-- viso -->
          <circle cx="60" cy="56" r="14" fill="#fed7aa"></circle>
          <!-- libro -->
          <path d="M30 78h60v20H30z" fill="#c7e0ff"></path>
          <path d="M60 78v20" stroke="#a5c9ff" stroke-width="2"></path>
          <rect x="36" y="82" width="20" height="4" rx="2" fill="#fff"></rect>
          <rect x="64" y="82" width="20" height="4" rx="2" fill="#fff"></rect>
        </svg>
      </div>
    </article>

    <!-- Box 4 — Ricette salvate (dx) -->
    <article class="thub-sbox" aria-labelledby="thub-box-fav-title">
      <div class="thub-sbox__text">
        <h3 id="thub-box-fav-title" class="thub-sbox__title">Ricette salvate</h3>
        <p class="thub-sbox__desc">
          Tutti i tuoi preferiti, dalla A alla Z, riuniti in un’unica pagina per ritrovarli al volo quando cucini.
        </p>
        <hr class="thub-sbox__divider" />
        <a class="thub-sbox__link" href="<?php echo esc_url( home_url('/servizi/ricette-salvate/') ); ?>" aria-label="Visualizza le tue ricette preferite">
          Visualizza le tue ricette preferite →
        </a>
      </div>
      <div class="thub-sbox__svg" aria-hidden="true">
        <!-- [THUB_SERV_HOME_SVG_FAV] Cuore/preferiti -->
        <svg viewBox="0 0 120 120" role="img" aria-label="Preferiti">
          <title>Ricette salvate — THUB Servizi</title>
          <rect x="14" y="18" width="92" height="84" rx="12" fill="#fff0f5"></rect>
          <path d="M60 92s-24-14-30-26c-6-12 2-22 12-22 7 0 12 5 18 12 6-7 11-12 18-12 10 0 18 10 12 22-6 12-30 26-30 26z" fill="#ffc2d6"></path>
          <circle cx="44" cy="48" r="2" fill="#ff84a7"></circle>
          <circle cx="76" cy="48" r="2" fill="#ff84a7"></circle>
        </svg>
      </div>
    </article>

    <!-- Box 5 — Raccolte (sx, riga successiva) -->
    <article class="thub-sbox" aria-labelledby="thub-box-collections-title">
      <div class="thub-sbox__text">
        <h3 id="thub-box-collections-title" class="thub-sbox__title">Raccolte</h3>
        <p class="thub-sbox__desc">
          Le ricette della Nonna e dello Chef raggruppate per portata in uno spazio condiviso e semplice da consultare.
        </p>
        <hr class="thub-sbox__divider" />
        <a class="thub-sbox__link" href="<?php echo esc_url( home_url('/servizi/raccolte/') ); ?>" aria-label="Visualizza le tue raccolte">
          Visualizza le tue raccolte →
        </a>
      </div>
      <div class="thub-sbox__svg" aria-hidden="true">
        <!-- [THUB_SERV_HOME_SVG_COLLECTIONS] Collana di libri/ricettari -->
        <svg viewBox="0 0 120 120" role="img" aria-label="Raccolte">
          <title>Raccolte ricette — THUB Servizi</title>
          <rect x="10" y="18" width="100" height="84" rx="12" fill="#ecfdf5"></rect>
          <!-- libri -->
          <rect x="28" y="34" width="16" height="54" rx="3" fill="#a7f3d0"></rect>
          <rect x="48" y="34" width="16" height="54" rx="3" fill="#6ee7b7"></rect>
          <rect x="68" y="34" width="16" height="54" rx="3" fill="#99f6e4"></rect>
          <!-- bande -->
          <rect x="30" y="40" width="12" height="4" rx="2" fill="#fff"></rect>
          <rect x="50" y="46" width="12" height="4" rx="2" fill="#fff"></rect>
          <rect x="70" y="52" width="12" height="4" rx="2" fill="#fff"></rect>
        </svg>
      </div>
    </article>

  </div><!-- /.thub-sboxes -->

</section>