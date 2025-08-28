<?php
/* =====================================================================
   [THUB_CRONOLOGIA] — Sezione "Cronologia delle ricerche"
   ===================================================================== */
if ( ! is_user_logged_in() ) {
  wp_safe_redirect( wp_login_url( home_url('/login/') ) );
  exit;
}
$current_user_id = get_current_user_id();
$nonce = wp_create_nonce('thub_search_history_nonce');
?>

<?php
/* [THUB_CRONOLOGIA_VIEW_GUARD] — Non mostrare se cronologia disattivata */
if ( get_user_meta( $current_user_id, 'thub_priv_history', true ) === '1' ) : ?>
  <div class="thub-box" style="border:1px solid #ededf3;border-radius:.8rem;padding:12px;background:#fff;">
    <p style="margin:0;color:#666;">Cronologia disattivata. Vai in <em>Altro</em> per riattivare la registrazione delle ricerche.</p>
  </div>
  <?php return; /* esci dall’intero partial */ ?>
<?php endif; ?>

<div class="thub-canvas-section">
  <header class="thub-section-header">
    <!-- Titolo centrale -->
    <h1 style="text-align:center;margin:0;">Cronologia delle ricerche</h1>
  </header>

  <!-- BOX1 -->
  <section class="thub-box thub-box--cronologia">
    <div>
      <h2>Salviamo le tue ricerche degli ultimi 30 giorni</h2>
      <div style="height:.75rem"></div>
      <a href="#" id="thub-clear-history" class="thub-link">Cancella cronologia</a>
    </div>
    <div style="display:flex;align-items:center;justify-content:center;min-height:160px;">
      <svg viewBox="0 0 64 64" width="120" height="120" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="28" cy="28" r="18" stroke="#7249a4"/>
        <path d="M43 43l12 12" stroke="#7249a4" stroke-linecap="round"/>
        <path d="M28 16v12l8 4" stroke="#467FF7" stroke-linecap="round"/>
      </svg>
    </div>

    <!-- Riga separazione + titolo tendina -->
    <div style="grid-column:1/-1;margin-top:20px;">
      <hr style="border:0;border-top:1px solid #eee;margin:0 0 12px;">
      <details id="thub-history-details">
        <summary style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;">
          <span style="font-weight:600;">Visualizza cronologia</span>
          <span aria-hidden="true">▾</span>
        </summary>
        <!-- ▼ Tabella cronologia allineata a destra -->
        <div class="thub-accordion__content thub-accordion__content--right" style="margin-top:12px;">
          <div id="thub-history-wrapper" style="background:#fff;border-radius:.6rem;padding:12px;">
            <div id="thub-history-empty" style="display:none;padding:12px 6px;">Nessuna ricerca negli ultimi 30 giorni.</div>
            <div id="thub-history-groups" aria-live="polite"></div>
          </div>
        </div>
      </details>
    </div>
  </section>

  <!-- BOX2 -->
  <section class="thub-box thub-box--privacy" style="margin-top:22px;display:flex;align-items:center;gap:12px;">
    <svg viewBox="0 0 64 64" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M32 6l18 6v12c0 14-8 24-18 28C22 48 14 38 14 24V12l18-6z" stroke="#467FF7"/>
      <path d="M24 30l6 6 10-12" stroke="#7249a4" stroke-linecap="round"/>
    </svg>
    <div><strong>T-Hub</strong> protegge la tua privacy e la tua sicurezza.</div>
  </section>
</div>

<style>
/* CSS inline dedicato a section-cronologia */
.thub-box--cronologia{display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:center}
@media(max-width:820px){.thub-box--cronologia{grid-template-columns:1fr}}

/* Tendina: 60% desktop, allineata a destra; fullwidth su mobile */
.thub-accordion__content--right{width:60%;margin-left:auto}
@media(max-width:980px){.thub-accordion__content--right{width:100%;margin-left:0}}

.thub-table.thub-table--borderless .thub-row--history+.thub-row--history{border-top:1px solid #f2f2f5}
.thub-row--history{display:grid;grid-template-columns:1fr auto;align-items:center;padding:.5rem .25rem;gap:10px}
.thub-row--history .thub-history-q{font-weight:600}
.thub-row--history .thub-history-t{font-size:.9rem;color:#666}
.thub-btn--icon{border:0;background:transparent;cursor:pointer;padding:4px;line-height:0}
.thub-btn--icon:hover svg path{stroke:#900}
</style>

<script>
(function(){
  const ajaxurl = window.ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>';
  const nonce   = '<?php echo esc_js($nonce); ?>';
  const groups = document.getElementById('thub-history-groups');
  const empty  = document.getElementById('thub-history-empty');

  async function loadHistory(){
    const fd=new FormData();fd.append('action','thub_get_search_history');fd.append('_wpnonce',nonce);
    const res=await fetch(ajaxurl,{method:'POST',body:fd,credentials:'same-origin'});
    if(!res.ok){renderEmpty();return;}
    const data=await res.json();
    if(!data.items||!data.items.length){renderEmpty();return;}
    renderGroups(data.items);
  }
  function renderEmpty(){groups.innerHTML='';empty.style.display='block';}
  function renderGroups(items){
    empty.style.display='none';groups.innerHTML='';
    const map=new Map();
    for(const r of items){const d=r.day_label||new Date(r.ts_iso).toLocaleDateString('it-IT');if(!map.has(d))map.set(d,[]);map.get(d).push(r);}
    map.forEach((arr,day)=>{
      const box=document.createElement('div');box.className='thub-history-day';
      const h3=document.createElement('h3');h3.textContent=day;box.appendChild(h3);
      const tbl=document.createElement('div');tbl.className='thub-table thub-table--borderless';
      arr.forEach(it=>{
        const row=document.createElement('div');row.className='thub-row thub-row--history';
        const col=document.createElement('div');
        const q=document.createElement('div');q.className='thub-history-q';q.textContent=it.query;col.appendChild(q);
        const t=document.createElement('div');t.className='thub-history-t';t.textContent=new Date(it.ts_iso).toLocaleTimeString('it-IT',{hour:'2-digit',minute:'2-digit'});col.appendChild(t);
        const act=document.createElement('div');
        const btn=document.createElement('button');btn.className='thub-btn thub-btn--icon';btn.innerHTML='<svg viewBox="0 0 24 24" width="20" height="20"><path d="M3 6h18" stroke="#b00" stroke-width="2"/><path d="M8 6V4h8v2m-9 0l1 14h8l1-14" stroke="#b00" stroke-width="2"/></svg>';
        btn.onclick=()=>deleteRow(it.id,row);act.appendChild(btn);
        row.appendChild(col);row.appendChild(act);tbl.appendChild(row);
      });
      box.appendChild(tbl);groups.appendChild(box);
    });
  }
  async function deleteRow(id,row){
    const fd=new FormData();fd.append('action','thub_delete_search_item');fd.append('_wpnonce',nonce);fd.append('id',id);
    const res=await fetch(ajaxurl,{method:'POST',body:fd,credentials:'same-origin'});const d=await res.json();
    if(d.success)row.remove();
  }
  document.getElementById('thub-clear-history').onclick=async e=>{
    e.preventDefault();if(!confirm('Vuoi cancellare tutta la cronologia?'))return;
    const fd=new FormData();fd.append('action','thub_clear_search_history');fd.append('_wpnonce',nonce);
    const res=await fetch(ajaxurl,{method:'POST',body:fd,credentials:'same-origin'});const d=await res.json();
    if(d.success)renderEmpty();
  };
  document.getElementById('thub-history-details').addEventListener('toggle',e=>{if(e.target.open)loadHistory();});
})();
</script>