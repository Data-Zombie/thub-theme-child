<?php
/* ============================================================
 * [THUB_ATTREZZATURE] Icone + testo (ACF Repeater)
 * - Usa SVG inline se caricato (custom_svg) con fallback <img>
 * - Per icone predefinite usa (se esiste) thub_icon_svg($key)
 * - Niente uso di wp_get_attachment_content() (NON esiste in WP)
 * ============================================================ */

// 1) Leggi il repeater
$atts = function_exists('get_field') ? get_field('attrezzature') : [];

// 2) Helper: renderizza un SVG caricato su Media inline (ONLY admin upload)
if (!function_exists('thub_render_svg_inline')) {
  function thub_render_svg_inline($attachment_id){
    $path = get_attached_file($attachment_id); // percorso su disco
    if ($path && file_exists($path)) {
      $ft = wp_check_filetype($path);
      if (!empty($ft['type']) && $ft['type'] === 'image/svg+xml') {
        // ATTENZIONE: inline SVG => fidati solo di upload da utenti di fiducia (admin/editor)
        return file_get_contents($path);
      }
    }
    return '';
  }
}
?>

<section class="thub-box thub-tools">
  <h2>Attrezzature</h2>
  <div class="thub-tools__grid">
    <?php if ($atts): foreach ($atts as $a):
      // 3) Estrai i campi riga
      $key   = $a['att_icone_key'] ?? '';
      $label = $a['att_testo']     ?? '';
      $svg_id= isset($a['att_svg_custom']) ? (int) $a['att_svg_custom'] : 0;

      // 4) Decidi l'icona da mostrare
      $icon_html = '';
      if ($key === 'custom_svg' && $svg_id) {
        // (a) SVG personalizzato inline, con fallback <img> se il file non è SVG valido
        $icon_html = thub_render_svg_inline($svg_id);
        if (!$icon_html) {
          $icon_html = wp_get_attachment_image($svg_id, 'thumbnail', false, ['alt'=>esc_attr($label)]);
        }
      } else {
        // (b) Icone predefinite: se esiste una funzione che restituisce l’SVG, usala
        if (function_exists('thub_icon_svg')) {
          $icon_html = thub_icon_svg($key); // es. 'forno','pentola','padella', ecc.
        }
      }
      ?>
      <div class="thub-tool">
        <div class="thub-tool__icon" aria-hidden="true">
          <?php echo $icon_html; // può essere stringa SVG inline o <img> ?>
        </div>
        <div class="thub-tool__text"><?php echo esc_html($label); ?></div>
      </div>
    <?php endforeach; else: ?>
      <p>Nessuna attrezzatura specificata.</p>
    <?php endif; ?>
  </div>
</section>