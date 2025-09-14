<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );



// Includi "ricetta" nella ricerca front-end (solo se post_type non è già impostato)
add_action('pre_get_posts', function ($q) {
  if (!is_admin() && $q->is_main_query() && $q->is_search()) {
    if (empty($q->get('post_type'))) {
      $q->set('post_type', ['ricetta', 'post']); // default “misto” solo se non specificato
    }
  }
});

// Placeholder intelligente per il titolo delle Ricette
add_filter('enter_title_here', function ($title, $post) {
  if ($post->post_type === 'ricetta') return 'Nome della ricetta (es. Spaghetti alla Carbonara)';
  return $title;
}, 10, 2);


// 1.0 Consenti upload SVG e ICO
add_filter('upload_mimes', function($mimes){
  $mimes['svg'] = 'image/svg+xml';
  $mimes['ico'] = 'image/x-icon'; // oppure 'image/vnd.microsoft.icon'
  return $mimes;
});

// 1.1 Sistema il controllo mime/estensione per SVG e ICO
add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes){
  $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  if ($ext === 'svg') {
    $data['ext']  = 'svg';
    $data['type'] = 'image/svg+xml';
  } elseif ($ext === 'ico') {
    $data['ext']  = 'ico';
    $data['type'] = 'image/x-icon';
  }
  return $data;
}, 10, 4);

// Esempio: logo bianco su pagine categoria "Night"
add_filter('get_custom_logo', function($html){
  if (is_category('night')) {
    $url = get_stylesheet_directory_uri() . '/assets/logo-thub-food-skyline-light.svg';
    $html = '<a href="'.esc_url(home_url('/')).'" class="custom-logo-link" rel="home">'.
            '<img class="custom-logo" src="'.esc_url($url).'" alt="Logo THUB" /></a>';
  }
  return $html;
});

/* === Disattiva Gutenberg (usa editor classico) === */
// Disattiva per tutti i singoli post/pagine/CPT
add_filter('use_block_editor_for_post', '__return_false', 10);
// Disattiva per tutti i tipi di post (post, page, ricetta, ecc.)
add_filter('use_block_editor_for_post_type', '__return_false', 10);

/* === Header: supporto logo + menu === */
add_action('after_setup_theme', function () {
  add_theme_support('custom-logo', [
    'height'      => 120,
    'width'       => 300,
    'flex-height' => true,
    'flex-width'  => true,
  ]);
  add_theme_support('title-tag');
  register_nav_menus([
    'primary' => 'Menu principale',
  ]);
});

/* === SEO: JSON-LD Recipe (solo HEAD) === */
if (!function_exists('thub_minutes_from_text')) {
  function thub_minutes_from_text($text){
    $text = strtolower(trim((string)$text));
    $h = 0; $m = 0;
    if (preg_match('/(\d+)\s*h/', $text, $mm)) $h = (int)$mm[1];
    if (preg_match('/(\d+)\s*m/', $text, $nn)) $m = (int)$nn[1];
    if ($h === 0 && $m === 0 && preg_match('/\d+/', $text, $kk)) $m = (int)$kk[0]; // solo numero → minuti
    return max(0, $h*60 + $m);
  }
}
if (!function_exists('thub_iso_duration_from_minutes')) {
  function thub_iso_duration_from_minutes($minutes){
    $minutes = max(0, (int)$minutes);
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    $out = 'PT';
    if ($h > 0) $out .= $h.'H';
    if ($m > 0) $out .= $m.'M';
    if ($out === 'PT') $out = 'PT0M';
    return $out;
  }
}

// ─────────────────────────────────────────────────────────────
// THUB: size per loghi sponsor (quadrata, crop)
// ─────────────────────────────────────────────────────────────
add_action('after_setup_theme', function(){
  // 64x64 ritagliata: ideale per il bollino tondo
  add_image_size('thub_sponsor_logo', 64, 64, true); // crop hard
});

/**
 * Normalizza telefono in “solo cifre”.
 */
function thub_norm_phone_for_meta($raw){
    return preg_replace('/\D+/', '', $raw ?? '');
}

/**
 * Hook authenticate: se username non è email, prova a trattarlo come telefono.
 * Se trova un utente con user_meta 'phone_number' uguale (normalizzato),
 * verifica la password e autentica quell’utente.
 */
add_filter('authenticate', function($user, $username, $password){
    // Se già autenticato o se è vuoto, lascia correre
    if ($user instanceof WP_User || empty($username) || empty($password)) {
        return $user;
    }

    // Se è un’email, lascia la gestione standard
    if (is_email($username)) return $user;

    // Altrimenti tentiamo come telefono
    $norm = thub_norm_phone_for_meta($username);
    if (!$norm) return $user;

    // Cerca utente con phone_number = $norm
    $args = [
        'meta_key'   => 'phone_number',
        'meta_value' => $norm,
        'number'     => 1,
        'count_total'=> false,
        'fields'     => 'all',
    ];
    $users = get_users($args);
    if (empty($users) || !($users[0] instanceof WP_User)) {
        // Heuristica: se inizia con 39 prova anche senza prefisso
        if (strpos($norm, '39') === 0) {
            $alt = substr($norm, 2);
            if ($alt) {
                $args['meta_value'] = $alt;
                $users = get_users($args);
            }
        }
    }

    if (!empty($users) && $users[0] instanceof WP_User) {
        $candidate = $users[0];
        // Verifica password
        if (wp_check_password($password, $candidate->user_pass, $candidate->ID)) {
            return $candidate; // Autenticazione OK
        } else {
            return new WP_Error('incorrect_password', __('Password non corretta.'));
        }
    }

    // Nessun match per telefono → lascia il flusso standard
    return $user;
}, 20, 3);

/**
 * Redirect post-login → Home (richiesta).
 */
add_filter('login_redirect', function($redirect_to, $requested_redirect_to, $user){
    return home_url('/');
}, 10, 3);

// [THUB_NOINDEX_UTILITY] Imposta noindex su pagine di utilità
add_action('wp_head', function(){
  if ( is_page( ['login','trova-email','password-dimenticata','registrati'] ) ) {
    echo "\n<!-- [THUB_META_NOINDEX] -->\n<meta name=\"robots\" content=\"noindex, nofollow\">\n";
  }
}, 1);

/* ============================================================================
 * [THUB_AUTH_REDIRECTS] Canonicalizza le pagine native WP verso le pagine custom
 * - Login:              /wp-login.php           → /login
 * - Password diment.:   /wp-login.php?action=lostpassword → /password-dimenticata
 * - Registrazione:      /wp-login.php?action=register     → /registrati
 * - Mantiene intatti i flussi rp/resetpass (link email)
 * - Propaga redirect_to se presente
 * ========================================================================== */

// [THUB_AUTH_SLUGS] — personalizza qui gli slug se usi nomi diversi
if ( ! defined('THUB_PAGE_LOGIN') )            define('THUB_PAGE_LOGIN', '/login');
if ( ! defined('THUB_PAGE_LOSTPASSWORD') )     define('THUB_PAGE_LOSTPASSWORD', '/password-dimenticata');
if ( ! defined('THUB_PAGE_REGISTER') )         define('THUB_PAGE_REGISTER', '/registrati');

/**
 * Intercetta gli accessi diretti a wp-login.php e reindirizza alle pagine custom.
 * NB: 'login_init' gira nel contesto di wp-login.php (il tema non è caricato completamente,
 * ma questa action sì). Evitiamo di toccare rp/resetpass/postpass/logout ecc.
 */
add_action('login_init', function () {
    // Azioni da NON reindirizzare (flussi sensibili o non pertinenti)
    $bypass = array('rp','resetpass','postpass','logout','confirm_admin_email','confirmaction');
    $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : 'login';
    if ( in_array($action, $bypass, true) ) {
        return; // lascia lavorare WordPress
    }

    // Valido e riprendo l'eventuale redirect_to in ingresso
    $raw_redirect = isset($_REQUEST['redirect_to']) ? wp_unslash($_REQUEST['redirect_to']) : '';
    $redirect_to  = $raw_redirect ? wp_validate_redirect($raw_redirect, '') : '';

    // Mappa azione → pagina custom
    $target = home_url( THUB_PAGE_LOGIN ); // default
    if ( $action === 'lostpassword' ) {
        $target = home_url( THUB_PAGE_LOSTPASSWORD );
    } elseif ( $action === 'register' ) {
        $target = home_url( THUB_PAGE_REGISTER );
    }

    if ( $redirect_to ) {
        $target = add_query_arg('redirect_to', rawurlencode($redirect_to), $target);
    }

    wp_safe_redirect( $target, 302 );
    exit;
});

/**
 * Riscrive i link generati da WP verso le pagine custom.
 * - login_url() / wp_login_url()
 * - lostpassword_url()
 * - register_url()
 */
add_filter('login_url', function ($login_url, $redirect, $force_reauth) {
    $url = home_url( THUB_PAGE_LOGIN );
    if ( ! empty($redirect) ) {
        $url = add_query_arg('redirect_to', rawurlencode($redirect), $url);
    }
    return $url;
}, 10, 3);

add_filter('lostpassword_url', function ($lost_url, $redirect) {
    $url = home_url( THUB_PAGE_LOSTPASSWORD );
    if ( ! empty($redirect) ) {
        $url = add_query_arg('redirect_to', rawurlencode($redirect), $url);
    }
    return $url;
}, 10, 2);

add_filter('register_url', function ($register_url) {
    return home_url( THUB_PAGE_REGISTER );
}, 10, 1);

/* (Facoltativo) Multisite: forza pagina iscrizione custom
add_filter('wp_signup_location', function ($url) {
    return home_url( THUB_PAGE_REGISTER );
});
*/

/* ===========================
 * [THUB_TAXONOMIES] Tassonomie filtrabili
 * =========================== */
add_action('init', function(){
  // Portata
  register_taxonomy('portata', 'ricetta', [
    'label' => 'Portata','public'=>true,'hierarchical'=>true,
    'show_admin_column'=>true,'rewrite'=>['slug'=>'portata']
  ]);
  // Cucina
  register_taxonomy('cucina', 'ricetta', [
    'label' => 'Cucina','public'=>true,'hierarchical'=>true,
    'show_admin_column'=>true,'rewrite'=>['slug'=>'cucina']
  ]);
  // Dieta
  register_taxonomy('dieta', 'ricetta', [
    'label' => 'Dieta','public'=>true,'hierarchical'=>true,
    'show_admin_column'=>true,'rewrite'=>['slug'=>'dieta']
  ]);
});

/* ===========================
 * [THUB_ARCHIVE_FILTER] Applica filtri tassonomici all'archivio ricette
 * =========================== */
add_action('pre_get_posts', function($q){
  // Evita admin e sottoquery
  if (is_admin() || !$q->is_main_query()) return;
  // Applica SOLO all'archivio del CPT ricetta
  if (!$q->is_post_type_archive('ricetta')) return;

  $tax = [];
  foreach (['portata','cucina','dieta'] as $tx){
    if (!empty($_GET[$tx])) {
      $val = sanitize_text_field($_GET[$tx]); // es. slug della tassonomia
      $tax[] = ['taxonomy'=>$tx,'field'=>'slug','terms'=>[$val]];
    }
  }
  if ($tax) $q->set('tax_query', $tax);
});

/* ===========================
 * [THUB_GATING] Helpers Free/Pro + Customizer per N step free
 * =========================== */
function thub_is_pro_user(){
  $uid = get_current_user_id();
  if(!$uid) return false;
  return (bool) get_user_meta($uid,'thub_is_pro',true); // semplice flag
}

function thub_free_steps_limit(){
  return max(1,(int) get_theme_mod('thub_free_steps_limit', 3));
}

add_action('customize_register', function($wp_customize){
  $wp_customize->add_section('thub_gating',[
    'title'=>'THUB – Impostazioni Free/Pro','priority'=>30
  ]);
  $wp_customize->add_setting('thub_free_steps_limit',['default'=>3,'transport'=>'refresh']);
  $wp_customize->add_control('thub_free_steps_limit',[
    'type'=>'number','section'=>'thub_gating','label'=>'Step visibili (utenti Free)','input_attrs'=>['min'=>1,'max'=>10]
  ]);
});

/* ===========================
 * [THUB_TRUNCATE_SEO] Limiti caratteri + meta SEO (title/description)
 * =========================== */
function thub_trim_chars($s,$limit){ $s=trim(wp_strip_all_tags($s)); return mb_strimwidth($s,0,$limit,'…','UTF-8'); }

add_action('wp_head', function(){
  if(!is_singular('ricetta')) return;
  $title_full = get_the_title();
  $intro      = get_field('intro_breve') ?: get_the_excerpt();
  $title_50   = thub_trim_chars($title_full, 50);
  $intro_150  = thub_trim_chars($intro ?: '', 150);

  // Meta SEO basilari (se non usi plugin SEO). Mantieni compatibilità con Yoast/RankMath (che sovrascrivono).
  echo '<meta name="title" content="'.esc_attr($title_50).'">'."\n";
  echo '<meta name="description" content="'.esc_attr($intro_150).'">'."\n";
}, 5);

/* ============================================================
 * [THUB_JSONLD_HELPERS] — Preferisci campi strutturati; fallback ai legacy
 * ============================================================ */

/* Converte “45”, “1h 30m”, “90 min” → ISO8601 (PT...) */
if (!function_exists('thub_to_iso8601_duration')) {
  function thub_to_iso8601_duration($val){
    $s = trim((string)$val);
    if ($s === '') return null;
    $n = mb_strtolower($s, 'UTF-8');
    preg_match('/(\d+)\s*h/', $n, $mh);
    preg_match('/(\d+)\s*m/', $n, $mm);
    $h = isset($mh[1]) ? intval($mh[1]) : 0;
    $m = isset($mm[1]) ? intval($mm[1]) : 0;
    if ($h === 0 && $m === 0 && preg_match('/^\d+$/', $n)) $m = intval($n);
    if ($h === 0 && $m === 0) return null;
    $iso = 'PT';
    if ($h > 0) $iso .= $h.'H';
    if ($m > 0) $iso .= $m.'M';
    return $iso;
  }
}

/* ===========================
 * [THUB_JSONLD_CANONICO_ONLY]
 * Ingredienti/Passaggi: SOLO campi strutturati (ingredienti_rep / passaggi_rep)
 * Nessun fallback ai legacy.
 * =========================== */

if (!function_exists('thub_jsonld_ingredients')) {
  function thub_jsonld_ingredients($post_id){
    $out = [];
    if (function_exists('get_field')) {
      $rows = (array) get_field('ingredienti_rep', $post_id);
      foreach ($rows as $r) {
        $nome = trim((string)($r['ing_nome'] ?? ''));
        $qta  = trim((string)($r['ing_qta']  ?? ''));
        $unit = trim((string)($r['ing_unita'] ?? ''));
        if ($unit === 'Altro') {
          $unit = trim((string)($r['ing_unita_altro'] ?? ''));
        }
        $line = trim(implode(' ', array_filter([$qta, $unit, $nome])));
        if ($line !== '') $out[] = $line;
      }
    }
    return $out;
  }
}

if (!function_exists('thub_jsonld_steps')) {
  function thub_jsonld_steps($post_id){
    $steps = [];
    if (function_exists('get_field')) {
      $rows = (array) get_field('passaggi_rep', $post_id);
      foreach ($rows as $r) {
        $t = trim((string)($r['passo_testo'] ?? ''));
        if ($t !== '') $steps[] = $t;
      }
    }
    return $steps;
  }
}

/* ============================================================
 * [THUB_JSONLD] — Output JSON-LD Recipe in <head>
 * ============================================================ */
if (!function_exists('thub_output_recipe_jsonld')) {
  function thub_output_recipe_jsonld(){
    if (!is_singular('ricetta') || is_feed()) return;

    $id    = get_the_ID();
    $url   = get_permalink($id);
    $title = wp_strip_all_tags(get_the_title($id));

    // Descrizione: preferisci intro_breve → excerpt
    $desc = '';
    if (function_exists('get_field')) $desc = (string) get_field('intro_breve', $id);
    if ($desc === '') $desc = has_excerpt($id) ? get_the_excerpt($id) : '';
    $desc = wp_html_excerpt(wp_strip_all_tags($desc), 300, '…');

    $img    = get_the_post_thumbnail_url($id, 'full');
    $images = $img ? [$img] : [];

    // Porzioni & Kcal
    $porz = (string) ( function_exists('get_field') ? get_field('porzioni_base', $id) : get_post_meta($id, 'porzioni_base', true) );
    $kcal = (string) ( function_exists('get_field') ? get_field('kcal_per_porz', $id) : get_post_meta($id, 'kcal_per_porz', true) );

    // Tempi → ISO8601
    $tp = (string) ( function_exists('get_field') ? get_field('tempo_di_preparazione', $id) : get_post_meta($id, 'tempo_di_preparazione', true) );
    $tc = (string) ( function_exists('get_field') ? get_field('tempo_di_cottura',      $id) : get_post_meta($id, 'tempo_di_cottura',      true) );
    $tprep = thub_to_iso8601_duration($tp);
    $tcook = thub_to_iso8601_duration($tc);

    // Ingredienti / Istruzioni
    $ingredients  = thub_jsonld_ingredients($id);
    $instructions = thub_jsonld_steps($id);

    // Tassonomie utili
    $cats_portata = wp_get_post_terms($id, 'portata', ['fields'=>'names']);
    $cats_cucina  = wp_get_post_terms($id, 'cucina',  ['fields'=>'names']);
    $cats_dieta   = wp_get_post_terms($id, 'dieta',   ['fields'=>'names']);
    $tags         = wp_get_post_terms($id, 'post_tag',['fields'=>'names']);

    // Autore
    $author_name = get_the_author_meta('display_name', get_post_field('post_author', $id));

    $data = [
      '@context'           => 'https://schema.org',
      '@type'              => 'Recipe',
      'mainEntityOfPage'   => $url,
      'name'               => $title,
      'description'        => $desc,
      'image'              => $images,
      'author'             => [ '@type' => 'Person', 'name' => $author_name ],
      'datePublished'      => get_the_date('c', $id),
      'dateModified'       => get_the_modified_date('c', $id),
      'keywords'           => implode(', ', array_filter(array_merge($tags, $cats_dieta))),
      'recipeIngredient'   => $ingredients,
      'recipeInstructions' => array_map(fn($t)=>['@type'=>'HowToStep','text'=>$t], $instructions),
      'recipeCategory'     => $cats_portata ? implode(', ', $cats_portata) : null,
      'recipeCuisine'      => $cats_cucina  ? $cats_cucina[0] : null,
      'url'                => $url,
    ];
    if ($porz !== '') $data['recipeYield'] = $porz;
    if ($tprep)        $data['prepTime']   = $tprep;
    if ($tcook)        $data['cookTime']   = $tcook;
    if ($kcal !== '') {
      $data['nutrition'] = [
        '@type'    => 'NutritionInformation',
        'calories' => trim($kcal).' kcal per porzione',
      ];
    }

    foreach ($data as $k => $v) {
      if ($v === null || $v === [] || $v === '') unset($data[$k]);
    }

    echo "\n".'<!-- [THUB_JSONLD] Recipe schema -->'."\n";
    echo '<script type="application/ld+json">'
         . wp_json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)
         . '</script>'."\n";
  }
  add_action('wp_head', 'thub_output_recipe_jsonld', 30);
}

/* ============================================================
 * [THUB_SPONSOR_RESOLVER_CANONICO_ONLY]
 * Resolver sponsor “canonico-only”
 * - 1) Post-level: Sponsor (unificato) → se attivo, ritorna subito
 * - 2) Category-level: Opzioni “Sponsor per categoria” → mappa per term
 * - 3) Nessun fallback legacy (sponsor_cpm_*)
 * Ritorna array ['nome','logo','url','claim','type'] oppure null
 * ============================================================ */
if ( ! function_exists('thub_get_sponsor_data') ) :
function thub_get_sponsor_data($post_id){
  // 1) Post-level (Sponsor unificato)
  if ( function_exists('get_field') ) {
    $attivo = (int) get_field('sponsor_attivo', $post_id);
    if ( $attivo ) {
      $nome  = (string) get_field('sponsor_nome', $post_id);
      $logo  =        get_field('sponsor_logo', $post_id); // può essere ID o URL
      $url   = (string) get_field('sponsor_url',  $post_id);
      $claim = (string) get_field('sponsor_claim',$post_id);

      if ( $nome || $logo ) {
        return [
          'nome'  => $nome,
          'logo'  => $logo,   // ID o URL (thub-result.php lo normalizza in URL)
          'url'   => $url,
          'claim' => $claim,
          'type'  => 'post',
        ];
      }
    }
  }

  // 2) Category-level (Opzioni → “Sponsor per categoria”)
  // Nota: nomi sub-field tipici: cat_term, spons_cat_nome, spons_cat_logo, spons_cat_url, spons_cat_claim
  if ( function_exists('get_field') ) {
    $rows = (array) get_field('sponsorizzazioni_cat', 'option');
    if ( $rows ) {
      // Recupero le categorie del post (usa 'category' o adegua se usi una tassonomia diversa)
      $terms = wp_get_post_terms($post_id, 'category');
      if ( ! is_wp_error($terms) && $terms ) {
        // Normalizzo un set di ID/slug del post
        $post_term_ids  = array_map(fn($t)=> (int)$t->term_id, $terms);
        $post_term_slugs= array_map(fn($t)=> (string)$t->slug,   $terms);

        foreach( $rows as $row ){
          $term = $row['cat_term'] ?? null;

          // Match per ID o per slug
          $match = false;
          if ( is_array($term) ) {
            // ACF può restituire un array con keys term_id/slug
            $rid  = isset($term['term_id']) ? (int)$term['term_id'] : 0;
            $rsl  = isset($term['slug'])    ? (string)$term['slug'] : '';
            if ( $rid && in_array($rid, $post_term_ids, true) ) $match = true;
            if ( !$match && $rsl && in_array($rsl, $post_term_slugs, true) ) $match = true;
          } elseif ( is_object($term) && isset($term->term_id) ) {
            $rid = (int) $term->term_id;
            if ( $rid && in_array($rid, $post_term_ids, true) ) $match = true;
          } elseif ( is_numeric($term) ) {
            $rid = (int) $term;
            if ( $rid && in_array($rid, $post_term_ids, true) ) $match = true;
          } elseif ( is_string($term) && $term !== '' ) {
            $rsl = sanitize_title($term);
            if ( in_array($rsl, $post_term_slugs, true) ) $match = true;
          }

          if ( $match ) {
            $nome  = (string) ($row['spons_cat_nome']  ?? '');
            $logo  =               $row['spons_cat_logo'] ?? ''; // ID o URL
            $url   = (string) ($row['spons_cat_url']   ?? '');
            $claim = (string) ($row['spons_cat_claim'] ?? '');

            if ( $nome || $logo ) {
              return [
                'nome'  => $nome,
                'logo'  => $logo,  // ID o URL
                'url'   => $url,
                'claim' => $claim,
                'type'  => 'category',
              ];
            }
          }
        }
      }
    }
  }

  // 3) Nessuno sponsor
  return null;
}
endif;

/* ===========================
 * [THUB_SPONSOR_ADMIN] Admin: conteggio ricette sponsorizzabili per categoria
 * =========================== */
add_action('admin_menu', function(){
  add_menu_page('THUB Sponsorizzazioni','THUB Sponsorizzazioni','manage_options','thub-sponsor','thub_admin_sponsor_page','dashicons-megaphone',58);
});
function thub_admin_sponsor_page(){
  if(!function_exists('get_field')){ echo '<div class="wrap"><h1>Richiede ACF</h1></div>'; return; }
  $rows = get_field('sponsorizzazioni_cat','option');
  echo '<div class="wrap"><h1>Sponsorizzazioni per categoria</h1><table class="widefat"><thead><tr><th>Categoria</th><th>Attivo</th><th>Ricette</th><th>Sponsor</th></tr></thead><tbody>';
  if($rows){
    foreach($rows as $r){
      $term_id = (int)($r['cat_term'] ?? 0);
      $term = $term_id ? get_term($term_id,'category') : null;
      $count = $term ? (int) $term->count : 0;
      echo '<tr>';
      echo '<td>'. ($term ? esc_html($term->name) : '-') .'</td>';
      echo '<td>'. (!empty($r['spons_cat_attivo'])?'Sì':'No') .'</td>';
      echo '<td>'.$count.'</td>';
      echo '<td>'. esc_html($r['spons_cat_nome'] ?? '') .'</td>';
      echo '</tr>';
    }
  } else {
    echo '<tr><td colspan="4">Nessuna mappatura configurata.</td></tr>';
  }
  echo '</tbody></table></div>';
}

//[THUB_ASSETS] Carica JS/CSS ricetta in modo condizionato
add_action('wp_enqueue_scripts', function(){
  $theme_uri = get_stylesheet_directory_uri();

  // CSS solo dove serve (ricetta singola, archivio ricette, tassonomie correlate)
  if ( is_singular('ricetta') || is_post_type_archive('ricetta') || is_tax(['portata','cucina','dieta']) ) {
    wp_enqueue_style('thub-recipe', $theme_uri.'/assets/css/thub-recipe.css', [], '1.0.0');
  }

  // JS solo in singola ricetta (porzioni/kcal + carousel varianti)
  if ( is_singular('ricetta') ) {
    wp_enqueue_script('thub-recipe', $theme_uri.'/assets/js/thub-recipe.js', [], '1.0.0', true);
  }
});

// [THUB_ACF_OPTIONS] Crea la pagina opzioni per le mappature sponsor → categoria
if ( function_exists('acf_add_options_page') ) {
  acf_add_options_page(array(
    'page_title'  => 'Sponsorizzazioni per categoria',
    'menu_title'  => 'Sponsor per categoria',
    'menu_slug'   => 'thub-sponsor-settings',
    'capability'  => 'manage_options',
    'redirect'    => false,
    'icon_url'    => 'dashicons-megaphone',
    'position'    => 58.1
  ));
}

/* =========================================================
 * [THUB_TAX] Registra tassonomie: PORTATA, CUCINA, DIETA
 * =======================================================*/
add_action('init', function(){

  /* ---------- PORTATA (gerarchica, tipo "categoria") ---------- */
  register_taxonomy('portata', ['ricetta'], [
    // [THUB_TAX_portata] Etichette in italiano
    'labels' => [
      'name'                       => 'Portate',
      'singular_name'              => 'Portata',
      'search_items'               => 'Cerca portate',
      'all_items'                  => 'Tutte le portate',
      'edit_item'                  => 'Modifica portata',
      'update_item'                => 'Aggiorna portata',
      'add_new_item'               => 'Aggiungi nuova portata',
      'new_item_name'              => 'Nuovo nome portata',
      'menu_name'                  => 'Portate',
      'separate_items_with_commas' => 'Separa portate con virgole',
      'add_or_remove_items'        => 'Aggiungi o rimuovi portate',
      'choose_from_most_used'      => 'Scegli tra le più usate',
      'not_found'                  => 'Nessuna portata trovata',
    ],
    'public'            => true,
    'hierarchical'      => true,               // Padre/figlio es. Antipasti > Freddi/Caldi
    'show_ui'           => true,
    'show_admin_column' => true,               // Colonna nel listing admin
    'show_in_nav_menus' => true,
    'show_in_rest'      => true,               // Gutenberg/REST
    'query_var'         => true,
    'rewrite'           => ['slug' => 'portata', 'with_front' => false],
  ]);

  /* ---------- CUCINA (gerarchica, es. Italiana > Lazio/Piemonte) ---------- */
  register_taxonomy('cucina', ['ricetta'], [
    'labels' => [
      'name'          => 'Cucine',
      'singular_name' => 'Cucina',
      'search_items'  => 'Cerca cucine',
      'all_items'     => 'Tutte le cucine',
      'edit_item'     => 'Modifica cucina',
      'update_item'   => 'Aggiorna cucina',
      'add_new_item'  => 'Aggiungi nuova cucina',
      'new_item_name' => 'Nuovo nome cucina',
      'menu_name'     => 'Cucine',
      'not_found'     => 'Nessuna cucina trovata',
    ],
    'public'            => true,
    'hierarchical'      => true,               // es. Italiana > Campania
    'show_ui'           => true,
    'show_admin_column' => true,
    'show_in_nav_menus' => true,
    'show_in_rest'      => true,
    'query_var'         => true,
    'rewrite'           => ['slug' => 'cucina', 'with_front' => false],
  ]);

  /* ---------- DIETA (non gerarchica, tipo "tag") ---------- */
  register_taxonomy('dieta', ['ricetta'], [
    'labels' => [
      'name'          => 'Diete',
      'singular_name' => 'Dieta',
      'search_items'  => 'Cerca diete',
      'all_items'     => 'Tutte le diete',
      'edit_item'     => 'Modifica dieta',
      'update_item'   => 'Aggiorna dieta',
      'add_new_item'  => 'Aggiungi nuova dieta',
      'new_item_name' => 'Nuovo nome dieta',
      'menu_name'     => 'Diete',
      'not_found'     => 'Nessuna dieta trovata',
    ],
    'public'            => true,
    'hierarchical'      => false,              // es. Vegetariana, Vegana, Senza glutine
    'show_ui'           => true,
    'show_admin_column' => true,
    'show_in_nav_menus' => true,
    'show_in_rest'      => true,
    'query_var'         => true,
    'rewrite'           => ['slug' => 'dieta', 'with_front' => false],
  ]);
}, 9);

/* =========================================================
 * [THUB_TAX_FLUSH] Flush rewrite una sola volta dopo la registrazione
 * =======================================================*/
add_action('init', function(){
  if (get_option('thub_flushed_tax_rules') !== '1') {
    flush_rewrite_rules(false);
    update_option('thub_flushed_tax_rules', '1');
  }
});

/* =========================================================
 * [THUB_CPT] Post type "ricetta" (replica CPT UI)
 * =======================================================*/
add_action('init', function(){

  $labels = [
    'name'               => 'Ricette',
    'singular_name'      => 'Ricetta',
    'menu_name'          => 'Ricette',
    'name_admin_bar'     => 'Ricetta',
    'add_new'            => 'Aggiungi nuova',
    'add_new_item'       => 'Aggiungi nuova ricetta',
    'new_item'           => 'Nuova ricetta',
    'edit_item'          => 'Modifica ricetta',
    'view_item'          => 'Vedi ricetta',
    'all_items'          => 'Tutte le ricette',
    'search_items'       => 'Cerca ricette',
    'not_found'          => 'Nessuna ricetta trovata',
    'not_found_in_trash' => 'Nessuna ricetta nel cestino',
    'archives'           => 'Archivio ricette',
  ];

  register_post_type('ricetta', [
    'labels'             => $labels,
    'public'             => true,
    'publicly_queryable' => true,
    'show_ui'            => true,
    'show_in_nav_menus'  => true,
    'show_in_menu'       => true,
    'show_in_rest'       => true,                 // Gutenberg/REST
    'rest_base'          => '',                   // default
    'menu_position'      => 5,
    'menu_icon'          => 'dashicons-carrot',
    'has_archive'        => true,                 // archivio ON
    'rewrite'            => ['slug'=>'ricette','with_front'=>false],
    'capability_type'    => 'post',
    'map_meta_cap'       => true,
    'hierarchical'       => false,
    'supports'           => ['title','editor','thumbnail','excerpt','revisions','author'],
    'taxonomies'         => ['category','post_tag','portata','cucina','dieta'], // includiamo anche le custom
    'can_export'         => true,
  ]);
}, 10);

/* [THUB_CPT_FLUSH] flush una sola volta */
add_action('init', function(){
  if (get_option('thub_flushed_cpt_rules') !== '1') {
    flush_rewrite_rules(false);
    update_option('thub_flushed_cpt_rules', '1');
  }
}, 99);

/* =========================================================
 * [THUB_QUERY] Filtri portata/cucina/dieta su archivio ricette
 * =======================================================*/

/* [THUB_QUERY_vars] consenti le query var personalizzate */
add_filter('query_vars', function($vars){
  $vars[] = 'portata';
  $vars[] = 'cucina';
  $vars[] = 'dieta';
  return $vars;
});

/* [THUB_pre_get_posts] applica i filtri sull'archivio ricetta */
add_action('pre_get_posts', function($q){
  if ( is_admin() || ! $q->is_main_query() ) return;

  // Applica solo nell'archivio del CPT "ricetta"
  if ( ! $q->is_post_type_archive('ricetta') ) return;

  $tax_filters = [];

  foreach (['portata','cucina','dieta'] as $tax){
    // Legge sia query var che GET (es. ?portata=primi)
    $val = $q->get($tax);
    if (empty($val) && isset($_GET[$tax])) {
      $val = sanitize_text_field(wp_unslash($_GET[$tax]));
    }
    if (!empty($val)) {
      // Supporto multi-valore separato da virgola (es. ?dieta=vegana,senza-glutine)
      $slugs = array_filter(array_map('sanitize_title', preg_split('/[,\s]+/', $val)));
      if ($slugs) {
        $tax_filters[] = [
          'taxonomy' => $tax,
          'field'    => 'slug',
          'terms'    => $slugs,
          'operator' => 'AND',
        ];
      }
    }
  }

  if ($tax_filters) {
    $q->set('tax_query', array_merge(['relation' => 'AND'], $tax_filters));
  }
});

/* =========================================================
 * [THUB_SEARCH_FILTERS] Applica portata/cucina/dieta anche sulla ricerca (?s=)
 * - Se sono presenti i parametri, limitiamo la ricerca al CPT "ricetta"
 *   e applichiamo la tax_query in AND.
 * =======================================================*/
add_action('pre_get_posts', function($q){
  if ( is_admin() || ! $q->is_main_query() ) return;
  if ( ! $q->is_search() ) return;

  $tax_filters = [];
  foreach (['portata','cucina','dieta'] as $tax){
    $val = $q->get($tax);
    if (empty($val) && isset($_GET[$tax])) {
      $val = sanitize_text_field( wp_unslash($_GET[$tax]) );
    }
    if (!empty($val)) {
      $slugs = array_filter(array_map('sanitize_title', preg_split('/[,\s]+/', $val)));
      if ($slugs) {
        $tax_filters[] = [
          'taxonomy' => $tax,
          'field'    => 'slug',
          'terms'    => $slugs,
          'operator' => 'AND',
        ];
      }
    }
  }

  if ($tax_filters) {
    // Se si usano filtri tassonomici, ha senso cercare solo nelle "ricette"
    $q->set('post_type', 'ricetta');

    $existing = $q->get('tax_query');
    if (!is_array($existing) || empty($existing)) {
      $q->set('tax_query', array_merge(['relation'=>'AND'], $tax_filters));
    } else {
      $q->set('tax_query', array_merge(['relation'=>'AND'], $existing, $tax_filters));
    }
  }
}, 20);

/* ============================================================
 * [THUB_INDEXER] Indicizzazione ingredienti per la ricerca
 * Popola il meta 'ingredienti_search' ogni volta che salvi una ricetta.
 * - Legge ACF repeater 'ingredienti_rep' (campi: ing_nome, ing_qta, ing_unita, ing_unita_altro)
 * - (Fallback) Legge il campo legacy 'ingredienti' se esiste
 * - Normalizza (minuscole, rimozione accenti, punteggiatura), deduplica e salva come stringa
 * ============================================================ */

if ( ! function_exists( 'thub_normalizza_testo' ) ) {
  /**
   * [THUB_INDEXER] Normalizza stringhe per indicizzazione:
   * - minuscole
   * - rimozione accenti
   * - rimozione apostrofi/punteggiatura multipla
   * - trim spazi multipli
   */
  function thub_normalizza_testo( $str ) {
    // minuscole
    $s = mb_strtolower( (string) $str, 'UTF-8' );

    // rimozione accenti (transliterator se disponibile)
    if ( class_exists('Transliterator') ) {
      $tr = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
      if ( $tr ) $s = $tr->transliterate($s);
    } else {
      // fallback basilare
      $accents = [
        'à'=>'a','è'=>'e','é'=>'e','ì'=>'i','ò'=>'o','ó'=>'o','ù'=>'u','ç'=>'c','ä'=>'a','ö'=>'o','ü'=>'u',
        'À'=>'a','È'=>'e','É'=>'e','Ì'=>'i','Ò'=>'o','Ó'=>'o','Ù'=>'u','Ç'=>'c','Ä'=>'a','Ö'=>'o','Ü'=>'u'
      ];
      $s = strtr($s, $accents);
    }

    // togli apostrofi e punteggiatura comune, sostituisci con spazio
    $s = preg_replace('/[\'"“”‘’`´^~.,;:!?()\\[\\]{}\\/\\\\|+*=<>@#£$%&]/u', ' ', $s);

    // spazi multipli → singolo
    $s = preg_replace('/\\s+/u', ' ', $s);

    return trim($s);
  }
}

if ( ! function_exists( 'thub_build_ingredienti_search' ) ) {
  /**
   * [THUB_INDEXER] Costruisce la stringa indicizzabile dagli ingredienti ACF + legacy.
   * Ritorna una stringa con i nomi ingredienti normalizzati, deduplicati, separati da spazio.
   */
  function thub_build_ingredienti_search( $post_id ) {
    $tokens = [];

    // 1) ACF repeater 'ingredienti_rep' (preferito)
    if ( function_exists('get_field') ) {
      $rows = get_field('ingredienti_rep', $post_id);
      if ( ! empty($rows) && is_array($rows) ) {
        foreach ( $rows as $row ) {
          $nome = isset($row['ing_nome']) ? $row['ing_nome'] : '';
          if ( $nome ) {
            $tokens[] = thub_normalizza_testo( $nome );
          }
          // Se vuoi indicizzare anche eventuali note/sinonimi, aggiungili qui
          // $note = $row['ing_note'] ?? '';
          // if ($note) $tokens[] = thub_normalizza_testo($note);
        }
      }
    }

    // 2) Fallback legacy 'ingredienti' (textarea: un ingrediente per riga)
    $legacy = get_post_meta( $post_id, 'ingredienti', true );
    if ( ! empty($legacy) && is_string($legacy) ) {
      $lines = preg_split('/\\r\\n|\\r|\\n/u', $legacy);
      foreach ( (array) $lines as $ln ) {
        $ln = trim($ln);
        if ( $ln !== '' ) {
          // estrai la parte testuale ignorando quantità/unità se presenti tipo "200 g Farina 00"
          // euristica: rimuovi cifre e unità comuni all'inizio
          $name_only = preg_replace('/^[0-9.,]+\\s*(g|gr|grammi|kg|ml|l|litri|tsp|tbsp|cucchiaini?|cucchiai?)?\\s*/u', '', $ln);
          $tokens[]  = thub_normalizza_testo( $name_only );
        }
      }
    }

    // Deduplica e ricompone
    $tokens = array_filter( array_unique( $tokens ) );
    return implode(' ', $tokens);
  }
}

if ( ! function_exists( 'thub_save_ingredienti_index' ) ) {
  /**
   * [THUB_INDEXER] Hook di salvataggio: aggiorna meta 'ingredienti_search'
   */
  function thub_save_ingredienti_index( $post_id, $post, $update ) {
    // Sicurezze standard
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) return;
    if ( 'ricetta' !== get_post_type( $post_id ) ) return;

    // Capacità minima: edit_post sul CPT ricetta
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $val = thub_build_ingredienti_search( $post_id );

    // Salva sempre: anche stringa vuota per "pulire" ricerche vecchie
    update_post_meta( $post_id, 'ingredienti_search', $val );
  }
  add_action( 'save_post', 'thub_save_ingredienti_index', 10, 3 );
}

/* ------------------------------------------------------------
 * [THUB_INDEXER_UTIL] Reindicizza tutte le ricette on-demand (facoltativo)
 * Visita: /?thub_reindex=1&_wpnonce=XYZ (solo admin loggati)
 * Utile se hai ricette già presenti prima di attivare l'hook.
 * ------------------------------------------------------------ */
if ( ! function_exists( 'thub_reindex_endpoint' ) ) {
  function thub_reindex_endpoint() {
    if ( ! is_user_logged_in() || ! current_user_can('manage_options') ) return;
    if ( empty($_GET['thub_reindex']) ) return;

    // Non fare output HTML, solo testo semplice
    header('Content-Type: text/plain; charset=utf-8');

    // Nonce minimale per evitare trigger accidentali
    if ( empty($_GET['_wpnonce']) || ! wp_verify_nonce( $_GET['_wpnonce'], 'thub_reindex_all' ) ) {
      echo "Nonce non valido. Genera un link con nonce valido.\n";
      exit;
    }

    $q = new WP_Query([
      'post_type'      => 'ricetta',
      'post_status'    => 'any',
      'posts_per_page' => -1,
      'fields'         => 'ids',
      'no_found_rows'  => true,
    ]);

    $count = 0;
    if ( $q->have_posts() ) {
      foreach ( $q->posts as $pid ) {
        $val = thub_build_ingredienti_search( $pid );
        update_post_meta( $pid, 'ingredienti_search', $val );
        $count++;
      }
    }

    echo "Reindicizzate $count ricette.\n";
    exit;
  }
  add_action( 'init', 'thub_reindex_endpoint' );
}

/* ------------------------------------------------------------
 * [THUB_INDEXER_LINK] Helper per generare rapidamente il link col nonce
 * (Puoi stampare questo link in admin bar, o copiarlo da var_dump una volta)
 * ------------------------------------------------------------ */
if ( ! function_exists( 'thub_get_reindex_url' ) ) {
  function thub_get_reindex_url() {
    return add_query_arg(
      [
        'thub_reindex' => '1',
        '_wpnonce'     => wp_create_nonce('thub_reindex_all'),
      ],
      home_url( '/' )
    );
  }
}

/* ============================================================
 * [THUB_SEARCH2] Ricerca: include 'ingredienti_search'
 * + Debug frontend in fondo pagina (solo admin loggato)
 * ============================================================ */
if ( ! function_exists( 'thub_search_include_ingredienti' ) ) {
  function thub_search_include_ingredienti( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;
    if ( ! $query->is_search() ) return;

    // Accetta sia ricerca globale che ?post_type=ricetta (anche array)
    $post_type = $query->get('post_type');
    $has_ricetta = (is_array($post_type) && in_array('ricetta', $post_type, true)) || ($post_type === 'ricetta');
    if ( $post_type && !$has_ricetta ) return;

    // Tokenizzazione semplice
    $s_norm = function_exists('thub_normalizza_testo') ? thub_normalizza_testo($s) : mb_strtolower($s,'UTF-8');
    $terms  = preg_split('/\s+/u', $s_norm );
    $terms  = array_slice( array_filter( array_unique( (array) $terms ) ), 0, 5 );

    if ( empty($terms) ) return;

    // Meta OR su ingredienti_search
    $meta_or = [ 'relation' => 'OR' ];
    foreach ( $terms as $t ) {
      $meta_or[] = [
        'key'     => 'ingredienti_search',
        'value'   => $t,
        'compare' => 'LIKE',
      ];
    }

    // Unisci ad eventuale meta_query esistente
    $existing_meta = (array) $query->get('meta_query');
    $meta = ! empty($existing_meta) ? [ 'relation' => 'AND', $existing_meta, $meta_or ] : $meta_or;
    $query->set( 'meta_query', $meta );

    // Forza CPT ricetta se non già impostato
    if ( empty($post_type) ) $query->set( 'post_type', ['ricetta'] );
  }
  add_action( 'pre_get_posts', 'thub_search_include_ingredienti' );
}

/* ===========================================================
 * [THUB_PROFILE_UPLOAD] Upload foto profilo via AJAX (frontend)
 *  - Action: wp_ajax_thub_upload_profile_photo (utenti loggati)
 *  - Salva in Media Library e aggiorna ACF user field 'thub_profile_photo'
 *  - Cancella l’eventuale allegato precedente per non lasciare file orfani
 *  - Limiti: 2MB, JPG/PNG/WEBP
 * =========================================================== */

/* -- [THUB_PROFILE_HELPER] Ricava ID allegato precedente da ACF/user_meta -- */
function thub_get_previous_profile_attachment_id( $user_id ){
  $old_id = 0;

  // Preferiamo ACF se presente: get_field(..., false) → valore "raw" (di solito ID)
  if ( function_exists('get_field') ) {
    $raw = get_field( 'thub_profile_photo', 'user_'.$user_id, false ); // false = no formatting
    if ( is_numeric($raw) ) {
      $old_id = intval($raw);
    } elseif ( is_array($raw) ) {
      // In casi rari il raw può essere array: tentiamo campi comuni
      if ( !empty($raw['ID']) ) {
        $old_id = intval($raw['ID']);
      } elseif ( !empty($raw['id']) ) {
        $old_id = intval($raw['id']);
      } elseif ( !empty($raw['url']) && is_string($raw['url']) ) {
        $maybe = attachment_url_to_postid( $raw['url'] );
        if ( $maybe ) $old_id = intval($maybe);
      }
    } elseif ( is_string($raw) && filter_var($raw, FILTER_VALIDATE_URL) ) {
      $maybe = attachment_url_to_postid( $raw );
      if ( $maybe ) $old_id = intval($maybe);
    }
  } else {
    // Fallback senza ACF: user_meta conserva un URL
    $url = get_user_meta( $user_id, 'thub_profile_photo', true );
    if ( $url && is_string($url) ) {
      $maybe = attachment_url_to_postid( $url );
      if ( $maybe ) $old_id = intval($maybe);
    }
  }

  // Verifica che l'ID punti davvero a un attachment
  if ( $old_id && get_post_type( $old_id ) !== 'attachment' ) {
    $old_id = 0;
  }
  return $old_id;
}

add_action('wp_ajax_thub_upload_profile_photo', 'thub_upload_profile_photo');
function thub_upload_profile_photo(){
  /* ---------------------------------
   * [THUB_SEC] Sicurezza e pre-check
   * --------------------------------- */
  if ( ! is_user_logged_in() ) {
    wp_send_json_error( ['message' => 'Devi essere loggato.'], 401 );
  }
  check_ajax_referer('thub_profile_upload', 'nonce');

  if ( empty($_FILES['profile_photo']) || ! isset($_FILES['profile_photo']['tmp_name']) ) {
    wp_send_json_error( ['message' => 'Nessun file ricevuto.'], 400 );
  }

  $file = $_FILES['profile_photo'];
  if ( (int)$file['size'] > 2 * 1024 * 1024 ) {
    wp_send_json_error( ['message' => 'File troppo grande (max 2MB).'], 400 );
  }

  $allowed_mimes = [
    'jpg|jpeg' => 'image/jpeg',
    'png'      => 'image/png',
    'webp'     => 'image/webp',
  ];
  $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );
  if ( empty($check['ext']) || empty($check['type']) ) {
    wp_send_json_error( ['message' => 'Formato non consentito (usa JPG, PNG o WEBP).'], 400 );
  }

  $user_id = get_current_user_id();

  /* --------------------------------------------------
   * [THUB_OLD] Ricava l’eventuale allegato precedente
   * -------------------------------------------------- */
  $old_attach_id = thub_get_previous_profile_attachment_id( $user_id );

  /* ----------------------------------------
   * [THUB_UPLOAD] Caricamento su /uploads
   * ---------------------------------------- */
  require_once ABSPATH . 'wp-admin/includes/file.php';
  $overrides = [
    'test_form' => false,
    'mimes'     => $allowed_mimes,
  ];
  $moved = wp_handle_upload( $file, $overrides );
  if ( isset($moved['error']) ) {
    wp_send_json_error( ['message' => 'Errore upload: '.$moved['error']], 500 );
  }

  /* --------------------------------------------------
   * [THUB_ATTACH] Crea attachment in Media Library
   * -------------------------------------------------- */
  $attachment = [
    'post_mime_type' => $moved['type'],
    'post_title'     => sanitize_file_name( pathinfo($moved['file'], PATHINFO_FILENAME) ),
    'post_content'   => '',
    'post_status'    => 'inherit',
    'post_author'    => $user_id, // utile per auditing/ownership
  ];
  $attach_id = wp_insert_attachment( $attachment, $moved['file'], 0, true );
  if ( is_wp_error($attach_id) ) {
    @unlink($moved['file']);
    wp_send_json_error( ['message' => 'Impossibile creare l’attachment.'], 500 );
  }

  require_once ABSPATH . 'wp-admin/includes/image.php';
  $metadata = wp_generate_attachment_metadata( $attach_id, $moved['file'] );
  wp_update_attachment_metadata( $attach_id, $metadata );

  /* ------------------------------------------------------------
   * [THUB_SAVE] Aggiorna profilo utente (ACF o user_meta fallback)
   * ------------------------------------------------------------ */
  if ( function_exists('update_field') ) {
    // ACF → salviamo l'ID dell'allegato (best practice)
    update_field( 'thub_profile_photo', $attach_id, 'user_'.$user_id );
  } else {
    // Fallback → salviamo l'URL in user_meta
    update_user_meta( $user_id, 'thub_profile_photo', wp_get_attachment_url($attach_id) );
  }

  /* --------------------------------------------------------
   * [THUB_CLEANUP] Cancella l’allegato precedente se esiste
   *  - Solo se è diverso dal nuovo
   *  - wp_delete_attachment(..., true) rimuove file + meta + thumbnail
   * -------------------------------------------------------- */
  if ( $old_attach_id && $old_attach_id !== $attach_id ) {
    // NB: Questo eliminerà l’attachment dal sito. NON farlo se riusi la stessa immagine altrove.
    wp_delete_attachment( $old_attach_id, true );
  }

  $thumb_url = wp_get_attachment_image_url( $attach_id, 'thumbnail' );
  $full_url  = wp_get_attachment_url( $attach_id );

  wp_send_json_success( [
    'id'      => $attach_id,
    'url'     => $thumb_url ?: $full_url,
    'full'    => $full_url,
    'message' => 'Foto aggiornata.',
  ] );
}

/* [THUB_CANVAS_MENU_LOCS] 10 locazioni: 9 pagine + 1 fallback di default */
add_action('after_setup_theme', function(){
  register_nav_menus( array(
    // Fallback usato se la locazione specifica di pagina non è assegnata
    'thub-canvas-default'           => __('THUB Canvas – Default', 'hello-elementor-child'),

    // Locazioni per pagina (slug-based)
    'thub-canvas-classroom'         => __('THUB Canvas – Classroom', 'hello-elementor-child'),
    'thub-canvas-gestione-profilo-attivita' => __('THUB Canvas – Gestione profilo attività', 'hello-elementor-child'),
    'thub-canvas-console'           => __('THUB Canvas – Console', 'hello-elementor-child'),
    'thub-canvas-account'           => __('THUB Canvas – Account', 'hello-elementor-child'),
    'thub-canvas-cronologia'        => __('THUB Canvas – Cronologia', 'hello-elementor-child'),
    'thub-canvas-servizi'           => __('THUB Canvas – Servizi', 'hello-elementor-child'),
    'thub-canvas-lingua-e-regione'  => __('THUB Canvas – Lingua e regione', 'hello-elementor-child'),
    'thub-canvas-impostazioni'      => __('THUB Canvas – Impostazioni', 'hello-elementor-child'),
    'thub-canvas-assistenza'        => __('THUB Canvas – Assistenza', 'hello-elementor-child'),
  ));
});

/* =========================================================
 * [THUB_CANVAS_BOOT] Setup unificato per TUTTE le pagine Canvas “a sezioni”
 * - Fallback location menu (se non selezioni un menu via ACF)
 * - ACF: popolamento select "thub_canvas_menu"
 * - URL SEO-friendly: /pagina/<section>/ → ?section=<section>
 * - Helpers per pagina e sezione correnti
 * - Evidenziazione attiva nel menu (classi + aria-current)
 * - Enqueue JS cosmetico su tutte le pagine Canvas a sezioni
 * ========================================================= */

/* 1) (Opzionale) Location fallback: usata se NON selezioni un menu via ACF */
add_action('after_setup_theme', function(){
  register_nav_menus([
    'thub-account-menu' => __('Menu Account (THUB)', 'hello-child'),
  ]);
});

/* 2) ACF: popola il Select "thub_canvas_menu" con tutti i menu WP */
// [THUB_ACF_MENU_CHOICES]
add_filter('acf/load_field/name=thub_canvas_menu', function($field){
  $field['choices'] = [];
  foreach (wp_get_nav_menus() as $m) {
    // WP_Term: $m->term_id (ID menu) — $m->name (nome)
    $field['choices'][(string)$m->term_id] = $m->name;
  }
  // (facoltativo) placeholder se Allow Null = Sì
  if (!isset($field['placeholder'])) {
    $field['placeholder'] = 'Seleziona un menu…';
  }
  return $field;
});

/* 3) Elenco slug delle pagine Canvas che hanno sezioni */
// [THUB_CANVAS_SECTIONS]
function thub_canvas_section_slugs(){
  return [
    'account',
    'gestione-profilo-attivita',
    'console',
    'cronologia',
    'servizi',
    'lingua-e-regione',
    'impostazioni',
    'assistenza',
    'classroom',
  ];
}

/* 4) Sezione di default per ciascuna pagina (se manca ?section=) */
// [THUB_CANVAS_DEFAULT_SECTION]
function thub_canvas_default_section($page_slug){
  $map = [
    'account'                   => 'home',
    'gestione-profilo-attivita' => 'home',
    'console'                   => 'dashboard',
    'cronologia'                => 'cronologia',
    'servizi'                   => 'home',
    'lingua-e-regione'          => 'lingua-di-visualizzazione',
    'impostazioni'              => 'impostazioni',
    'assistenza'                => 'centro-assistenza',
    'classroom'                 => 'home',
  ];
  return $map[$page_slug] ?? 'home';
}

/* 5) URL SEO-friendly: /{pagina}/{section}/ → ?section={section} */
// [THUB_CANVAS_REWRITES]
add_action('init', function(){
  $slugs = thub_canvas_section_slugs();
  // Esempio di pattern risultante: ^(account|console|servizi)/([^/]+)/?$
  $pattern = '^(' . implode('|', array_map('preg_quote', $slugs)) . ')/([^/]+)/?$';
  add_rewrite_rule($pattern, 'index.php?pagename=$matches[1]&section=$matches[2]', 'top');
});

// [THUB_CANVAS_QUERYVAR] registra la query var "section"
add_filter('query_vars', function($vars){
  $vars[] = 'section';
  return $vars;
});

// [THUB_CANVAS_REWRITES_FLUSH] flush automatico dopo attivazione tema
add_action('after_switch_theme', function(){
  $slugs = thub_canvas_section_slugs();
  $pattern = '^(' . implode('|', array_map('preg_quote', $slugs)) . ')/([^/]+)/?$';
  add_rewrite_rule($pattern, 'index.php?pagename=$matches[1]&section=$matches[2]', 'top');
  flush_rewrite_rules();
});

/* 6) Helper correnti (slug pagina e section) */
// [THUB_CANVAS_HELPERS]
function thub_canvas_current_page_slug(){
  $qo = get_queried_object();
  return (!empty($qo->post_name)) ? $qo->post_name : '';
}
function thub_canvas_current_section(){
  $sec = get_query_var('section');
  if (!$sec) {
    $sec = isset($_GET['section']) ? sanitize_key($_GET['section']) : '';
  }
  return $sec;
}
/* Estrae la 'section' dall'URL di un item di menu (?section=... O /pagina/section/) */
function thub_canvas_extract_section_from_item_url($url, $page_slug){
  $url  = esc_url_raw($url);

  // Tentativo 1: query string
  $q = wp_parse_url($url, PHP_URL_QUERY);
  if ($q){
    parse_str($q, $out);
    if (!empty($out['section'])) return sanitize_key($out['section']);
  }

  // Tentativo 2: path /pagina/section/
  $path = wp_parse_url($url, PHP_URL_PATH);
  if ($path){
    $parts = array_values(array_filter(explode('/', trim($path,'/'))));
    $i = array_search($page_slug, $parts, true);
    if ($i !== false && isset($parts[$i+1])) return sanitize_key($parts[$i+1]);
  }
  return null;
}

/* 7) Evidenziazione del link attivo nel menu (uniforme per tutte le pagine Canvas a sezioni) */
// [THUB_CANVAS_MENU_ACTIVE]
add_filter('nav_menu_css_class', function($classes, $item){
  if (!is_page_template('page-thub-canvas.php')) return $classes;

  $page = thub_canvas_current_page_slug();
  if (!in_array($page, thub_canvas_section_slugs(), true)) return $classes;

  // Classe base per i <li> (stile uniforme)
  $classes[] = 'thub-account__item';

  $current = thub_canvas_current_section();
  if (!$current) { $current = thub_canvas_default_section($page); }

  $section = thub_canvas_extract_section_from_item_url($item->url, $page);
  if ($section && $section === $current){
    $classes[] = 'is-active';
    $classes[] = 'current-menu-item';
  }
  return array_unique($classes);
}, 10, 2);

add_filter('nav_menu_link_attributes', function($atts, $item){
  if (!is_page_template('page-thub-canvas.php')) return $atts;

  $page = thub_canvas_current_page_slug();
  if (!in_array($page, thub_canvas_section_slugs(), true)) return $atts;

  // Classe base per i <a> (stile uniforme)
  $atts['class'] = isset($atts['class']) ? ($atts['class'].' thub-account__link') : 'thub-account__link';

  $current = thub_canvas_current_section();
  if (!$current) { $current = thub_canvas_default_section($page); }

  $section = thub_canvas_extract_section_from_item_url($item->url, $page);
  if ($section && $section === $current){
    $atts['aria-current'] = 'page';
  }
  return $atts;
}, 10, 2);

/* 8) Enqueue JS cosmetico (evidenziazione al volo) su tutte le pagine Canvas a sezioni */
// [THAB_CANVAS_JS_ENQUEUE]
add_action('wp_enqueue_scripts', function(){
  if (!is_page_template('page-thub-canvas.php')) return;
  $page = thub_canvas_current_page_slug();
  if (in_array($page, thub_canvas_section_slugs(), true)) {
    wp_enqueue_script(
      'thub-account', // riutilizziamo lo stesso handle
      get_stylesheet_directory_uri() . '/assets/js/thub-account.js',
      [],
      '1.1.0',
      true
    );
  }
}, 20);

/* =========================================================
 * [THUB_LOGIN_GUARD] Protezione pagine Canvas a sezioni
 * - Se utente NON loggato e pagina usa page-thub-canvas.php
 *   e slug appartiene a thub_canvas_section_slugs(),
 *   reindirizza alla pagina di login con redirect_to=URL richiesto.
 * ========================================================= */

/* [THUB_LOGIN_URL_HELPER] Ricava l'URL della pagina login (custom o fallback) */
function thub_get_login_url_with_redirect($redirect_to = ''){
  // URL richiesto (fallback: home)
  if (!$redirect_to) {
    $scheme      = is_ssl() ? 'https' : 'http';
    $redirect_to = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  }
  $redirect_to = esc_url_raw($redirect_to);

  // 1) Prova a trovare una pagina che usa il template 'page-login.php'
  $login_url = '';
  $pages = get_pages([
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'page-login.php',
    'post_status'=> 'publish',
    'number'     => 1,
  ]);
  if (!empty($pages)) {
    $login_url = get_permalink($pages[0]->ID);
  }

  // 2) Se non trovata, prova slug comuni (login / accedi)
  if (!$login_url) {
    foreach (['login','accedi'] as $slug_guess){
      $p = get_page_by_path($slug_guess);
      if ($p && $p->post_status === 'publish') {
        $login_url = get_permalink($p->ID);
        break;
      }
    }
  }

  // 3) Fallback assoluto: wp-login.php
  if (!$login_url) {
    return wp_login_url($redirect_to);
  }

  // Appendi redirect_to anche alla pagina login custom
  return add_query_arg(['redirect_to' => rawurlencode($redirect_to)], $login_url);
}

/* [THUB_LOGIN_GUARD_ACTION] Reindirizza i non loggati alle pagine protette */
add_action('template_redirect', function(){
  // Solo front-end template Canvas
  if (!is_page_template('page-thub-canvas.php')) return;

  // Se già nella pagina login custom, non fare nulla (evita loop)
  if (is_page() && is_page_template('page-login.php')) return;

  // Lista pagine Canvas "a sezioni" (definita in precedenza)
  if (!function_exists('thub_canvas_section_slugs')) return;
  $qo   = get_queried_object();
  $slug = !empty($qo->post_name) ? $qo->post_name : '';
  if (!$slug || !in_array($slug, thub_canvas_section_slugs(), true)) return;

  // Utente loggato? ok. Altrimenti redirect a login
  if (!is_user_logged_in()) {
    $scheme      = is_ssl() ? 'https' : 'http';
    $current_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $login_url   = thub_get_login_url_with_redirect($current_url);

    // Reindirizza in sicurezza e termina
    wp_safe_redirect($login_url, 302);
    exit;
  }
});

/* ==========================================================
 * [THUB_HELPER_AVATAR_URL] Ritorna URL immagine profilo utente (ACF) o "".
 * - Cerca ACF user field "thub_profile_photo" (ID/URL/Array)
 * - Se non presente → ritorna stringa vuota (mostreremo icona generica)
 * ========================================================== */
if (!function_exists('thub_get_user_avatar_url')) {
  function thub_get_user_avatar_url($user_id, $size = 'thumbnail'){
    if (!$user_id) return '';
    // ACF: restituisce ID/URL/Array a seconda del "return format"
    if (function_exists('get_field')) {
      $raw = get_field('thub_profile_photo', 'user_'.$user_id, false); // RAW
      if (is_numeric($raw)) {
        return wp_get_attachment_image_url((int)$raw, $size) ?: wp_get_attachment_url((int)$raw);
      } elseif (is_array($raw)) {
        if (!empty($raw['ID']))  return wp_get_attachment_image_url((int)$raw['ID'], $size) ?: wp_get_attachment_url((int)$raw['ID']);
        if (!empty($raw['url'])) return esc_url($raw['url']);
      } elseif (is_string($raw) && filter_var($raw, FILTER_VALIDATE_URL)) {
        return esc_url($raw);
      }
    }
    // Fallback senza ACF: non usiamo Gravatar qui (ritorniamo "")
    $meta_url = get_user_meta($user_id, 'thub_profile_photo', true);
    if ($meta_url && filter_var($meta_url, FILTER_VALIDATE_URL)) return esc_url($meta_url);
    return '';
  }
}

/* ============================================================
 * [THUB_SAVE_PERSONALI] Handlers salvataggio sezione Informazioni personali
 * File: functions.php (child theme)
 * Requisito: utente loggato. I form postano a admin-post.php.
 * ============================================================ */

/* -- Helper: redirect sicuro alla pagina di provenienza con flag updated -- */
function thub_redirect_back_updated() {
  $ref = wp_get_referer();
  $url = $ref ? $ref : home_url('/');
  // aggiunge/aggiorna ?updated=1
  $sep = (strpos($url, '?') === false) ? '?' : '&';
  wp_safe_redirect( $url . $sep . 'updated=1' );
  exit;
}

/* -- Helper: richiede login + verifica nonce generico -- */
function thub_require_auth_and_check_nonce($nonce_field, $action) {
  if ( ! is_user_logged_in() ) {
    wp_die(__('Devi essere loggato per eseguire questa azione.', 'thub'));
  }
  if ( empty($_POST[$nonce_field]) || ! wp_verify_nonce( $_POST[$nonce_field], $action ) ) {
    wp_die(__('Token di sicurezza non valido.', 'thub'));
  }
}

/* ============================================================
 * 1) Nome + Nickname
 * ------------------------------------------------------------ */
add_action('admin_post_thub_update_name', function(){
  thub_require_auth_and_check_nonce('thub_nonce_name','thub_update_name');

  $uid   = get_current_user_id();
  $first = isset($_POST['first_name'])   ? sanitize_text_field($_POST['first_name'])   : '';
  $last  = isset($_POST['last_name'])    ? sanitize_text_field($_POST['last_name'])    : '';
  $pub   = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';

  update_user_meta($uid, 'first_name', $first);
  update_user_meta($uid, 'last_name',  $last);

  if ($pub) {
    wp_update_user(['ID'=>$uid, 'display_name'=>$pub]);
  }

  thub_redirect_back_updated();
});

/* ============================================================
 * 2) Data di nascita (GG/MM/AAAA o varianti in base a paese)
 * ------------------------------------------------------------ */
add_action('admin_post_thub_update_dob', function(){
  thub_require_auth_and_check_nonce('thub_nonce_dob','thub_update_dob');

  $uid = get_current_user_id();
  $d = isset($_POST['dob_day'])   ? absint($_POST['dob_day'])   : 0;
  $m = isset($_POST['dob_month']) ? absint($_POST['dob_month']) : 0;
  $y = isset($_POST['dob_year'])  ? absint($_POST['dob_year'])  : 0;

  // Validazioni minime
  if ($d>=1 && $d<=31)   update_user_meta($uid,'thub_dob_day',   $d);  else delete_user_meta($uid,'thub_dob_day');
  if ($m>=1 && $m<=12)   update_user_meta($uid,'thub_dob_month', $m);  else delete_user_meta($uid,'thub_dob_month');
  if ($y>=1900 && $y<= (int)date('Y')) update_user_meta($uid,'thub_dob_year',  $y); else delete_user_meta($uid,'thub_dob_year');

  thub_redirect_back_updated();
});

/* ============================================================
 * 3) Email di recupero + Email di contatto
 * ------------------------------------------------------------ */
add_action('admin_post_thub_update_emails', function(){
  thub_require_auth_and_check_nonce('thub_nonce_emails','thub_update_emails');

  $uid = get_current_user_id();
  $rec = isset($_POST['thub_email_recovery']) ? sanitize_email($_POST['thub_email_recovery']) : '';
  $con = isset($_POST['thub_email_contact'])  ? sanitize_email($_POST['thub_email_contact'])  : '';

  /* [THUB_FIX_EMAILS] Ripristino chiave recovery corretta */
  if ($rec && is_email($rec)) update_user_meta($uid, 'thub_email_recovery', $rec);
  else delete_user_meta($uid, 'thub_email_recovery');

  if ($con && is_email($con)) update_user_meta($uid, 'thub_email_contact', $con);
  else delete_user_meta($uid, 'thub_email_contact');

  thub_redirect_back_updated();
});

/* ============================================================
 * 4) Numero di telefono (prefisso + numero)
 * ------------------------------------------------------------ */
add_action('admin_post_thub_update_phone', function(){
  thub_require_auth_and_check_nonce('thub_nonce_phone','thub_update_phone');

  $uid = get_current_user_id();
  $cc  = isset($_POST['thub_phone_cc'])     ? sanitize_text_field($_POST['thub_phone_cc'])     : '';
  $num = isset($_POST['thub_phone_number']) ? sanitize_text_field($_POST['thub_phone_number']) : '';

  // Normalizzazione semplice: prefisso deve iniziare con +, numero solo cifre/spazi
  if ($cc && $cc[0] !== '+') $cc = '+' . preg_replace('/\D+/','',$cc);
  $num = preg_replace('/\D+/','',$num);

  if ($cc)  update_user_meta($uid,'thub_phone_cc',$cc);   else delete_user_meta($uid,'thub_phone_cc');
  if ($num) update_user_meta($uid,'thub_phone_number',$num); else delete_user_meta($uid,'thub_phone_number');

  thub_redirect_back_updated();
});

/* ============================================================
 * 5) Indirizzo di casa
 *    Paese: IT/SM => campi strutturati; OTHER => campo libero
 * ------------------------------------------------------------ */
add_action('admin_post_thub_update_address_home', function(){
  thub_require_auth_and_check_nonce('thub_nonce_addr_home','thub_update_address_home');

  $uid = get_current_user_id();
  $country = isset($_POST['thub_home_country']) ? sanitize_text_field($_POST['thub_home_country']) : 'IT';
  update_user_meta($uid,'thub_home_country', $country);

  if (in_array($country, ['IT','SM'], true)){
    $addr    = isset($_POST['thub_home_address'])  ? sanitize_text_field($_POST['thub_home_address'])  : '';
    $number  = isset($_POST['thub_home_number'])   ? sanitize_text_field($_POST['thub_home_number'])   : '';
    $interno = isset($_POST['thub_home_interno'])  ? sanitize_text_field($_POST['thub_home_interno'])  : '';
    $cap     = isset($_POST['thub_home_cap'])      ? sanitize_text_field($_POST['thub_home_cap'])      : '';
    $city    = isset($_POST['thub_home_city'])     ? sanitize_text_field($_POST['thub_home_city'])     : '';
    $prov    = isset($_POST['thub_home_province']) ? sanitize_text_field($_POST['thub_home_province']) : '';

    update_user_meta($uid,'thub_home_address',  $addr);
    update_user_meta($uid,'thub_home_number',   $number);
    update_user_meta($uid,'thub_home_interno',  $interno);
    update_user_meta($uid,'thub_home_cap',      $cap);
    update_user_meta($uid,'thub_home_city',     $city);
    update_user_meta($uid,'thub_home_province', $prov);

    delete_user_meta($uid,'thub_home_free');
  } else {
    $free = isset($_POST['thub_home_free']) ? sanitize_text_field($_POST['thub_home_free']) : '';
    update_user_meta($uid,'thub_home_free',$free);

    // pulizia campi strutturati
    delete_user_meta($uid,'thub_home_address');
    delete_user_meta($uid,'thub_home_number');
    delete_user_meta($uid,'thub_home_interno');
    delete_user_meta($uid,'thub_home_cap');
    delete_user_meta($uid,'thub_home_city');
    delete_user_meta($uid,'thub_home_province');
  }

  thub_redirect_back_updated();
});

/* ============================================================
 * 6) Indirizzo di lavoro
 * ------------------------------------------------------------ */
add_action('admin_post_thub_update_address_work', function(){
  thub_require_auth_and_check_nonce('thub_nonce_addr_work','thub_update_address_work');

  $uid = get_current_user_id();
  $country = isset($_POST['thub_work_country']) ? sanitize_text_field($_POST['thub_work_country']) : 'IT';
  update_user_meta($uid,'thub_work_country', $country);

  if (in_array($country, ['IT','SM'], true)){
    $addr    = isset($_POST['thub_work_address'])  ? sanitize_text_field($_POST['thub_work_address'])  : '';
    $number  = isset($_POST['thub_work_number'])   ? sanitize_text_field($_POST['thub_work_number'])   : '';
    $interno = isset($_POST['thub_work_interno'])  ? sanitize_text_field($_POST['thub_work_interno'])  : '';
    $cap     = isset($_POST['thub_work_cap'])      ? sanitize_text_field($_POST['thub_work_cap'])      : '';
    $city    = isset($_POST['thub_work_city'])     ? sanitize_text_field($_POST['thub_work_city'])     : '';
    $prov    = isset($_POST['thub_work_province']) ? sanitize_text_field($_POST['thub_work_province']) : '';

    update_user_meta($uid,'thub_work_address',  $addr);
    update_user_meta($uid,'thub_work_number',   $number);
    update_user_meta($uid,'thub_work_interno',  $interno);
    update_user_meta($uid,'thub_work_cap',      $cap);
    update_user_meta($uid,'thub_work_city',     $city);
    update_user_meta($uid,'thub_work_province', $prov);

    delete_user_meta($uid,'thub_work_free');
  } else {
    $free = isset($_POST['thub_work_free']) ? sanitize_text_field($_POST['thub_work_free']) : '';
    update_user_meta($uid,'thub_work_free',$free);

    delete_user_meta($uid,'thub_work_address');
    delete_user_meta($uid,'thub_work_number');
    delete_user_meta($uid,'thub_work_interno');
    delete_user_meta($uid,'thub_work_cap');
    delete_user_meta($uid,'thub_work_city');
    delete_user_meta($uid,'thub_work_province');
  }

  thub_redirect_back_updated();
});

/* ============================================================
 * 7) Profili social (repeater: array di [platform,url])
 * ------------------------------------------------------------ */
add_action('admin_post_thub_update_socials', function(){
  thub_require_auth_and_check_nonce('thub_nonce_socials','thub_update_socials');

  $uid = get_current_user_id();
  $rows = isset($_POST['thub_socials']) && is_array($_POST['thub_socials']) ? $_POST['thub_socials'] : [];
  $clean = [];

  foreach($rows as $row){
    $platform = isset($row['platform']) ? sanitize_text_field($row['platform']) : '';
    $url      = isset($row['url'])      ? esc_url_raw($row['url'])              : '';
    if ($platform && $url) {
      $clean[] = ['platform'=>$platform, 'url'=>$url];
    }
  }

  update_user_meta($uid,'thub_socials', $clean);
  thub_redirect_back_updated();
});

/* ============================================================
 * [THUB_PRIVACY_SAVE_HANDLER] Salvataggio “Dati e privacy” (scoped)
 * - Endpoint: admin-post.php?action=thub_save_privacy
 * - Aggiorna solo le chiavi presenti in POST (thub_keys[])
 * ============================================================ */
add_action('admin_post_thub_save_privacy', function(){
  if ( ! is_user_logged_in() ){
    wp_safe_redirect( wp_get_referer() ?: home_url('/') );
    exit;
  }

  // Nonce: accettiamo i nonce dei singoli box (box1/box2/box3/box4)
  $nonce_ok =
    ( isset($_POST['_thub_privacy_nonce']) && (
        wp_verify_nonce($_POST['_thub_privacy_nonce'], 'thub_privacy_box1') ||
        wp_verify_nonce($_POST['_thub_privacy_nonce'], 'thub_privacy_box2') ||
        wp_verify_nonce($_POST['_thub_privacy_nonce'], 'thub_privacy_box3') ||
        wp_verify_nonce($_POST['_thub_privacy_nonce'], 'thub_privacy_box4')
      )
    );

  if ( ! $nonce_ok ){
    wp_safe_redirect( add_query_arg('thub_privacy_saved', '0', wp_get_referer() ?: home_url('/') ) );
    exit;
  }

  $uid = get_current_user_id();

  // Solo le chiavi dichiarate in questo form
  $allowed = array_map('sanitize_key', (array)($_POST['thub_keys'] ?? []));

  // Whitelist globale (sicurezza)
  $whitelist = [
    'thub_priv_web_activity',
    'thub_priv_history',
    'thub_ads_personalized',
    'thub_ads_partners',
    'thub_search_personalized',
    'thub_share_birthdate',
    'thub_share_gender',
    'thub_share_email',
    'thub_share_phone',
    'thub_share_social',
    'thub_share_geoloc',
  ];

  // Intersezione: chiavi valide e dichiarate dal form
  $keys_to_touch = array_values(array_intersect($allowed, $whitelist));

  foreach( $keys_to_touch as $meta_key ){
    // Se la checkbox è presente → '1', altrimenti '0'
    $val = (isset($_POST[$meta_key]) && $_POST[$meta_key] === '1') ? '1' : '0';
    update_user_meta( $uid, $meta_key, $val );
  }

  wp_safe_redirect( add_query_arg('thub_privacy_saved', '1', wp_get_referer() ?: home_url('/') ) );
  exit;
});

/* ============================================================
 * [THUB_PRIVACY_EXPORT_HANDLER] Download dati utente (JSON)
 * - Endpoint: admin-post.php?action=thub_download_data
 * - Esegue il download immediato di un JSON con i principali meta
 *   gestiti in questa sezione (non sostituisce l’export GDPR completo).
 * ============================================================ */
add_action('admin_post_thub_download_data', function(){
  if ( ! is_user_logged_in() ){
    wp_safe_redirect( wp_get_referer() ?: home_url('/') );
    exit;
  }
  if ( ! isset($_POST['_thub_privacy_export_nonce']) || ! wp_verify_nonce($_POST['_thub_privacy_export_nonce'], 'thub_privacy_export_nonce') ){
    wp_safe_redirect( add_query_arg('thub_privacy_export', 'err', wp_get_referer() ?: home_url('/') ) );
    exit;
  }

  $uid   = get_current_user_id();
  $user  = get_userdata($uid);
  $email = $user ? $user->user_email : '';

  // Whitelist meta pertinenti (puoi estendere in futuro)
  $keys = [
    'thub_priv_web_activity',
    'thub_priv_history',
    'thub_ads_personalized',
    'thub_ads_partners',
    'thub_search_personalized',
    'thub_share_birthdate',
    'thub_share_gender',
    'thub_share_email',
    'thub_share_phone',
    'thub_share_social',
    'thub_share_geoloc',
  ];

  $data = [
    'generated_at' => current_time('mysql'),
    'site'         => home_url('/'),
    'user' => [
      'id'    => $uid,
      'email' => $email,
      'login' => $user ? $user->user_login : '',
      'name'  => $user ? trim($user->first_name.' '.$user->last_name) : '',
    ],
    'privacy_meta' => [],
  ];
  foreach($keys as $k){
    $data['privacy_meta'][$k] = get_user_meta($uid, $k, true);
  }

  // Output JSON scaricabile
  $filename = 'thub-privacy-data-user-'.$uid.'-'.date('Ymd-His').'.json';
  nocache_headers();
  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  echo wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
});

/* ============================================================
 * [THUB_PRIVACY_ERASE_HANDLER] Richiesta eliminazione dati (GDPR)
 * - Endpoint: admin-post.php?action=thub_erase_data
 * - Crea una richiesta "remove_personal_data" e invia email conferma.
 * ============================================================ */
add_action('admin_post_thub_erase_data', function(){
  if ( ! is_user_logged_in() ){
    wp_safe_redirect( wp_get_referer() ?: home_url('/') );
    exit;
  }
  if ( ! isset($_POST['_thub_privacy_erase_nonce']) || ! wp_verify_nonce($_POST['_thub_privacy_erase_nonce'], 'thub_privacy_erase_nonce') ){
    wp_safe_redirect( add_query_arg('thub_privacy_export', 'err', wp_get_referer() ?: home_url('/') ) );
    exit;
  }

  $uid   = get_current_user_id();
  $user  = get_userdata($uid);
  if( ! $user ){
    wp_safe_redirect( add_query_arg('thub_privacy_export', 'err', wp_get_referer() ?: home_url('/') ) );
    exit;
  }

  // Crea richiesta ufficiale di rimozione dati e invia email
  $request_id = wp_create_user_request( $user->user_email, 'remove_personal_data', [ 'user_id' => $uid ] );
  if ( is_wp_error($request_id) || ! $request_id ){
    wp_safe_redirect( add_query_arg('thub_privacy_export', 'err', wp_get_referer() ?: home_url('/') ) );
    exit;
  }

  wp_send_user_request( $request_id ); // invia email con link di conferma

  // Torna alla pagina con messaggio informativo
  wp_safe_redirect( add_query_arg('thub_privacy_export', 'mail', wp_get_referer() ?: home_url('/') ) );
  exit;
});

/* ============================================================
 * [THUB_PRIVACY_AJAX_SAVE] Salvataggio ON/OFF via AJAX
 * Endpoint: admin-ajax.php?action=thub_toggle_privacy_save
 * Body atteso: meta_key, value ('1'|'0'), _ajax_nonce
 * ============================================================ */
add_action('wp_ajax_thub_toggle_privacy_save', function(){
  if( ! is_user_logged_in() ){
    wp_send_json_error(['message'=>'Non autenticato'], 401);
  }

  // Verifica nonce
  $nonce = isset($_POST['_ajax_nonce']) ? sanitize_text_field($_POST['_ajax_nonce']) : '';
  if( ! wp_verify_nonce($nonce, 'thub_privacy_ajax') ){
    wp_send_json_error(['message'=>'Nonce non valido'], 403);
  }

  // Whitelist chiavi consentite
  $whitelist = [
    'thub_priv_web_activity',
    'thub_priv_history',
    'thub_ads_personalized',
    'thub_ads_partners',
    'thub_search_personalized',
    'thub_share_birthdate',
    'thub_share_gender',
    'thub_share_email',
    'thub_share_phone',
    'thub_share_social',
    'thub_share_geoloc',
  ];

  $meta_key = isset($_POST['meta_key']) ? sanitize_key($_POST['meta_key']) : '';
  $value    = isset($_POST['value'])    ? ($_POST['value'] === '1' ? '1':'0') : '0';

  if( ! in_array($meta_key, $whitelist, true) ){
    wp_send_json_error(['message'=>'Chiave non consentita'], 400);
  }

  $uid = get_current_user_id();
  update_user_meta($uid, $meta_key, $value);

  wp_send_json_success([
    'message'  => 'Salvato',
    'meta_key' => $meta_key,
    'value'    => $value,
  ]);
});

// [THUB_ENQUEUE_ACCOUNT_JS] Enqueue + AJAX url
add_action('wp_enqueue_scripts', function(){
  // registra o enqueue il tuo JS (aggiorna handle/percorso se diverso)
  wp_enqueue_script(
    'thub-account',
    get_stylesheet_directory_uri() . '/assets/js/thub-account.js',
    [], // dipendenze se servono
    null,
    true
  );
  // passa ajaxurl al JS
  wp_localize_script('thub-account', 'thubAccount', [
    'ajaxurl' => admin_url('admin-ajax.php'),
  ]);
});

/* ===========================================
 * [THUB_SEC_CHANGE_PWD_AJAX] Cambio password via AJAX
 * - Verifica nonce
 * - Forza utente loggato
 * - Valida lunghezza min. 8
 * - Aggiorna password e meta 'thub_last_password_change'
 * =========================================== */
add_action('wp_ajax_thub_change_password', 'thub_ajax_change_password');
function thub_ajax_change_password(){
  if ( ! is_user_logged_in() ){
    wp_send_json( ['success'=>false, 'message'=>'Non sei autenticato.'], 403 );
  }

  $user_id = get_current_user_id();

  // Nonce
  $nonce = isset($_POST['nonce']) ? sanitize_text_field( wp_unslash($_POST['nonce']) ) : '';
  if ( ! wp_verify_nonce( $nonce, 'thub_change_password' ) ){
    wp_send_json( ['success'=>false, 'message'=>'Token di sicurezza non valido.'], 400 );
  }

  // Nuova password
  $newpwd = isset($_POST['newpwd']) ? (string) $_POST['newpwd'] : '';
  $newpwd = trim( wp_unslash( $newpwd ) );

  if ( strlen( $newpwd ) < 8 ){
    wp_send_json( ['success'=>false, 'message'=>'La password deve avere almeno 8 caratteri.'], 400 );
  }

  // Aggiorna password
  wp_set_password( $newpwd, $user_id );

  // Memorizza data ultima modifica password
  update_user_meta( $user_id, 'thub_last_password_change', time() );

  // Ri‑autentica l’utente su questa sessione (utile perché wp_set_password può invalidarla)
  wp_clear_auth_cookie();
  wp_set_current_user( $user_id );
  wp_set_auth_cookie( $user_id, true ); // true = remember me

  wp_send_json( ['success'=>true, 'message'=>'Password aggiornata con successo.'] );
}

/* ===========================
   [THUB_PASS_LAST_CHANGED_HOOKS]
   - Inizializza alla registrazione (ulteriore safety net)
   - Aggiorna dopo reset password (flusso "password dimenticata")
   =========================== */

// Alla registrazione (se non già settato)
add_action('user_register', function($user_id){
  if ( ! get_user_meta($user_id, 'thub_last_password_change', true) ) {
    update_user_meta($user_id, 'thub_last_password_change', time());
  }
}, 10);

// Dopo reset password
add_action('after_password_reset', function($user, $new_pass){
  update_user_meta($user->ID, 'thub_last_password_change', time());
}, 10, 2);

/* ===========================================================
 * [THUB_LOGIN_DEVICES] Tracciamento dispositivi
 * - A: registra/aggiorna il device al login (wp_login)
 * - B: aggiorna last_seen del device corrente ad ogni richiesta (init)
 * - Retention: 60 giorni / max 20 device
 * =========================================================== */

if ( ! defined('DAY_IN_SECONDS') ) define('DAY_IN_SECONDS', 86400);

function thub_get_client_ip(){
  // Semplice, senza X-Forwarded-For (SiteGround può ripulire a monte)
  return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
}
function thub_get_client_ua(){
  return isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 512) : '';
}
function thub_fingerprint($ip, $ua){
  return md5($ip . '|' . $ua);
}
function thub_parse_ua_basic($ua){
  // Parser "povero ma robusto" (evita dipendenze esterne)
  $browser = 'Sconosciuto';
  $os      = 'Sconosciuto';

  if (stripos($ua,'Firefox') !== false)         $browser = 'Firefox';
  elseif (stripos($ua,'Chrome') !== false && stripos($ua,'Chromium') === false && stripos($ua,'Edg') === false) $browser = 'Chrome';
  elseif (stripos($ua,'Edg') !== false)         $browser = 'Edge';
  elseif (stripos($ua,'Safari') !== false && stripos($ua,'Chrome') === false) $browser = 'Safari';

  if (stripos($ua,'Windows') !== false)         $os = 'Windows';
  elseif (stripos($ua,'Mac OS X') !== false || stripos($ua,'Macintosh') !== false) $os = 'macOS';
  elseif (stripos($ua,'Android') !== false)     $os = 'Android';
  elseif (stripos($ua,'iPhone') !== false || stripos($ua,'iPad') !== false) $os = 'iOS';
  elseif (stripos($ua,'Linux') !== false)       $os = 'Linux';

  return ['browser'=>$browser, 'os'=>$os];
}

/* --- A) Registra/aggiorna device al login --- */
add_action('wp_login', function($user_login, $user){
  $user_id = is_object($user) ? (int)$user->ID : 0;
  if(!$user_id) return;

  $ip  = thub_get_client_ip();
  $ua  = thub_get_client_ua();
  $fp  = thub_fingerprint($ip, $ua);
  $now = time();

  $list = get_user_meta($user_id, 'thub_login_devices', true);
  if(!is_array($list)) $list = [];

  $parsed = thub_parse_ua_basic($ua);

  if(!isset($list[$fp])){
    $list[$fp] = [
      'ip'         => $ip,
      'ua'         => $ua,
      'browser'    => $parsed['browser'],
      'os'         => $parsed['os'],
      'first_seen' => $now,
      'last_seen'  => $now,
      'count'      => 1,
      // 'country'  => '', 'city' => ''  // opzionale (GeoIP in futuro)
    ];
  }else{
    $list[$fp]['last_seen'] = $now;
    $list[$fp]['count']     = (int)$list[$fp]['count'] + 1;
  }

  // Retention: 60 giorni / massimo 20 record
  $cut = $now - 60*DAY_IN_SECONDS;
  foreach($list as $k => $row){
    if( !is_array($row) || (isset($row['last_seen']) && (int)$row['last_seen'] < $cut) ){
      unset($list[$k]);
    }
  }
  if (count($list) > 20){
    // ordina per last_seen desc e taglia
    uasort($list, fn($a,$b)=> ($b['last_seen']??0) <=> ($a['last_seen']??0) );
    $list = array_slice($list, 0, 20, true);
  }

  update_user_meta($user_id, 'thub_login_devices', $list);
}, 10, 2);

/* --- B) Aggiorna last_seen ad ogni richiesta autenticata (leggerissimo) --- */
add_action('init', function(){
  if(!is_user_logged_in()) return;
  $user_id = get_current_user_id();
  if(!$user_id) return;

  $ip  = thub_get_client_ip();
  $ua  = thub_get_client_ua();
  if(!$ua) return;

  $fp  = thub_fingerprint($ip, $ua);
  $now = time();

  $list = get_user_meta($user_id, 'thub_login_devices', true);
  if(!is_array($list) || !isset($list[$fp])) return; // aggiorniamo solo se già esiste
  $list[$fp]['last_seen'] = $now;

  update_user_meta($user_id, 'thub_login_devices', $list);
});

/* ==========================================================================================
   [THUB_SUPPORT_HANDLER] — Ricezione form centro assistenza (section-centro-assistenza.php)
   - Verifica honeypot + nonce
   - Validazione: subject 10..100, message non vuoto
   - Invio wp_mail a info@t-hub.it con Reply-To utente loggato (se disponibile)
   - Redirect con query var ?thub_support=ok|invalid|spam|err
   ========================================================================================== */
add_action('init', function(){

  if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) return;
  if ( empty($_POST['thub_support_action']) || $_POST['thub_support_action'] !== 'create' ) return;

  // 0) Honeypot (bot)
  if ( ! empty($_POST['thub_hp'] ?? '') ) {
    $back = wp_get_referer() ?: home_url('/');
    wp_safe_redirect( add_query_arg('thub_support','spam', $back) );
    exit;
  }

  // 1) Nonce
  if ( ! isset($_POST['_thub_support_nonce']) || ! wp_verify_nonce($_POST['_thub_support_nonce'], 'thub_support') ) {
    $back = wp_get_referer() ?: home_url('/');
    wp_safe_redirect( add_query_arg('thub_support','spam', $back) );
    exit;
  }

  // 2) Input sanitizzati
  $subject_raw = isset($_POST['thub_support_subject']) ? wp_unslash($_POST['thub_support_subject']) : '';
  $message_raw = isset($_POST['thub_support_message']) ? wp_unslash($_POST['thub_support_message']) : '';

  $subject = sanitize_text_field( $subject_raw );
  // Sanitizzazione "sicura" con wp_kses() (niente HTML, manteniamo solo testo)
  $message = wp_kses( $message_raw, array() );

  // 3) Validazioni
  if ( mb_strlen($subject) < 10 || mb_strlen($subject) > 100 || trim($message) === '' ) {
    $back = wp_get_referer() ?: home_url('/');
    wp_safe_redirect( add_query_arg('thub_support','invalid', $back) );
    exit;
  }

  // 4) Costruzione email
  $to       = 'info@t-hub.it';
  $site     = wp_specialchars_decode( get_bloginfo('name'), ENT_QUOTES );
  $mail_sub = sprintf('[Supporto %s] %s', $site, $subject);

  // Corpo email (testo semplice)
  $user     = wp_get_current_user();
  $userMail = ( $user && $user->exists() ) ? $user->user_email : '';
  $userID   = ( $user && $user->exists() ) ? $user->ID : 0;

  $body  = "Richiesta di assistenza dal sito: $site\n\n";
  $body .= "Utente: " . ( $userID ? "ID #$userID, {$user->display_name}, {$userMail}" : "ospite (non loggato)" ) . "\n";
  $body .= "Data: " . wp_date('Y-m-d H:i:s') . "\n\n";
  $body .= "Sintesi:\n$subject\n\n";
  $body .= "Messaggio:\n$message\n";

  // Headers
  $headers = array('Content-Type: text/plain; charset=UTF-8');
  if ( is_email($userMail) && ! empty($_POST['thub_support_reply_ok']) ) {
    $headers[] = 'Reply-To: '.$userMail;
  }

  // 5) Invio
  $ok = wp_mail( $to, $mail_sub, $body, $headers );

  // 6) Redirect con esito
  $back = wp_get_referer() ?: home_url('/assistenza/');
  wp_safe_redirect( add_query_arg('thub_support', $ok ? 'ok' : 'err', $back) );
  exit;
});

/* ============================================================
   [THUB_SECURITY_CONTROLLER] — Sorgente unica per stato sicurezza
   Usata da: section-sicurezza.php (dettaglio) e section-home.php (box)
   Ritorna:
     - status:       'ok' | 'attention'
     - title:        string (H3)
     - text:         string (testo coerente con lo status)
     - text_ok:      string (per chi vuole scegliere a runtime)
     - text_att:     string (per chi vuole scegliere a runtime)
   ============================================================ */
function thub_get_security_summary($user_id){
  $user_id = (int) $user_id;

  // Leggo i metadati che già usi in section-sicurezza.php
  $last_pwd_change = (int) get_user_meta( $user_id, 'thub_last_password_change', true );
  $rec_email       = trim( (string) get_user_meta( $user_id, 'thub_email_recovery', true ) );
  $rec_phone_cc    = trim( (string) get_user_meta( $user_id, 'thub_phone_cc', true ) );
  $rec_phone_num   = trim( (string) get_user_meta( $user_id, 'thub_phone_number', true ) );

  $has_pwd_date    = ! empty( $last_pwd_change );
  $has_phone       = ( $rec_phone_cc !== '' && $rec_phone_num !== '' );
  $has_email       = ( $rec_email !== '' );

  $needs_attention = ( ! $has_pwd_date || ! $has_phone || ! $has_email );

  // Testi (identici a quelli già presenti in section-sicurezza.php)
  $msg_ok  = __('Lo strumento di controllo della sicurezza ha esaminato il tuo account e non ha trovato azioni da consigliare', 'thub');
  $msg_att = __('Lo strumento di controllo della sicurezza ha identificato alcune azioni per rendere più sicuro il tuo account', 'thub');

  $status  = $needs_attention ? 'attention' : 'ok';
  $title   = $needs_attention
             ? __('Il tuo account richiede attenzione', 'thub')
             : __('Il tuo account è protetto', 'thub');
  $text    = $needs_attention ? $msg_att : $msg_ok;

  // Se vuoi, qui puoi applicare filtri per personalizzare da plugin/child
  $summary = [
    'status'   => $status,
    'title'    => $title,
    'text'     => $text,
    'text_ok'  => $msg_ok,
    'text_att' => $msg_att,
  ];

  return apply_filters('thub_security_summary', $summary, $user_id, [
    'has_pwd_date' => $has_pwd_date,
    'has_phone'    => $has_phone,
    'has_email'    => $has_email,
  ]);
}

/* ============================================================
   [THUB_SEARCH_HISTORY_DB] — Setup tabella custom per cronologia
   Tabella: {$wpdb->prefix}thub_search_history
   Campi: id, user_id, query, ts_utc (datetime), meta (json testuale)
   ============================================================ */
add_action('init', function(){
  if( ! is_user_logged_in() ) return; // inizializziamo comunque al primo hit di un loggato
  $opt_key = 'thub_search_history_db_ver';
  $current = (int) get_option($opt_key, 0);
  $target  = 1;

  if($current >= $target) return;

  global $wpdb;
  $table = $wpdb->prefix . 'thub_search_history';
  $charset_collate = $wpdb->get_charset_collate();
  $sql = "CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    query TEXT NOT NULL,
    ts_utc DATETIME NOT NULL,
    meta TEXT NULL,
    PRIMARY KEY (id),
    KEY user_ts (user_id, ts_utc)
  ) {$charset_collate};";

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);

  update_option($opt_key, $target, true);
});

/* ===================================================================
   [THUB_SEARCH_HISTORY_LOG] — Salvataggio ricerche lato server
   Quando l'utente esegue una ricerca (is_search && s != ''), registriamo
   query + timestamp UTC per l'utente loggato.
   =================================================================== */
add_action('template_redirect', function(){
  if( ! is_user_logged_in() ) return;
  if( ! is_search() ) return;

  $q = get_search_query();
  if( ! $q ) return;

  $user_id = get_current_user_id();
  global $wpdb;
  $table = $wpdb->prefix . 'thub_search_history';

  /* [THUB_HISTORY_GUARD] — Rispetta il toggle “Disattiva cronologia”
  Se user_meta('thub_priv_history') === '1' → NON loggare */
  if ( get_user_meta( $user_id, 'thub_priv_history', true ) === '1' ) {
    return;
  }

  // Inserisci riga (ts in UTC)
  $now_utc = gmdate('Y-m-d H:i:s');
  $wpdb->insert($table, [
    'user_id' => $user_id,
    'query'   => wp_strip_all_tags( $q ),
    'ts_utc'  => $now_utc,
    'meta'    => null, // opzionale: puoi salvare filtri, device, ecc.
  ], ['%d','%s','%s','%s']);

  // Pulizia: manteniamo solo ultimi 30 giorni per questo utente
  $cutoff = gmdate('Y-m-d H:i:s', time() - 30*DAY_IN_SECONDS);
  $wpdb->query( $wpdb->prepare(
    "DELETE FROM {$table} WHERE user_id = %d AND ts_utc < %s",
    $user_id, $cutoff
  ) );
});

/* ============================================================
   [THUB_SEARCH_HISTORY_AJAX_LIST] — thub_get_search_history
   Ritorna JSON: items = [{id, query, ts_iso, day_label}, ...]
   ============================================================ */
add_action('wp_ajax_thub_get_search_history', function(){
  check_ajax_referer('thub_search_history_nonce');

  if( ! is_user_logged_in() ){
    wp_send_json([ 'items' => [] ]);
  }

  $user_id = get_current_user_id();

  // [THUB_HISTORY_GUARD_VIEW] Non mostrare nulla se cronologia disattivata
  if ( get_user_meta( $user_id, 'thub_priv_history', true ) === '1' ) {
    wp_send_json([ 'items' => [] ]);
  }

  global $wpdb;
  $table  = $wpdb->prefix . 'thub_search_history';
  $cutoff = gmdate('Y-m-d H:i:s', time() - 30*DAY_IN_SECONDS);

  $rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT id, query, ts_utc
     FROM {$table}
     WHERE user_id = %d AND ts_utc >= %s
     ORDER BY ts_utc DESC
     LIMIT 500",
    $user_id, $cutoff
  ), ARRAY_A );

  $items = [];
  if($rows){
    $tz = wp_timezone();
    foreach($rows as $r){
      $dt = date_create_from_format('Y-m-d H:i:s', $r['ts_utc'], new DateTimeZone('UTC'));
      if($dt){
        $dt->setTimezone($tz);
        $ts_iso    = $dt->format('c');
        $day_label = wp_date( 'l j F Y', $dt->getTimestamp(), $tz );
      }else{
        $ts_iso    = gmdate('c');
        $day_label = '';
      }
      $items[] = [
        'id'        => (int) $r['id'],
        'query'     => $r['query'],
        'ts_iso'    => $ts_iso,
        'day_label' => $day_label,
      ];
    }
  }

  wp_send_json([ 'items' => $items ]);
});

/* ============================================================
   [THUB_SEARCH_HISTORY_AJAX_DELETE] — thub_delete_search_item
   ============================================================ */
add_action('wp_ajax_thub_delete_search_item', function(){
  check_ajax_referer('thub_search_history_nonce');

  if( ! is_user_logged_in() ){
    wp_send_json([ 'success' => false ]);
  }

  $id = (int) ($_POST['id'] ?? 0);
  if($id <= 0){
    wp_send_json([ 'success' => false ]);
  }

  global $wpdb;
  $user_id = get_current_user_id();
  $table   = $wpdb->prefix . 'thub_search_history';

  // Cancella solo se appartiene all'utente corrente
  $deleted = $wpdb->query( $wpdb->prepare(
    "DELETE FROM {$table} WHERE id = %d AND user_id = %d",
    $id, $user_id
  ) );

  wp_send_json([ 'success' => (bool) $deleted ]);
});

/* ============================================================
   [THUB_SEARCH_HISTORY_AJAX_CLEAR] — thub_clear_search_history
   ============================================================ */
add_action('wp_ajax_thub_clear_search_history', function(){
  check_ajax_referer('thub_search_history_nonce');

  if( ! is_user_logged_in() ){
    wp_send_json([ 'success' => false ]);
  }

  global $wpdb;
  $user_id = get_current_user_id();
  $table   = $wpdb->prefix . 'thub_search_history';
  $cutoff  = gmdate('Y-m-d H:i:s', time() - 30*DAY_IN_SECONDS);

  // Cancella solo il range mantenuto (ultimi 30 giorni)
  $deleted = $wpdb->query( $wpdb->prepare(
    "DELETE FROM {$table} WHERE user_id = %d AND ts_utc >= %s",
    $user_id, $cutoff
  ) );

  wp_send_json([ 'success' => true, 'deleted' => (int) $deleted ]);
});

/* ==========================================================================================
   [THUB_LANG_SUPPORTED] — Elenco lingue supportate + filtri
   Nota: puoi filtrare/estendere via 'thub_supported_locales'.
   ========================================================================================== */
if ( ! function_exists('thub_get_supported_locales') ) {
  function thub_get_supported_locales(){
    $locales = [
      // UE/SEE + UK + CH + IS (baseline)
      'it-IT' => 'Italiano (Italia)',
      'en-GB' => 'English (UK)',
      'fr-FR' => 'Français (France)',
      'de-DE' => 'Deutsch (Deutschland)',
      'es-ES' => 'Español (España)',
      'pt-PT' => 'Português (Portugal)',
      'nl-NL' => 'Nederlands (Nederland)',
      'sv-SE' => 'Svenska (Sverige)',
      'da-DK' => 'Dansk (Danmark)',
      'fi-FI' => 'Suomi (Suomi)',
      'no-NO' => 'Norsk (Norge)',
      'el-GR' => 'Ελληνικά (Ελλάδα)',
      'cs-CZ' => 'Čeština (Česko)',
      'sk-SK' => 'Slovenčina (Slovensko)',
      'pl-PL' => 'Polski (Polska)',
      'hu-HU' => 'Magyar (Magyarország)',
      'ro-RO' => 'Română (România)',
      'bg-BG' => 'Български (България)',
      'hr-HR' => 'Hrvatski (Hrvatska)',
      'sl-SI' => 'Slovenščina (Slovenija)',
      'lt-LT' => 'Lietuvių (Lietuva)',
      'lv-LV' => 'Latviešu (Latvija)',
      'et-EE' => 'Eesti (Eesti)',
      'ga-IE' => 'Gaeilge (Éire)',
      'mt-MT' => 'Malti (Malta)',
      'is-IS' => 'Íslenska (Ísland)',
      'en-IE' => 'English (Ireland)',
      'lb-LU' => 'Lëtzebuergesch (Lëtzebuerg)',
      'ca-ES' => 'Català (Espanya)',
      'eu-ES' => 'Euskara (Espainia)',
      'gl-ES' => 'Galego (España)',

      // Extra europee (abilitali o disabilitali con filtro se vuoi “solo UE/SEE”)
      'sr-RS' => 'Srpski (Srbija)',
      'bs-BA' => 'Bosanski (Bosna i Hercegovina)',
      'mk-MK' => 'Македонски (Северна Македонија)',
      'sq-AL' => 'Shqip (Shqipëri)',
      'uk-UA' => 'Українська (Україна)',
      'tr-TR' => 'Türkçe (Türkiye)',
      'ru-RU' => 'Русский (Россия)',
      'be-BY' => 'Беларуская (Беларусь)',
      'fo-FO' => 'Føroyskt (Føroyar)',
    ];
    /**
     * Filtro per personalizzare/limitare la lista:
     * add_filter('thub_supported_locales', function($locales){
     *    unset($locales['ru-RU'], $locales['tr-TR']); // Esempio: disabilita extra
     *    return $locales;
     * });
     */
    return apply_filters('thub_supported_locales', $locales);
  }
}

/* ==========================================================================================
   [THUB_LANG_ACCEPT_PARSE] — Rileva best-match da HTTP_ACCEPT_LANGUAGE
   ========================================================================================== */
if ( ! function_exists('thub_detect_locale_from_accept') ) {
  function thub_detect_locale_from_accept(){
    $supported = thub_get_supported_locales();
    $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if( ! $header ){
      return null;
    }

    // Parsing semplice tipo: "it-IT,it;q=0.9,en-GB;q=0.8,en;q=0.7"
    $candidates = [];
    foreach( explode(',', $header) as $part ){
      $sub = explode(';', trim($part));
      $tag = str_replace('_','-', strtolower(trim($sub[0])) ); // it-it
      $q   = 1.0;
      if( isset($sub[1]) && str_starts_with($sub[1], 'q=') ){
        $q = floatval(substr($sub[1], 2));
      }
      $candidates[$tag] = $q;
    }

    // Normalizzazione: proviamo match completi (xx-YY), poi base (xx)
    $best = null; $best_q = 0;
    foreach( $candidates as $tag => $q ){
      // xx-yy → xx-YY
      $full = preg_replace_callback('/^([a-z]{2})-([a-z]{2})$/', function($m){
        return strtolower($m[1]) . '-' . strtoupper($m[2]);
      }, $tag);

      // 1) Match completo
      if( isset($supported[$full]) && $q > $best_q ){
        $best = $full; $best_q = $q; continue;
      }

      // 2) Match per lingua base: prendi la prima variante dei supported che inizia con xx-
      if( preg_match('/^[a-z]{2}$/', $tag) ){
        foreach( array_keys($supported) as $loc ){
          if( str_starts_with(strtolower($loc), $tag.'-') && $q > $best_q ){
            $best = $loc; $best_q = $q; break;
          }
        }
      }
    }

    return $best ?: null;
  }
}

/* ==========================================================================================
   [THUB_LANG_PHONE_HINT] — Mappa prefisso → locale (hint “soft”, usata solo come fallback)
   NB: useremo valori base ragionevoli; personalizzabili via filtro.
   ========================================================================================== */
if ( ! function_exists('thub_hint_locale_from_phone_cc') ) {
  function thub_hint_locale_from_phone_cc( $cc ){
    $map = [
      '+39'  => 'it-IT', '+44'  => 'en-GB', '+33' => 'fr-FR', '+49' => 'de-DE', '+34' => 'es-ES',
      '+351' => 'pt-PT', '+31'  => 'nl-NL', '+46' => 'sv-SE', '+45' => 'da-DK', '+358'=> 'fi-FI',
      '+47'  => 'no-NO', '+30'  => 'el-GR', '+420'=> 'cs-CZ', '+421'=> 'sk-SK', '+48' => 'pl-PL',
      '+36'  => 'hu-HU', '+40'  => 'ro-RO', '+359'=> 'bg-BG', '+385'=> 'hr-HR', '+386'=> 'sl-SI',
      '+370' => 'lt-LT', '+371' => 'lv-LV', '+372'=> 'et-EE', '+353'=> 'en-IE', '+356'=> 'mt-MT',
      '+354' => 'is-IS', '+352' => 'lb-LU', '+34' => 'es-ES',
      // Extra opzionali:
      '+381' => 'sr-RS', '+387' => 'bs-BA', '+389'=> 'mk-MK', '+355'=> 'sq-AL', '+380'=> 'uk-UA',
      '+90'  => 'tr-TR', '+7'   => 'ru-RU', '+375'=> 'be-BY', '+298'=> 'fo-FO',
    ];
    $map = apply_filters('thub_phone_cc_locale_map', $map);
    return $map[$cc] ?? null;
  }
}

/* ==========================================================================================
   [THUB_LANG_HELPERS] — Locale corrente, label, sigla, set da POST/COOKIE
   ========================================================================================== */
if ( ! function_exists('thub_get_current_locale') ) {
  function thub_get_current_locale(){
    $supported = thub_get_supported_locales();

    // 1) Utente loggato → user_meta
    if( is_user_logged_in() ){
      $u = wp_get_current_user();
      $meta = get_user_meta($u->ID, 'thub_user_locale', true);
      if( $meta && isset($supported[$meta]) ){
        return $meta;
      }
      // fallback one-shot da cookie o Accept-Language:
      $cookie = isset($_COOKIE['thub_user_locale']) ? sanitize_text_field($_COOKIE['thub_user_locale']) : null;
      if( $cookie && isset($supported[$cookie]) ){
        return $cookie;
      }
      $acc = thub_detect_locale_from_accept();
      if( $acc && isset($supported[$acc]) ){
        return $acc;
      }
      return 'it-IT';
    }

    // 2) Ospite → cookie → Accept → default
    $cookie = isset($_COOKIE['thub_user_locale']) ? sanitize_text_field($_COOKIE['thub_user_locale']) : null;
    if( $cookie && isset($supported[$cookie]) ){
      return $cookie;
    }
    $acc = thub_detect_locale_from_accept();
    if( $acc && isset($supported[$acc]) ){
      return $acc;
    }
    return 'it-IT';
  }
}

if ( ! function_exists('thub_get_locale_label') ) {
  function thub_get_locale_label( $locale ){
    $supported = thub_get_supported_locales();
    return $supported[$locale] ?? $locale;
  }
}

if ( ! function_exists('thub_get_locale_sigla') ) {
  function thub_get_locale_sigla( $locale ){
    // es. it-IT → IT
    if( preg_match('/^[a-z]{2}-([A-Z]{2})$/', $locale, $m) ){
      return $m[1];
    }
    // fallback: prime due lettere maiuscole della lingua
    return strtoupper(substr($locale, 0, 2));
  }
}

/* ==========================================================================================
   [THUB_LANG_SETTERS] — Salvataggio lingua (user_meta o cookie)
   ========================================================================================== */
if ( ! function_exists('thub_set_user_locale') ) {
  function thub_set_user_locale( $locale ){
    $supported = thub_get_supported_locales();
    if( ! isset($supported[$locale]) ){
      return false;
    }
    if( is_user_logged_in() ){
      $u = wp_get_current_user();
      update_user_meta($u->ID, 'thub_user_locale', $locale);
    } else {
      // Cookie 1 anno, path /
      setcookie('thub_user_locale', $locale, time()+YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true );
      $_COOKIE['thub_user_locale'] = $locale; // riflette subito
    }
    return true;
  }
}

/* ==========================================================================================
   [THUB_LANG_HOOKS] — Init meta alla registrazione + init da cookie al login (se manca)
   ========================================================================================== */
// Alla registrazione: se non impostato, inizializza da Accept-Language (o hint prefisso tel.)
add_action('user_register', function($user_id){
  $supported = thub_get_supported_locales();

  $locale = thub_detect_locale_from_accept();

  // Hint opzionale da prefisso tel. se non abbiamo nulla
  if( ! $locale ){
    // se hai salvato il prefisso in user_meta (es. 'thub_phone_cc'), recuperalo:
    $cc = get_user_meta($user_id, 'thub_phone_cc', true);
    if( $cc ){
      $hint = thub_hint_locale_from_phone_cc( $cc );
      if( $hint && isset($supported[$hint]) ){
        $locale = $hint;
      }
    }
  }

  if( ! $locale || ! isset($supported[$locale]) ){
    $locale = 'it-IT';
  }

  update_user_meta($user_id, 'thub_user_locale', $locale);
}, 10, 1);

// Al login: se la meta non esiste, inizializza da cookie (se valido) o Accept-Language
add_action('wp_login', function($user_login, $user){
  $supported = thub_get_supported_locales();
  $existing = get_user_meta($user->ID, 'thub_user_locale', true);
  if( $existing && isset($supported[$existing]) ){
    return;
  }
  $cookie = $_COOKIE['thub_user_locale'] ?? '';
  if( $cookie && isset($supported[$cookie]) ){
    update_user_meta($user->ID, 'thub_user_locale', $cookie);
    return;
  }
  $acc = thub_detect_locale_from_accept();
  update_user_meta($user->ID, 'thub_user_locale', $acc && isset($supported[$acc]) ? $acc : 'it-IT');
}, 10, 2);

/* ============================================================
   [THUB_AJAX_SET_LOCALE] Salva lingua (user_meta o cookie)
   - Logged-in: update_user_meta via thub_set_user_locale()
   - Guest: opzionale → setcookie server-side (comunque già facciamo lato client)
   Sicurezza: nonce 'thub_locale'
   ============================================================ */
add_action('wp_ajax_thub_set_locale', 'thub_ajax_set_locale');
add_action('wp_ajax_nopriv_thub_set_locale', 'thub_ajax_set_locale');

function thub_ajax_set_locale(){
  if( ! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'thub_locale') ){
    wp_send_json_error([ 'message' => 'Nonce non valido' ], 403);
  }

  $locale = sanitize_text_field( $_POST['locale'] ?? '' );
  if( ! function_exists('thub_set_user_locale') || ! function_exists('thub_get_supported_locales') ){
    wp_send_json_error([ 'message' => 'Helper non disponibile' ], 500);
  }

  $supported = thub_get_supported_locales();
  if( ! isset($supported[$locale]) ){
    wp_send_json_error([ 'message' => 'Lingua non supportata' ], 400);
  }

  // thub_set_user_locale gestisce: logged-in → user_meta, guest → cookie
  $ok = thub_set_user_locale($locale);

  if( $ok ){
    wp_send_json_success([ 'locale' => $locale, 'label' => $supported[$locale] ]);
  } else {
    wp_send_json_error([ 'message' => 'Impossibile salvare la lingua' ], 500);
  }
}

/* ============================================================
   [THUB_LANG_FORMATTER] Formatter lingua corrente
   - $style = 'after'  → "Italiano (IT)" (default)
   - $style = 'before' → "(IT) Italiano"
   - $bold  = true     → avvolge in <strong>
   ============================================================ */
function thub_format_locale($style='after', $bold=true){
  if( ! function_exists('thub_get_current_locale') 
      || ! function_exists('thub_get_locale_label') 
      || ! function_exists('thub_get_locale_sigla') ){
    return ''; // safety guard
  }

  $loc   = thub_get_current_locale();
  $label = thub_get_locale_label($loc); // es. "Italiano (Italia)"
  $sigla = thub_get_locale_sigla($loc); // es. "IT"

  // pulizia: togliamo "(Italia)" o simili dal label
  $name = preg_replace('/\s*\(.*\)$/','',$label);

  // Composizione in base allo stile richiesto
  if($style === 'before'){
    $out = '(' . $sigla . ') ' . $name;
  } else {
    $out = $name . ' (' . $sigla . ')';
  }

  // Bold opzionale
  return $bold ? '<strong>'.esc_html($out).'</strong>' : esc_html($out);
}

/* ==========================================================================================
   [THUB_RESULTS_LANG_CORE] — Lingua dei risultati di ricerca (strato parallelo)
   - Mantiene separati i concetti da "Lingua di visualizzazione"
   - Persistenza:
     - Utente loggato: user_meta 'thub_results_lang'
     - Ospite: cookie 'thub_results_lang' (1 anno)
   - Rilevazione iniziale: Accept-Language → fallback hint da prefisso tel.
   ========================================================================================== */

/* [THUB_RESULTS_LANG_KEYS] Chiavi dedicate per NON mischiare con la lingua UI */
if ( ! defined('THUB_RESULTS_LANG_META') )   define('THUB_RESULTS_LANG_META',   'thub_results_lang');
if ( ! defined('THUB_RESULTS_LANG_COOKIE') ) define('THUB_RESULTS_LANG_COOKIE', 'thub_results_lang');

/* [THUB_RESULTS_LANG_ALLOWED] Ricicla l’elenco lingue europee che già usi */
if ( ! function_exists('thub_results_get_locales') ){
  function thub_results_get_locales(){
    // Se hai già un helper globale (es. thub_get_supported_locales o simili), usa quello.
    if ( function_exists('thub_get_eu_locales') ) return thub_get_eu_locales();
    // Fallback minimo se l’helper non esistesse.
    return [
      'it'=>'Italiano (IT)','en'=>'English (EN)','fr'=>'Français (FR)','de'=>'Deutsch (DE)','es'=>'Español (ES)',
      'pt'=>'Português (PT)','nl'=>'Nederlands (NL)','sv'=>'Svenska (SE)','da'=>'Dansk (DK)','fi'=>'Suomi (FI)',
      'no'=>'Norsk (NO)','pl'=>'Polski (PL)','cs'=>'Čeština (CZ)','sk'=>'Slovenčina (SK)','hu'=>'Magyar (HU)',
      'ro'=>'Română (RO)','bg'=>'Български (BG)','el'=>'Ελληνικά (GR)','hr'=>'Hrvatski (HR)','sl'=>'Slovenščina (SI)',
      'et'=>'Eesti (EE)','lv'=>'Latviešu (LV)','lt'=>'Lietuvių (LT)','ga'=>'Gaeilge (IE)','mt'=>'Malti (MT)',
    ];
  }
}

/* [THUB_RESULTS_DETECT_BROWSER] Usa il tuo detector se già presente, altrimenti fallback */
if ( ! function_exists('thub_results_detect_browser_lang') ){
  function thub_results_detect_browser_lang(){
    // Se hai già una funzione globale per Accept-Language, usala qui (es. thub_detect_browser_lang()).
    if ( function_exists('thub_detect_browser_lang') ) return thub_detect_browser_lang();

    $fallback = 'it';
    $allowed  = array_keys( thub_results_get_locales() );
    $hdr = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if ( ! $hdr ) return $fallback;
    foreach( explode(',', $hdr) as $p ){
      $p = strtolower(trim($p));
      $p = preg_replace('~;q=.*$~', '', $p);
      $lang = substr($p, 0, 2);
      if ( in_array($lang, $allowed, true) ) return $lang;
    }
    return $fallback;
  }
}

/* [THUB_RESULTS_HINT_PHONE] Hint da prefisso tel. (riusa il tuo mapper se esiste) */
if ( ! function_exists('thub_results_lang_from_phone') ){
  function thub_results_lang_from_phone( $phone_cc = '' ){
    // Se esiste già un helper (es. thub_guess_lang_from_phone), riusalo:
    if ( function_exists('thub_guess_lang_from_phone') ) return thub_guess_lang_from_phone($phone_cc);

    $map = [
      '39'=>'it','41'=>'de','33'=>'fr','49'=>'de','34'=>'es','351'=>'pt','44'=>'en','31'=>'nl',
      '46'=>'sv','45'=>'da','358'=>'fi','47'=>'no','48'=>'pl','420'=>'cs','421'=>'sk','36'=>'hu',
      '40'=>'ro','359'=>'bg','30'=>'el','385'=>'hr','386'=>'sl','372'=>'et','371'=>'lv','370'=>'lt',
      '353'=>'ga','356'=>'mt',
    ];
    $cc = preg_replace('/\D+/', '', (string)$phone_cc);
    return $map[$cc] ?? 'it';
  }
}

/* [THUB_GET_USER_RESULTS_LANG] Risoluzione lingua risultati (user/cookie/browser/hint) */
if ( ! function_exists('thub_get_user_results_lang') ){
  function thub_get_user_results_lang( $user_id = 0 ){
    $locales = thub_results_get_locales();

    if ( $user_id ){
      // 1) user_meta
      $lang = get_user_meta($user_id, THUB_RESULTS_LANG_META, true);
      if ( $lang && isset($locales[$lang]) ) return $lang;

      // 2) browser
      $lang = thub_results_detect_browser_lang();
      if ( $lang ) return $lang;

      // 3) hint dal prefisso tel. salvato nei meta (se usi es. thub_phone_cc)
      $cc = get_user_meta($user_id, 'thub_phone_cc', true);
      if ( $cc ) return thub_results_lang_from_phone($cc);

      return 'it';
    }

    // Ospite: cookie se presente
    if ( isset($_COOKIE[THUB_RESULTS_LANG_COOKIE]) ){
      $c = strtolower(substr($_COOKIE[THUB_RESULTS_LANG_COOKIE],0,5));
      if ( isset($locales[$c]) ) return $c;
    }

    // Fallback browser
    return thub_results_detect_browser_lang();
  }
}

/* [THUB_BOOTSTRAP_GUEST_COOKIE] Inizializza cookie per ospiti se mancante */
add_action('init', function(){
  if ( is_user_logged_in() ) return;
  if ( ! isset($_COOKIE[THUB_RESULTS_LANG_COOKIE]) ){
    $lang = thub_results_detect_browser_lang();
    setcookie(THUB_RESULTS_LANG_COOKIE, $lang, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
    $_COOKIE[THUB_RESULTS_LANG_COOKIE] = $lang; // disponibilità immediata nel run corrente
  }
});

/* [THUB_RESULTS_LANG_SAVE] AJAX — salva meta (loggato) o cookie (ospite) */
add_action('wp_ajax_thub_save_results_lang', function(){
  check_ajax_referer('thub_results_lang_nonce');
  $lang    = isset($_POST['lang']) ? strtolower(sanitize_text_field(wp_unslash($_POST['lang']))) : '';
  $locales = thub_results_get_locales();
  if ( ! isset($locales[$lang]) ) wp_send_json_error(['message'=>'Lingua non valida.']);

  if ( is_user_logged_in() ){
    update_user_meta( get_current_user_id(), THUB_RESULTS_LANG_META, $lang );
    wp_send_json_success(['scope'=>'user','lang'=>$lang]);
  } else {
    setcookie(THUB_RESULTS_LANG_COOKIE, $lang, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
    $_COOKIE[THUB_RESULTS_LANG_COOKIE] = $lang;
    wp_send_json_success(['scope'=>'guest','lang'=>$lang]);
  }
});

/* [THUB_RESULTS_LANG_ENQUEUE] Espone nonce/labels in JS (riuso pattern esistente) */
add_action('wp_enqueue_scripts', function(){
  // Non serve un file JS separato: usiamo un "inline handle" come già fai altrove
  wp_register_script('thub-results-lang', false, [], null, true);
  wp_enqueue_script('thub-results-lang');

  $nonce   = wp_create_nonce('thub_results_lang_nonce');
  $current = thub_get_user_results_lang( get_current_user_id() );
  wp_add_inline_script('thub-results-lang', 'window.THUB_RESULTS_LANG = '.wp_json_encode([
    'ajax'   => admin_url('admin-ajax.php'),
    'nonce'  => $nonce,
    'current'=> $current,
    'labels' => thub_results_get_locales(),
  ]).';');
});

/* ==========================================================================================
   [THUB_RESULTS_REGION_CORE] — Regione/cucina preferita per i risultati di ricerca
   - Persistenza:
     - Utente loggato: user_meta 'thub_results_region' (THUB_RESULTS_REGION_META)
     - Ospite: cookie 'thub_results_region' (THUB_RESULTS_REGION_COOKIE)
   - Rilevazione iniziale: Accept-Language → fallback hint da prefisso telefonico
   - UI: select/menù in Canvas + dropdown nella barra dei risultati (come per la lingua)
   ========================================================================================== */

if ( ! defined('THUB_RESULTS_REGION_META') )   define('THUB_RESULTS_REGION_META',   'thub_results_region');
if ( ! defined('THUB_RESULTS_REGION_COOKIE') ) define('THUB_RESULTS_REGION_COOKIE', 'thub_results_region');



/* [THUB_RESULTS_REGION_CHOICES] Elenco cucine europee principali (senza “contaminazioni” extra-europee) */
if ( ! function_exists('thub_get_results_region_choices') ){
  function thub_get_results_region_choices(){
    return [
      'italiana'   => 'Cucina Italiana',
      'francese'   => 'Cucina Francese',
      'spagnola'   => 'Cucina Spagnola',
      'portoghese' => 'Cucina Portoghese',
      'tedesca'    => 'Cucina Tedesca',
      'austriaca'  => 'Cucina Austriaca',
      'svizzera'   => 'Cucina Svizzera',
      'britannica' => 'Cucina Britannica',
      'irlandese'  => 'Cucina Irlandese',
      'olandese'   => 'Cucina Olandese',
      'belga'      => 'Cucina Belga',
      'svedese'    => 'Cucina Svedese',
      'norvegese'  => 'Cucina Norvegese',
      'danese'     => 'Cucina Danese',
      'finlandese' => 'Cucina Finlandese',
      'islandese'  => 'Cucina Islandese',
      'greca'      => 'Cucina Greca',
      'cipriota'   => 'Cucina Cipriota',
      'maltese'    => 'Cucina Maltese',
      'polacca'    => 'Cucina Polacca',
      'ceca'       => 'Cucina Ceca',
      'slovacca'   => 'Cucina Slovacca',
      'ungherese'  => 'Cucina Ungherese',
      'slovena'    => 'Cucina Slovena',
      'croata'     => 'Cucina Croata',
      'serba'      => 'Cucina Serba',
      'rumena'     => 'Cucina Rumena',
      'bulgara'    => 'Cucina Bulgara',
      'albanese'   => 'Cucina Albanese',
      'macedone'   => 'Cucina Macedone',
      'ucraina'    => 'Cucina Ucraina',
      'lituana'    => 'Cucina Lituana',
      'lettone'    => 'Cucina Lettone',
      'estone'     => 'Cucina Estone',
      // opzionali di confine:
      // 'turca'    => 'Cucina Turca',
      // 'georgiana'=> 'Cucina Georgiana',
    ];
  }
}

/* [THUB_RESULTS_REGION_FROM_LANG] Mappa Accept-Language → slug cucina */
if ( ! function_exists('thub_results_region_from_lang') ){
  function thub_results_region_from_lang( $lang_code ){
    $lang_code = strtolower( substr( (string)$lang_code, 0, 2 ) );
    $map = [
      'it'=>'italiana','fr'=>'francese','es'=>'spagnola','pt'=>'portoghese','de'=>'tedesca',
      'en'=>'britannica','nl'=>'olandese','sv'=>'svedese','no'=>'norvegese','da'=>'danese',
      'fi'=>'finlandese','is'=>'islandese','el'=>'greca','pl'=>'polacca','cs'=>'ceca',
      'sk'=>'slovacca','hu'=>'ungherese','sl'=>'slovena','hr'=>'croata','sr'=>'serba',
      'ro'=>'rumena','bg'=>'bulgara','sq'=>'albanese','mk'=>'macedone','uk'=>'ucraina',
      'lt'=>'lituana','lv'=>'lettone','et'=>'estone',
      // opzionali: 'tr'=>'turca',
    ];
    return $map[$lang_code] ?? '';
  }
}

/* [THUB_RESULTS_REGION_FROM_PHONE] Mappa prefisso telefonico → slug cucina (hint) */
if ( ! function_exists('thub_results_region_from_phone') ){
  function thub_results_region_from_phone( $cc ){
    $cc = preg_replace('/\D+/', '', (string)$cc);
    $map = [
      '39'=>'italiana','33'=>'francese','34'=>'spagnola','351'=>'portoghese','49'=>'tedesca',
      '44'=>'britannica','31'=>'olandese','41'=>'svizzera','46'=>'svedese','47'=>'norvegese',
      '45'=>'danese','358'=>'finlandese','354'=>'islandese','30'=>'greca','48'=>'polacca',
      '420'=>'ceca','421'=>'slovacca','36'=>'ungherese','386'=>'slovena','385'=>'croata',
      '381'=>'serba','40'=>'rumena','359'=>'bulgara','355'=>'albanese','389'=>'macedone',
      '380'=>'ucraina','370'=>'lituana','371'=>'lettone','372'=>'estone',
      // opzionali: '90'=>'turca',
    ];
    return $map[$cc] ?? '';
  }
}

/* [THUB_RESULTS_REGION_GET] Ritorna la regione/cucina corrente (user_meta → cookie → '') */
if ( ! function_exists('thub_get_user_results_region') ){
  function thub_get_user_results_region( $user_id = 0 ){
    $val = '';
    if ( $user_id ) $val = get_user_meta( $user_id, THUB_RESULTS_REGION_META, true );
    if ( ! $val && isset($_COOKIE[THUB_RESULTS_REGION_COOKIE]) ){
      $val = sanitize_key( wp_unslash($_COOKIE[THUB_RESULTS_REGION_COOKIE]) );
    }
    return $val;
  }
}

/* [THUB_RESULTS_REGION_SAVE] AJAX — salva meta (loggato) e sempre cookie (12 mesi) */
add_action('wp_ajax_thub_save_results_region', function(){
  check_ajax_referer('thub_results_region_nonce');
  $value   = sanitize_key( $_POST['value'] ?? '' );
  $choices = thub_get_results_region_choices();
  if( ! isset($choices[$value]) ) wp_send_json_error(['message'=>'Valore non valido.']);

  if ( is_user_logged_in() ){
    update_user_meta( get_current_user_id(), THUB_RESULTS_REGION_META, $value );
  }
  setcookie(THUB_RESULTS_REGION_COOKIE, $value, time()+YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
  $_COOKIE[THUB_RESULTS_REGION_COOKIE] = $value;

  wp_send_json_success(['value'=>$value]);
});

add_action('wp_ajax_nopriv_thub_save_results_region', function(){
  check_ajax_referer('thub_results_region_nonce');
  $value   = sanitize_key( $_POST['value'] ?? '' );
  $choices = thub_get_results_region_choices();
  if( ! isset($choices[$value]) ) wp_send_json_error(['message'=>'Valore non valido.']);

  setcookie(THUB_RESULTS_REGION_COOKIE, $value, time()+YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
  $_COOKIE[THUB_RESULTS_REGION_COOKIE] = $value;

  wp_send_json_success(['value'=>$value]);
});

/* [THUB_RESULTS_REGION_ENQUEUE] Espone nonce/labels in JS (parallelo alla lingua) */
add_action('wp_enqueue_scripts', function(){
  wp_register_script('thub-results-region', false, [], null, true);
  wp_enqueue_script('thub-results-region');

  $nonce   = wp_create_nonce('thub_results_region_nonce');
  $current = thub_get_user_results_region( get_current_user_id() );
  wp_add_inline_script('thub-results-region', 'window.THUB_RESULTS_REGION = '.wp_json_encode([
    'ajax'   => admin_url('admin-ajax.php'),
    'nonce'  => $nonce,
    'current'=> $current,
    'labels' => thub_get_results_region_choices(),
  ]).';');
});

/* ============================================================
   [THUB_SAVE_SETTINGS] — Salvataggio preferenze (solo utenti loggati)
   Chiavi ammesse:
   - thub_theme_mode: 'light'|'dark'
   - thub_results_new_window: 'on'|'off'
   - thub_tts_recipes: 'on'|'off'
   ============================================================ */
add_action('wp_ajax_thub_save_settings', function(){
  check_ajax_referer('thub_settings_nonce');

  if( ! is_user_logged_in() ){
    wp_send_json_error(['msg' => 'Non loggato']);
  }

  $user_id = get_current_user_id();

  // Tema
  if ( isset($_POST['thub_theme_mode']) ){
    $mode = sanitize_text_field( wp_unslash($_POST['thub_theme_mode']) );
    if ( in_array($mode, ['light','dark'], true) ){
      update_user_meta($user_id, 'thub_theme_mode', $mode);
    }
  }

  // Nuova finestra
  if ( isset($_POST['thub_results_new_window']) ){
    $nw = sanitize_text_field( wp_unslash($_POST['thub_results_new_window']) );
    if ( in_array($nw, ['on','off'], true) ){
      update_user_meta($user_id, 'thub_results_new_window', $nw);
    }
  }

  // TTS ricette
  if ( isset($_POST['thub_tts_recipes']) ){
    $tts = sanitize_text_field( wp_unslash($_POST['thub_tts_recipes']) );
    if ( in_array($tts, ['on','off'], true) ){
      update_user_meta($user_id, 'thub_tts_recipes', $tts);
    }
  }

  wp_send_json_success(['ok'=>1]);
});

/* ============================================================
   [THUB_THEME_BODY_CLASS] — Forza classe tema per ogni pagina
   Regole:
   - Utente loggato → legge user_meta 'thub_theme_mode' (light|dark)
   - Ospite → sempre light (NESSUN cookie)
   ============================================================ */
add_filter('body_class', function($classes){
  $mode = 'light';

  if ( is_user_logged_in() ){
    $m = get_user_meta( get_current_user_id(), 'thub_theme_mode', true );
    if ($m === 'dark' || $m === 'light') {
      $mode = $m;
    }
  } else {
    $mode = 'light';
  }

  // Pulisci eventuali residui
  $classes = array_diff($classes, ['thub-theme-light','thub-theme-dark']);
  $classes[] = ($mode === 'dark') ? 'thub-theme-dark' : 'thub-theme-light';
  return $classes;
});

/* ============================================================
   [THUB_RESULTS_NEWWIN_HELPER] — Preferenza “nuova finestra”
   ============================================================ */
function thub_results_open_in_new_window(){
  if( ! is_user_logged_in() ) return false;            // ospiti: off
  $v = get_user_meta( get_current_user_id(), 'thub_results_new_window', true );
  return ($v === 'on');
}








/* ============================================================
 * [THUB_PRO_CONFIG] — Config “Ricette dello Chef (Pro)”
 * - Tassonomia/termine usati per marcare le ricette Pro
 * - Mantieni 'category' e 'pro' salvo diverse esigenze
 * ============================================================ */
if ( ! defined('THUB_PRO_TERM_TAX') )  define('THUB_PRO_TERM_TAX',  'category');
if ( ! defined('THUB_PRO_TERM_SLUG') ) define('THUB_PRO_TERM_SLUG', 'pro');

/* ============================================================
 * [THUB_IS_PRO] — Verifica se l’utente è Pro (filterable)
 * ============================================================ */
function thub_is_pro( $user_id ){
  $flag = (bool) get_user_meta( $user_id, 'thub_is_pro', true );
  return (bool) apply_filters( 'thub_is_pro', $flag, $user_id );
}

/* (opzionale) MIME immagini consentite per upload featured */
if ( ! defined('THUB_ALLOWED_IMAGE_MIME') ) {
  define( 'THUB_ALLOWED_IMAGE_MIME', 'image/jpeg,image/png,image/webp,image/gif,image/svg+xml' );
}










/* ============================================================
 * [THUB_ADMIN_PENDING] — Pannello moderazione ricette
 * ============================================================ */
add_action('admin_menu', function(){
  add_menu_page(
    'Ricette da validare', 'Ricette da validare', 'edit_posts',
    'thub-ricette-validare', 'thub_admin_pending_nonna',
    'dashicons-yes-alt', 58
  );
  add_submenu_page(
    'thub-ricette-validare', 'Ricette della Nonna', 'Ricette della Nonna', 'edit_posts',
    'thub-ricette-validare', 'thub_admin_pending_nonna'
  );
  add_submenu_page(
    'thub-ricette-validare', 'Ricette dello Chef', 'Ricette dello Chef', 'edit_posts',
    'thub-ricette-chef', 'thub_admin_pending_chef'
  );
});

/* Liste */
function thub_admin_pending_nonna(){
  thub_admin_pending_list([
    'title'   => 'Ricette della Nonna — In attesa di approvazione',
    'is_chef' => false,
  ]);
}
function thub_admin_pending_chef(){
  thub_admin_pending_list([
    'title'   => 'Ricette dello Chef — In attesa di approvazione',
    'is_chef' => true,
  ]);
}

/* Tabella generica */
function thub_admin_pending_list( $args ){
  $is_chef = ! empty($args['is_chef']);
  $title   = $args['title'] ?? 'Ricette da validare';

  $tax  = THUB_PRO_TERM_TAX;
  $slug = THUB_PRO_TERM_SLUG;

  $tax_query = $is_chef
    ? [[ 'taxonomy'=>$tax, 'field'=>'slug', 'terms'=>[$slug], 'operator'=>'IN' ]]
    : [[ 'taxonomy'=>$tax, 'field'=>'slug', 'terms'=>[$slug], 'operator'=>'NOT IN' ]];

  $q = new WP_Query([
    'post_type'      => 'ricetta',
    'post_status'    => 'pending',
    'posts_per_page' => 20,
    'tax_query'      => $tax_query,
    'orderby'        => 'date',
    'order'          => 'DESC',
  ]);

  echo '<div class="wrap"><h1>'.esc_html($title).'</h1>';
  if ( ! $q->have_posts() ){
    echo '<p>Nessuna ricetta in attesa.</p></div>'; return;
  }

  echo '<table class="widefat striped"><thead><tr>';
  echo '<th>Titolo</th><th>Utente</th><th>Azioni</th>';
  echo '</tr></thead><tbody>';

  while ( $q->have_posts() ){ $q->the_post();
    $pid   = get_the_ID();
    $auth  = get_user_by( 'id', (int) get_post_field('post_author', $pid) );
    $uname = $auth ? ( $auth->display_name ?: $auth->user_login ) : '—';
    $uemail= $auth ? $auth->user_email : '—';

    $nonce_app = wp_create_nonce( 'thub_recipe_approve_'.$pid );
    $nonce_den = wp_create_nonce( 'thub_recipe_deny_'.$pid );

    $link_preview = get_preview_post_link( $pid );
    $link_app     = admin_url( 'admin-post.php?action=thub_recipe_approve&post_id='.$pid.'&_wpnonce='.$nonce_app );
    $link_den     = admin_url( 'admin-post.php?action=thub_recipe_deny&post_id='.$pid.'&_wpnonce='.$nonce_den );

    echo '<tr>';
    echo '<td><strong>'.esc_html(get_the_title()).'</strong></td>';
    echo '<td>'.esc_html($uname).' &lt;'.esc_html($uemail).'&gt;</td>';
    echo '<td>
            <a class="button" href="'.esc_url($link_preview).'" target="_blank" rel="noopener">Leggi ricetta</a>
            <a class="button button-primary" href="'.esc_url($link_app).'" style="margin-left:6px;">Approva</a>
            <a class="button" href="'.esc_url($link_den).'" style="margin-left:6px;">Nega</a>
          </td>';
    echo '</tr>';
  }
  wp_reset_postdata();
  echo '</tbody></table></div>';
}

/* ============================================================
 * [THUB_ADMIN_PENDING_ACTIONS] — Approva / Nega
 * ============================================================ */
add_action('admin_post_thub_recipe_approve', function(){
  $pid = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
  if ( ! current_user_can('edit_post', $pid) ) wp_die('Permessi insufficienti.');
  check_admin_referer('thub_recipe_approve_'.$pid);
  wp_update_post([ 'ID'=>$pid, 'post_status'=>'publish' ]);
  delete_post_meta( $pid, 'thub_approval_status' );
  wp_safe_redirect( wp_get_referer() ?: admin_url('admin.php?page=thub-ricette-validare') );
  exit;
});

add_action('admin_post_thub_recipe_deny', function(){
  $pid = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
  if ( ! current_user_can('edit_post', $pid) ) wp_die('Permessi insufficienti.');
  check_admin_referer('thub_recipe_deny_'.$pid);
  wp_update_post([ 'ID'=>$pid, 'post_status'=>'draft' ]);
  update_post_meta( $pid, 'thub_approval_status', 'denied' );
  wp_safe_redirect( wp_get_referer() ?: admin_url('admin.php?page=thub-ricette-validare') );
  exit;
});