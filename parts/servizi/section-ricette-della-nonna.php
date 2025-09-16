<?php
/* ============================================================
 * [THUB_SERVIZI_NONNA] /servizi — section-ricette-della-nonna.php (v1.2.2)
 * - Titolo/hero centrato con SVG richiesto
 * - Box “Scrivi una ricetta” full-width + margini aggiornati
 * - Due filtri con SVG diversi e icone centrate
 * - Tabelle pulite, più spazio a “Stato”, “Azioni” a destra
 * - Hover riga rimosso, azioni icon-only (matita nuova + swap)
 * - Mobile tweaks (≤760 e ≤480)
 * ============================================================ */

if (!defined('ABSPATH')) exit;

// [THUB_NONNA_VARS]
$user_id   = get_current_user_id();
$ajaxurl   = admin_url('admin-ajax.php');
$nonce_vis = wp_create_nonce('thub_recipe_vis_toggle');

$tax       = defined('THUB_PRO_TERM_TAX')  ? THUB_PRO_TERM_TAX  : 'category';
$pro_slug  = defined('THUB_PRO_TERM_SLUG') ? THUB_PRO_TERM_SLUG : 'pro';

// [THUB_NONNA_QUERY] — NON-Pro
$base_args = [
  'post_type'      => 'ricetta',
  'author'         => $user_id,
  'posts_per_page' => -1,
  'orderby'        => 'title',
  'order'          => 'ASC',
  'tax_query'      => [[ 'taxonomy'=>$tax, 'field'=>'slug', 'terms'=>[$pro_slug], 'operator'=>'NOT IN' ]],
];

$q_pub = new WP_Query( array_merge( $base_args, [ 'post_status' => ['publish', 'pending', 'draft'] ]) );
$q_prv = new WP_Query( array_merge( $base_args, [ 'post_status' => ['private'] ]) );

// Helper stato → badge + info (solo i 3 stati previsti)
function thub_nonna_status_ui($post){
  $ps     = get_post_status($post);
  $denied = (get_post_meta($post->ID, 'thub_approval_status', true) === 'denied');
  if ($ps === 'publish') return ['label'=>'Pubblicata','info'=>'Ricetta pubblicata','class'=>'thub-badge thub-badge--ok'];
  if ($ps === 'pending') return ['label'=>'In approvazione','info'=>'La ricetta è in fase di approvazione','class'=>'thub-badge thub-badge--pend'];
  if ($ps === 'draft' && $denied) return ['label'=>'Richiesta di modifica','info'=>'La ricetta non ha superato la verifica, modifica e pubblica nuovamente','class'=>'thub-badge thub-badge--warn'];
  return null; // esclude bozze “neutre”
}

// Build liste
$pub_items = [];
if ($q_pub->have_posts()){
  while($q_pub->have_posts()){ $q_pub->the_post();
    $ui = thub_nonna_status_ui( get_post() );
    if (!$ui) continue;
    $title  = get_the_title() ?: '(senza titolo)';
    $letter = strtoupper( mb_substr( ltrim($title), 0, 1 ) );
    $letter = preg_match('/[A-ZÀ-Ü]/u', $letter) ? $letter : '#';
    $pub_items[] = [
      'ID'=>get_the_ID(), 'title'=>$title, 'letter'=>$letter, 'ui'=>$ui,
      'edit'=>add_query_arg(['post_id'=>get_the_ID(),'mode'=>'edit'], home_url('/servizi/nuova-ricetta/')),
      'admin'=>admin_url('post.php?post='.get_the_ID().'&action=edit'),
      'view'=>get_permalink(),
    ];
  } wp_reset_postdata();
}
$prv_items = [];
if ($q_prv->have_posts()){
  while($q_prv->have_posts()){ $q_prv->the_post();
    $title  = get_the_title() ?: '(senza titolo)';
    $letter = strtoupper( mb_substr( ltrim($title), 0, 1 ) );
    $letter = preg_match('/[A-ZÀ-Ü]/u', $letter) ? $letter : '#';
    $prv_items[] = [
      'ID'=>get_the_ID(), 'title'=>$title, 'letter'=>$letter,
      'edit'=>add_query_arg(['post_id'=>get_the_ID(),'mode'=>'edit'], home_url('/servizi/nuova-ricetta/')),
      'admin'=>admin_url('post.php?post='.get_the_ID().'&action=edit'),
      'view'=>get_permalink(),
    ];
  } wp_reset_postdata();
}

/* =======================
   SVG inline (pastello)
   ======================= */
function thub_svg_nonnav2(){ ?>
  <!-- [THUB_SVG_NONNA] — versione richiesta -->
  <svg viewBox="0 0 120 120" width="72" height="72" role="img" aria-label="Ricette della nonna">
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
<?php }
function thub_svg_chefhat(){ ?>
  <!-- [THUB_SVG_CHEF] -->
  <svg viewBox="0 0 120 120" width="56" height="56" role="img" aria-label="Cappello da cuoco">
    <title>Cappello da cuoco — THUB Servizi</title>
    <rect x="10" y="20" width="100" height="80" rx="14" fill="#f4f2fb"></rect>
    <path d="M60 28c16 0 28 9 28 20v6H32v-6c0-11 12-20 28-20z" fill="#e9e1fb"></path>
    <rect x="40" y="58" width="40" height="30" rx="8" fill="#d9c9f6"></rect>
    <rect x="30" y="90" width="60" height="6" rx="3" fill="#c9b5f1"></rect>
  </svg>
<?php }
function thub_svg_filter(){ ?>
  <!-- [THUB_SVG_FILTER_LINES] -->
  <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
    <path d="M3 6h18M6 12h12M10 18h4" stroke="#7249a4" stroke-width="2" fill="none" stroke-linecap="round"/>
  </svg>
<?php }
function thub_svg_addressbook(){ ?>
  <!-- [THUB_SVG_ADDRESSBOOK] -->
  <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
    <rect x="4" y="3" width="15" height="18" rx="2" fill="none" stroke="#7249a4" stroke-width="2"/>
    <path d="M7 7h6M7 11h6M7 15h6" stroke="#7249a4" stroke-width="2" stroke-linecap="round"/>
    <path d="M20 6v2M20 11v2M20 16v2" stroke="#7249a4" stroke-width="2" stroke-linecap="round"/>
  </svg>
<?php }
function thub_svg_pencil(){ ?>
  <!-- [THUB_SVG_PENCIL] — matita nuova -->
  <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
    <path d="M3 17.25V21h3.75L17.5 10.25a2.5 2.5 0 0 0-3.54-3.54L3 17.25z" fill="none" stroke="#7249a4" stroke-width="2" stroke-linejoin="round"/>
    <path d="M11.5 6.75l3.75 3.75" stroke="#7249a4" stroke-width="2" stroke-linecap="round"/>
  </svg>
<?php }
function thub_svg_swap(){ ?>
  <!-- [THUB_SVG_SWAP] -->
  <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
    <path d="M7 7h10M7 7l3-3M7 7l3 3M17 17H7M17 17l-3-3M17 17l-3 3"
      stroke="#7249a4" stroke-width="2" fill="none" stroke-linecap="round"/>
  </svg>
<?php }
?>

<style>
/* ============================================================
   [THUB_NONNA_CSS]
   ============================================================ */
#thub-nonna{display:block}

/* HERO centrato */
.thub-hero{ display:flex; flex-direction:column; align-items:center; text-align:center; gap:.4rem; padding:.25rem 0 .2rem; }
.thub-hero__title{ margin:0; font-weight:700; font-size:clamp(20px,2.6vw,26px); }
.thub-hero__subtitle{ margin:.15rem 0 0; color:#555; }

/* Card “Scrivi una ricetta” — FULL WIDTH */
.thub-card--half{
  width: 100%; /* full width del contenitore */
  margin: 1.65rem auto 1.65rem; /* ⬅ aggiornato */
  display:grid; grid-template-columns: 72px 1fr; align-items:center; gap:.75rem;
  background:#fff; border:1px solid var(--border,#eee); border-radius:1rem; padding:.75rem .9rem;
}
.thub-card__title{ margin:0; font-weight:700; font-size:clamp(18px,2.2vw,22px); }
.thub-card__text{ margin:.2rem 0 .35rem; color:#555; }
.thub-link-cta{ text-decoration:none; font-weight:600; }

/* Filtri (icone centrate) */
.thub-filtersbar{ display:flex; justify-content:flex-end; gap:.45rem; margin:.25rem 0 .6rem; }
.thub-iconbtn{
  height:38px; width:auto; /* ⬅ aggiornato */
  display:grid; place-items:center; line-height:0;
  padding:0 .6rem; border:1px solid var(--border,#e6e6ea); border-radius:.7rem; background:#fff; cursor:pointer;
  transition: background .15s ease, transform .1s ease;
}
.thub-iconbtn svg{ display:block; width:20px; height:20px; }
.thub-iconbtn:hover{ background:#f7f5fb; transform: translateY(-1px); }
.thub-iconbtn:focus-visible{ outline:2px solid var(--violet,#7249a4); outline-offset:2px; }

/* Dropdown/modal */
.thub-modal{
  position:absolute; z-index:1200; right:0; margin-top:.35rem;
  min-width:220px; background:#fff; border:1px solid var(--border,#eee); border-radius:.8rem; box-shadow:0 6px 22px rgba(0,0,0,.08);
  padding:.5rem; display:none;
}
.thub-modal[aria-hidden="false"]{ display:block; }
.thub-modal h4{ margin:.2rem .2rem .5rem; font-size:.95rem; }
.thub-modal .thub-opt{ display:flex; align-items:center; gap:.5rem; padding:.35rem .4rem; border-radius:.45rem; }
.thub-modal .thub-opt:hover{ background:#f7f5fb; }
.thub-modal .thub-az{ max-height: 42vh; overflow:auto; scrollbar-width:none; -ms-overflow-style:none; }
.thub-modal .thub-az::-webkit-scrollbar{ width:0; height:0; }
.thub-modal .thub-az button{
  display:block; width:100%; text-align:left; padding:.4rem .5rem; border:0; background:transparent; cursor:pointer; border-radius:.4rem;
}
.thub-modal .thub-az button:hover{ background:#f7f5fb; }
.thub-modal .thub-az .is-active{ background:#ede7f5; }

/* Tabelle “pulite” */
.thub-table{ background:#fff; border:0; border-radius:.9rem; padding:.7rem; }
.thub-thead, .thub-trow{
  /* più spazio a “Stato” (200px) + Azioni (140px) */
  display:grid; grid-template-columns: 1fr 200px 140px; gap:.6rem; align-items:center;
}
.thub-thead{
  padding:.1rem .2rem .35rem; color:#666; font-weight:600;
  margin-top: 2.65rem; /* ⬅ aggiornato */
}
.thub-thead > div:last-child{ text-align:end; } /* ⬅ azioni a destra */
.thub-trow{ padding:.45rem .3rem; border-radius:.5rem; }
/* rimuovi hover riga */
.thub-trow:hover{ background:transparent; }

/* Box privato: 2 colonne + margine superiore richiesto */
.thub-priv .thub-thead, .thub-priv .thub-trow{ grid-template-columns: 1fr 140px; }
.thub-priv .thub-thead > div:last-child{ text-align:end; }
#thub-box-prv{ margin-top: 1.6rem; } /* ⬅ distanza dal box precedente */

/* Badges */
.thub-badge{
  display:inline-block; padding:.3rem .55rem; border-radius:.6rem; font-size:.86rem; line-height:1; border:1px solid #eee;
  background:#f7f7fa; color:#333;
}
.thub-badge--ok{ background:#eaf8ef; border-color:#d8f0e0; color:#245d39; }
.thub-badge--pend{ background:#fff7df; border-color:#f0e6c8; color:#6b5b2b; }
.thub-badge--warn{ background:#fff0f0; border-color:#f4d5d5; color:#8a3232; }

/* “i” info */
.thub-i{
  display:inline-grid; place-items:center; width:18px; height:18px; border:1px solid #ddd; border-radius:50%;
  font-size:.8rem; font-weight:700; margin-left:.4rem; cursor:pointer; user-select:none;
}
.thub-i:hover{ background:#f7f5fb; }
.thub-i[data-tip]{ position:relative; }
.thub-i[data-tip].is-open::after{
  content: attr(data-tip);
  position:absolute; left:50%; top:-8px; transform: translate(-50%, -100%);
  background:#111; color:#fff; white-space:nowrap; font-size:.8rem; padding:.35rem .5rem; border-radius:.4rem;
}

/* Azioni → icon-only */
.thub-actions{ display:flex; justify-content:flex-end; gap:.35rem; }
.thub-icbtn{
  width:36px; height:36px; display:grid; place-items:center;
  padding:0; border:1px solid var(--violet,#7249a4); color:var(--violet,#7249a4);
  background:#fff; border-radius:.6rem; text-decoration:none; cursor:pointer; line-height:0;
}
.thub-icbtn:hover{ background:#f7f5fb; }
.thub-icbtn svg{ width:18px; height:18px; display:block; }

/* Chips opzionali */
.thub-chips{ display:flex; gap:.35rem; justify-content:flex-end; margin:.35rem 0; }
.thub-chip{ font-size:.85rem; padding:.2rem .5rem; background:#f4effc; border:1px solid #e6dafc; border-radius:999px; }

/* Mobile */
@media (max-width:760px){
  .thub-card--half{ grid-template-columns: 56px 1fr; }
  .thub-thead{ display:none; }
  .thub-trow, .thub-priv .thub-trow{ grid-template-columns: 1fr; gap:.35rem; align-items:flex-start; }
  .thub-actions{ justify-content:flex-start; }
}
@media (max-width:480px){
  .thub-hero__title{ font-size: 20px; }
  .thub-hero__subtitle{ font-size: 14px; }
  .thub-card--half{ padding: .65rem .75rem; }
  .thub-iconbtn{ height:40px; }
  .thub-icbtn{   width:40px; height:40px; }
  .thub-trow{ padding:.4rem .25rem; }
  .thub-badge{ font-size:.84rem; }
}
</style>

<section id="thub-nonna" aria-labelledby="thub-nonna-title">
  <!-- HERO -->
  <div class="thub-hero">
    <div class="thub-hero__icon"><?php thub_svg_nonnav2(); ?></div>
    <h1 class="thub-hero__title" id="thub-nonna-title">Ricette della Nonna</h1>
    <p class="thub-hero__subtitle">Il ricettario con le tue creazioni pubbliche e private</p>
  </div>

  <!-- Card full: Scrivi una ricetta -->
  <div class="thub-card thub-card--half" aria-labelledby="scrivi-title">
    <div class="thub-card__icon"><?php thub_svg_chefhat(); ?></div>
    <div class="thub-card__body">
      <div class="thub-card__title" id="scrivi-title">Scrivi una ricetta</div>
      <div class="thub-card__text">Crea ricette gustose: piatti della tradizione locale o idee gourmet.</div>
      <a href="<?php echo esc_url( home_url('/servizi/nuova-ricetta/') ); ?>" class="thub-link-cta">Crea una ricetta -></a>
    </div>
  </div>

  <!-- Filtri -->
  <div class="thub-filtersbar" id="thub-nonna-filters" data-letter="">
    <div style="position:relative">
      <button type="button" class="thub-iconbtn" id="thub-filter-vis" aria-haspopup="dialog" aria-expanded="false" aria-controls="thub-modal-vis" aria-label="Filtra per visibilità"><?php thub_svg_filter(); ?></button>
      <div class="thub-modal" id="thub-modal-vis" role="dialog" aria-hidden="true" aria-label="Filtro visibilità">
        <h4>Mostra</h4>
        <label class="thub-opt"><input type="checkbox" id="thub-chk-pub" checked> Ricette pubbliche</label>
        <label class="thub-opt"><input type="checkbox" id="thub-chk-prv" checked> Ricette private</label>
      </div>
    </div>
    <div style="position:relative">
      <button type="button" class="thub-iconbtn" id="thub-filter-az" aria-haspopup="dialog" aria-expanded="false" aria-controls="thub-modal-az" aria-label="Filtra per iniziale"><?php thub_svg_addressbook(); ?></button>
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

  <!-- BOX 1 — Ricette pubbliche -->
  <div class="thub-table thub-pub" id="thub-box-pub">
    <div class="thub-box__title">Ricette pubbliche</div>
    <div class="thub-box__subtitle">Le tue creazioni culinarie visibili a tutti gli utenti nei risultati di ricerca.</div>

    <div class="thub-thead" aria-hidden="true">
      <div>Titolo della ricetta</div><div>Stato</div><div>Azioni</div>
    </div>

    <?php if (empty($pub_items)): ?>
      <div class="thub-trow"><em>Nessuna ricetta pubblica al momento.</em></div>
    <?php else: foreach ($pub_items as $it): $ui = $it['ui']; ?>
      <div class="thub-trow" data-letter="<?php echo esc_attr($it['letter']); ?>">
        <div><a href="<?php echo esc_url($it['view']); ?>" style="text-decoration:none;"><?php echo esc_html($it['title']); ?></a></div>
        <div>
          <span class="<?php echo esc_attr($ui['class']); ?>"><?php echo esc_html($ui['label']); ?></span>
          <span class="thub-i" data-tip="<?php echo esc_attr($ui['info']); ?>">i</span>
        </div>
        <div class="thub-actions">
          <a class="thub-icbtn" href="<?php echo esc_url( $it['edit'] ); ?>" title="Modifica" aria-label="Modifica"><?php thub_svg_pencil(); ?></a>
          <button class="thub-icbtn thub-move" data-post="<?php echo (int)$it['ID']; ?>" data-to="private" title="Sposta a privata" aria-label="Sposta a privata"><?php thub_svg_swap(); ?></button>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- BOX 2 — Ricette private -->
  <div class="thub-table thub-priv" id="thub-box-prv">
    <div class="thub-box__title">Ricette private</div>
    <div class="thub-box__subtitle">Le tue creazioni culinarie visibili solo a te.</div>

    <div class="thub-thead" aria-hidden="true">
      <div>Titolo della ricetta</div><div>Azioni</div>
    </div>

    <?php if (empty($prv_items)): ?>
      <div class="thub-trow"><em>Nessuna ricetta privata al momento.</em></div>
    <?php else: foreach ($prv_items as $it): ?>
      <div class="thub-trow" data-letter="<?php echo esc_attr($it['letter']); ?>">
        <div><a href="<?php echo esc_url($it['view']); ?>" style="text-decoration:none;"><?php echo esc_html($it['title']); ?></a></div>
        <div class="thub-actions">
          <a class="thub-icbtn" href="<?php echo esc_url( $it['edit'] ); ?>" title="Modifica" aria-label="Modifica"><?php thub_svg_pencil(); ?></a>
          <button class="thub-icbtn thub-move" data-post="<?php echo (int)$it['ID']; ?>" data-to="public" title="Sposta a pubblica (in approvazione)" aria-label="Sposta a pubblica"><?php thub_svg_swap(); ?></button>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</section>

<script>
/* ============================================================
   [THUB_NONNA_JS] — Filtri + Toggle info + Sposta (AJAX)
   ============================================================ */
(function(){
  const $  = (s, c=document)=>c.querySelector(s);
  const $$ = (s, c=document)=>Array.from(c.querySelectorAll(s));

  // Dropdown ancorati
  function bindDropdown(btnId, modalId){
    const btn = document.getElementById(btnId);
    const dd  = document.getElementById(modalId);
    if(!btn || !dd) return;
    const closeAll = ()=>{ dd.setAttribute('aria-hidden','true'); btn.setAttribute('aria-expanded','false');
      document.removeEventListener('click', onDoc, true); document.removeEventListener('keydown', onKey); };
    const onDoc = (e)=>{ if(!dd.contains(e.target) && !btn.contains(e.target)) closeAll(); };
    const onKey = (e)=>{ if(e.key === 'Escape') closeAll(); };
    btn.addEventListener('click', ()=>{
      const open = dd.getAttribute('aria-hidden') !== 'false';
      if(open){ dd.setAttribute('aria-hidden','false'); btn.setAttribute('aria-expanded','true');
        setTimeout(()=>{ document.addEventListener('click', onDoc, true); document.addEventListener('keydown', onKey); }, 0);
      } else { closeAll(); }
    });
  }
  bindDropdown('thub-filter-vis','thub-modal-vis');
  bindDropdown('thub-filter-az','thub-modal-az');

  // Visibilità
  const chkPub = document.getElementById('thub-chk-pub');
  const chkPrv = document.getElementById('thub-chk-prv');
  const boxPub = document.getElementById('thub-box-pub');
  const boxPrv = document.getElementById('thub-box-prv');
  function applyVis(){ if(boxPub) boxPub.style.display = chkPub?.checked ? '' : 'none';
                      if(boxPrv) boxPrv.style.display = chkPrv?.checked ? '' : 'none'; }
  chkPub?.addEventListener('change', applyVis);
  chkPrv?.addEventListener('change', applyVis);
  applyVis();

  // A–Z
  const chipWrap    = document.getElementById('thub-filter-chips');
  const chipLetter  = document.getElementById('chip-letter');
  $$('#thub-az-list button').forEach(b=>{
    b.addEventListener('click', ()=>{
      const L = b.getAttribute('data-letter') || '';
      $$('.thub-trow').forEach(r=>{
        const rl = (r.getAttribute('data-letter')||'').toUpperCase();
        r.style.display = L && rl !== L ? 'none' : '';
      });
      document.getElementById('thub-modal-az')?.setAttribute('aria-hidden','true');
      document.getElementById('thub-filter-az')?.setAttribute('aria-expanded','false');
      if(!chipWrap || !chipLetter) return;
      if(L){ chipLetter.textContent = 'Lettera: ' + L; chipLetter.hidden = false; chipWrap.hidden = false; }
      else { chipLetter.hidden = true; chipWrap.hidden = true; }
    });
  });

  // Info “i”
  $$('.thub-i[data-tip]').forEach(i=>{
    i.addEventListener('click', ()=>{
      const open = i.classList.toggle('is-open'); if(open){ setTimeout(()=> i.classList.remove('is-open'), 1600); }
    });
  });

  // Sposta (AJAX)
  const AJAX = '<?php echo esc_js($ajaxurl); ?>';
  const NONCE= '<?php echo esc_js($nonce_vis); ?>';
  $$('.thub-icbtn.thub-move').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const pid = btn.getAttribute('data-post');
      const to  = btn.getAttribute('data-to');
      if(!pid || !to) return;
      btn.disabled = true;
      try{
        const res = await fetch(AJAX, {
          method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}, credentials:'same-origin',
          body: new URLSearchParams({ action:'thub_toggle_recipe_visibility', _ajax_nonce:NONCE, post_id:pid, to:to }).toString()
        });
        const json = await res.json();
        if(!res.ok || !json || !json.success){ throw new Error(json?.data?.message || 'Errore'); }
        location.reload();
      }catch(err){
        alert('Impossibile spostare la ricetta: ' + (err.message||'Errore'));
        btn.disabled = false;
      }
    });
  });
})();
</script>
