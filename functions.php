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

if (!function_exists('thub_build_recipe_jsonld')) {
  function thub_build_recipe_jsonld($post_id){
    // Prendi ACF se presente, altrimenti meta standard
    $getmeta = function($key) use ($post_id){
      if (function_exists('get_field')) return (string) get_field($key, $post_id);
      return (string) get_post_meta($post_id, $key, true);
    };

    $title = get_the_title($post_id);
    $img   = get_the_post_thumbnail_url($post_id, 'full');
    $prep  = $getmeta('tempo_di_preparazione');
    $cook  = $getmeta('tempo_di_cottura');
    // [THUB_JSONLD] Yield: preferisci 'porzioni_base', fallback al legacy 'porzioni'
    $yield = $getmeta('porzioni_base') ?: $getmeta('porzioni');
    $ing   = $getmeta('ingredienti');
    $steps = $getmeta('passaggi');

    // Ingredienti e istruzioni (una riga = un elemento)
    $ingredients = array_values(array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", (string)$ing))));
    $instructions = [];
    foreach (array_values(array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", (string)$steps)))) as $s) {
      $instructions[] = ['@type'=>'HowToStep', 'text'=>$s];
    }

    // Tempi
    $prepMin  = thub_minutes_from_text($prep);
    $cookMin  = thub_minutes_from_text($cook);
    $totalMin = $prepMin + $cookMin;

    // Tassonomie e autore
    $cats   = wp_get_post_terms($post_id, 'category', ['fields'=>'names']);
    $tags   = wp_get_post_terms($post_id, 'post_tag', ['fields'=>'names']);
    $author = ['@type'=>'Person','name'=> get_the_author_meta('display_name', get_post_field('post_author',$post_id))];
    // Se preferisci autore come brand del sito: usa la riga seguente e commenta quella sopra
    // $author = ['@type'=>'Organization','name'=> get_bloginfo('name')];

    // Descrizione di fallback
    $desc = get_the_excerpt($post_id);
    if (!$desc) $desc = wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $post_id)), 40);

    $url = get_permalink($post_id);

    /* [THUB_JSONLD_EXT] Preleva tassonomie e campi extra */
    $cucina = wp_get_post_terms($post_id, 'cucina', ['fields'=>'names']);
    $portata= wp_get_post_terms($post_id, 'portata',['fields'=>'names']);
    $dieta  = wp_get_post_terms($post_id, 'dieta',  ['fields'=>'names']);
    $kcal   = function_exists('get_field') ? (string) get_field('kcal_per_porz', $post_id) : '';
    $video  = function_exists('get_field') ? (string) get_field('video_url',      $post_id) : '';

    return [
      '@context'           => 'https://schema.org',
      '@type'              => 'Recipe',
      '@id'                => $url . '#recipe',
      'mainEntityOfPage'   => $url,
      'name'               => $title,
      'description'        => $desc,
      'image'              => $img ? [$img] : [],
      'datePublished'      => get_the_date('c', $post_id),
      'dateModified'       => get_the_modified_date('c', $post_id),
      'author'             => $author,
      'recipeCuisine'      => $cucina ? $cucina[0] : null,
      'recipeCategory'     => $portata ? $portata[0] : null,
      'suitableForDiet'    => $dieta ?: null,
      'recipeYield'        => $yield ?: null,
      'prepTime'           => thub_iso_duration_from_minutes($prepMin),
      'cookTime'           => thub_iso_duration_from_minutes($cookMin),
      'totalTime'          => thub_iso_duration_from_minutes($totalMin),
      'recipeIngredient'   => $ingredients,
      'recipeInstructions' => $instructions,
      'keywords'           => implode(', ', $tags),
      'nutrition'          => $kcal ? ['@type'=>'NutritionInformation','calories'=>$kcal.' kcal'] : null,
      'video'              => $video ? ['@type'=>'VideoObject','url'=>$video] : null,
    ];
  }
}

if (!function_exists('thub_echo_recipe_jsonld')) {
  function thub_echo_recipe_jsonld(){
    if (!is_singular('ricetta')) return;
    $data = thub_build_recipe_jsonld(get_the_ID());
    echo '<script type="application/ld+json">'.wp_json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).'</script>' . "\n";
  }
}
// Stampa SOLO nell'head
add_action('wp_head', 'thub_echo_recipe_jsonld', 25);

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

/* ===========================
 * [THUB_SPONSOR_RESOLVER] Supporta campi unificati e legacy "Sponsor Ricetta (CPM)" 
 * =========================== */
function thub_get_sponsor_data($post_id){
  if (!function_exists('get_field')) return null;

  // 1) Campi unificati (nuovi)
  $attivo = get_field('sponsor_attivo', $post_id);
  $nome   = get_field('sponsor_nome',   $post_id);
  $logo   = get_field('sponsor_logo',   $post_id); // ID allegato
  $url    = get_field('sponsor_url',    $post_id);
  $claim  = get_field('sponsor_claim',  $post_id);

  if ($attivo && ($nome || $logo || $url)) {
    return [
      'nome'  => $nome ?: '',
      'logo'  => $logo ?: 0,
      'url'   => $url  ?: '',
      'claim' => $claim ?: '«%s» consiglia questa ricetta',
      'scope' => 'post'
    ];
  }

  // 2) Legacy (gruppo "Sponsor Ricetta (CPM)")
  $cpm_enabled = get_field('sponsor_cpm_enabled', $post_id);
  $cpm_name    = get_field('sponsor_cpm_name',    $post_id);
  $cpm_logo    = get_field('sponsor_cpm_logo',    $post_id); // ID allegato
  if ($cpm_enabled && ($cpm_name || $cpm_logo)) {
    return [
      'nome'  => $cpm_name ?: '',
      'logo'  => $cpm_logo ?: 0,
      'url'   => '', // il legacy non lo prevede
      'claim' => '«%s» consiglia questa ricetta',
      'scope' => 'post-legacy'
    ];
  }

  // 3) Mappature per categoria (Options Page)
  $rows = get_field('sponsorizzazioni_cat','option');
  if ($rows && is_array($rows)) {
    $cats = wp_get_post_terms($post_id, 'category', ['fields'=>'ids']);
    foreach ($rows as $r) {
      $active  = !empty($r['spons_cat_attivo']);
      $term_id = (int) ($r['cat_term'] ?? 0);
      if ($active && $term_id && in_array($term_id, $cats, true)) {
        return [
          'nome'  => $r['spons_cat_nome']  ?? '',
          'logo'  => $r['spons_cat_logo']  ?? 0,
          'url'   => $r['spons_cat_url']   ?? '',
          'claim' => $r['spons_cat_claim'] ?: '«%s» consiglia questa ricetta',
          'scope' => 'category'
        ];
      }
    }
  }
  return null;
}

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

/* ===========================
 * [THUB_JSONLD] Schema Recipe esteso (SAFE)
 * =========================== */
add_action('wp_head', function(){
  if ( ! is_singular('ricetta') ) return;

  $id  = get_the_ID();
  $img = get_the_post_thumbnail_url($id,'large') ?: '';

  // [THUB_jsonld] ACF può non esserci → gestisco in sicurezza
  $has_acf = function_exists('get_field');

  $ing_rows   = $has_acf ? (array) get_field('ingredienti',          $id) : [];
  $steps_rows = $has_acf ? (array) get_field('passaggi',              $id) : [];
  $prep_raw   = $has_acf ? (string) get_field('tempo_di_preparazione',$id) : '';
  $cook_raw   = $has_acf ? (string) get_field('tempo_di_cottura',     $id) : '';
  $yield      = $has_acf ? (string) get_field('porzioni_base',        $id) : '1';
  $kcal       = $has_acf ? (string) get_field('kcal_per_porz',        $id) : '';
  $video      = $has_acf ? (string) get_field('video_url',            $id) : '';

  // [THUB_jsonld] Helper: prendi i nomi dei termini con guardia WP_Error
  $get_terms_names = function($post_id, $tax){
    $t = wp_get_post_terms($post_id, $tax, ['fields'=>'names']);
    if ( is_wp_error($t) || empty($t) ) return [];
    return array_map('wp_strip_all_tags', (array) $t);
  };

  // [THUB_jsonld] Helper: durata → ISO 8601 (es. "45" → "PT45M")
  $to_iso8601 = function($val){
    $val = trim((string)$val);
    if ($val === '') return '';
    if (preg_match('/^\d+$/', $val)) return 'PT'.$val.'M';
    return $val; // assumo già ISO 8601 o stringa valida
  };

  // [THUB_jsonld] Ingredienti come stringhe "qta unit nome"
  $ings = [];
  foreach ($ing_rows as $row){
    $nome = isset($row['ing_nome']) ? trim($row['ing_nome']) : '';
    $qta  = isset($row['ing_qta'])  ? trim((string)$row['ing_qta']) : '';
    $unit = '';
    if (isset($row['ing_unita'])) {
      $unit = ($row['ing_unita'] === 'Altro')
        ? trim($row['ing_unita_altro'] ?? '')
        : trim($row['ing_unita']);
    }
    $line = trim($qta.' '.$unit.' '.$nome);
    if ($line !== '') $ings[] = $line;
  }

  // [THUB_jsonld] Passaggi HowToStep
  $instr = [];
  foreach ($steps_rows as $s) {
    $text = wp_strip_all_tags($s['passo_testo'] ?? '');
    if ($text !== '') $instr[] = ['@type'=>'HowToStep','text'=>$text];
  }

  // [THUB_jsonld] Tassonomie custom con fallback
  $cucina_names   = $get_terms_names($id, 'cucina');
  $portata_names  = $get_terms_names($id, 'portata');
  $dieta_names    = $get_terms_names($id, 'dieta');

  // Fallback categoria WP → recipeCategory se 'portata' non c'è
  if (empty($portata_names)) {
    $cats = get_the_terms($id, 'category');
    if (!is_wp_error($cats) && !empty($cats)) {
      $portata_names = [ $cats[0]->name ];
    }
  }

  // [THUB_jsonld] Costruzione schema
  $schema = [
    '@context'            => 'https://schema.org',
    '@type'               => 'Recipe',
    'name'                => get_the_title($id),
    'image'               => $img,
    'recipeIngredient'    => $ings,
    'recipeInstructions'  => $instr,
    'prepTime'            => $to_iso8601($prep_raw),
    'cookTime'            => $to_iso8601($cook_raw),
    'recipeYield'         => (string) $yield,
  ];
  if (!empty($cucina_names))  $schema['recipeCuisine']  = $cucina_names[0];
  if (!empty($portata_names)) $schema['recipeCategory'] = $portata_names[0];
  if ($kcal !== '')           $schema['nutrition']      = ['@type'=>'NutritionInformation','calories'=> $kcal.' kcal'];
  if (!empty($dieta_names))   $schema['suitableForDiet']= $dieta_names;
  if ($video !== '')          $schema['video']          = ['@type'=>'VideoObject','url'=> esc_url_raw($video)];

  echo "\n<script type='application/ld+json'>",
       wp_json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
       "</script>\n";
}, 20);

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