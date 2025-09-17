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
    
    // Porzioni di base (solo CANONICO): 'porzioni_base' (default 1)
    $porz_base = (int) ( function_exists('get_field')
      ? ( get_field('porzioni_base') ?: 1 )
      : ( get_post_meta($post_id, 'porzioni_base', true) ?: 1 )
    );
    $porz_base = max(1, $porz_base);

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
          <?php
            /* --------------------------------------------
            * [THUB_ACTIONS_VARS]
            * - $is_pro: stato abbonamento
            * - $is_saved: stato preferito corrente
            * - $ajaxurl / $fav_nonce / $login_url: per toggle AJAX preferiti
            * -------------------------------------------- */
            $is_pro = false;
            if (function_exists('thub_current_user_is_pro')) {
              $is_pro = (bool) thub_current_user_is_pro();
            } elseif (function_exists('thub_is_pro_user')) {
              $is_pro = (bool) thub_is_pro_user();
            } else {
              $is_pro = (bool) apply_filters('thub_is_pro_user', false);
            }

            $post_id   = get_the_ID();
            $user_id   = get_current_user_id();
            $ajaxurl   = admin_url('admin-ajax.php');
            $fav_nonce = wp_create_nonce('thub_fav_toggle');
            $login_url = wp_login_url( get_permalink($post_id) );

            if (function_exists('thub_is_recipe_saved_by_user')) {
              $is_saved = $user_id ? thub_is_recipe_saved_by_user($user_id, $post_id) : false;
            } else {
              $arr = (array) get_user_meta($user_id, 'thub_saved_recipes', true);
              $is_saved = $user_id ? in_array((int)$post_id, array_map('intval',$arr), true) : false;
            }

            $gating_msg = __('Disponibile con Pro', 'thub');
          ?>

          <?php if ($is_pro): ?>
            <!-- [THUB_PRINT_BTN] Stampa libera -->
            <button class="btn-print"
                    onclick="window.print()"
                    aria-label="<?php esc_attr_e('Stampa la ricetta','thub'); ?>">🖨️ <?php esc_html_e('Stampa','thub'); ?></button>

            <!-- [THUB_SHARE_BTN] Condividi libero -->
            <button class="btn-share"
                    onclick="thubShare()"
                    aria-label="<?php esc_attr_e('Condividi la ricetta','thub'); ?>">🔗 <?php esc_html_e('Condividi','thub'); ?></button>
          <?php else: ?>
            <!-- [THUB_PRINT_BTN_LOCKED] Stampa bloccata (tooltip JS su click) -->
            <button class="btn-print is-locked"
                    data-lock-msg="<?php echo esc_attr($gating_msg); ?>"
                    aria-label="<?php esc_attr_e('Stampa disponibile con Pro','thub'); ?>">🖨️ <?php esc_html_e('Stampa','thub'); ?></button>

            <!-- [THUB_SHARE_BTN_LOCKED] Condividi bloccata (tooltip JS su click) -->
            <button class="btn-share is-locked"
                    data-lock-msg="<?php echo esc_attr($gating_msg); ?>"
                    aria-label="<?php esc_attr_e('Condividi disponibile con Pro','thub'); ?>">🔗 <?php esc_html_e('Condividi','thub'); ?></button>
          <?php endif; ?>

          <!-- ==========================================
              [THUB_FAV_TOGGLE_BTN] PREFERITI (★/cuore)
              - Sempre visibile (se non loggato → redirect a /login?redirect_to=…)
              - Catturato da [THUB_FAV_TOGGLE_JS] in thub-recipe.js
              ========================================== -->
          <button
            type="button"
            class="btn-fav thub-btn thub-btn--fav <?php echo $is_saved ? 'is-on' : ''; ?>"
            data-action="fav-toggle"
            data-ajax="<?php echo esc_url($ajaxurl); ?>"
            data-nonce="<?php echo esc_attr($fav_nonce); ?>"
            data-post="<?php echo (int) $post_id; ?>"
            data-login-url="<?php echo esc_url($login_url); ?>"
            aria-pressed="<?php echo $is_saved ? 'true' : 'false'; ?>"
            aria-label="<?php echo $is_saved ? esc_attr__('Rimuovi dai preferiti','thub') : esc_attr__('Salva nei preferiti','thub'); ?>"
            title="<?php echo $is_saved ? esc_attr__('Rimuovi dai preferiti','thub') : esc_attr__('Salva nei preferiti','thub'); ?>"
          >
            <!-- Icona cuore 18px -->
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
              <path d="M12 21s-6.7-3.9-8.4-7.3C1.9 10.4 3.7 8 6.1 8c1.7 0 3 1.2 3.9 2.4C10.9 9.2 12.2 8 13.9 8c2.4 0 4.2 2.4 2.5 5.7C18.7 17.1 12 21 12 21z" fill="currentColor"/>
            </svg>
            <span class="thub-fav-label"><?php echo $is_saved ? esc_html__('Salvata','thub') : esc_html__('Salva','thub'); ?></span>
          </button>
        </div>

        <?php
        /* ============================================================
          [THUB_TITLE_INLINE_TTS] — H1 con pulsante TTS in coda al testo
          - Il bottone è dentro l’H1, così resta subito dopo l’ultima lettera.
          - Visibile solo se thub_tts_recipes = 'on' per l’utente loggato.
          ============================================================ */
        $__tts_on = is_user_logged_in() ? ( get_user_meta( get_current_user_id(), 'thub_tts_recipes', true ) === 'on' ) : false;
        ?>
        <h1 class="ricetta-title"><?php the_title(); ?>
          <?php if ( $__tts_on ) : ?>
            <button class="thub-tts-btn thub-tts-btn--inline"
                    type="button"
                    aria-label="Leggi la ricetta a voce alta"
                    title="Leggi la ricetta"
                    aria-pressed="false">
              <!-- [THUB_ICON_AUDIO_LIGHT] icona centrata -->
              <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
                <rect x="1" y="3" width="22" height="18" rx="4" fill="#eaf2ff"></rect>
                <path d="M3 10v4h4l5 5V5L7 10H3z" fill="#7c8cf8"></path>
                <path d="M16.5 12a4.5 4.5 0 0 0-2.5-4v8a4.5 4.5 0 0 0 2.5-4z" fill="#9bbcf7"></path>
                <path d="M19 12a7 7 0 0 1-3.5 6.06v-12.1A7 7 0 0 1 19 12z" fill="#cfe0ff"></path>
              </svg>
            </button>
          <?php endif; ?>
        </h1>

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
            Riferimento selettori JS: thub-recipe.js (porzioni/kcal)
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
         Richiesto dal JS per l’update delle quantità. */
      get_template_part('parts/ricetta/ingredienti');
      ?>

      <?php
      /* ============================================================
        [THUB_TTS_TEXT_BLOCK] — Testo invisibile “pulito” per TTS
        Versione CANONICO-ONLY:
        - Ingredienti da ingredienti_rep (repeater)
        - Passaggi   da passaggi_rep    (repeater)
        - Nessun uso di campi legacy (ingredienti/passaggi textarea)
        - Requisiti JS: esistenza di [data-ricetta-tts] (thub-recipe.js) [THUB_TTS_USE_HELPERS_CANONICO]
        ============================================================ */
      if ( $__tts_on ) :
        $titolo = get_the_title();
        $post_id = get_the_ID();

        /* --- Ingredienti: ingredienti_rep → stringa unica --- */
        $ingredienti_txt = '';
        if ( function_exists('get_field') ) {
          $ing_rows = (array) get_field('ingredienti_rep', $post_id); // repeater canonico
          if ($ing_rows) {
            $buf = [];
            foreach ($ing_rows as $r) {
              $nome = trim((string)($r['ing_nome'] ?? ''));
              $qta  = trim((string)($r['ing_qta']  ?? ''));
              $unit = trim((string)($r['ing_unita'] ?? ''));
              if (strcasecmp($unit, 'altro') === 0) {
                $unit = trim((string)($r['ing_unita_altro'] ?? ''));
              }
              // Costruisco “qta unita nome” (senza doppi spazi)
              $line = trim(implode(' ', array_filter([$qta, $unit, $nome])));
              if ($line !== '') $buf[] = $line;
            }
            if ($buf) $ingredienti_txt = implode(', ', $buf);
          }
        }

        /* --- Passaggi: passaggi_rep → "1. step 2. step ..." --- */
        $passaggi_txt = '';
        if ( function_exists('get_field') ) {
          $pas_rows = (array) get_field('passaggi_rep', $post_id); // repeater canonico
          if ($pas_rows) {
            $i = 0; $buf = [];
            foreach ($pas_rows as $r) {
              $t = trim((string)($r['passo_testo'] ?? ''));
              if ($t !== '') $buf[] = (++$i).'. '.wp_strip_all_tags($t);
            }
            if ($buf) $passaggi_txt = implode(' ', $buf);
          }
        }
        ?>
        <!-- [THUB_TTS_STYLE_FIX] wrapper invisibile (richiesto da thub-recipe.js) -->
        <div data-ricetta-tts class="screen-reader-only" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
          <?php
            $tts_full = trim(
              $titolo . '. ' .
              ( $ingredienti_txt ? ('Ingredienti: ' . $ingredienti_txt . '. ') : '' ) .
              ( $passaggi_txt    ? ('Passaggi: '    . $passaggi_txt          ) : '' )
            );
            echo esc_html( $tts_full );
          ?>
        </div>
      <?php endif; ?>

      <?php
      /* [THUB_PASSAGGI_INCLUDE] Preparazione: partial canonico con repeater 'passaggi_rep' */
      get_template_part('parts/ricetta/passaggi');
      ?>

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
          // [THUB_ATTREZZATURE_ICON_FALLBACK] prepara HTML icona: prima prova SVG custom, altrimenti fallback predefinito
          $icon_html = $svg_inline ?: ( function_exists('thub_icon_svg') && !empty($k) ? thub_icon_svg($k) : '' );
          echo '<div class="att-icon">'.$icon_html.'</div>';
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