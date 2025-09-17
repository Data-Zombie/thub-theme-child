<?php
/* ============================================================
 * [THUB_SERVIZI_CHEF] /servizi — section-ricette-dello-chef.php (v1.0.4)
 * - Allineamento 1:1 a “Nonna” per spaziature, padding e dimensioni
 * - Hero 72×72; Card FULL WIDTH con stesso bordo/raggi; Filtri a destra
 * - “Acquistate” include pending → badge “Ricetta in aggiornamento”
 * - Nessun pulsante “Sposta”
 * ============================================================ */

if (!defined('ABSPATH')) exit;

/* ---------------------------------------------
 * [THUB_CHEF_VARS] Variabili base + helper Pro
 * -------------------------------------------*/
$user_id  = get_current_user_id();
$is_pro   = function_exists('thub_is_pro') ? (bool) thub_is_pro($user_id) : false; // user_meta thub_is_pro
$tax      = defined('THUB_PRO_TERM_TAX')  ? THUB_PRO_TERM_TAX  : 'category';
$pro_slug = defined('THUB_PRO_TERM_SLUG') ? THUB_PRO_TERM_SLUG : 'pro';

/* -------------------------------------------------------------
 * [THUB_CHEF_BUY_QUERY] Ricette ACQUISTATE dall’utente
 * - Fonte: user_meta 'thub_purchased_recipes' (array di ID)
 * - Visibilità: includiamo publish + pending (pendenti = “in aggiornamento”)
 * -----------------------------------------------------------*/
$buy_ids = (array) get_user_meta($user_id, 'thub_purchased_recipes', true);
$buy_ids = array_filter(array_map('intval', $buy_ids));

$buy_items = [];
if (!empty($buy_ids)) {
  $q_buy = new WP_Query([
    'post_type'      => 'ricetta',
    'post__in'       => $buy_ids,
    'post_status'    => ['publish','pending'], // include pending
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'tax_query'      => [[ 'taxonomy'=>$tax, 'field'=>'slug', 'terms'=>[$pro_slug], 'operator'=>'IN' ]],
  ]);
  if ($q_buy->have_posts()){
    while($q_buy->have_posts()){ $q_buy->the_post();
      $title  = get_the_title() ?: '(senza titolo)';
      $letter = strtoupper( mb_substr( ltrim($title), 0, 1 ) );
      $letter = preg_match('/[A-ZÀ-Ü]/u', $letter) ? $letter : '#';
      $ps     = get_post_status(); // publish|pending

      $buy_items[] = [
        'ID'        => get_the_ID(),
        'title'     => $title,
        'letter'    => $letter,
        'view'      => get_permalink(),
        'can_view'  => ($ps === 'publish'), // publish → link attivo
        'is_update' => ($ps === 'pending'), // pending → “Ricetta in aggiornamento”
      ];
    }
    wp_reset_postdata();
  }
}

/* -------------------------------------------------------------
 * [THUB_CHEF_PRO_QUERY] “Le tue ricette Pro” (autore = utente)
 * - Stato: publish/pending/draft/private (badge come Nonna)
 * - Appartenenza al termine Pro obbligatoria
 * -----------------------------------------------------------*/
$pro_items = [];
$base_args = [
  'post_type'      => 'ricetta',
  'author'         => $user_id,
  'posts_per_page' => -1,
  'orderby'        => 'title',
  'order'          => 'ASC',
  'tax_query'      => [[ 'taxonomy'=>$tax, 'field'=>'slug', 'terms'=>[$pro_slug], 'operator'=>'IN' ]],
];

// Helper stato → badge + tooltip (riuso schema Nonna)
if (!function_exists('thub_recipe_status_ui')) {
  /** [THUB_STATUS_UI] Ritorna [label, info, class] per lo stato della ricetta */
  function thub_recipe_status_ui($post){
    $ps     = get_post_status($post);
    $denied = ( get_post_meta($post->ID, 'thub_approval_status', true) === 'denied' );
    if ($ps === 'publish')                 return ['label'=>'Pubblicata',        'info'=>'Ricetta pubblicata',             'class'=>'thub-badge thub-badge--ok'];
    if ($ps === 'pending')                 return ['label'=>'In approvazione',   'info'=>'In fase di approvazione',        'class'=>'thub-badge thub-badge--pend'];
    if ($ps === 'draft' && $denied)        return ['label'=>'Richiesta modifica','info'=>'Aggiorna e ripubblica',          'class'=>'thub-badge thub-badge--warn'];
    if ($ps === 'private')                 return ['label'=>'Privata',           'info'=>'Visibile solo a te',             'class'=>'thub-badge'];
    return null;
  }
}

$q_pro = new WP_Query( array_merge($base_args, [ 'post_status' => ['publish','pending','draft','private'] ]) );
if ($q_pro->have_posts()){
  while($q_pro->have_posts()){ $q_pro->the_post();
    $ui = thub_recipe_status_ui( get_post() );
    if (!$ui) continue;
    $title  = get_the_title() ?: '(senza titolo)';
    $letter = strtoupper( mb_substr( ltrim($title), 0, 1 ) );
    $letter = preg_match('/[A-ZÀ-Ü]/u', $letter) ? $letter : '#';
    $ps     = get_post_status();

    $pro_items[] = [
      'ID'       => get_the_ID(),
      'title'    => $title,
      'letter'   => $letter,
      'ui'       => $ui,
      'edit'     => add_query_arg(['post_id'=>get_the_ID(),'mode'=>'edit'], home_url('/servizi/nuova-ricetta/')),
      'admin'    => admin_url('post.php?post='.get_the_ID().'&action=edit'),
      'view'     => get_permalink(),
      'can_view' => ($ps === 'publish'),
    ];
  }
  wp_reset_postdata();
}
?>

<style>
/* ============================================================
   [THUB_CHEF_CSS] — Allineamento 1:1 con “Nonna”
   - Blocchi marcati [THUB_NONNA_MATCH_*] copiano valori da section-ricette-della-nonna.php
   ============================================================ */

/* Wrapper sez. */
#thub-chef{display:block}

/* HERO centrato — [THUB_NONNA_MATCH_HERO] */
#thub-chef .thub-hero{ display:flex; flex-direction:column; align-items:center; text-align:center; gap:.4rem; padding:.25rem 0 .2rem; }
#thub-chef .thub-hero__title{ margin:0; font-weight:700; font-size:clamp(20px,2.6vw,26px); }
#thub-chef .thub-hero__subtitle{ margin:.15rem 0 0; color:#555; }
#thub-chef .thub-hero__icon{ width:72px; height:72px; margin: 0 auto .5rem; }
#thub-chef .thub-hero__icon svg{ width:72px; height:72px; display:block; } /* 72×72 fisso */

/* Card “Scrivi una ricetta” FULL WIDTH — [THUB_NONNA_MATCH_CARD] */
#thub-chef .thub-card--half{
  width: 100%;
  margin: 1.65rem auto 1.65rem;
  display:grid; grid-template-columns: 72px 1fr; align-items:center; gap:.75rem;
  background:#fff; border:1px solid var(--border,#eee); border-radius:1rem; padding:.75rem .9rem;
}
#thub-chef .thub-card__icon{ width:64px; height:64px; display:grid; place-items:center; background:#f4f6ff; border-radius:12px; }
#thub-chef .thub-card__title{ font-weight:700; font-size:1.05rem; }
#thub-chef .thub-card__text{ color:#666; margin:.2rem 0 .5rem; max-width:70ch; }
#thub-chef .thub-link-cta{ color:var(--violet,#4a3ce8); text-decoration:none; font-weight:700; }

/* Barra filtri a DESTRA — [THUB_NONNA_MATCH_FILTERBAR] */
#thub-chef .thub-filtersbar{ display:flex; justify-content:flex-end; gap:.45rem; margin:.25rem 0 .6rem; }
#thub-chef .thub-iconbtn{
  height:38px; width:auto; line-height:0;
  display:grid; place-items:center;
  padding:0 .6rem; border:1px solid var(--border,#e6e6ea); border-radius:.7rem; background:#fff; cursor:pointer;
  transition: background .15s ease, transform .1s ease;
}
#thub-chef .thub-iconbtn svg{ display:block; width:20px; height:20px; }
#thub-chef .thub-iconbtn:hover{ background:#f7f5fb; transform: translateY(-1px); }
#thub-chef .thub-iconbtn:focus-visible{ outline:2px solid var(--violet,#7249a4); outline-offset:2px; }

/* Dropdown/modal — [THUB_NONNA_MATCH_MODAL] */
#thub-chef .thub-modal{
  position:absolute; z-index:1200; right:0; margin-top:.35rem;
  min-width:220px; background:#fff; border:1px solid var(--border,#e6e6ea); border-radius:.8rem; box-shadow:0 6px 22px rgba(0,0,0,.08);
  padding:.5rem; display:none;
}
#thub-chef .thub-modal[aria-hidden="false"]{ display:block; }
#thub-chef .thub-modal h4{ margin:.2rem .2rem .5rem; font-size:.95rem; }
#thub-chef .thub-modal .thub-opt{ display:flex; justify-content:space-between; align-items:center; gap:.5rem; padding:.35rem .4rem; border-radius:.45rem; }
#thub-chef .thub-modal .thub-opt:hover{ background:#f7f5fb; }
#thub-chef .thub-modal .thub-az{ max-height:42vh; overflow:auto; scrollbar-width:none; -ms-overflow-style:none; }
#thub-chef .thub-modal .thub-az::-webkit-scrollbar{ width:0; height:0; }
#thub-chef .thub-modal .thub-az button{ display:block; width:100%; text-align:left; padding:.4rem .5rem; border:0; background:transparent; cursor:pointer; border-radius:.4rem; }
#thub-chef .thub-modal .thub-az button:hover{ background:#f7f5fb; }
#thub-chef .thub-modal .thub-az .is-active{ background:#ede7f5; }

/* Chips */
#thub-chef .thub-chips{ display:flex; gap:.5rem; margin: .4rem 0 1rem; }
#thub-chef .thub-chip{
  display:inline-flex; align-items:center; gap:.45rem; background:#f6f3ff; border:1px solid #e9e2ff; color:#4a3ce8; padding:.25rem .5rem; border-radius:.7rem; font-weight:700;
}
#thub-chef .thub-chip[hidden]{ display:none !important; }

/* Tabelle — [THUB_NONNA_MATCH_TABLE] */
#thub-chef .thub-table{ background:#fff; border:0; border-radius:.9rem; padding:.7rem; }
#thub-chef .thub-box__title{ font-weight:800; margin:.25rem .35rem .35rem; }
#thub-chef .thub-box__subtitle{ color:#666; margin:0 .35rem .6rem; }
#thub-chef .thub-thead, #thub-chef .thub-trow{
  display:grid; grid-template-columns: 1fr 200px 140px; gap:.6rem; align-items:center;
}
#thub-chef .thub-trow{ border-bottom:1px dashed #eee; padding:.45rem .35rem; }
#thub-chef .thub-trow:last-child{ border-bottom:0; }

/* Variante 2 colonne per “Acquistate” (Titolo | Azioni) */
#thub-chef #thub-box-buy .thub-thead,
#thub-chef #thub-box-buy .thub-trow{ grid-template-columns: 1fr 140px; }

/* Badge stato */
#thub-chef .thub-badge{ display:inline-block; padding:.2rem .45rem; border:1px solid #ddd; border-radius:.45rem; font-size:.82rem; }
#thub-chef .thub-badge--ok{   background:#eaf7ee; border-color:#c7e8d1; color:#1b7b38; }
#thub-chef .thub-badge--pend{ background:#fff7e6; border-color:#ffe3a3; color:#9a6700; }
#thub-chef .thub-badge--warn{ background:#fff0f0; border-color:#f4d5d5; color:#8a3232; }

/* Tooltip “i” */
#thub-chef .thub-i{
  display:inline-grid; place-items:center; width:18px; height:18px; border:1px solid #ddd; border-radius:50%;
  font-size:.8rem; font-weight:700; margin-left:.4rem; cursor:pointer; user-select:none;
}
#thub-chef .thub-i:hover{ background:#f7f5fb; }
#thub-chef .thub-i[data-tip]{ position:relative; }
#thub-chef .thub-i[data-tip].is-open::after{
  content: attr(data-tip);
  position:absolute; left:50%; top:-8px; transform: translate(-50%, -100%);
  background:#111; color:#fff; white-space:nowrap; font-size:.8rem; padding:.35rem .5rem; border-radius:.4rem;
}

/* Azioni → icon-only */
#thub-chef .thub-actions{ display:flex; justify-content:flex-end; gap:.35rem; }
#thub-chef .thub-icbtn{
  width:36px; height:36px; display:grid; place-items:center;
  padding:0; border:1px solid #ddd; border-radius:.55rem; background:#fff; cursor:pointer; text-decoration:none;
}
#thub-chef .thub-icbtn[disabled],
#thub-chef .thub-icbtn.is-disabled{ opacity:.45; pointer-events:none; }

/* Titolo disattivato per pending in “Acquistate” */
#thub-chef .thub-title--muted{ color:#888; text-decoration:none; cursor:default; }

/* Mobile tweaks — [THUB_NONNA_MATCH_MQ] */
@media (max-width:760px){
  #thub-chef .thub-card--half{ grid-template-columns: 56px 1fr; }
  #thub-chef .thub-thead{ display:none; }
  #thub-chef .thub-trow{ grid-template-columns: 1fr; gap:.35rem; align-items:flex-start; }
  #thub-chef .thub-actions{ justify-content:flex-start; }
}
@media (max-width:480px){
  #thub-chef .thub-hero__title{ font-size: 20px; }
  #thub-chef .thub-hero__subtitle{ font-size: 14px; }
  #thub-chef .thub-card--half{ padding: .65rem .75rem; }
  #thub-chef .thub-iconbtn{ height:40px; }
  #thub-chef .thub-icbtn{   width:40px; height:40px; }
  #thub-chef .thub-trow{ padding:.4rem .25rem; }
  #thub-chef .thub-badge{ font-size:.84rem; }
}
</style>

<section id="thub-chef" aria-labelledby="thub-chef-title">
  <!-- HERO -->
  <div class="thub-hero">
    <div class="thub-hero__icon">
      <!-- [THUB_CHEF_SVG_HERO] Icona di testata — 72×72 -->
      <svg viewBox="0 0 120 120" role="img" aria-label="Ricette dello chef" width="72" height="72">
        <title>Ricette dello chef — THUB Servizi</title>
        <rect x="10" y="16" width="100" height="88" rx="12" fill="#eef6ff"></rect>
        <path d="M60 26c14 0 24 8 24 18v6H36v-6c0-10 10-18 24-18z" fill="#dbeafe"></path>
        <circle cx="60" cy="56" r="14" fill="#fed7aa"></circle>
        <path d="M30 78h60v20H30z" fill="#c7e0ff"></path>
        <path d="M60 78v20" stroke="#a5c9ff" stroke-width="2"></path>
        <rect x="36" y="82" width="20" height="4" rx="2" fill="#fff"></rect>
        <rect x="64" y="82" width="20" height="4" rx="2" fill="#fff"></rect>
      </svg>
    </div>
    <h1 class="thub-hero__title" id="thub-chef-title">Ricette dello Chef</h1>
    <p class="thub-hero__subtitle">Il ricettario di alta cucina scritto da Chef di tutto il mondo.</p>
  </div>

  <!-- Card full: Scrivi una ricetta — allineata a Nonna -->
  <div class="thub-card thub-card--half" aria-labelledby="scrivi-title">
    <div class="thub-card__icon">
      <!-- [THUB_SVG_CHEFHAT_INLINE] fornito (56×56) -->
      <svg viewBox="0 0 120 120" width="56" height="56" role="img" aria-label="Cappello da cuoco">
        <title>Cappello da cuoco — THUB Servizi</title>
        <rect x="10" y="20" width="100" height="80" rx="14" fill="#f4f2fb"></rect>
        <path d="M60 28c16 0 28 9 28 20v6H32v-6c0-11 12-20 28-20z" fill="#e9e1fb"></path>
        <rect x="40" y="58" width="40" height="30" rx="8" fill="#d9c9f6"></rect>
        <rect x="30" y="90" width="60" height="6" rx="3" fill="#c9b5f1"></rect>
      </svg>
    </div>
    <div class="thub-card__body">
      <div class="thub-card__title" id="scrivi-title">Scrivi una ricetta</div>
      <div class="thub-card__text">Crea ricette gustose: piatti della tradizione locale o idee gourmet.</div>
      <a href="<?php echo esc_url( home_url('/servizi/nuova-ricetta/') ); ?>" class="thub-link-cta">Crea una ricetta -></a>
    </div>
  </div>

  <!-- Filtri (allineati a DESTRA) -->
  <div class="thub-filtersbar" id="thub-chef-filters" data-letter="">
    <div style="position:relative">
      <button type="button" class="thub-iconbtn" id="thub-filter-vis" aria-haspopup="dialog" aria-expanded="false" aria-controls="thub-modal-vis" aria-label="Filtra per categoria">
        <!-- [THUB_SVG_FILTER_INLINE] -->
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
          <path d="M3 6h18M6 12h12M10 18h4" stroke="#7249a4" stroke-width="2" fill="none" stroke-linecap="round"/>
        </svg>
      </button>
      <div class="thub-modal" id="thub-modal-vis" role="dialog" aria-hidden="true" aria-label="Filtro visibilità">
        <h4>Mostra</h4>
        <label class="thub-opt"><input type="checkbox" id="thub-chk-buy" checked> Ricette acquistate</label>
        <?php if ($is_pro): ?>
        <label class="thub-opt"><input type="checkbox" id="thub-chk-pro" checked> Ricette pubblicate</label>
        <?php endif; ?>
      </div>
    </div>
    <div style="position:relative">
      <button type="button" class="thub-iconbtn" id="thub-filter-az" aria-haspopup="dialog" aria-expanded="false" aria-controls="thub-modal-az" aria-label="Filtra per iniziale">
        <!-- [THUB_SVG_ADDRESSBOOK_INLINE] -->
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
          <rect x="4" y="3" width="15" height="18" rx="2" fill="none" stroke="#7249a4" stroke-width="2"/>
          <path d="M7 7h6M7 11h6M7 15h6" stroke="#7249a4" stroke-width="2" stroke-linecap="round"/>
          <path d="M20 6v2M20 11v2M20 16v2" stroke="#7249a4" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
      <div class="thub-modal" id="thub-modal-az" role="dialog" aria-hidden="true" aria-label="Filtra per lettera">
        <h4>Lettera iniziale</h4>
        <div class="thub-az" id="thub-az-list">
          <?php foreach (range('A','Z') as $L): ?>
            <button type="button" data-letter="<?php echo esc_attr($L); ?>"><?php echo esc_html($L); ?></button>
          <?php endforeach; ?>
          <button type="button" data-letter="">Tutte</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Chips opzionali -->
  <div class="thub-chips" id="thub-filter-chips" hidden>
    <span class="thub-chip" id="chip-letter" hidden></span>
  </div>

  <!-- BOX 1 — Ricette acquistate -->
  <div class="thub-table thub-buy" id="thub-box-buy">
    <div class="thub-box__title">Ricette acquistate</div>
    <div class="thub-box__subtitle">Le creazioni culinarie dei tuoi Chef preferiti.</div>

    <div class="thub-thead" aria-hidden="true">
      <div>Titolo della ricetta</div><div>Azioni</div>
    </div>

    <?php if (empty($buy_items)): ?>
      <div class="thub-trow"><em>Nessuna ricetta acquistata al momento.</em></div>
    <?php else: foreach ($buy_items as $it): ?>
      <div class="thub-trow" data-letter="<?php echo esc_attr($it['letter']); ?>">
        <div>
          <?php if (!empty($it['can_view'])): ?>
            <a href="<?php echo esc_url($it['view']); ?>" style="text-decoration:none;"><?php echo esc_html($it['title']); ?></a>
          <?php else: ?>
            <!-- [THUB_BUY_PENDING_TITLE] Titolo non cliccabile se pending -->
            <span class="thub-title--muted" title="Ricetta in aggiornamento"><?php echo esc_html($it['title']); ?></span>
          <?php endif; ?>
        </div>
        <div class="thub-actions">
          <?php if (!empty($it['can_view'])): ?>
            <!-- [THUB_BTN_VIEW] Visualizza (attivo) -->
            <a class="thub-icbtn" href="<?php echo esc_url($it['view']); ?>" aria-label="Visualizza">
              <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" role="img"><path d="M12 5c4.5 0 8.4 2.7 10 7-1.6 4.3-5.5 7-10 7S3.6 16.3 2 12c1.6-4.3 5.5-7 10-7Zm0 2C8.7 7 5.9 8.9 4.5 12 5.9 15.1 8.7 17 12 17s6.1-1.9 7.5-5C18.1 8.9 15.3 7 12 7Zm0 2.5A2.5 2.5 0 1 1 9.5 12 2.5 2.5 0 0 1 12 9.5Z"></path></svg>
            </a>
          <?php else: ?>
            <!-- [THUB_BTN_VIEW_DISABLED] Visualizza (disabilitato) + dicitura -->
            <span class="thub-icbtn is-disabled" aria-disabled="true" title="Temporaneamente in aggiornamento">
              <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" role="img"><path d="M12 5c4.5 0 8.4 2.7 10 7-1.6 4.3-5.5 7-10 7S3.6 16.3 2 12c1.6-4.3 5.5-7 10-7Zm0 2C8.7 7 5.9 8.9 4.5 12 5.9 15.1 8.7 17 12 17s6.1-1.9 7.5-5C18.1 8.9 15.3 7 12 7Zm0 2.5A2.5 2.5 0 1 1 9.5 12 2.5 2.5 0 0 1 12 9.5Z"></path></svg>
            </span>
            <span class="thub-badge thub-badge--pend" aria-label="Stato ricetta">Ricetta in aggiornamento</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <?php if ($is_pro): ?>
  <!-- BOX 2 — Le tue ricette Pro (visibile solo se utente Pro) -->
  <div class="thub-table thub-pro" id="thub-box-pro">
    <div class="thub-box__title">Le tue ricette Pro</div>
    <div class="thub-box__subtitle">Le tue creazioni culinarie in vendita nel T-Hub Market.</div>

    <div class="thub-thead" aria-hidden="true">
      <div>Titolo della ricetta</div><div>Stato</div><div>Azioni</div>
    </div>

    <?php if (empty($pro_items)): ?>
      <div class="thub-trow"><em>Nessuna ricetta Pro presente.</em></div>
    <?php else: foreach ($pro_items as $it): $ui = $it['ui']; ?>
      <div class="thub-trow" data-letter="<?php echo esc_attr($it['letter']); ?>">
        <div><a href="<?php echo esc_url($it['view']); ?>" style="text-decoration:none;"><?php echo esc_html($it['title']); ?></a></div>
        <div>
          <span class="<?php echo esc_attr($ui['class']); ?>"><?php echo esc_html($ui['label']); ?></span>
          <span class="thub-i" data-tip="<?php echo esc_attr($ui['info']); ?>">i</span>
        </div>
        <div class="thub-actions">
          <!-- [THUB_BTN_EDIT] Modifica -->
          <a class="thub-icbtn" href="<?php echo esc_url( $it['edit'] ); ?>" title="Modifica" aria-label="Modifica">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.8 9.95l-3.75-3.75L3 17.25Zm14.71-9.04a1 1 0 0 0 0-1.41L15.2 4.29a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.66Z" fill="currentColor"/></svg>
          </a>
          <!-- [THUB_BTN_VIEW] Visualizza (solo se pubblicata) -->
          <?php if ($it['can_view']): ?>
            <a class="thub-icbtn" href="<?php echo esc_url($it['view']); ?>" aria-label="Visualizza">
              <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" role="img"><path d="M12 5c4.5 0 8.4 2.7 10 7-1.6 4.3-5.5 7-10 7S3.6 16.3 2 12c1.6-4.3 5.5-7 10-7Zm0 2C8.7 7 5.9 8.9 4.5 12 5.9 15.1 8.7 17 12 17s6.1-1.9 7.5-5C18.1 8.9 15.3 7 12 7Zm0 2.5A2.5 2.5 0 1 1 9.5 12 2.5 2.5 0 0 1 12 9.5Z"></path></svg>
            </a>
          <?php else: ?>
            <span class="thub-icbtn is-disabled" aria-disabled="true" title="Disponibile dopo la pubblicazione">
              <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" role="img"><path d="M12 5c4.5 0 8.4 2.7 10 7-1.6 4.3-5.5 7-10 7S3.6 16.3 2 12c1.6-4.3 5.5-7 10-7Zm0 2C8.7 7 5.9 8.9 4.5 12 5.9 15.1 8.7 17 12 17s6.1-1.9 7.5-5C18.1 8.9 15.3 7 12 7Zm0 2.5A2.5 2.5 0 1 1 9.5 12 2.5 2.5 0 0 1 12 9.5Z"></path></svg>
            </span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <?php endif; ?>
</section>

<script>
/* ============================================================
   [THUB_CHEF_JS] — Filtri, modali, A–Z (senza “Sposta”)
   (mantiene la stessa UX della Nonna per apertura/chiusura modali)
   ============================================================ */
(function(){
  const $  = (s,el)=> (el||document).querySelector(s);
  const $$ = (s,el)=> Array.from( (el||document).querySelectorAll(s) );

  // Tooltip “i”
  $$('#thub-chef .thub-i[data-tip]').forEach(i=>{
    i.addEventListener('click', ()=> i.classList.toggle('is-open'));
  });

  // Modali (apri/chiudi su click fuori)
  function bindModal(btnId, modalId){
    const btn = $('#'+btnId), modal = $('#'+modalId);
    if(!btn || !modal) return;
    btn.addEventListener('click', ()=>{
      const hidden = modal.getAttribute('aria-hidden') !== 'false';
      modal.setAttribute('aria-hidden', hidden ? 'false' : 'true');
      btn.setAttribute('aria-expanded', hidden ? 'true' : 'false');
    });
    document.addEventListener('click', (e)=>{
      if (!modal.contains(e.target) && !btn.contains(e.target)){
        modal.setAttribute('aria-hidden','true'); btn.setAttribute('aria-expanded','false');
      }
    });
  }
  bindModal('thub-filter-vis','thub-modal-vis');
  bindModal('thub-filter-az','thub-modal-az');

  // Filtro Mostra (Acquistate / Pubblicate [solo Pro])
  const boxBuy = $('#thub-box-buy');
  const boxPro = $('#thub-box-pro'); // può non esistere se non-Pro
  const chkBuy = $('#thub-chk-buy');
  const chkPro = $('#thub-chk-pro'); // può essere null
  function applyVis(){
    if (chkBuy) boxBuy.style.display = chkBuy.checked ? '' : 'none';
    if (boxPro && chkPro) boxPro.style.display = chkPro.checked ? '' : 'none';
  }
  if (chkBuy) chkBuy.addEventListener('change', applyVis);
  if (chkPro) chkPro.addEventListener('change', applyVis);
  applyVis();

  // Filtro A–Z (+ chip)
  const chipWrap   = $('#thub-filter-chips');
  const chipLetter = $('#chip-letter');
  $$('#thub-az-list button').forEach(b=>{
    b.addEventListener('click', ()=>{
      const L = b.getAttribute('data-letter') || '';
      $$('#thub-chef .thub-trow').forEach(r=>{
        const rl = (r.getAttribute('data-letter')||'').toUpperCase();
        r.style.display = L && rl !== L ? 'none' : '';
      });
      if (chipWrap && chipLetter){
        if (L){ chipLetter.textContent = 'Lettera: ' + L; chipLetter.hidden = false; chipWrap.hidden = false; }
        else   { chipLetter.hidden = true; chipWrap.hidden = true; }
      }
      const modalAz = $('#thub-modal-az');
      if (modalAz){ modalAz.setAttribute('aria-hidden','true'); }
    });
  });
})();
</script>