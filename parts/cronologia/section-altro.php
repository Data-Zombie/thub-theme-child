<?php
/**
 * =====================================================================
 * [THUB_SECTION_ALTRO] — Sezione "Altro" (Canvas)
 * Client JS:  assets/js/thub-account.js ([THUB_ACCOUNT_PRIVACY_AUTOSAVE])
 * Server PHP: handler AJAX in functions.php → action=thub_toggle_privacy_save
 * Nonce usato: 'thub_privacy_ajax'
 * Meta key usata: 'thub_priv_history' (whitelist esistente)
 *
 * Semantica:
 *   checked  = '1' → cronologia DISATTIVATA
 *   unchecked= '0' → cronologia ATTIVA
 * In section-cronologia.php mostrare tabella SOLO se meta != '1'
 * =====================================================================
 */

if ( ! is_user_logged_in() ) {
  wp_safe_redirect( wp_login_url( get_permalink() ) );
  exit;
}

$uid      = get_current_user_id();
$meta_key = 'thub_priv_history';
$current  = get_user_meta( $uid, $meta_key, true );   // '1' (off) | '0' | ''
$is_off   = ($current === '1');
$nonce    = wp_create_nonce( 'thub_privacy_ajax' );
?>
<section id="thub-privacy"
         class="thub-account__section"
         aria-labelledby="thub-altro-title"
         data-nonce="<?php echo esc_attr( $nonce ); ?>"
         style="margin-top:6px;">

  <header style="text-align:center; margin:0 0 18px 0;">
    <h2 id="thub-altro-title" style="font-size:1.35rem; line-height:1.1; margin:0;">Altro</h2>
  </header>

  <!-- ============================
       [THUB_BOX1] Disattiva cronologia
       ============================ -->
  <div class="thub-box thub-box--inline" style="border:1px solid #ededf3; border-radius:.8rem; padding:14px 16px; background:#fff; margin-bottom:14px;">
    <div class="thub-row thub-row--inline" style="display:grid; grid-template-columns: 1fr auto; align-items:center; gap:14px;">

      <div class="thub-col thub-col--label">
        <strong style="font-weight:600; display:inline-block;">Disattiva cronologia T-Hub</strong>
      </div>

      <div class="thub-col thub-col--toggle" style="justify-self:end;">
        <label class="thub-switch" style="display:inline-flex; align-items:center; cursor:pointer; position:relative;">
          <input
            type="checkbox"
            class="thub-toggle"
            id="thub_priv_history"
            name="thub_priv_history"
            <?php checked( $is_off, true ); ?>
            aria-label="Disattiva cronologia T-Hub"
            role="switch"
            aria-checked="<?php echo $is_off ? 'true' : 'false'; ?>"
            data-meta-key="<?php echo esc_attr( $meta_key ); ?>"
            style="position:absolute; opacity:0; width:1px; height:1px; overflow:hidden;"
          />
          <!-- Track (nessun background inline) -->
          <span aria-hidden="true" class="thub-switch__ui">
            <span class="thub-switch__dot"></span>
          </span>
        </label>
      </div>
    </div>

    <div class="thub-save-msg" hidden>
      <!-- 'Salvato ✓' / testo errore impostato dal JS -->
    </div>
  </div>
</section>

<style>
  /* [THUB_ALTRO_BASE] */
  #thub-privacy .thub-box + .thub-box{ margin-top:14px; }

  /* Track base (OFF) */
  #thub-privacy .thub-switch__ui{
    width:46px;
    height:26px;
    border-radius:999px;
    position:relative;
    transition:background .2s ease;
    background:#ccc; /* OFF */
  }
  /* Knob */
  #thub-privacy .thub-switch__dot{
    position:absolute;
    top:3px;
    left:3px;
    width:20px;
    height:20px;
    background:#fff;
    border-radius:50%;
    box-shadow:0 1px 2px rgba(0,0,0,.08);
    transition:left .2s ease;
  }
  /* Stato ON */
  #thub-privacy input.thub-toggle:checked + .thub-switch__ui{
    background: var(--violet);
  }
  #thub-privacy input.thub-toggle:checked + .thub-switch__ui .thub-switch__dot{
    left:23px;
  }

  /* Focus accessibile */
  #thub-privacy input.thub-toggle:focus + .thub-switch__ui{
    outline: 2px solid #b8a3d4;
    outline-offset: 2px;
  }

  /* [THUB_SAVE_MSG] Messaggi */
  #thub-privacy .thub-save-msg{
    margin-top:10px;
    font-size:.92rem;
    padding:.35rem 0;
    border:0;
    background:transparent;
  }
  #thub-privacy .thub-save-msg.thub-save-ok{
    color:#2c7a4b;
  }
  #thub-privacy .thub-save-msg.thub-save-err{
    color:#9b1c1c;
  }

  @media (min-width: 992px){
    #thub-privacy .thub-row--inline{ grid-template-columns: 1fr auto; }
  }
</style>

<script>
  window.thubAccount = window.thubAccount || {};
  window.thubAccount.ajaxurl = window.thubAccount.ajaxurl || '<?php echo esc_js( admin_url('admin-ajax.php') ); ?>';

  // [THUB_A11Y_SYNC] Aggiorna aria-checked al cambio
  (function(){
    const root = document.getElementById('thub-privacy');
    if(!root) return;
    root.addEventListener('change', function(e){
      const el = e.target;
      if(el && el.type === 'checkbox'){
        el.setAttribute('aria-checked', el.checked ? 'true' : 'false');
      }
    }, true);
  })();
</script>