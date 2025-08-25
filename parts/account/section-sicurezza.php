<?php
/**
 * [THUB_SECTION_SICUREZZA] Sezione "Sicurezza" (Canvas)
 * Percorso: parts/account/section-sicurezza.php
 * Note: impaginazione/box/pulsanti sono gestiti da style.css del tema [THUB_STYLE]
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------------------------
   [THUB_SEC_USER] Utente corrente
----------------------------*/
$current_user = wp_get_current_user();
if ( ! $current_user || 0 === $current_user->ID ) {
  echo '<div class="thub-box">Devi effettuare l\'accesso per vedere questa pagina.</div>';
  return;
}
$user_id = (int) $current_user->ID;

/* ---------------------------
   [THUB_SEC_META] Meta ufficiali THUB
   - thub_last_password_change : timestamp ultima modifica password (int)
   - thub_email_recovery       : email di recupero (string)
   - thub_phone_cc             : prefisso (string, es. +39)
   - thub_phone_number         : numero senza prefisso (string)
----------------------------*/
$last_pwd_change = (int) get_user_meta( $user_id, 'thub_last_password_change', true );
$rec_email       = trim( (string) get_user_meta( $user_id, 'thub_email_recovery', true ) );
$rec_phone_cc    = trim( (string) get_user_meta( $user_id, 'thub_phone_cc', true ) );
$rec_phone_num   = trim( (string) get_user_meta( $user_id, 'thub_phone_number', true ) );
$rec_phone       = trim( trim($rec_phone_cc . ' ' . $rec_phone_num) );

/* ---------------------------
   [THUB_SEC_FLAGS] Completezza dati
----------------------------*/
$has_pwd_date  = ! empty( $last_pwd_change );
$has_phone     = ( $rec_phone_cc !== '' && $rec_phone_num !== '' );
$has_email     = ( $rec_email !== '' );

/* Se manca almeno 1 voce → "richiede attenzione" */
$needs_attention = ( ! $has_pwd_date || ! $has_phone || ! $has_email );
$status_label    = $needs_attention ? 'richiede attenzione' : 'è protetto';

/* Testi richiesti (formulazione esatta richiesta) */
$msg_ok  = 'Lo strumento di controllo della sicurezza ha esaminato il tuo account e non ha trovato azioni da consigliare';
$msg_att = 'Lo strumento di controllo della sicurezza ha identificato alcune azioni per rendere più sicuro il tuo account';

/* ---------------------------
   [THUB_SEC_FMT] Data IT
----------------------------*/
function thub_fmt_date_it( $ts ){
  if ( empty( $ts ) ) return '—';
  return date_i18n( 'd/m/Y', (int) $ts );
}

/* ---------------------------
   [THUB_SEC_SVG] Icone stato
----------------------------*/
function thub_sec_svg_ok(){
  return '<svg width="80" height="80" viewBox="0 0 80 80" aria-hidden="true" role="img">
    <circle cx="40" cy="40" r="38" fill="#eff8f1" stroke="#2c7a4b" stroke-width="2"/>
    <path d="M25 41 l10 10 l20 -22" fill="none" stroke="#2c7a4b" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>';
}
function thub_sec_svg_warn(){
  return '<svg width="80" height="80" viewBox="0 0 80 80" aria-hidden="true" role="img">
    <circle cx="40" cy="40" r="38" fill="#fff7e6" stroke="#d97706" stroke-width="2"/>
    <path d="M40 22 v26" stroke="#d97706" stroke-width="5" stroke-linecap="round"/>
    <circle cx="40" cy="56" r="3.5" fill="#d97706"/>
  </svg>';
}

/* ---------------------------
   [THUB_SEC_NONCE + URL]
----------------------------*/
$nonce_change_pwd = wp_create_nonce( 'thub_change_password' );
$url_info_pers    = esc_url( home_url( '/account/informazioni-personali/' ) );
?>
<section class="thub-sec thub-sec--sicurezza">

  <!-- ===========================
       [THUB_SEC_TITLE] Titolo & intro
       =========================== -->
  <header style="text-align:center; margin:.5rem 0 1rem;">
    <h2 class="thub-account__title" style="margin:.2rem 0;">Sicurezza</h2>
    <div style="height:.5rem;"></div> <!-- spazio richiesto -->
    <p style="color:#555; margin:.25rem 0 .5rem;">
      Impostazioni e consigli per contribuire a mantenere sicuro il tuo account
    </p>
  </header>

  <!-- ===========================
       [THUB_SEC_BOX1] Stato account (2 colonne, sintetico)
       =========================== -->
  <div class="thub-box" style="display:grid; grid-template-columns: 1.2fr .8fr; gap:1rem; align-items:center;">
    <div>
      <h3 style="margin:.2rem 0 .4rem;">
        Il tuo account <span style="font-weight:700;"><?php echo esc_html( $status_label ); ?></span>
      </h3>
      <p style="margin:.6rem 0 0; color:#444;">
        <?php echo $needs_attention ? esc_html( $msg_att ) : esc_html( $msg_ok ); ?>
      </p>
      <!-- Nessun elenco dettagliato: richiesto output sintetico -->
    </div>
    <div style="display:grid; place-items:center;">
      <?php echo $needs_attention ? thub_sec_svg_warn() : thub_sec_svg_ok(); ?>
    </div>
  </div>

  <!-- ===========================
       [THUB_SEC_BOX2] Come accedi a T‑Hub + Opzioni
       =========================== -->
  <div class="thub-box">
    <h3 style="margin:.2rem 0 .4rem;">Come accedi a T‑Hub</h3>
    <p style="margin:.2rem 0 .8rem; color:#444;">
      Assicurati di poter accedere sempre al tuo Account T‑Hub tenendo aggiornate queste informazioni
    </p>

    <!-- ===========================
        [THUB_SEC_PASSWORD] Riga: Password (scheda espandibile)
        =========================== -->
    <div class="thub-row" style="display:grid; grid-template-columns: 1fr 2fr; gap:14px; align-items:start; padding:.6rem 0;">
      <div><strong>Password</strong></div>
      <div>
        <?php
        /* [THUB_SEC_FALLBACK] Se non c'è mai stato cambio, mostra registrazione */
        $label_pwd = '—';
        if ( $last_pwd_change ) {
          $label_pwd = esc_html( thub_fmt_date_it($last_pwd_change) );
        } else {
          // Fallback: prendi data registrazione
          $uobj = get_userdata( $user_id );
          if ( $uobj && $uobj->user_registered ) {
            $label_pwd = 'all’atto della registrazione • ' . esc_html( date_i18n( 'd/m/Y', strtotime($uobj->user_registered) ) );
          }
        }
        ?>
        <details>
          <summary style="cursor:pointer;display:flex;justify-content:flex-end;align-items:center;padding:.2rem 0;">
            <span style="margin-right:.4rem; color:#555;">
              Ultima modifica: <?php echo esc_html( $label_pwd ); ?>
            </span>
            <span aria-hidden="true">▾</span>
          </summary>
          <div style="padding:.7rem .2rem .2rem;">
            <!-- Campi input su due colonne -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.6rem; margin:.4rem 0;">
              <label style="display:block;">
                <span class="screen-reader-text">Nuova password</span>
                <input type="password" id="thub-new-pwd" placeholder="Nuova password"
                      style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
              </label>
              <label style="display:block;">
                <span class="screen-reader-text">Conferma la nuova password</span>
                <input type="password" id="thub-new-pwd2" placeholder="Conferma la nuova password"
                      style="width:100%;padding:.5rem .6rem;border:1px solid #ddd;border-radius:.6rem;">
              </label>
            </div>

            <p style="font-size:.92rem; color:#555; margin:.5rem 0 .5rem;">
              <strong>Sicurezza della password:</strong><br>
              Usa almeno 8 caratteri. Non utilizzare una password di un altro sito o troppo ovvia, come il nome del tuo animale domestico.
            </p>

            <!-- Pulsante -->
            <button id="thub-btn-change-pwd"
                    data-nonce="<?php echo esc_attr( $nonce_change_pwd ); ?>"
                    style="border:1px solid #7249a4;border-radius:.6rem;padding:.45rem .8rem;background:#7249a4;color:#fff;cursor:pointer;">
              Modifica password
            </button>

            <div id="thub-change-pwd-msg" aria-live="polite" style="font-size:.92rem; margin-top:.25rem;"></div>
          </div>
        </details>
      </div>
    </div>

    <hr style="margin:1rem 0; border:0; border-top:1px solid #eee;" />

    <!-- Opzione 2: Numero di telefono di recupero -->
    <div class="thub-sec__opt">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:.6rem; flex-wrap:wrap;">
        <span><strong>Numero di telefono di recupero</strong></span>
        <span style="color:#555;">
          <?php
          if ( $has_phone ) {
            echo esc_html( $rec_phone );
          } else {
            echo '<a href="' . $url_info_pers . '">Imposta numero di telefono</a>';
          }
          ?>
        </span>
      </div>
    </div>

    <hr style="margin:1rem 0; border:0; border-top:1px solid #eee;" />

    <!-- Opzione 3: Email di recupero -->
    <div class="thub-sec__opt">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:.6rem; flex-wrap:wrap;">
        <span><strong>Email di recupero</strong></span>
        <span style="color:#555;">
          <?php
          if ( $has_email ) {
            echo esc_html( $rec_email );
          } else {
            echo '<a href="' . $url_info_pers . '">Imposta email di recupero</a>';
          }
          ?>
        </span>
      </div>
    </div>
  </div>

  <!-- ===========================
       [THUB_SEC_BOX3] I tuoi dispositivi — tabella pulita
       =========================== -->
  <div class="thub-box">
    <h3 style="margin:.2rem 0 .4rem;">I tuoi dispositivi</h3>
    <p style="margin:.2rem 0 .8rem; color:#444;">Dispositivi da cui hai effettuato l'accesso</p>
    <?php
    // Carica lista “device umani” popolata dagli hook in functions.php
    $now    = time();
    $cutoff = $now - 60*DAY_IN_SECONDS;
    $devs   = get_user_meta($user_id, 'thub_login_devices', true);
    $devs   = is_array($devs) ? $devs : [];

    $rows = array_filter($devs, fn($r)=> is_array($r) && !empty($r['last_seen']) && (int)$r['last_seen'] >= $cutoff );
    usort($rows, fn($a,$b)=> ($b['last_seen']??0) <=> ($a['last_seen']??0) );

    echo '<div style="overflow:auto;">';
    if (empty($rows)){
      echo '<div style="color:#666; padding:.25rem 0;">Nessun dispositivo negli ultimi 30 giorni.</div>';
    } else {
      echo '<table style="width:100%; border-collapse:collapse; background:#fff;">';
      echo '<thead><tr style="text-align:left;">
              <th style="padding:.4rem .25rem; font-weight:600;">Browser</th>
              <th style="padding:.4rem .25rem; font-weight:600;">OS</th>
              <th style="padding:.4rem .25rem; font-weight:600;">IP</th>
              <th style="padding:.4rem .25rem; font-weight:600;">Ultimo accesso</th>
              <th style="padding:.4rem .25rem; font-weight:600;">Primo accesso</th>
              <th style="padding:.4rem .25rem; font-weight:600;">Accessi</th>
            </tr></thead><tbody>';

      foreach($rows as $r){
        $browser = esc_html($r['browser'] ?? '—');
        $os      = esc_html($r['os'] ?? '—');
        $ip      = esc_html($r['ip'] ?? '—');
        $ls      = !empty($r['last_seen'])  ? thub_fmt_date_it((int)$r['last_seen'])  : '—';
        $fs      = !empty($r['first_seen']) ? thub_fmt_date_it((int)$r['first_seen']) : '—';
        $cnt     = isset($r['count']) ? (int)$r['count'] : 1;

        echo '<tr>
                <td style="padding:.35rem .25rem;">'.$browser.'</td>
                <td style="padding:.35rem .25rem;">'.$os.'</td>
                <td style="padding:.35rem .25rem;">'.$ip.'</td>
                <td style="padding:.35rem .25rem;">'.$ls.'</td>
                <td style="padding:.35rem .25rem;">'.$fs.'</td>
                <td style="padding:.35rem .25rem;">'.$cnt.'</td>
              </tr>';
      }

      echo '</tbody></table>';
    }
    echo '</div>';
    ?>
  </div>
</section>

<!-- ===========================
     [THUB_SEC_JS] Toggle tendina + Cambio password AJAX
     =========================== -->
<script>
(function(){
  // Cambio password via AJAX
  const btn  = document.getElementById('thub-btn-change-pwd');
  const p1   = document.getElementById('thub-new-pwd');
  const p2   = document.getElementById('thub-new-pwd2');
  const msg  = document.getElementById('thub-change-pwd-msg');
  if(!btn || !p1 || !p2 || !msg) return;

  const ajaxurl = (window.ajaxurl || "<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>");
  const nonce   = btn.getAttribute('data-nonce');

  btn.addEventListener('click', function(e){
    e.preventDefault();
    msg.textContent = '';
    const v1 = (p1.value || '').trim();
    const v2 = (p2.value || '').trim();

    if (v1.length < 8){ msg.textContent = 'La password deve avere almeno 8 caratteri.'; msg.style.color = '#c0392b'; return; }
    if (v1 !== v2){    msg.textContent = 'Le password non coincidono.';               msg.style.color = '#c0392b'; return; }

    btn.disabled = true;

    const form = new FormData();
    form.append('action', 'thub_change_password');
    form.append('nonce',  nonce);
    form.append('newpwd', v1);

    fetch(ajaxurl, { method:'POST', body: form })
      .then(r => r.json())
      .then(data => {
        if (data && data.success){
          msg.textContent = data.message || 'Password aggiornata con successo.';
          msg.style.color = '#2c7a4b';
          p1.value = ''; p2.value = '';
        } else {
          msg.textContent = (data && data.message) ? data.message : 'Errore durante l\'aggiornamento della password.';
          msg.style.color = '#c0392b';
        }
      })
      .catch(()=>{
        msg.textContent = 'Impossibile contattare il server.';
        msg.style.color = '#c0392b';
      })
      .finally(()=>{ btn.disabled = false; });
  });
})();
</script>