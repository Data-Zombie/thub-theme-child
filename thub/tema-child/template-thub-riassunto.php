<?php
/**
 * Template Name: THUB Riassunto (auto)
 * Description: Mostra un riassunto sempre aggiornato leggendo i file del tema child + alberatura cartelle + export ZIP (files o alberatura).
 * Sicurezza: visibile agli admin O a chi possiede un token nell'URL (?thub_token=7f8ab4e5c1d24a0b9c3e6f1a2b4c8d0e9f12a3b4c5d6e7f8091a2b3c4d5e6f70).
 * [THUB_TEMPLATE] v1.2 — compat: &thub_zip=1 -> files; &thub_zip=files -> files; &thub_zip=tree -> alberatura
 */

if (!defined('ABSPATH')) exit; // [THUB_SECURITY] blocco diretto

/* ============================================================
 * [THUB_ACCESS] — Admin OPPURE Token in URL
 * - Imposta la pagina come "Pubblica"
 * - Condividi l'URL SOLO con il token
 * - Per revocare: cambia $THUB_TOKEN_VALUE
 * ============================================================ */
$THUB_TOKEN_VALUE = '7f8ab4e5c1d24a0b9c3e6f1a2b4c8d0e9f12a3b4c5d6e7f8091a2b3c4d5e6f70'; // <-- CAMBIA QUI se serve
$THUB_TOKEN_PARAM = 'thub_token';
$solo_admin = true;

$provided = isset($_GET[$THUB_TOKEN_PARAM]) ? sanitize_text_field( wp_unslash($_GET[$THUB_TOKEN_PARAM]) ) : '';
$token_ok = (is_string($provided) && $provided !== '' && hash_equals($THUB_TOKEN_VALUE, $provided));
$autorizzato = current_user_can('manage_options') || $token_ok;

if ($solo_admin && !$autorizzato) {
  status_header(404);
  nocache_headers();
  echo '<p>Pagina non disponibile.</p>';
  exit;
}

// [THUB_SEO] noindex
add_action('wp_head', function(){
  echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
}, 1);

// [THUB_NOCACHE] disabilita cache se accesso via token
if ($token_ok) {
  nocache_headers();
}

/* ============================================================
 * [THUB_TREE_CONFIG] — CONFIG ALBERATURA CARTELLE
 * - Aggiungi radici: label => path_assoluto
 * - Limita profondità con $tree_max_depth
 * - Filtri esclusioni con $exclude_patterns (regex)
 * ============================================================ */
$theme_dir = get_stylesheet_directory(); // cartella del tema child

$tree_roots = [
  'Tema child' => $theme_dir,
  // ESEMPI (decommenta se servono):
  // 'Parts' => $theme_dir . '/parts',
  // 'Uploads ' . date('Y') => WP_CONTENT_DIR . '/uploads/' . date('Y'),
];

$tree_max_depth = 6;
$exclude_patterns = [
  // Cartelle nascoste/VCS
  '#(^|/)\.(git|svn|hg)(/|$)#i',
  // Cartelle pesanti o non utili
  '#(^|/)(node_modules|vendor|cache|tmp|backup|backups)(/|$)#i',
  // File binari/archivi/font pesanti
  '#\.(log|md|map|lock|psd|ai|zip|tar|gz|br|woff2?)$#i',
  // Media pesanti (albero testuale pulito)
  '#\.(png|jpe?g|webp|avif|gif|mp4|mov|avi|mkv|mp3|wav|ogg)$#i',
];

/* [THUB_TREE_HELPER] — exclude checker */
function thub_should_exclude_path($path_rel, $exclude_patterns){
  foreach ($exclude_patterns as $re){
    if (preg_match($re, $path_rel)) return true;
  }
  return false;
}

/* [THUB_TREE_HTML] — costruisce HTML annidato dell’albero per una radice */
function thub_build_tree_html($root_abs, $root_label, $max_depth, $exclude_patterns){
  $root_abs = rtrim($root_abs, DIRECTORY_SEPARATOR);
  if (!is_dir($root_abs)) {
    return '<p class="thub-note">Radice non trovata: <code>'.esc_html($root_abs).'</code></p>';
  }

  $base_len = strlen($root_abs) + 1;

  $walker = function($dir_abs, $depth) use (&$walker, $max_depth, $exclude_patterns, $base_len){
    if ($depth > $max_depth) return '';
    $items = @scandir($dir_abs);
    if ($items === false) return '';

    $items = array_values(array_filter($items, function($x){ return $x !== '.' && $x !== '..'; }));
    usort($items, function($a, $b) use ($dir_abs){
      $pa = $dir_abs . DIRECTORY_SEPARATOR . $a;
      $pb = $dir_abs . DIRECTORY_SEPARATOR . $b;
      $isDirA = is_dir($pa);
      $isDirB = is_dir($pb);
      if ($isDirA !== $isDirB) return $isDirA ? -1 : 1;
      return strcasecmp($a, $b);
    });

    $html = '';
    foreach ($items as $name){
      $abs = $dir_abs . DIRECTORY_SEPARATOR . $name;
      $rel = substr($abs, $base_len);
      $rel_norm = str_replace('\\','/',$rel);

      if (thub_should_exclude_path($rel_norm, $exclude_patterns)) continue;

      $isDir = is_dir($abs);
      $mtime = @filemtime($abs);
      $mtime_str = $mtime ? date_i18n('Y-m-d H:i', $mtime) : '—';

      if ($isDir) {
        $children = $walker($abs, $depth + 1);
        $html .= '<li class="thub-tree-dir" data-type="dir" title="Cartella • Ultima modifica: '.$mtime_str.'">'
              .  '<button class="thub-tree-toggle" aria-label="Espandi/Comprimi"></button>'
              .  '<span class="thub-tree-name">'.esc_html($name).'/</span>'
              .  ($children ? '<ul>'.$children.'</ul>' : '')
              .  '</li>';
      } else {
        $html .= '<li class="thub-tree-file" data-type="file" title="File • Ultima modifica: '.$mtime_str.'">'
              .  '<span class="thub-tree-name">'.esc_html($name).'</span>'
              .  '</li>';
      }
    }
    return $html ? '<ul>'.$html.'</ul>' : '';
  };

  $tree_inner = $walker($root_abs, 1);
  $root_label_safe = esc_html($root_label);
  $root_rel = str_replace(ABSPATH, '', $root_abs);

  $out  = '<section class="thub-tree-root">';
  $out .=   '<h3>'.$root_label_safe.' <small style="font-weight:normal;color:#555;">('.esc_html($root_rel).')</small></h3>';
  $out .=   ($tree_inner ?: '<p class="thub-note">Nessun elemento (o tutti esclusi dai filtri).</p>');
  $out .= '</section>';

  return $out;
}

/* ============================================================
 * [THUB_FILES_LIST] — ELENCO FILE "SELEZIONATI" PER LA SEZIONE CONTENUTI
 * ============================================================ */
$files = [
  // Core [THUB_CORE]
  'functions.php'       => $theme_dir . '/functions.php',
  'style.css'           => $theme_dir . '/style.css',
  'assets/css/thub-recipe.css' => $theme_dir . '/assets/css/thub-recipe.css',
  'assets/js/thub-recipe.js'   => $theme_dir . '/assets/js/thub-recipe.js',
  'assets/js/thub-account.js'  => $theme_dir . '/assets/js/thub-account.js',

  // Struttura front-end [THUB_LAYOUT]
  'header.php'          => $theme_dir . '/header.php',
  'footer.php'          => $theme_dir . '/footer.php',
  'front-page.php'      => $theme_dir . '/front-page.php',

  // CPT Ricetta [THUB_CPT]
  'archive-ricetta.php' => $theme_dir . '/archive-ricetta.php',
  'single-ricetta.php'  => $theme_dir . '/single-ricetta.php',

  // Ricerca [THUB_SEARCH]
  'search.php'          => $theme_dir . '/search.php',
  'parts/thub-result-sponsored.php' => $theme_dir . '/parts/thub-result-sponsored.php',
  'parts/thub-result.php'           => $theme_dir . '/parts/thub-result.php',
  'parts/thub-search.php'           => $theme_dir . '/parts/thub-search.php',

  // Parts Ricetta [THUB_PARTS_RICETTA]
  'parts/ricetta/attrezzature.php' => $theme_dir . '/parts/ricetta/attrezzature.php',
  'parts/ricetta/cta-pro.php'      => $theme_dir . '/parts/ricetta/cta-pro.php',
  'parts/ricetta/hero.php'         => $theme_dir . '/parts/ricetta/hero.php',
  'parts/ricetta/ingredienti.php'  => $theme_dir . '/parts/ricetta/ingredienti.php',
  'parts/ricetta/meta.php'         => $theme_dir . '/parts/ricetta/meta.php',
  'parts/ricetta/passaggi.php'     => $theme_dir . '/parts/ricetta/passaggi.php',
  'parts/ricetta/print-share.php'  => $theme_dir . '/parts/ricetta/print-share.php',
  'parts/ricetta/varianti.php'     => $theme_dir . '/parts/ricetta/varianti.php',

  // Parts Header Button [THUB_PARTS_HEADER_BUTTON]
  'parts/account-modal.php' => $theme_dir . '/parts/account-modal.php',
  'parts/apps-modal.php'    => $theme_dir . '/parts/apps-modal.php',

  // Parts Account [THUB_PARTS_ACCOUNT]
  'parts/account/section-dati-privacy.php'            => $theme_dir . '/parts/account/section-dati-privacy.php',
  'parts/account/section-home.php'                    => $theme_dir . '/parts/account/section-home.php',
  'parts/account/section-informazioni-personali.php'  => $theme_dir . '/parts/account/section-informazioni-personali.php',
  'parts/account/section-informazioni.php'            => $theme_dir . '/parts/account/section-informazioni.php',
  'parts/account/section-pagamenti-abbonamenti.php'   => $theme_dir . '/parts/account/section-pagamenti-abbonamenti.php',
  'parts/account/section-sicurezza.php'               => $theme_dir . '/parts/account/section-sicurezza.php',

  // Parts Assistenza [THUB_PARTS_ASSISTENZA]
  'parts/assistenza/section-centro-assistenza.php'    => $theme_dir . '/parts/assistenza/section-centro-assistenza.php',
  'parts/assistenza/section-checklist-account-THUB.php' => $theme_dir . '/parts/assistenza/section-checklist-account-THUB.php',
  'parts/assistenza/section-community.php'            => $theme_dir . '/parts/assistenza/section-community.php',

  // Parts Classroom [THUB_PARTS_CLASSROOM]
  'parts/classroom/section-calendario.php'     => $theme_dir . '/parts/classroom/section-calendario.php',
  'parts/classroom/section-corsi-archiviati.php'=> $theme_dir . '/parts/classroom/section-corsi-archiviati.php',
  'parts/classroom/section-home.php'           => $theme_dir . '/parts/classroom/section-home.php',

  // Parts Console [THUB_PARTS_CONSOLE]
  'parts/console/section-dashboard.php' => $theme_dir . '/parts/console/section-dashboard.php',

  // Parts Cronologia [THUB_PARTS_CRONOLOGIA]
  'parts/cronologia/section-altro.php'      => $theme_dir . '/parts/cronologia/section-altro.php',
  'parts/cronologia/section-cronologia.php' => $theme_dir . '/parts/cronologia/section-cronologia.php',

  // Parts Gestione Profilo Attività [THUB_PARTS_ATTIVITA]
  'parts/gestione-profilo-attivita/section-foto.php'           => $theme_dir . '/parts/gestione-profilo-attivita/section-foto.php',
  'parts/gestione-profilo-attivita/section-home.php'           => $theme_dir . '/parts/gestione-profilo-attivita/section-home.php',
  'parts/gestione-profilo-attivita/section-modifica-profilo.php'=> $theme_dir . '/parts/gestione-profilo-attivita/section-modifica-profilo.php',
  'parts/gestione-profilo-attivita/section-post.php'           => $theme_dir . '/parts/gestione-profilo-attivita/section-post.php',
  'parts/gestione-profilo-attivita/section-pubblicita.php'     => $theme_dir . '/parts/gestione-profilo-attivita/section-pubblicita.php',
  'parts/gestione-profilo-attivita/section-recensioni.php'     => $theme_dir . '/parts/gestione-profilo-attivita/section-recensioni.php',
  'parts/gestione-profilo-attivita/section-rendimento.php'     => $theme_dir . '/parts/gestione-profilo-attivita/section-rendimento.php',

  // Parts Impostazioni [THUB_PARTS_IMPOSTAZIONI]
  'parts/impostazioni/section-impostazioni.php' => $theme_dir . '/parts/impostazioni/section-impostazioni.php',

  // Parts Lingua e Regione [THUB_PARTS_LANG]
  'parts/lingua-e-regione/section-lingua-di-visualizzazione.php' => $theme_dir . '/parts/lingua-e-regione/section-lingua-di-visualizzazione.php',
  'parts/lingua-e-regione/section-lingua-e-regione-dei-risultati.php' => $theme_dir . '/parts/lingua-e-regione/section-lingua-e-regione-dei-risultati.php',

  // Parts Servizi [THUB_PARTS_SERVIZI]
  'parts/servizi/section-home.php'              => $theme_dir . '/parts/servizi/section-home.php',
  'parts/servizi/section-raccolte.php'          => $theme_dir . '/parts/servizi/section-raccolte.php',
  'parts/servizi/section-ricette-dello-chef.php'=> $theme_dir . '/parts/servizi/section-ricette-dello-chef.php',
  'parts/servizi/section-ricette-salvate.php'   => $theme_dir . '/parts/servizi/section-ricette-salvate.php',

  // Pagine custom [THUB_PAGES]
  'page-login.php'                => $theme_dir . '/page-login.php',
  'page-password-dimenticata.php' => $theme_dir . '/page-password-dimenticata.php',
  'page-registrati.php'           => $theme_dir . '/page-registrati.php',
  'page-trova-email.php'          => $theme_dir . '/page-trova-email.php',
  'page-thub-canvas.php'          => $theme_dir . '/page-thub-canvas.php',

  // Template custom [THUB_TEMPLATES]
  'template-thub-riassunto.php'   => $theme_dir . '/template-thub-riassunto.php',
];

/* [THUB_FS] — lettura sicura dei file per la stampa */
function thub_read_esc($path){
  if (!file_exists($path)) return '— file non trovato —';
  return htmlspecialchars(file_get_contents($path));
}

/* ============================================================
 * [THUB_ZIP_HELPER] — aggiunge ALBERATURA allo ZIP preservando i percorsi
 * ============================================================ */
function thub_zip_add_tree(ZipArchive $zip, $root_abs, $base_in_zip, $exclude_patterns, $max_depth){
  $root_abs = rtrim($root_abs, DIRECTORY_SEPARATOR);
  if (!is_dir($root_abs)) return;

  $base_len = strlen($root_abs) + 1;
  $zip->addEmptyDir(rtrim($base_in_zip, '/').'/');

  $add = function($dir_abs, $depth) use (&$add, $zip, $base_in_zip, $exclude_patterns, $max_depth, $base_len){
    if ($depth > $max_depth) return;
    $items = @scandir($dir_abs);
    if ($items === false) return;

    foreach ($items as $name){
      if ($name === '.' || $name === '..') continue;
      $abs = $dir_abs . DIRECTORY_SEPARATOR . $name;
      $rel = substr($abs, $base_len);
      $rel_norm = str_replace('\\','/',$rel);

      if (thub_should_exclude_path($rel_norm, $exclude_patterns)) continue;

      $path_in_zip = rtrim($base_in_zip, '/') . '/' . $rel_norm;

      if (is_dir($abs)){
        $zip->addEmptyDir($path_in_zip . '/');
        $add($abs, $depth + 1);
      } else {
        $zip->addFile($abs, $path_in_zip);
      }
    }
  };

  $add($root_abs, 1);
}

/* ============================================================
 * [THUB_ZIP_HANDLER] — ESPORTA ZIP on-demand
 *  - &thub_zip=1     => selezione $files (retro-compat)
 *  - &thub_zip=files => selezione $files
 *  - &thub_zip=tree  => alberatura da $tree_roots (rispetta esclusioni/profondità)
 * ============================================================ */
$zip_param_raw = isset($_GET['thub_zip']) ? sanitize_text_field( wp_unslash($_GET['thub_zip']) ) : '';
$zip_mode = '';
if ($zip_param_raw === '1' || $zip_param_raw === 'files') {
  $zip_mode = 'files';
} elseif ($zip_param_raw === 'tree') {
  $zip_mode = 'tree';
}

if ( ($token_ok || current_user_can('manage_options')) && $zip_mode ){
  nocache_headers();

  if (!class_exists('ZipArchive')) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "ZipArchive non disponibile su questo hosting.";
    exit;
  }

  $zip_name = 'thub-snapshot-' . $zip_mode . '-' . date('Ymd-His') . '.zip';
  $zip_tmp  = tempnam(sys_get_temp_dir(), 'thubzip');
  $zip      = new ZipArchive();

  if ($zip->open($zip_tmp, ZipArchive::OVERWRITE) !== true) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Impossibile creare lo ZIP temporaneo.";
    exit;
  }

  if ($zip_mode === 'files') {
    // [THUB_ZIP_FILES] — replica esatto comportamento precedente (piatto)
    foreach ($files as $label => $path) {
      if (file_exists($path)) {
        $zip->addFile($path, 'hello-child/' . basename($path));
      }
    }
  } else {
    // [THUB_ZIP_TREE] — nuova modalità a ALBERATURA
    $prefix = 'thub'; // cartella radice nello zip

    // Slug sicuri per i nomi delle radici
    $mk_slug = function($text){
      $text = strtolower( trim( preg_replace('~[^\pL\d]+~u', '-', $text), '-' ) );
      $text = preg_replace('~[^-\w]+~', '', $text);
      return $text ?: 'root';
    };

    foreach ($tree_roots as $label => $root_abs){
      $root_abs = rtrim($root_abs, DIRECTORY_SEPARATOR);
      if (!is_dir($root_abs)) continue;

      $slug = $mk_slug($label);
      $base_in_zip = $prefix . '/' . $slug;

      thub_zip_add_tree($zip, $root_abs, $base_in_zip, $exclude_patterns, $tree_max_depth);
    }
  }

  $zip->close();

  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="' . $zip_name . '"');
  header('Content-Length: ' . filesize($zip_tmp));
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');

  readfile($zip_tmp);
  @unlink($zip_tmp);
  exit;
}

/* ============================================================
 * [THUB_VIEW] — OUTPUT HTML
 * ============================================================ */
get_header(); ?>
<main class="thub-doc wrap">
  <!-- [THUB_TITLE] -->
  <h1>THUB — Riassunto auto-aggiornato</h1>
  <p><strong>Generato:</strong> <?php echo esc_html( date_i18n('Y-m-d H:i') ); ?></p>

  <?php if ($autorizzato):
    // Link export (mostriamo entrambi per comodità)
    $zip_href_tree  = esc_url( add_query_arg('thub_zip', 'tree') );
    $zip_href_files = esc_url( add_query_arg('thub_zip', 'files') ); // alias di &thub_zip=1
    $zip_available  = class_exists('ZipArchive');
  ?>
    <div class="thub-top-actions">
      <a class="thub-btn thub-btn-zip" href="<?php echo $zip_href_tree; ?>"
         <?php echo $zip_available ? '' : 'aria-disabled="true" tabindex="-1"'; ?>>
        <!-- [THUB_ICON] download -->
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v10m0 0l-4-4m4 4l4-4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 17h16v2H4z" /></svg>
        <span>Scarica ZIP (alberatura)</span>
      </a>
      <a class="thub-btn thub-btn-zip" href="<?php echo $zip_href_files; ?>"
         <?php echo $zip_available ? '' : 'aria-disabled="true" tabindex="-1"'; ?>>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v10m0 0l-4-4m4 4l4-4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 17h16v2H4z" /></svg>
        <span>Scarica ZIP (files selezionati)</span>
      </a>
      <?php if (!$zip_available): ?>
        <small class="thub-note">ZipArchive non disponibile su questo hosting.</small>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- [THUB_TREE_SECTION] — ALBERATURA -->
  <h2>Alberatura cartelle</h2>
  <p class="thub-note">Suggerimento: clicca sulle freccette per espandere/comprimere le cartelle. Filtri esclusi: node_modules, vendor, cache, immagini pesanti, ecc.</p>

  <div class="thub-tree">
    <?php
      foreach ($tree_roots as $label => $root){
        echo thub_build_tree_html($root, $label, $tree_max_depth, $exclude_patterns);
      }
    ?>
  </div>

  <!-- [THUB_FILES_SECTION] — LISTA FILE DEL TEMA CHILD -->
  <h2>File del tema child</h2>
  <?php foreach ($files as $label => $path):
    $exists = file_exists($path);
    $rel    = str_replace(ABSPATH, '', $path);
    $mtime  = $exists ? date_i18n('Y-m-d H:i', filemtime($path)) : '—';
  ?>
    <section class="thub-file" id="<?php echo esc_attr(str_replace(['.php','.css'], '', $label)); ?>">
      <h3>
        <?php echo esc_html($label); ?>
        <small style="font-weight:normal; color:#555;">
          (<?php echo esc_html($rel); ?><?php echo $exists ? " • ultima modifica: ".esc_html($mtime) : ""; ?>)
        </small>
      </h3>
      <pre><code><?php echo $exists ? thub_read_esc($path) : '— file non trovato —'; ?></code></pre>
    </section>
  <?php endforeach; ?>
</main>

<style>
  /* [THUB_STYLE] — Stili minimi di leggibilità */
  .thub-doc.wrap { max-width: 1100px; margin: 0 auto; padding: 1rem; }
  .thub-doc h1 { margin: .5rem 0 0; font-size: 1.6rem; }
  .thub-doc h2 { margin-top: 1.6rem; border-bottom: 1px solid #e5e7eb; padding-bottom: .35rem; }
  .thub-file h3 { margin-top: 1.2rem; }
  .thub-doc pre { background: #0b1020; color: #e2e8f0; border: 1px solid #1f2937; border-radius: .6rem; padding: 1rem; overflow:auto; }
  .thub-doc code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .92rem; }

  /* Disattiva header/footer fissi su questa pagina */
  header.site-header.thub-header, .site-footer.thub-footer { position: static !important; }
  body { padding-top: 0 !important; padding-bottom: 0 !important; }
  
  /* Bottoni ZIP */
  .thub-top-actions { margin: .5rem 0 1rem; display: flex; align-items: center; gap: .75rem; }
  .thub-btn.thub-btn-zip {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .55rem .9rem; border: 1px solid #e5e7eb; border-radius: .6rem;
    background: #ffffff; color: #111827; text-decoration: none; font-weight: 600; cursor: pointer;
    transition: background .15s ease, border-color .15s ease, transform .1s ease, box-shadow .15s ease;
  }
  .thub-btn.thub-btn-zip:hover { background: #f8fafc; border-color: #d1d5db; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
  .thub-btn.thub-btn-zip[aria-disabled="true"] { opacity: .55; pointer-events: none; }
  .thub-btn.thub-btn-zip svg { width: 18px; height: 18px; display: block; }
  .thub-note { color:#6b7280; }

  /* Albero cartelle */
  .thub-tree { margin: .8rem 0 1rem; }
  .thub-tree-root { margin-bottom: 1rem; }
  .thub-tree-root > h3 { margin: .9rem 0 .4rem; }
  .thub-tree-root ul { list-style: none; margin: .2rem 0 .2rem 1rem; padding: 0; }
  .thub-tree-root li { position: relative; margin: .15rem 0; line-height: 1.35; }
  .thub-tree-name { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .95rem; }

  /* Toggle a triangolino */
  .thub-tree-toggle {
    width: 1rem; height: 1rem; display: inline-block; margin-right: .35rem; border: none; background: transparent; cursor: pointer; position: relative; top: .1rem;
  }
  .thub-tree-toggle::before {
    content: ''; display: inline-block; width: 0; height: 0;
    border-left: .35rem solid currentColor; border-top: .28rem solid transparent; border-bottom: .28rem solid transparent;
    transform: rotate(0deg); transition: transform .12s ease;
  }
  .thub-tree-dir > ul { display: none; }
  .thub-tree-dir.open > ul { display: block; }
  .thub-tree-dir.open > .thub-tree-toggle::before { transform: rotate(90deg); }
  .thub-tree-dir .thub-tree-name { color: #111827; }
  .thub-tree-file .thub-tree-name { color: #374151; }
</style>

<script>
/* [THUB_JS] — Espandi/Comprimi cartelle */
document.addEventListener('DOMContentLoaded', function(){
  // Apri la prima cartella di ciascuna radice per comodità
  document.querySelectorAll('.thub-tree-root').forEach(function(root){
    const first = root.querySelector('li.thub-tree-dir');
    if (first) first.classList.add('open');
  });
  // Toggle su click
  document.querySelectorAll('.thub-tree-toggle').forEach(function(btn){
    btn.addEventListener('click', function(e){
      const li = e.target.closest('li.thub-tree-dir');
      if (li) li.classList.toggle('open');
    });
  });
});
</script>

<?php
/* [THUB_FOOTER] — chiusura */
get_footer();