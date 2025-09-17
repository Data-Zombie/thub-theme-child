<?php
/**
 * ============================================================
 * [THUB_SERV_RACCOLTE] Canvas Servizi — Sezione "Raccolte" (Libro di Cucina)
 * - Pixel-perfect 1:1 con section-ricette-della-nonna.php (hero, CTA, filtri, tabella)
 * - Filtri:
 *     [1] "Mostra" per PORTATA (taxonomy 'portata'): Antipasto, Primo, Secondo, Contorno, Dessert
 *     [2] "Lettera iniziale" A–Z
 * - Tabella unica: "Raccolte" (o nome portata se filtrata) con colonne:
 *     Titolo della ricetta | Azioni (solo "Visualizza")
 * - Contenuto = unione ID:
 *     a) ricette dell’utente (publish/private)
 *     b) ricette Pro acquistate (via filtro 'thub_get_user_purchased_recipe_ids')
 *     c) ricette salvate nei preferiti (user_meta 'thub_saved_recipes')
 * - Sicurezza: solo utenti loggati; escape titoli/URL.
 * - Nota: l’azione “Rimuovi dai preferiti” è stata rimossa in questa section (richiesta utente).
 * ============================================================
 */

if (!defined('ABSPATH')) exit; // [THUB_SECURE_EXIT]

// ------------------------------------------------------------
// [THUB_SERV_RACCOLTE_GUARD] Solo utenti loggati
// ------------------------------------------------------------
$user_id = get_current_user_id();
if (!$user_id) : ?>
  <section id="thub-raccolte" class="thub-canvas-section">
    <div class="thub-table">
      <em><?php echo esc_html__('Devi essere loggato per vedere il tuo Libro di Cucina.', 'hello-elementor-child'); ?></em>
    </div>
  </section>
  <?php return;
endif;

// ------------------------------------------------------------
// [THUB_SERV_RACCOLTE_PORTATE] Mappa PORTATE supportate (slug => Label)
// - Adegua gli slug se la tassonomia 'portata' nel tuo sito usa nomi diversi.
// ------------------------------------------------------------
$THUB_PORTATE = [
  'antipasto' => 'Antipasto',
  'primo'     => 'Primo',
  'secondo'   => 'Secondo',
  'contorno'  => 'Contorno',
  'dessert'   => 'Dessert',
];

// ------------------------------------------------------------
// [THUB_SERV_RACCOLTE_DATA] Costruzione set ID ricette
//   - proprie (publish/private)
//   - Pro acquistate (via filtro estensibile)
//   - preferite (user_meta thub_saved_recipes)
// ------------------------------------------------------------

// a) Ricette proprie
$own_ids = get_posts([
  'post_type'      => 'ricetta',
  'author'         => $user_id,
  'post_status'    => ['publish','private'],
  'fields'         => 'ids',
  'posts_per_page' => -1,
]);

// b) Ricette Pro acquistate (filtro estendibile)
$purchased_ids = apply_filters('thub_get_user_purchased_recipe_ids', [], $user_id);
$purchased_ids = is_array($purchased_ids) ? array_map('intval', $purchased_ids) : [];

// c) Ricette preferite (user_meta array di ID)
$fav_raw = get_user_meta($user_id, 'thub_saved_recipes', true);
$fav_ids = is_array($fav_raw) ? array_map('intval', $fav_raw) : [];

// Unione + dedup + filtro validi
$union_ids = array_values( array_unique( array_filter( array_merge($own_ids, $purchased_ids, $fav_ids) ) ) );

// ------------------------------------------------------------
// [THUB_SERV_RACCOLTE_QUERY] Query finale A→Z
// ------------------------------------------------------------
$q = null;
if ($union_ids){
  $q = new WP_Query([
    'post_type'      => 'ricetta',
    'post_status'    => ['publish','private'],
    'posts_per_page' => -1,
    'post__in'       => $union_ids,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'no_found_rows'  => true,
  ]);
}

// ------------------------------------------------------------
// [THUB_SERV_RACCOLTE_HELPERS] Utility locali
// ------------------------------------------------------------
if (!function_exists('thub_sr_first_letter')){
  /**
   * Ritorna la lettera iniziale A–Z del titolo (altrimenti "#").
   * @param string $str
   * @return string
   */
  function thub_sr_first_letter($str){
    $t = trim( wp_strip_all_tags((string)$str) );
    if ($t === '') return '#';
    if (function_exists('remove_accents')) $t = remove_accents($t);
    $L = strtoupper( mb_substr($t, 0, 1, 'UTF-8') );
    return preg_match('/[A-Z]/', $L) ? $L : '#';
  }
}
?>
<style>
/* ============================================================
   [THUB_RACCOLTE_CSS] — Base 1:1 da “section-ricette-della-nonna.php”
   (hero, card CTA, filtri dropdown ancorati, chips, tabella griglia)
   Differenza: griglia 2 colonne → 1fr | 140px (niente colonna “Stato”)
   ============================================================ */

/* [THUB_RACCOLTE_WRAP] */
#thub-raccolte{display:block}

/* HERO centrato */
.thub-hero{
  display:flex; flex-direction:column; align-items:center; text-align:center; gap:.4rem;
  padding:.25rem 0 .2rem;
}
.thub-hero__title{
  margin:0; font-weight:700; font-size:clamp(20px,2.6vw,26px);
}
.thub-hero__subtitle{
  margin:.15rem 0 0; color:#555;
}
/* [THUB_RACCOLTE_CSS_HERO_ICON] dimensioni icona hero (72×72), coerente con Nonna */
#thub-raccolte .thub-hero__icon svg{
  width:72px; height:72px; display:block;
}

/* Card “Scrivi una ricetta” — FULL WIDTH come Nonna */
.thub-card--half{
  width:100%;
  margin:1.65rem auto 1.65rem;
  display:grid; grid-template-columns:72px 1fr; align-items:center; gap:.75rem;
  background:#fff; border:1px solid var(--border,#eee); border-radius:1rem; padding:.75rem .9rem;
}
.thub-card__icon svg{ width:56px; height:56px; display:block; }
.thub-card__title{ font-weight:700; margin:.1rem 0 .2rem; }
.thub-link-cta{
  display:inline-block; margin-top:.35rem; text-decoration:none; color:#fff;
  background:var(--violet,#7249a4); border:1px solid var(--violet,#7249a4);
  border-radius:.7rem; padding:.5rem .8rem; font-weight:600;
}

/* Barra filtri + chips (destra) */
.thub-filtersbar{
  display:flex; gap:.5rem; justify-content:flex-end; align-items:center; flex-wrap:wrap;
  margin:.35rem 0 .5rem;
}
.thub-iconbtn{
  height:44px; padding:0 .6rem; border:1px solid var(--border,#e6e6ea); border-radius:.7rem; background:#fff;
  cursor:pointer; line-height:0;
}
.thub-iconbtn:hover{ background:#f7f5fb; }
.thub-iconbtn svg{ width:18px; height:18px; display:block; }

/* Chips */
.thub-chips{ display:flex; gap:.35rem; justify-content:flex-end; margin:.35rem 0; }
.thub-chip{ font-size:.85rem; padding:.2rem .5rem; background:#f4effc; border:1px solid #e6dafc; border-radius:999px; }

/* Dropdown/modal ancorati (come Nonna) */
.thub-modal{
  position:absolute; z-index:1200; right:0; margin-top:.35rem;
  min-width:220px; background:#fff; border:1px solid var(--border,#e6e6ea);
  border-radius:.8rem; box-shadow:0 6px 22px rgba(0,0,0,.08);
  padding:.5rem; display:none;
}
.thub-modal[aria-hidden="false"]{ display:block; }
.thub-modal h4{ margin:.2rem .2rem .5rem; font-size:.95rem; }
.thub-opt{ display:flex; align-items:center; gap:.5rem; padding:.35rem .4rem; border-radius:.45rem; }
.thub-opt:hover{ background:#f7f5fb; }
.thub-az{ max-height:42vh; overflow:auto; scrollbar-width:none; -ms-overflow-style:none; }
.thub-az::-webkit-scrollbar{ width:0; height:0; }
.thub-az button{
  display:block; width:100%; text-align:left; padding:.4rem .5rem; border:0; background:transparent;
  cursor:pointer; border-radius:.4rem;
}
.thub-az button:hover{ background:#f7f5fb; }
.thub-az .is-active{ background:#ede7f5; }

/* Tabella pulita (griglia) */
.thub-table{
  background:#fff; border:0; border-radius:.9rem; padding:.7rem;
}
.thub-thead,.thub-trow{
  /* 2 colonne: Titolo | Azioni (140px) */
  display:grid; grid-template-columns:1fr 140px; gap:.6rem; align-items:center;
}
.thub-thead{
  padding:.1rem .2rem .35rem; color:#666; font-weight:600;
  margin-top:2.65rem;
}
.thub-thead>div:last-child{ text-align:end; }
.thub-trow{ padding:.45rem .3rem; border-radius:.6rem; }
.thub-trow+.thub-trow{ border-top:1px dashed var(--border,#eee); }
.thub-actions{ display:flex; justify-content:flex-end; gap:.35rem; }

/* Pulsanti azione icon-only */
.thub-icbtn{
  width:36px; height:36px; display:grid; place-items:center;
  padding:0; border:1px solid var(--violet,#7249a4); color:var(--violet,#7249a4);
  background:#fff; border-radius:.6rem; cursor:pointer; line-height:0; text-decoration:none;
}
.thub-icbtn:hover{ background:#f7f5fb; }
.thub-icbtn:focus-visible{ outline:2px solid var(--violet,#7249a4); outline-offset:2px; }
.thub-icbtn svg{ width:18px; height:18px; display:block; }

/* Empty */
.thub-empty{ color:#666; font-size:.98rem; margin:.4rem 0; }

/* Mobile */
@media (max-width:760px){
  .thub-card--half{ grid-template-columns:56px 1fr; }
  .thub-thead{ display:none; }
  .thub-trow{ grid-template-columns:1fr; gap:.35rem; align-items:flex-start; }
  .thub-actions{ justify-content:flex-start; }
}
@media (max-width:480px){
  .thub-hero__title{ font-size:20px; }
  .thub-hero__subtitle{ font-size:14px; }
  .thub-card--half{ padding:.65rem .75rem; }
  .thub-icbtn{ width:40px; height:40px; }
}
</style>

<section id="thub-raccolte" aria-labelledby="thub-raccolte-title"><!-- [THUB_RACCOLTE_SECTION] -->
  <!-- =======================
       HERO titolo + icona (72×72)
       ======================= -->
  <div class="thub-hero"><!-- [THUB_RACCOLTE_HERO] -->
    <div class="thub-hero__icon" aria-hidden="true">
      <!-- [THUB_RACCOLTE_ICON] Libri -->
      <svg viewBox="0 0 120 120" role="img" aria-label="<?php echo esc_attr__('Raccolte', 'hello-elementor-child'); ?>">
        <title><?php echo esc_html__('Raccolte ricette — THUB Servizi', 'hello-elementor-child'); ?></title>
        <rect x="10" y="18" width="100" height="84" rx="12" fill="#ecfdf5"></rect>
        <rect x="28" y="34" width="16" height="54" rx="3" fill="#a7f3d0"></rect>
        <rect x="48" y="34" width="16" height="54" rx="3" fill="#6ee7b7"></rect>
        <rect x="68" y="34" width="16" height="54" rx="3" fill="#99f6e4"></rect>
        <rect x="30" y="40" width="12" height="4" rx="2" fill="#fff"></rect>
        <rect x="50" y="46" width="12" height="4" rx="2" fill="#fff"></rect>
        <rect x="70" y="52" width="12" height="4" rx="2" fill="#fff"></rect>
      </svg>
    </div>
    <h1 class="thub-hero__title" id="thub-raccolte-title"><?php echo esc_html__('Libro di Cucina', 'hello-elementor-child'); ?></h1>
    <p class="thub-hero__subtitle">
      <?php echo esc_html__('Le tue ricette organizzate per portata: trova subito ciò che cerchi.', 'hello-elementor-child'); ?>
    </p>
  </div>

  <!-- =======================
       Card CTA: Scrivi una ricetta (identico concetto a Nonna)
       ======================= -->
  <div class="thub-card thub-card--half" aria-labelledby="scrivi-title"><!-- [THUB_RACCOLTE_CTA] -->
    <div class="thub-card__icon">
      <!-- Cappello da cuoco -->
      <svg viewBox="0 0 120 120" role="img" aria-label="<?php echo esc_attr__('Cappello da cuoco', 'hello-elementor-child'); ?>">
        <title><?php echo esc_html__('Cappello da cuoco — THUB Servizi', 'hello-elementor-child'); ?></title>
        <rect x="10" y="20" width="100" height="80" rx="14" fill="#f4f2fb"></rect>
        <path d="M60 28c16 0 28 9 28 20v6H32v-6c0-11 12-20 28-20z" fill="#e9e1fb"></path>
        <rect x="40" y="58" width="40" height="30" rx="8" fill="#d9c9f6"></rect>
        <rect x="30" y="90" width="60" height="6" rx="3" fill="#c9b5f1"></rect>
      </svg>
    </div>
    <div class="thub-card__body">
      <div class="thub-card__title" id="scrivi-title"><?php echo esc_html__('Scrivi una ricetta', 'hello-elementor-child'); ?></div>
      <div class="thub-card__text">
        <?php echo esc_html__('Crea ricette gustose e raccoglile per portata. Scegli cosa rendere pubblico e cosa tenere solo per te.', 'hello-elementor-child'); ?>
      </div>
      <a href="<?php echo esc_url( home_url('/servizi/nuova-ricetta/') ); ?>" class="thub-link-cta">
        <?php echo esc_html__('Crea una ricetta', 'hello-elementor-child'); ?> →
      </a>
    </div>
  </div>

  <!-- =======================
       Filtri: Mostra (Portata) + Lettera iniziale
       ======================= -->
  <div class="thub-filtersbar" id="thub-raccolte-filters" data-letter=""><!-- [THUB_RACCOLTE_FILTERS] -->
    <!-- Mostra (Portata) -->
    <div style="position:relative">
      <button type="button" class="thub-iconbtn" id="thub-filter-portata" aria-haspopup="dialog" aria-expanded="false" aria-label="<?php echo esc_attr__('Filtra per portata', 'hello-elementor-child'); ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M3 6h18M6 12h12M10 18h4" stroke="#7249a4" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
      </button>
      <div class="thub-modal" id="thub-modal-portata" role="dialog" aria-hidden="true" aria-label="<?php echo esc_attr__('Filtra per portata', 'hello-elementor-child'); ?>">
        <h4><?php echo esc_html__('Mostra', 'hello-elementor-child'); ?></h4>
        <?php foreach($THUB_PORTATE as $slug => $label): ?>
          <label class="thub-opt"><input type="checkbox" value="<?php echo esc_attr($slug); ?>" checked> <?php echo esc_html($label); ?></label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Lettera iniziale -->
    <div style="position:relative">
      <button type="button" class="thub-iconbtn" id="thub-filter-az" aria-haspopup="dialog" aria-expanded="false" aria-label="<?php echo esc_attr__('Filtra per lettera iniziale', 'hello-elementor-child'); ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
          <rect x="4" y="3" width="15" height="18" rx="2" fill="none" stroke="#7249a4" stroke-width="2"/>
          <path d="M7 7h6M7 11h6M7 15h6" stroke="#7249a4" stroke-width="2" stroke-linecap="round"/>
          <path d="M20 6v2M20 11v2M20 16v2" stroke="#7249a4" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
      <div class="thub-modal" id="thub-modal-az" role="dialog" aria-hidden="true" aria-label="<?php echo esc_attr__('Filtra per lettera iniziale', 'hello-elementor-child'); ?>">
        <h4><?php echo esc_html__('Lettera iniziale', 'hello-elementor-child'); ?></h4>
        <div class="thub-az" id="thub-az-list">
          <?php foreach (range('A','Z') as $L): ?>
            <button type="button" data-letter="<?php echo esc_attr($L); ?>"><?php echo esc_html($L); ?></button>
          <?php endforeach; ?>
          <button type="button" data-letter=""><?php echo esc_html__('Tutte', 'hello-elementor-child'); ?></button>
        </div>
      </div>
    </div>

    <!-- Chips riassunto -->
    <div class="thub-chips">
      <span class="thub-chip" id="chip-portata" hidden></span>
      <span class="thub-chip" id="chip-letter" hidden></span>
    </div>
  </div>

  <!-- =======================
       Tabella Raccolte (Titolo + 2 colonne)
       ======================= -->
  <div class="thub-table" id="thub-box-raccolte"><!-- [THUB_RACCOLTE_TABLE] -->
    <div class="thub-box__title" id="thub-table-title"><?php echo esc_html__('Raccolte', 'hello-elementor-child'); ?></div>
    <div class="thub-box__subtitle" id="thub-table-sub">
      <?php echo esc_html__('Elenco delle tue ricette raccolte (proprie, acquistate, preferite).', 'hello-elementor-child'); ?>
    </div>

    <div class="thub-thead" aria-hidden="true">
      <div><?php echo esc_html__('Titolo della ricetta', 'hello-elementor-child'); ?></div>
      <div><?php echo esc_html__('Azioni', 'hello-elementor-child'); ?></div>
    </div>

    <?php
    if ($q && $q->have_posts()):
      while ($q->have_posts()): $q->the_post();
        $pid    = get_the_ID();
        $title  = get_the_title() ?: '(senza titolo)';
        $plink  = get_permalink($pid);
        $letter = thub_sr_first_letter($title);

        // Termini 'portata' → lista slug (anche multipli)
        $portata_terms = wp_get_post_terms($pid, 'portata', ['fields'=>'slugs']);
        $portata_terms = is_wp_error($portata_terms) ? [] : array_values(array_filter($portata_terms));
        $portata_list  = $portata_terms ? implode(',', $portata_terms) : 'nessuna';

        // Origini (own/purchased/favorite) — utile per debug / evoluzioni future
        $origins = [];
        if (in_array($pid, $own_ids, true))       $origins[] = 'own';
        if (in_array($pid, $purchased_ids, true)) $origins[] = 'purchased';
        if (in_array($pid, $fav_ids, true))       $origins[] = 'favorite';
        $origin_str = implode(',', $origins);
        ?>
        <div class="thub-trow"
             data-letter="<?php echo esc_attr($letter); ?>"
             data-portata-list="<?php echo esc_attr($portata_list); ?>"
             data-origins="<?php echo esc_attr($origin_str); ?>"
             data-post="<?php echo (int)$pid; ?>">
          <div>
            <a href="<?php echo esc_url($plink); ?>" style="text-decoration:none"><?php echo esc_html($title); ?></a>
          </div>
          <div class="thub-actions">
            <!-- [THUB_RACCOLTE_ACTIONS] Solo Visualizza -->
            <a class="thub-icbtn thub-view" href="<?php echo esc_url($plink); ?>" title="<?php echo esc_attr__('Visualizza ricetta', 'hello-elementor-child'); ?>" aria-label="<?php echo esc_attr__('Visualizza', 'hello-elementor-child'); ?>">
              <!-- SVG occhio -->
              <svg aria-hidden="true" viewBox="0 0 24 24" role="img"><path d="M12 5c4.5 0 8.4 2.7 10 7-1.6 4.3-5.5 7-10 7S3.6 16.3 2 12c1.6-4.3 5.5-7 10-7Zm0 2C8.7 7 5.9 8.9 4.5 12 5.9 15.1 8.7 17 12 17s6.1-1.9 7.5-5C18.1 8.9 15.3 7 12 7Zm0 2.5A2.5 2.5 0 1 1 9.5 12 2.5 2.5 0 0 1 12 9.5Z"></path></svg>
            </a>
          </div>
        </div>
      <?php endwhile; wp_reset_postdata();
    else: ?>
      <div class="thub-trow"><em class="thub-empty"><?php echo esc_html__('Nessuna ricetta in raccolta al momento.', 'hello-elementor-child'); ?></em></div>
    <?php endif; ?>
  </div>

  <script>
  /* ============================================================
     [THUB_RACCOLTE_JS] — Dropdown filtri + filtro client-side
     - Nota: azione “rimuovi dai preferiti” rimossa (richiesta utente).
     ============================================================ */
  (function(){
    const $  = (s, c)=> (c || document).querySelector(s);
    const $$ = (s, c)=> Array.from((c || document).querySelectorAll(s));

    // ---- Dropdown ancorati (stesso pattern di "Nonna")
    function bindDropdown(btnId, modalId){
      const btn = document.getElementById(btnId);
      const dd  = document.getElementById(modalId);
      if(!btn || !dd) return;
      const closeAll = ()=>{
        dd.setAttribute('aria-hidden','true'); btn.setAttribute('aria-expanded','false');
        document.removeEventListener('click', onDoc, true);
        document.removeEventListener('keydown', onKey);
      };
      const onDoc = (e)=>{ if(!dd.contains(e.target) && !btn.contains(e.target)) closeAll(); };
      const onKey = (e)=>{ if(e.key === 'Escape') closeAll(); };
      btn.addEventListener('click', ()=>{
        const isClosed = dd.getAttribute('aria-hidden') !== 'false';
        if(isClosed){
          dd.setAttribute('aria-hidden','false'); btn.setAttribute('aria-expanded','true');
          setTimeout(()=>{
            document.addEventListener('click', onDoc, true);
            document.addEventListener('keydown', onKey);
          }, 0);
        } else { closeAll(); }
      });
    }
    bindDropdown('thub-filter-portata','thub-modal-portata');
    bindDropdown('thub-filter-az','thub-modal-az');

    // ---- Stato filtri
    const box   = document.getElementById('thub-box-raccolte');
    const rows  = $$('.thub-trow', box);
    const chipP = document.getElementById('chip-portata');
    const chipL = document.getElementById('chip-letter');
    const title = document.getElementById('thub-table-title');

    // Tutte le portate attive di default (mappa dallo stesso PHP)
    const PORTATE = Object.keys(<?php echo wp_json_encode($THUB_PORTATE); ?>);
    let selectedPortate = new Set(PORTATE); // tutte ON
    let selectedLetter  = '';               // '' = tutte

    // Portata → toggle checkbox
    const modalP = document.getElementById('thub-modal-portata');
    if(modalP){
      modalP.addEventListener('click', (e)=>{
        const lab = e.target.closest('.thub-opt');
        if(!lab) return;
        const inp = lab.querySelector('input[type="checkbox"]');
        if(!inp) return;
        setTimeout(()=>{
          if(inp.checked) selectedPortate.add(inp.value); else selectedPortate.delete(inp.value);
          updateUI();
        }, 0);
      });
    }

    // Lettera → bottoni
    const modalAZ = document.getElementById('thub-modal-az');
    if(modalAZ){
      const az = document.getElementById('thub-az-list');
      az?.addEventListener('click', (e)=>{
        const btn = e.target.closest('button[data-letter]');
        if(!btn) return;
        $$('.thub-az button', az).forEach(b=>b.classList.remove('is-active'));
        btn.classList.add('is-active');
        selectedLetter = btn.getAttribute('data-letter') || '';
        updateUI();
      });
    }

    function ucFirst(s){ return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }

    function updateUI(){
      // 1) Filtra righe
      rows.forEach(row=>{
        // lettera
        const L = (row.getAttribute('data-letter') || '').toUpperCase();
        const letterOK = (selectedLetter === '' || L === selectedLetter);

        // portata (lista multipla): se nessuna selezionata → mostra niente
        const plist = (row.getAttribute('data-portata-list') || 'nessuna').split(',').filter(Boolean);
        let portOK = false;
        if(selectedPortate.size === 0){
          portOK = false;
        } else if (plist.length === 0){
          // se la ricetta non ha portata, la consideriamo “nessuna”: visibile solo quando TUTTE le portate sono attive
          portOK = (selectedPortate.size === PORTATE.length);
        } else {
          portOK = plist.some(p => selectedPortate.has(p));
        }

        row.style.display = (letterOK && portOK) ? '' : 'none';
      });

      // 2) Chips + titolo tabella
      if(selectedPortate.size === 0){
        chipP.hidden = false; chipP.textContent = 'Portata: nessuna selezionata';
        title.textContent = 'Raccolte';
      } else if (selectedPortate.size === 1){
        const slug = Array.from(selectedPortate)[0];
        chipP.hidden = false; chipP.textContent = 'Portata: ' + ucFirst(slug);
        title.textContent = ucFirst(slug);
      } else if (selectedPortate.size === PORTATE.length){
        chipP.hidden = true; title.textContent = 'Raccolte';
      } else {
        chipP.hidden = false; chipP.textContent = 'Portate: ' + selectedPortate.size + ' selezioni';
        title.textContent = 'Raccolte';
      }

      if(selectedLetter === ''){
        chipL.hidden = true;
      } else {
        chipL.hidden = false; chipL.textContent = 'Lettera: ' + selectedLetter;
      }
    }

    // Init
    updateUI();
  })();
  </script>
</section>
