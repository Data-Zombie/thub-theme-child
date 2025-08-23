<?php
/* ============================================================
 * [THUB_SINGLE_RICETTA] Template singola ricetta — allineato a JS porzioni/kcal
 * - Ingredienti: usa partial parts/ricetta/ingredienti (repeater + data-*)
 * - Gating Free/Pro: stampa & share bloccati e step limitati ai non‑Pro
 * - Sponsor claim + logo (unico blocco)
 * - Title/Intro (150) con fallback
 * - UI porzioni: ID/class allineati a thub-recipe.js
 * - Varianti/Vini/Attrezzature integrati
 * ============================================================ */
get_header();
?>

<main class="container thub-recipe"><!-- [THUB_CONTAINER] -->
  <?php if (have_posts()): while (have_posts()): the_post();
    $post_id = get_the_ID();

    /* --- [THUB_INTRO] Intro breve (150) – se mancante, dall’estratto/contenuto --- */
    $intro_raw  = function_exists('get_field') ? (string) get_field('intro_breve') : '';
    $intro_auto = $intro_raw ?: get_the_excerpt();
    if (!$intro_auto) $intro_auto = wp_trim_words(wp_strip_all_tags(get_the_content('')), 28, '…'); // ~150 char approx
    $intro_150 = thub_trim_chars($intro_auto, 150);

    /* --- [THUB_SPONSOR] Dati sponsor (per-ricetta → per-categoria) --- */
    $spons = thub_get_sponsor_data($post_id); // array|null

    /* --- [THUB_META] Meta tempi/porzioni/kcal --- */
    $prep  = function_exists('get_field') ? (string) get_field('tempo_di_preparazione') : '';
    $cook  = function_exists('get_field') ? (string) get_field('tempo_di_cottura')     : '';

    // Porzioni di base: usa 'porzioni_base' (nuovo), fallback 'porzioni' (legacy), default 1
    $porz_base = (int) ( (function_exists('get_field') ? get_field('porzioni_base') : null)
                  ?: (function_exists('get_field') ? get_field('porzioni') : 1) ?: 1 );

    // Kcal per porzione (per UI dinamica)
    $kcal_porz = (float) (function_exists('get_field') ? (get_field('kcal_per_porz') ?: 0) : 0);
    $kcal_tot  = $kcal_porz ? (int) round($kcal_porz * max(1,$porz_base)) : 0;

    /* --- [THUB_GATING_FLAGS] --- */
    $is_pro      = thub_is_pro_user();
    $limit_steps = thub_free_steps_limit();
  ?>
    <article class="ricetta"><!-- [THUB_ARTICLE] -->
      <header class="ricetta-header"><!-- [THUB_HEADER] -->

        <!-- [THUB_ACTIONS] Pulsanti con gating -->
        <div class="ricetta-actions">
          <?php if ($is_pro): ?>
            <button class="btn-print" onclick="window.print()" aria-label="Stampa la ricetta">🖨️ Stampa</button>
            <button class="btn-share" onclick="thubShare()" aria-label="Condividi la ricetta">🔗 Condividi</button>
          <?php else: ?>
            <button class="btn-print is-locked" data-lock-msg="Disponibile con Pro" aria-label="Stampa disponibile con Pro">🖨️ Stampa</button>
            <button class="btn-share is-locked" data-lock-msg="Disponibile con Pro" aria-label="Condividi disponibile con Pro">🔗 Condividi</button>
          <?php endif; ?>
        </div>

        <h1 class="ricetta-title"><?php the_title(); ?></h1><!-- [THUB_TITLE] -->

        <?php if ($spons): ?><!-- [THUB_SPONSOR_CLAIM] Unico blocco sponsor -->
          <?php
            $logo_url = !empty($spons['logo']) ? wp_get_attachment_image_url($spons['logo'],'thumbnail') : '';
            $nome     = (string) ($spons['nome']  ?? '');
            $claimTxt = (string) ($spons['claim'] ?? '«%s» consiglia questa ricetta');
            $url      = (string) ($spons['url']   ?? '');
            $claim    = sprintf($claimTxt, $nome ?: 'Il nostro partner');
          ?>
          <div class="thub-sponsor-claim">
            <?php if ($logo_url): ?>
              <img class="thub-sponsor-claim__logo" src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($nome); ?>">
            <?php endif; ?>
            <span class="thub-sponsor-claim__text"><?php echo esc_html($claim); ?></span>
            <?php if ($url): ?>
              <a class="thub-sponsor-claim__link" href="<?php echo esc_url($url); ?>" rel="sponsored noopener">Scopri</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (has_post_thumbnail()): ?>
          <figure class="ricetta-cover"><?php the_post_thumbnail('large', ['class'=>'cover-img','loading'=>'eager']); ?></figure>
        <?php endif; ?>

        <?php if ($intro_150): ?>
          <p class="ricetta-intro"><?php echo esc_html($intro_150); ?></p>
        <?php endif; ?>

        <!-- [THUB_META_LIST] Meta + UI porzioni (allineata a thub-recipe.js) -->
        <ul class="meta">
          <?php if ($prep): ?><li><strong>Prep:</strong> <?php echo esc_html($prep); ?></li><?php endif; ?>
          <?php if ($cook): ?><li><strong>Cottura:</strong> <?php echo esc_html($cook); ?></li><?php endif; ?>
          <li><strong>Porzioni:</strong> <span id="thub-porzioni-label"><?php echo esc_html($porz_base); ?></span></li>
          <?php if ($kcal_porz): ?>
            <li><strong>Kcal/porz:</strong> <span id="thub-kcal-porz"><?php echo (int) $kcal_porz; ?></span></li>
            <li><strong>Kcal tot:</strong> <span id="thub-kcal-tot"><?php echo (int) $kcal_tot; ?></span></li>
          <?php endif; ?>
        </ul>

        <?php
        /* [THUB_PORZ_UI] UI porzioni — class/ID secondo lo script:
           - .thub-porzioni-ui → dataset basePorzioni + kcalPorz
           - #thub-porzioni (input number)
           - #thub-porzioni-label (nel blocco meta sopra, aggiornato dallo script)
           - #thub-porz-dyn → già stampato nel partial ingredienti come contatore dinamico
           - #thub-kcal-porz / #thub-kcal-tot gestiti dal JS
           Riferimento selettori JS: thub-recipe.js (porzioni/kcal) :contentReference[oaicite:1]{index=1}
        */
        ?>
        <div class="thub-porzioni-ui"
             data-base-porzioni="<?php echo esc_attr($porz_base); ?>"
             data-kcal-porz="<?php echo esc_attr($kcal_porz); ?>">
          <button type="button" class="thub-porz-minus" aria-label="Diminuisci porzioni">–</button>
          <input type="number" id="thub-porzioni" value="<?php echo (int) $porz_base; ?>" min="1" inputmode="numeric" pattern="[0-9]*" aria-label="Numero porzioni">
          <button type="button" class="thub-porz-plus" aria-label="Aumenta porzioni">+</button>
          <div class="thub-porz-quick" aria-label="Porzioni rapide">
            <button type="button" data-q="1">1</button>
            <button type="button" data-q="2">2</button>
            <button type="button" data-q="4">4</button>
            <button type="button" data-q="6">6</button>
          </div>
        </div>
      </header>

      <?php
      /* [THUB_INGREDIENTI_NEW] Ingredienti dal partial (repeater + data-*) — scala con JS
         Il partial stampa: <ul id="thub-ingredienti-list"> con <li class="thub-ing" data-base-qta …>
         Richiesto dal JS per l’update delle quantità. :contentReference[oaicite:2]{index=2} */
      get_template_part('parts/ricetta/ingredienti');
      ?>

      <section class="passaggi"><!-- [THUB_PASSAGGI] Gating step -->
        <h2>Preparazione</h2>
        <?php
          // Compat: campo passaggi come testo multilinea (se non stai ancora usando il repeater)
          $steps_raw = trim((string) (function_exists('get_field') ? get_field('passaggi') : ''));
          if ($steps_raw) {
            $rows  = array_values(array_filter(preg_split("/\r\n|\r|\n/", $steps_raw), fn($x)=>$x!==''));
            $total = count($rows);
            $show  = $is_pro ? $total : min($limit_steps, $total);

            echo '<ol>';
            for ($i=0; $i<$show; $i++) {
              echo '<li>'.esc_html($rows[$i]).'</li>';
            }
            echo '</ol>';

            if (!$is_pro && $total > $limit_steps) {
              // [THUB_CTA_PRO]
              echo '<div class="thub-cta-pro" role="note"><p><strong>Iscriviti a Pro</strong> per sbloccare tutti gli step, video e stampa.</p><a class="thub-btn thub-btn--pro" href="/abbonati" rel="nofollow">Attiva Pro</a></div>';
            }
          } else {
            echo '<p>Nessun passaggio indicato.</p>';
          }
        ?>
      </section>

      <?php
      /* [THUB_VARIANTI] Carousel varianti via partial dedicato (scroll-snap + frecce) */
      get_template_part('parts/ricetta/varianti');
      ?>

      <?php
      /* [THUB_ATTREZZATURE] Icone + testo (legge il repeater se popolato) */
      $atts = function_exists('get_field') ? get_field('attrezzature') : [];
      if ($matts = $atts){
        echo '<section class="attrezzature"><h2>Attrezzature</h2><div class="att-grid">';
        foreach($matts as $a){
          $k = $a['att_icone_key'] ?? '';
          $label = $a['att_testo'] ?? '';
          $svg_inline = '';
          if ($k==='custom_svg' && !empty($a['att_svg_custom'])){
            $path = get_attached_file($a['att_svg_custom']);
            if ($path && file_exists($path)) {
              $ft = wp_check_filetype($path);
              if (!empty($ft['type']) && $ft['type']==='image/svg+xml') {
                $svg_inline = file_get_contents($path); // ATTENZIONE: inline consentito perché file da Media
              }
            }
          }
          echo '<div class="att-item">';
          echo   '<div class="att-icon">'.($svg_inline ?: '').'</div>';
          echo   '<div class="att-text">'.esc_html($label).'</div>';
          echo '</div>';
        }
        echo '</div></section>';
      }
      ?>

      <?php
      /* [THUB_VINI] Repeater semplice */
      $vini = function_exists('get_field') ? get_field('vini') : [];
      if ($vini){
        echo '<section class="vini"><h2>Vini suggeriti</h2><ul>';
        foreach($vini as $w){
          $nome = $w['vino_nome'] ?? '';
          $den  = $w['vino_denominazione'] ?? '';
          $url  = $w['vino_link'] ?? '';
          echo '<li>'.esc_html($nome).($den?' – '.esc_html($den):'').($url?' <a href="'.esc_url($url).'" rel="nofollow">scheda</a>':'').'</li>';
        }
        echo '</ul></section>';
      }
      ?>

      <?php
      /* [THUB_NOTE_TECNICHE] Pro only */
      $note = trim((string) (function_exists('get_field') ? get_field('eventuali_note_tecniche') : ''));
      if ($note) {
        if ($is_pro) {
          echo '<section class="note"><h3>Note tecniche</h3><div class="note-content">'.$note.'</div></section>'; // wysiwyg: niente esc_html
        } else {
          echo '<section class="note"><h3>Note tecniche</h3><div class="thub-cta-pro" role="note"><p>Disponibili con Pro.</p><a class="thub-btn thub-btn--pro" href="/abbonati" rel="nofollow">Attiva Pro</a></div></section>';
        }
      }

      /* [THUB_VIDEO] Pro only (se esiste video_url) */
      $video_url = (string) (function_exists('get_field') ? get_field('video_url') : '');
      if ($video_url) {
        if ($is_pro) {
          echo '<section class="video"><h3>Video ricetta</h3><div class="video-embed"><iframe src="'.esc_url($video_url).'" loading="lazy" allowfullscreen></iframe></div></section>';
        } else {
          echo '<section class="video"><h3>Video ricetta</h3><div class="thub-cta-pro" role="note"><p>Disponibile con Pro.</p><a class="thub-btn thub-btn--pro" href="/abbonati" rel="nofollow">Attiva Pro</a></div></section>';
        }
      }
      ?>
    </article>
  <?php endwhile; endif; ?>
</main>

<!-- [THUB_JS] Tooltip per pulsanti bloccati + Share -->
<script>
  // [THUB_LOCK_TOOLTIP] tooltip bottoni locked
  (function(){
    document.querySelectorAll('.is-locked').forEach(function(btn){
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var msg = btn.getAttribute('data-lock-msg') || 'Disponibile con Pro';
        btn.setAttribute('data-thub-tooltip', msg);
        btn.classList.add('show-tip');
        setTimeout(function(){ btn.classList.remove('show-tip'); }, 1400);
      });
    });
  })();

  // [THUB_SHARE] Condivisione robusta (Web Share API + fallback)
  function thubShare(){
    var data = { title: document.title, url: location.href };
    if (navigator.share) {
      navigator.share(data).catch(function(){ /* utente annulla */ });
    } else if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(location.href).then(function(){ alert('Link copiato negli appunti'); })
      .catch(function(){ prompt('Copia il link:', location.href); });
    } else {
      prompt('Copia il link:', location.href);
    }
  }
</script>

<style>
/* [THUB_MINICSS] pulsanti/tooltip + sponsor + grid attrezzature */
.thub-cta-pro{background:#f7f2ff;border:1px solid #e6dafc;padding:12px;border-radius:10px;margin-top:10px}
.thub-btn{display:inline-block;border:1px solid #7249a4;padding:8px 12px;border-radius:10px;text-decoration:none}
.thub-btn--pro{background:#7249a4;color:#fff}
.btn-print.is-locked,.btn-share.is-locked{opacity:.65;position:relative;cursor:not-allowed}
.is-locked.show-tip::after{
  content: attr(data-thub-tooltip);
  position:absolute;top:-34px;left:50%;transform:translateX(-50%);
  background:#111;color:#fff;font-size:.8rem;padding:4px 8px;border-radius:6px;white-space:nowrap
}
.ricetta-cover .cover-img{border-radius:12px;display:block}
.meta{display:flex;gap:10px;flex-wrap:wrap;list-style:none;padding:0;margin:10px 0}
.meta li{background:#f7f7f7;border-radius:999px;padding:6px 10px;font-size:.9rem}
.thub-porzioni-ui{display:flex;align-items:center;gap:8px;margin:6px 0 4px}
.thub-porz-quick button{margin-left:6px}
.thub-porzioni-ui input{width:64px;text-align:center;padding:6px 8px;border:1px solid #ccc;border-radius:8px}
.thub-porz-minus,.thub-porz-plus{width:32px;height:32px;border-radius:8px;border:1px solid #ccc;background:#fff}
.att-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px}
.att-item{border:1px solid #eee;border-radius:12px;padding:10px;background:#fff;text-align:center}
.att-icon svg{width:36px;height:36px;display:inline-block}
</style>

<?php get_footer(); ?>