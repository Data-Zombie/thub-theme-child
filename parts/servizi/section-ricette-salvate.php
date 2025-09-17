<?php
/* =====================================================================
 * [THUB_SERVIZI_SALVATE] — Sezione "Ricette salvate" (Canvas Servizi)
 * Pixel-perfect 1:1 con “Nonna/Chef” + Azione “Rimuovi dai preferiti”
 * - Filtri: Mostra (Salvate|Bozze), Lettera A–Z
 * - Tabella 1: Visualizza + Rimuovi (AJAX thub_remove_favorite)
 * - Tabella 2: Modifica bozza
 * ===================================================================== */

if (!defined('ABSPATH')) exit;

$current_user_id = get_current_user_id();
if (!$current_user_id){
  echo '<div class="thub-box">Devi essere loggato per vedere le tue ricette salvate.</div>';
  return;
}

/* --------------------------------------------
 * [THUB_FAV_LIST] Recupero preferiti utente
 * -------------------------------------------- */
if (function_exists('thub_get_saved_recipes_for_user')) {
  $fav_ids = (array) thub_get_saved_recipes_for_user($current_user_id);
} else {
  $fav_ids = (array) get_user_meta($current_user_id, 'thub_saved_recipes', true);
  $fav_ids = array_values(array_unique(array_filter(array_map('intval', $fav_ids))));
}

/* [THUB_QUERY_SAVED] Salvate visibili (A→Z) */
$saved_query = new WP_Query([
  'post_type'      => 'ricetta',
  'post__in'       => $fav_ids ?: [0],
  'post_status'    => ['publish','private'],
  'perm'           => 'readable', // [THUB_SAFETY] evita private non leggibili
  'orderby'        => 'title',
  'order'          => 'ASC',
  'posts_per_page' => -1,
]);

/* [THUB_QUERY_DRAFTS] Bozze autore (A→Z) */
$drafts_query = new WP_Query([
  'post_type'      => 'ricetta',
  'author'         => $current_user_id,
  'post_status'    => 'draft',
  'orderby'        => 'title',
  'order'          => 'ASC',
  'posts_per_page' => -1,
]);

/* [THUB_HELPER_LETTER] Iniziale A–Z o # */
$thub_initial_letter = function($text){
  $t = ltrim(wp_strip_all_tags((string) $text));
  if ($t === '') return '#';
  $ch = mb_substr($t, 0, 1, 'UTF-8');
  $up = mb_strtoupper($ch, 'UTF-8');
  return preg_match('/[A-ZÀ-ÖØ-Ý]/u', $up) ? $up : '#';
};

/* [THUB_AJAX_VARS] per rimuovere dai preferiti */
$ajaxurl   = admin_url('admin-ajax.php');
$nonce_fav = wp_create_nonce('thub_fav'); // check_ajax_referer('thub_fav','_ajax_nonce')
?>

<section id="thub-salvate" data-ajax="<?php echo esc_url($ajaxurl); ?>"><!-- [THUB_SECTION_ROOT] -->

  <!-- =========================================================
       [THUB_HERO] Testata (pixel-perfect con Nonna/Chef)
       ========================================================= -->
  <div class="thub-hero">
    <div class="thub-hero__icon" aria-hidden="true">
      <!-- Icona cuore 72×72 -->
      <svg viewBox="0 0 120 120" width="72" height="72" role="img" aria-label="Preferiti">
        <title>Ricette salvate — THUB Servizi</title>
        <rect x="14" y="18" width="92" height="84" rx="12" fill="#fff0f5"></rect>
        <path d="M60 92s-24-14-30-26c-6-12 2-22 12-22 7 0 12 5 18 12 6-7 11-12 18-12 10 0 18 10 12 22-6 12-30 26-30 26z" fill="#ffc2d6"></path>
        <circle cx="44" cy="48" r="2" fill="#ff84a7"></circle>
        <circle cx="76" cy="48" r="2" fill="#ff84a7"></circle>
      </svg>
    </div>
    <h1 class="thub-hero__title">Ricette Salvate</h1>
    <p class="thub-hero__subtitle">Le tue bozze e le ricette che hai aggiunto ai preferiti: tutto in un unico spazio.</p>
  </div>

  <!-- =========================================================
       [THUB_CARD_CTA] Scrivi una ricetta (pixel-perfect)
       ========================================================= -->
  <div class="thub-card--half">
    <div class="thub-card__icon" aria-hidden="true">
      <svg viewBox="0 0 120 120" width="72" height="72" role="img" aria-label="Cappello da cuoco">
        <title>Cappello da cuoco — THUB Servizi</title>
        <rect x="10" y="20" width="100" height="80" rx="14" fill="#f4f2fb"></rect>
        <path d="M60 28c16 0 28 9 28 20v6H32v-6c0-11 12-20 28-20z" fill="#e9e1fb"></path>
        <rect x="40" y="58" width="40" height="30" rx="8" fill="#d9c9f6"></rect>
        <rect x="30" y="90" width="60" height="6" rx="3" fill="#c9b5f1"></rect>
      </svg>
    </div>
    <div>
      <div class="thub-card__title">Scrivi una ricetta</div>
      <p class="thub-card__text">Crea ricette gustose, piatti della tradizione locale e ricette gourmet. Scegli quali mostrare a tutti e quali tenere solo nel tuo ricettario.</p>
      <a class="thub-link-cta" href="<?php echo esc_url( home_url('/servizi/nuova-ricetta/') ); ?>">Crea una ricetta →</a>
    </div>
  </div>

  <!-- =========================================================
       [THUB_FILTERS] Mostra | Lettera iniziale (pixel-perfect)
       ========================================================= -->
  <div class="thub-filtersbar">
    <!-- Mostra -->
    <div class="thub-filter">
      <button type="button" class="thub-iconbtn" id="thub-filter-vis" aria-expanded="false" aria-controls="thub-modal-vis" aria-haspopup="dialog" aria-label="Mostra">
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M3 6h18M6 12h12M10 18h4" stroke="#7249a4" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
      </button>
      <div class="thub-modal" id="thub-modal-vis" role="dialog" aria-hidden="true" aria-label="Filtra cosa mostrare">
        <h4>Mostra</h4>
        <label class="thub-opt"><input type="checkbox" id="thub-chk-saved" checked> Ricette salvate</label>
        <label class="thub-opt"><input type="checkbox" id="thub-chk-draft" checked> Ricette in bozza</label>
      </div>
    </div>

    <!-- Lettera iniziale -->
    <div class="thub-filter">
      <button type="button" class="thub-iconbtn" id="thub-filter-az" aria-expanded="false" aria-controls="thub-modal-az" aria-haspopup="dialog" aria-label="Filtra per iniziale">
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

  <!-- =========================================================
       [THUB_SAVED] Tabella Ricette salvate — Visualizza + Rimuovi
       ========================================================= -->
  <div class="thub-table thub-saved" id="thub-box-saved" data-nonce-fav="<?php echo esc_attr($nonce_fav); ?>">
    <div class="thub-box__title">Ricette salvate</div>
    <div class="thub-box__subtitle">Le tue ricette preferite, tutte raccolte in un unico spazio.</div>

    <div class="thub-thead" aria-hidden="true">
      <div>Titolo della ricetta</div><div>Azioni</div>
    </div>

    <div id="thub-saved-list">
    <?php if ($saved_query->have_posts()): while($saved_query->have_posts()): $saved_query->the_post();
      $pid    = get_the_ID();
      $title  = get_the_title() ?: '(senza titolo)';
      $letter = $thub_initial_letter($title);
      $plink  = get_permalink($pid);
    ?>
      <div class="thub-trow" data-letter="<?php echo esc_attr($letter); ?>" data-post="<?php echo (int)$pid; ?>">
        <div><a class="thub-row__link" href="<?php echo esc_url($plink); ?>"><?php echo esc_html($title); ?></a></div>
        <div class="thub-actions">
          <!-- Visualizza -->
          <a class="thub-icbtn" href="<?php echo esc_url($plink); ?>" aria-label="<?php echo esc_attr('Visualizza “'.$title.'”'); ?>">
            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" role="img"><path d="M12 5c4.5 0 8.4 2.7 10 7-1.6 4.3-5.5 7-10 7S3.6 16.3 2 12c1.6-4.3 5.5-7 10-7Zm0 2C8.7 7 5.9 8.9 4.5 12 5.9 15.1 8.7 17 12 17s6.1-1.9 7.5-5C18.1 8.9 15.3 7 12 7Zm0 2.5A2.5 2.5 0 1 1 9.5 12 2.5 2.5 0 0 1 12 9.5Z"></path></svg>
          </a>

          <!-- Rimuovi dai preferiti (AJAX) -->
          <button class="thub-icbtn thub-unfav" type="button"
                  data-post="<?php echo (int)$pid; ?>"
                  data-nonce="<?php echo esc_attr($nonce_fav); ?>"
                  title="Rimuovi dai preferiti" aria-label="Rimuovi dai preferiti">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img" focusable="false">
              <!-- Cuore (riempito col currentColor = colore del bottone) -->
              <path fill="currentColor" d="M12 21s-6.7-3.9-9.3-8.1C1.1 10.4 2.3 7 5.6 6.3c1.7-.3 3.3.3 4.4 1.5 1.1-1.2 2.7-1.8 4.4-1.5 3.3.7 4.5 4.1 2.9 6.6C18.8 15.7 16 18 12 21z"></path>
              <!-- Slash nero in diagonale sopra al cuore -->
              <path d="M4 4 L20 20" stroke="#000" stroke-width="2.25" stroke-linecap="round"></path>
            </svg>
          </button>
        </div>
      </div>
    <?php endwhile; wp_reset_postdata(); else: ?>
      <div class="thub-trow"><em>Nessuna ricetta salvata.</em></div>
    <?php endif; ?>
    </div>
  </div>

  <!-- =========================================================
       [THUB_DRAFTS] Tabella Bozze — Modifica
       ========================================================= -->
  <div class="thub-table thub-drafts" id="thub-box-drafts">
    <div class="thub-box__title">Ricette in bozza</div>
    <div class="thub-box__subtitle">Le tue creazioni culinarie ancora in fase di scrittura.</div>

    <div class="thub-thead" aria-hidden="true">
      <div>Titolo della ricetta</div><div>Azioni</div>
    </div>

    <div id="thub-drafts-list">
    <?php if ($drafts_query->have_posts()): while($drafts_query->have_posts()): $drafts_query->the_post();
      $pid    = get_the_ID();
      $title  = get_the_title() ?: '(senza titolo)';
      $letter = $thub_initial_letter($title);
      $edit   = add_query_arg(['post_id'=>$pid,'mode'=>'edit'], home_url('/servizi/nuova-ricetta/'));
    ?>
      <div class="thub-trow" data-letter="<?php echo esc_attr($letter); ?>">
        <div><span><?php echo esc_html($title); ?></span></div>
        <div class="thub-actions">
          <a class="thub-icbtn" href="<?php echo esc_url($edit); ?>" aria-label="<?php echo esc_attr('Modifica “'.$title.'”'); ?>">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M3 17.25V21h3.75L17.8 9.95l-3.75-3.75L3 17.25Zm14.71-9.04a1 1 0 0 0 0-1.41L15.2 4.29a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.66Z" fill="currentColor"/></svg>
            <span class="visually-hidden">Modifica</span>
          </a>
        </div>
      </div>
    <?php endwhile; wp_reset_postdata(); else: ?>
      <div class="thub-trow"><em>Non ci sono bozze.</em></div>
    <?php endif; ?>
    </div>
  </div>

  <!-- =========================================================
       [THUB_STYLES] CSS "pixel-perfect" (clonato Nonna/Chef)
       ========================================================= -->
  <style>
    /* HERO — 72×72, clamp titoli, spacing */
    #thub-salvate .thub-hero{ display:flex; flex-direction:column; align-items:center; text-align:center; gap:.4rem; padding:.25rem 0 .2rem; }
    #thub-salvate .thub-hero__title{ margin:0; font-weight:700; font-size:clamp(20px,2.6vw,26px); }
    #thub-salvate .thub-hero__subtitle{ margin:.15rem 0 0; color:#555; }
    #thub-salvate .thub-hero__icon{ width:72px; height:72px; margin: 0 auto .5rem; }
    #thub-salvate .thub-hero__icon svg{ width:72px; height:72px; display:block; }

    /* Card CTA — griglia 72px|1fr, margini/padding */
    #thub-salvate .thub-card--half{
      width: 100%;
      margin: 1.65rem auto 1.65rem;
      display:grid; grid-template-columns: 72px 1fr; align-items:center; gap:.75rem;
      background:#fff; border:1px solid var(--border,#eee); border-radius:1rem; padding:.75rem .9rem;
    }
    #thub-salvate .thub-card__title{ margin:0; font-weight:700; font-size:clamp(18px,2.2vw,22px); }
    #thub-salvate .thub-card__text{ margin:.2rem 0 .35rem; color:#555; }
    #thub-salvate .thub-link-cta{ text-decoration:none; font-weight:600; }

    /* Filtri — bottoni 38px, dropdown 220px, raggio .8rem */
    #thub-salvate .thub-filtersbar{ display:flex; justify-content:flex-end; gap:.45rem; margin:.25rem 0 .6rem; flex-wrap:wrap; }
    #thub-salvate .thub-iconbtn{
      height:38px; width:auto; line-height:0;
      display:grid; place-items:center;
      padding:0 .6rem; border:1px solid var(--border,#e6e6ea); border-radius:.7rem; background:#fff; cursor:pointer;
      transition: background .15s ease, transform .1s ease;
    }
    #thub-salvate .thub-iconbtn svg{ display:block; width:20px; height:20px; }
    #thub-salvate .thub-iconbtn:hover{ background:#f7f5fb; transform: translateY(-1px); }
    #thub-salvate .thub-iconbtn:focus-visible{ outline:2px solid var(--violet,#7249a4); outline-offset:2px; }

    #thub-salvate .thub-filter{ position:relative; }
    #thub-salvate .thub-modal{
      position:absolute; z-index:1200; right:0; margin-top:.35rem;
      min-width:220px; background:#fff; border:1px solid var(--border,#eee); border-radius:.8rem; box-shadow:0 6px 22px rgba(0,0,0,.08);
      padding:.5rem; display:none;
    }
    #thub-salvate .thub-modal[aria-hidden="false"]{ display:block; }
    #thub-salvate .thub-modal h4{ margin:.2rem .2rem .5rem; font-size:.95rem; }
    #thub-salvate .thub-modal .thub-opt{ display:flex; align-items:center; gap:.5rem; padding:.35rem .4rem; border-radius:.45rem; }
    #thub-salvate .thub-modal .thub-opt:hover{ background:#f7f5fb; }
    #thub-salvate .thub-modal .thub-az{ max-height: 42vh; overflow:auto; scrollbar-width:none; -ms-overflow-style:none; }
    #thub-salvate .thub-modal .thub-az::-webkit-scrollbar{ width:0; height:0; }
    #thub-salvate .thub-modal .thub-az button{
      display:block; width:100%; text-align:left; padding:.4rem .5rem; border:0; background:transparent; cursor:pointer; border-radius:.4rem;
    }
    #thub-salvate .thub-modal .thub-az button:hover{ background:#f7f5fb; }

    /* Titoli box */
    #thub-salvate .thub-box__title{ font-weight:800; margin:.25rem .35rem .35rem; }
    #thub-salvate .thub-box__subtitle{ color:#666; margin:0 .35rem .6rem; }

    /* Tabelle — default 3 col (Nonna), override 2 col (Chef) */
    #thub-salvate .thub-thead,
    #thub-salvate .thub-trow{
      display:grid; grid-template-columns: 1fr 200px 140px; gap:.6rem; align-items:center;
    }
    #thub-salvate .thub-thead{ padding:.1rem .2rem .35rem; color:#666; font-weight:600; margin-top: 2.65rem; }
    #thub-salvate .thub-thead > div:last-child{ text-align:end; }
    #thub-salvate .thub-trow{ padding:.45rem .3rem; border-radius:.5rem; }
    #thub-salvate .thub-row__link{ text-decoration:none; color:#222; }
    #thub-salvate .thub-row__link:hover{ color:#7249a4; }

    /* Azioni (Chef style) — icone 18px, btn 36×36, bordo violet */
    #thub-salvate .thub-actions{ display:flex; justify-content:flex-end; gap:.35rem; }
    #thub-salvate .thub-icbtn{
      width:36px; height:36px; display:grid; place-items:center;
      padding:0; border:1px solid var(--violet,#7249a4); color:var(--violet,#7249a4);
      background:#fff; border-radius:.6rem; text-decoration:none; cursor:pointer; line-height:0;
    }
    #thub-salvate .thub-icbtn:hover{ background:#f7f5fb; }
    #thub-salvate .thub-icbtn svg{ width:18px; height:18px; display:block; }

    /* Override 2 colonne per le nostre tabelle (Titolo | Azioni) */
    #thub-salvate .thub-saved .thub-thead,
    #thub-salvate .thub-saved .thub-trow,
    #thub-salvate .thub-drafts .thub-thead,
    #thub-salvate .thub-drafts .thub-trow{
      grid-template-columns: 1fr 140px;
    }

    /* Empty state */
    #thub-salvate .thub-trow em{ color:#666; }

    /* Responsive */
    @media (max-width:760px){
      #thub-salvate .thub-card--half{ grid-template-columns: 56px 1fr; }
      #thub-salvate .thub-thead{ display:none; }
      #thub-salvate .thub-trow{ grid-template-columns: 1fr; gap:.35rem; align-items:flex-start; }
      #thub-salvate .thub-actions{ justify-content:flex-start; }
    }
    @media (max-width:480px){
      #thub-salvate .thub-hero__title{ font-size: 20px; }
      #thub-salvate .thub-hero__subtitle{ font-size: 14px; }
      #thub-salvate .thub-card--half{ padding: .65rem .75rem; }
      #thub-salvate .thub-iconbtn{ height:40px; }
      #thub-salvate .thub-icbtn{   width:40px; height:40px; }
      #thub-salvate .thub-trow{ padding:.4rem .25rem; }
    }
  </style>

  <!-- =========================================================
       [THUB_JS] Filtri + Unfavorite (AJAX) — pixel-match
       ========================================================= -->
  <script>
  (function(){
    'use strict';
    const $  = (s, c=document)=> c.querySelector(s);
    const $$ = (s, c=document)=> Array.from((c||document).querySelectorAll(s));

    const root      = document.getElementById('thub-salvate');
    const ajaxUrl   = root?.getAttribute('data-ajax') || '';
    const boxSaved  = document.getElementById('thub-box-saved');
    const savedList = document.getElementById('thub-saved-list');
    const boxDraft  = document.getElementById('thub-box-drafts');
    const nonceFav  = boxSaved?.getAttribute('data-nonce-fav') || '';

    /* Dropdowns ancorati */
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

    /* Mostra (Salvate | Bozze) */
    const chkSaved = document.getElementById('thub-chk-saved');
    const chkDraft = document.getElementById('thub-chk-draft');
    function applyVis(){
      if (boxSaved) boxSaved.style.display = (chkSaved && chkSaved.checked) ? '' : 'none';
      if (boxDraft) boxDraft.style.display = (chkDraft && chkDraft.checked) ? '' : 'none';
    }
    chkSaved?.addEventListener('change', applyVis);
    chkDraft?.addEventListener('change', applyVis);
    applyVis();

    /* Lettera A–Z */
    $$('#thub-az-list button').forEach(b=>{
      b.addEventListener('click', ()=>{
        const L = (b.getAttribute('data-letter')||'').toUpperCase();
        ['thub-saved-list','thub-drafts-list'].forEach(id=>{
          const body = document.getElementById(id);
          if(!body) return;
          $$('.thub-trow', body).forEach(r=>{
            const rl = (r.getAttribute('data-letter')||'').toUpperCase();
            r.style.display = (L && L!=='' && rl !== L) ? 'none' : '';
          });
        });
        // chiudi modale
        const modalAz  = document.getElementById('thub-modal-az');
        const filterAz = document.getElementById('thub-filter-az');
        if (modalAz)  modalAz.setAttribute('aria-hidden','true');
        if (filterAz) filterAz.setAttribute('aria-expanded','false');
      });
    });

    /* Unfavorite AJAX */
    function handleEmptySaved(){
      if (!savedList) return;
      const rows = $$('.thub-trow', savedList).filter(r=>!r.classList.contains('thub-row--empty'));
      if (rows.length === 0 && !savedList.querySelector('.thub-row--empty')) {
        const empty = document.createElement('div');
        empty.className = 'thub-trow thub-row--empty';
        empty.innerHTML = '<em>Nessuna ricetta salvata.</em>';
        savedList.appendChild(empty);
      }
    }

    document.addEventListener('click', async (ev)=>{
      const btn = ev.target.closest?.('.thub-unfav');
      if (!btn || !savedList) return;

      const postId = btn.getAttribute('data-post');
      const nonce  = btn.getAttribute('data-nonce') || nonceFav;

      btn.disabled = true;

      try{
        const fd = new FormData();
        fd.append('action', 'thub_remove_favorite'); // [THUB_AJAX_ACTION]
        fd.append('_ajax_nonce', nonce);             // check_ajax_referer('thub_fav','_ajax_nonce')
        fd.append('post_id', postId);

        const res  = await fetch(ajaxUrl, { method:'POST', credentials:'same-origin', body: fd });
        const json = await res.json();

        if (!res.ok || !json || json.success !== true) {
          btn.disabled = false; return;
        }

        // Rimuovi riga
        btn.closest('.thub-trow')?.remove();
        handleEmptySaved();
      }catch(e){
        btn.disabled = false;
      }
    });

    // Prima valutazione “vuoto”
    handleEmptySaved();
  })();
  </script>
</section>