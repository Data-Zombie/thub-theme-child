<?php
/**
 * [THUB_SECTION] Account Canvas — Section: Pagamenti e abbonamenti (UI-only)
 * File: parts/account/section-pagamenti-abbonamenti.php
 *
 * NOTE VISIVE (rev):
 * - Titolo/intro centrati
 * - Box 1: griglia testo+SVG; tendina full-width SENZA riga separatrice
 * - Nelle tendine, layout 40% | 60% con contenuto principale a destra (60%).
 *   Su schermi piccoli: full-width (stack).
 * - Richieste recap: sposta a DX (60%) link “+ Aggiungi carta…”, testo “Segui le istruzioni…”, 
 *   testo “Per attivare un abbonamento…”, testo “Qui sono visibili solo gli ultimi 30…”.
 * - SVG colonna 2 reso più comprensibile (carta + chip + lucchetto).
 * - Box vuoti “Nessun …” senza bordo.
 * - Summary Box4: “Dettagli”.
 *
 * Endpoint server (da implementare in functions.php quando abiliti Stripe Checkout):
 * - thub_stripe_create_setup_session (mode: 'setup')
 * - thub_stripe_create_sub_session (mode: 'subscription')
 * - thub_stripe_set_default_pm, thub_stripe_cancel_subscription
 * - Helpers: lista PM/Sub/Payments
 */

if ( ! defined('ABSPATH') ) exit;

$current_user_id = get_current_user_id();
if ( ! $current_user_id ) {
  echo '<div class="thub-box"><p>Per gestire i pagamenti devi essere loggato.</p></div>';
  return;
}

/* [THUB_PAY_PROFILE_ID] ID profilo pagamenti (user_meta) */
$pay_profile_meta_key = 'thub_pay_profile_id';
$pay_profile_id = get_user_meta($current_user_id, $pay_profile_meta_key, true);
if ( empty($pay_profile_id) ) {
  $pay_profile_id = 'pay_' . wp_generate_uuid4();
  update_user_meta($current_user_id, $pay_profile_meta_key, $pay_profile_id);
}

/* [THUB_STRIPE_PLACEHOLDERS] In attesa di integrazione functions.php */
$stripe_customer_id = get_user_meta($current_user_id, 'thub_stripe_customer_id', true);
$payment_methods = [];  // es.: [['id'=>'pm_x','brand'=>'visa','last4'=>'4242','exp_month'=>12,'exp_year'=>2027,'is_default'=>true], ...]
$subscriptions   = [];  // es.: [['id'=>'sub_x','product'=>'T-Hub Pro','price'=>'€10/mese','status'=>'active','renews_at'=>'2025-09-10'], ...]
$payments        = [];  // es.: [['id'=>'pi_x','date'=>'2025-08-26','desc'=>'T-Hub Pro 08/2025','amount'=>'€10,00','status'=>'succeeded'], ...]

?>
<section class="thub-account__section thub-section-pagamenti">

  <!-- [THUB_PAYMENTS_INLINE_CSS] Utility 40/60 che collassa a full-width su schermi piccoli -->
  <style>
    /* [THUB_SPLIT_40_60] griglia 2fr|3fr → full-width su schermi piccoli */
    .thub-split-40-60{
      display:grid;
      grid-template-columns: 2fr 3fr; /* 40% | 60% */
      gap: 18px;
      align-items:start;
    }
    @media (max-width: 900px){
      .thub-split-40-60{ grid-template-columns: 1fr; }
    }
  </style>

  <!-- ===============================
       [THUB_PAY_HDR] Titolo/intro centrati
       =============================== -->
  <div style="text-align:center; margin: .25rem 0 1rem;">
    <h2 class="thub-account__title">Pagamenti e abbonamenti</h2>
    <div style="height:.4rem;"></div> <!-- spazio -->
    <p class="thub-account__intro" style="margin:0; color:#444;">
      I tuoi dati di pagamento, le tue transazioni e i tuoi pagamenti ricorrenti
    </p>
  </div>

  <?php wp_nonce_field('thub_payments_nonce', '_thub_payments'); ?>

  <!-- =========================================================
       [BOX 1] Metodi di pagamento
       ========================================================= -->
  <div class="thub-box">
    <!-- Riga top: testo + SVG (immagine più “parlante”) -->
    <div class="[THUB_PM_TOP]" style="display:grid;grid-template-columns:1.4fr .8fr;gap:16px;align-items:center;">
      <div>
        <h3 class="thub-box__title">Metodi di pagamento</h3>
        <!-- (Testo introduttivo rimosso come da richiesta) -->
      </div>
      <div aria-hidden="true" style="display:grid;place-items:center;">
        <!-- [THUB_PM_SVG2] Carta con chip + lucchetto -->
        <svg width="188" height="120" viewBox="0 0 188 120" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Carta e sicurezza">
          <defs>
            <linearGradient id="g1" x1="0" x2="1" y1="0" y2="1">
              <stop offset="0" stop-color="#e9e7fb"/><stop offset="1" stop-color="#f5f4fe"/>
            </linearGradient>
          </defs>
          <!-- carta -->
          <rect x="16" y="26" width="124" height="76" rx="10" fill="url(#g1)" stroke="#e6e6ea"/>
          <rect x="28" y="44" width="68" height="10" rx="5" fill="#dcdcf2"/>
          <rect x="28" y="62" width="96" height="8"  rx="4" fill="#efeff7"/>
          <rect x="28" y="74" width="84" height="8"  rx="4" fill="#efeff7"/>
          <!-- chip -->
          <rect x="106" y="38" width="18" height="14" rx="3" fill="#ffc15a" stroke="#e0a84d"/>
          <path d="M108 45h14M108 41h14M108 49h14" stroke="#b6812f" stroke-width="1"/>
          <!-- lucchetto -->
          <rect x="136" y="52" width="32" height="28" rx="6" fill="#7249a4" opacity=".25"/>
          <rect x="136" y="56" width="32" height="24" rx="6" fill="#7249a4" opacity=".35"/>
          <path d="M146 56v-4a8 8 0 0 1 16 0v4" fill="none" stroke="#7249a4" stroke-width="2"/>
          <circle cx="152" cy="67" r="3" fill="#7249a4"/>
          <path d="M152 70v7" stroke="#7249a4" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>

    <!-- Tendina full-width (NESSUNA riga separatrice) -->
    <details class="[THUB_PM_DETAILS]" style="margin-top:.7rem;">
      <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.2rem 0;list-style:none;">
        <span><strong>Gestisci i metodi di pagamento</strong></span>
        <span aria-hidden="true">▾</span>
      </summary>

      <!-- Contenuto 40% | 60%: link e nota a DESTRA (60%) -->
      <div class="[THUB_PM_CONTENT] thub-split-40-60" style="margin-top:.45rem;">
        <!-- Colonna sinistra (40%): vuota o futura guida contestuale -->
        <div></div>

        <!-- Colonna destra (60%): link + nota + elenco PM -->
        <div>
          <!-- [THUB_PM_ADD_LINK] Link al posto del pulsante (destra 60%) -->
          <p style="margin:0;">
            <a href="#"
               class="thub-link-add-pm"
               data-thub-action="create_setup_session"
               style="text-decoration:none;border-bottom:1px dotted currentColor;color:#7249a4;font-weight:600;">
              + Aggiungi una carta o un metodo di pagamento
            </a>
          </p>
          <p style="font-size:.9rem;color:#666;margin:.25rem 0 .6rem;">
            Segui le istruzioni fornite da Stripe Checkout.
          </p>

          <?php if(empty($stripe_customer_id) || empty($payment_methods)): ?>
            <!-- [THUB_PM_EMPTY] Messaggio senza bordo/box -->
            <p style="margin:0;">Nessun metodo salvato.</p>
          <?php else: ?>
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.5rem;">
              <?php foreach($payment_methods as $pm): ?>
                <?php
                  $is_default = !empty($pm['is_default']);
                  $label = strtoupper($pm['brand'] ?? 'CARD').' •••• '.($pm['last4'] ?? '----').' — '.sprintf('%02d',$pm['exp_month']??0).'/'.($pm['exp_year']??'----');
                ?>
                <li class="[THUB_PM_ITEM]" style="display:flex;justify-content:space-between;align-items:center;border:1px solid #eee;border-radius:.6rem;padding:.5rem .6rem;">
                  <span><?php echo esc_html($label); ?><?php echo $is_default ? ' • <em style="color:#2c7a4b;">Predefinito</em>' : ''; ?></span>
                  <span style="display:flex;gap:.4rem;">
                    <?php if(!$is_default): ?>
                      <button class="button thub-set-default-pm" data-pm="<?php echo esc_attr($pm['id']); ?>" style="border:1px solid #ddd;background:#f7f7f9;border-radius:.5rem;padding:.35rem .6rem;cursor:pointer;">Imposta predefinito</button>
                    <?php endif; ?>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </details>
  </div>

  <!-- =========================================================
       [BOX 2] Abbonamenti
       ========================================================= -->
  <div class="thub-box">
    <h3 class="thub-box__title">Abbonamenti</h3>
    <p style="margin:0;">I tuoi pagamenti ricorrenti per i servizi su abbonamento</p>

    <details class="[THUB_SUB_DETAILS]" style="margin-top:.7rem;">
      <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.2rem 0;">
        <span><strong>Gestisci abbonamenti</strong></span>
        <span aria-hidden="true">▾</span>
      </summary>

      <!-- Contenuto 40% | 60%: testi a DESTRA -->
      <div class="[THUB_SUB_CONTENT] thub-split-40-60" style="margin-top:.45rem;">
        <!-- Sinistra (40%): vuoto o spazio per comunicazioni future -->
        <div></div>

        <!-- Destra (60%): messaggi/CTA + elenco -->
        <div>
          <?php if(empty($stripe_customer_id)): ?>
            <p style="margin:0 0 .6rem;color:#555;">Per attivare un abbonamento devi prima aggiungere un metodo di pagamento.</p>
          <?php else: ?>
            <p style="margin:0;">
              <a href="#"
                 data-thub-action="create_subscription_session"
                 style="text-decoration:none;border-bottom:1px dotted currentColor;color:#7249a4;font-weight:600;">
                + Attiva nuovo abbonamento
              </a>
            </p>
            <p style="font-size:.9rem;color:#666;margin:.25rem 0 .6rem;">Segui le istruzioni fornite da Stripe Checkout.</p>
          <?php endif; ?>

          <?php if(empty($stripe_customer_id) || empty($subscriptions)): ?>
            <!-- [THUB_SUB_EMPTY] Messaggio senza bordo/box -->
            <p style="margin:0;">Nessun abbonamento attivo.</p>
          <?php else: ?>
            <div style="overflow:auto;">
              <table style="width:100%;border-collapse:collapse;">
                <thead>
                  <tr>
                    <th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">ID</th>
                    <th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Prodotto</th>
                    <th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Prezzo</th>
                    <th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Stato</th>
                    <th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Rinnovo</th>
                    <th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($subscriptions as $sub): ?>
                    <tr>
                      <td style="padding:.45rem;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($sub['id']??''); ?></td>
                      <td style="padding:.45rem;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($sub['product']??''); ?></td>
                      <td style="padding:.45rem;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($sub['price']??''); ?></td>
                      <td style="padding:.45rem;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($sub['status']??''); ?></td>
                      <td style="padding:.45rem;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($sub['renews_at']??''); ?></td>
                      <td style="padding:.45rem;border-bottom:1px solid #f2f2f2;">
                        <button class="button thub-cancel-sub" data-sub="<?php echo esc_attr($sub['id']??''); ?>" style="border:1px solid #ddd;background:#fff;border-radius:.5rem;padding:.35rem .6rem;cursor:pointer;">Annulla</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </details>
  </div>

  <!-- =========================================================
       [BOX 3] Transazioni (ultimi 30)
       ========================================================= -->
  <div class="thub-box">
    <h3 class="thub-box__title">Transazioni e attività relative ai pagamenti</h3>
    <p style="margin:0;">Tutti i tuoi pagamenti per i servizi.</p>

    <details class="[THUB_TX_DETAILS]" style="margin-top:.7rem;">
      <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.2rem 0;">
        <span><strong>Vedi transazioni recenti</strong></span>
        <span aria-hidden="true">▾</span>
      </summary>

      <!-- Contenuto 40% | 60%: nota a DESTRA -->
      <div class="[THUB_TX_CONTENT] thub-split-40-60" style="margin-top:.45rem;">
        <!-- Sinistra (40%): vuoto per simmetria -->
        <div></div>

        <!-- Destra (60%): nota + tabella -->
        <div>
          <p style="margin:0 0 .6rem;color:#555;">Qui sono visibili solamente gli ultimi 30 pagamenti eseguiti.</p>

          <?php if(empty($stripe_customer_id) || empty($payments)): ?>
            <!-- [THUB_TX_EMPTY] Messaggio senza bordo/box -->
            <p style="margin:0;">Nessuna transazione da mostrare.</p>
          <?php else: ?>
            <div style="overflow:auto;">
              <table style="width:100%;border-collapse:collapse;">
                <thead>
                  <tr>
                    <th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Data</th>
                    <th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">ID</th>
                    <th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Descrizione</th>
                    <th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Importo</th>
                    <th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Stato</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($payments as $p): ?>
                    <tr>
                      <td style="padding:.45rem;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($p['date']??''); ?></td>
                      <td style="padding:.45rem;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($p['id']??''); ?></td>
                      <td style="padding:.45rem;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($p['desc']??''); ?></td>
                      <td style="padding:.45rem;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($p['amount']??''); ?></td>
                      <td style="padding:.45rem;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($p['status']??''); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </details>
  </div>

  <!-- =========================================================
       [BOX 4] Informazioni generali
       ========================================================= -->
  <div class="thub-box">
    <h3 class="thub-box__title">Informazioni generali</h3>
    <p style="margin:0;">Maggiori informazioni sul tuo account</p>

    <details class="[THUB_INFO_DETAILS]" style="margin-top:.7rem;">
      <summary style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:.2rem 0;">
        <span><strong>Dettagli</strong></span>
        <span aria-hidden="true">▾</span>
      </summary>

      <div class="[THUB_INFO_CONTENT]" style="margin-top:.45rem;">
        <h4 class="thub-box__subtitle">ID profilo pagamenti</h4>
        <p style="margin:.1rem 0 .5rem;"><code><?php echo esc_html($pay_profile_id); ?></code></p>

        <hr style="border:0;border-top:1px solid #eee;margin:.7rem 0;"><!-- Divider richiesto -->

        <h4 class="thub-box__subtitle">Dati fiscali</h4>
        <p style="margin:0;">(da completare configurazione)</p>
      </div>
    </details>
  </div>

  <!-- =========================================================
       [BOX 5] Privacy/Sicurezza (inline, molto discreto)
       ========================================================= -->
  <div class="thub-box" style="display:flex;align-items:center;gap:.6rem;">
    <svg width="20" height="20" viewBox="0 0 24 24" role="img" aria-label="Sicurezza">
      <path d="M12 2l7 3v6c0 5-3.8 9.4-7 10-3.2-.6-7-5-7-10V5l7-3z" fill="#7249a4" opacity=".12"/>
      <path d="M12 2l7 3v6c0 5-3.8 9.4-7 10-3.2-.6-7-5-7-10V5l7-3z" fill="none" stroke="#7249a4" stroke-width="1.5"/>
      <path d="M9.5 12.5l2 2 4-4" fill="none" stroke="#2c7a4b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span>T-Hub protegge la tua privacy e la tua sicurezza.</span>
  </div>
</section>

<!-- =========================
     [THUB_PAYMENTS_JS_MIN] Frontend minimale (AJAX stub)
     - Gestisce sia <a data-thub-action="..."> sia <button ...>
     ========================= -->
<script>
(function(){
  const $$ = (s,c=document)=>Array.from(c.querySelectorAll(s));
  const ajaxurl = window.ajaxurl || '/wp-admin/admin-ajax.php';
  const nonce = document.querySelector('input[name="_thub_payments"]')?.value || '';

  // Crea Setup Session (salvataggio PM) — link/pulsante
  $$('.thub-box [data-thub-action="create_setup_session"]').forEach(el=>{
    el.addEventListener('click', async (e)=>{
      e.preventDefault();
      el.disabled = true;
      try{
        const res = await fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:new URLSearchParams({action:'thub_stripe_create_setup_session',_thub_payments:nonce})});
        const j = await res.json();
        if(j?.url){ location.href=j.url; } else { alert(j?.message||'Impossibile creare la sessione.'); }
      }catch(err){ alert('Errore di rete.'); } finally{ el.disabled=false; }
    });
  });

  // Crea Subscription Session — link/pulsante
  $$('.thub-box [data-thub-action="create_subscription_session"]').forEach(el=>{
    el.addEventListener('click', async (e)=>{
      e.preventDefault();
      el.disabled = true;
      try{
        const res = await fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:new URLSearchParams({action:'thub_stripe_create_sub_session',price_id:'<?php echo esc_js(apply_filters("thub_default_price_id","price_XXXX")); ?>',_thub_payments:nonce})});
        const j = await res.json();
        if(j?.url){ location.href=j.url; } else { alert(j?.message||'Impossibile creare la sessione.'); }
      }catch(err){ alert('Errore di rete.'); } finally{ el.disabled=false; }
    });
  });

  // Imposta metodo predefinito
  $$('.thub-set-default-pm').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const pm = btn.getAttribute('data-pm'); if(!pm) return;
      btn.disabled = true;
      try{
        const res = await fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:new URLSearchParams({action:'thub_stripe_set_default_pm',pm_id:pm,_thub_payments:nonce})});
        const j = await res.json();
        if(j?.ok){ location.reload(); } else { alert(j?.message||'Impossibile impostare il predefinito.'); }
      }catch(err){ alert('Errore di rete.'); } finally{ btn.disabled=false; }
    });
  });

  // Annulla abbonamento
  $$('.thub-cancel-sub').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const sub = btn.getAttribute('data-sub'); if(!sub) return;
      if(!confirm('Confermi l’annullamento di questo abbonamento?')) return;
      btn.disabled = true;
      try{
        const res = await fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:new URLSearchParams({action:'thub_stripe_cancel_subscription',sub_id:sub,_thub_payments:nonce})});
        const j = await res.json();
        if(j?.ok){ location.reload(); } else { alert('Impossibile annullare.'); }
      }catch(err){ alert('Errore di rete.'); } finally{ btn.disabled=false; }
    });
  });
})();
</script>