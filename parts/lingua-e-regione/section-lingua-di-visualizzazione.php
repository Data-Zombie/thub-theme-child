<?php
/**
 * [THUB_SECTION] section-lingua-di-visualizzazione.php
 * Layout Desktop: griglia 40% | 60%. La colonna DX (60%) contiene:
 * - Select a larghezza 100% della colonna
 * - Pulsante sotto allineato a sinistra (quindi sotto “l’inizio” della select)
 */

$msg = '';
if( $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['thub_lang_action'], $_POST['_thub_lang_nonce'])
    && $_POST['thub_lang_action'] === 'save'
    && wp_verify_nonce($_POST['_thub_lang_nonce'], 'thub_lang_save') ){
  $locale = sanitize_text_field($_POST['thub_user_locale'] ?? '');
  if( function_exists('thub_set_user_locale') && thub_set_user_locale($locale) ){
    $msg = 'OK';
  } else {
    $msg = 'ERR';
  }
}

$current   = function_exists('thub_get_current_locale')    ? thub_get_current_locale()    : 'it-IT';
$supported = function_exists('thub_get_supported_locales') ? thub_get_supported_locales() : [];
?>

<!-- ===========================
     Titolo centrato
     =========================== -->
<div style="text-align:center; margin: 1.6rem 0 1rem;">
  <h2 style="margin:0; font-size:1.6rem;">Lingua e regione</h2>
</div>

<!-- ===========================
     Box Lingua di visualizzazione
     =========================== -->
<div class="thub-box" style="border:1px solid #e6e6ea; border-radius:.8rem; padding:1rem; background:#fff;">

  <!-- Griglia 40% | 60% -->
  <div class="thub-grid" style="display:grid; grid-template-columns: 40% 60%; gap:14px; align-items:start;">

    <!-- Colonna SX (40%) -->
    <div>
      <h3 class="thub-box__title" style="margin:.2rem 0 .3rem; font-size:1.1rem;">Lingua di visualizzazione</h3>
      <p style="margin:0; color:#555;">Seleziona la lingua che preferisci per il testo visualizzato.</p>
    </div>

    <!-- Colonna DX (60%) -->
    <div>
      <form method="post" style="display:flex; flex-direction:column; gap:.6rem; width:100%;">
        <?php wp_nonce_field('thub_lang_save', '_thub_lang_nonce'); ?>
        <input type="hidden" name="thub_lang_action" value="save" />

        <!-- Select: riempie il 60% (cioè 100% della colonna DX) -->
        <label for="thub_user_locale" class="screen-reader-text">Seleziona la lingua</label>
        <select id="thub_user_locale" name="thub_user_locale"
          style="
            width:100%;
            max-width:520px; /* limite estetico opzionale */
            padding:.55rem .7rem;
            border:1px solid #e1e1e6;
            border-radius:.6rem;
            background:#fff;
          ">
          <?php
          if( ! empty($supported) && function_exists('thub_get_locale_sigla') ){
            foreach( $supported as $loc => $label ){
              $abbr = thub_get_locale_sigla($loc);
              $name = preg_replace('/\s*\(.*\)$/', '', (string)$label);
              $opt  = $abbr . ' - ' . $name; // IT - Italiano
              ?>
              <option value="<?php echo esc_attr($loc); ?>" <?php selected($current, $loc); ?>>
                <?php echo esc_html($opt); ?>
              </option>
              <?php
            }
          }
          ?>
        </select>

        <!-- Pulsante: sotto la select, allineato a sinistra (quindi parte dove inizia la select) -->
        <button type="submit"
          style="
            border:1px solid #7249a4;
            border-radius:.6rem;
            padding:.45rem .8rem;
            background:#7249a4;
            color:#fff;
            cursor:pointer;
            width:auto;         /* compatto */
            align-self:flex-start; /* parte dall’inizio della colonna DX */
          ">
          Salva
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ===========================
     Responsive rules
     =========================== -->
<style>
@media (max-width: 860px){
  .thub-box .thub-grid{
    grid-template-columns: 1fr !important; /* una colonna */
  }
  #thub_user_locale{
    width: 100% !important;   /* full-width su mobile */
    max-width: none !important;
  }
  .thub-box .thub-grid form{
    width: 100% !important;
  }
  .thub-box .thub-grid form button{
    align-self: flex-start !important; /* resta a sinistra su mobile */
  }
}
</style>