<?php 
/**
 * THUB – Front Page (Home) full-width
 * - Mostra il logo
 * - Nessun titolo "Cerca una ricetta"
 * - Usa il componente riutilizzabile della barra di ricerca (variant: "stacked")
 * - La dettatura (microfono) è gestita dal binder unico in footer.php
 */
get_header(); 
?>

<main class="home-hero"><!-- // sezione full-height: vedi CSS .home-hero -->
  <div class="home-box"><!-- // contenitore centrale -->

    <!-- // Logo del sito (se impostato in Aspetto > Personalizza) -->
    <div class="home-logo">
      <?php if ( function_exists('the_custom_logo') ) the_custom_logo(); ?>
    </div>

    <!-- // Barra di ricerca (componente riutilizzabile), variante "stacked"
         // Include: input ricerca (senza placeholder), mic, e pulsante "Cerca con T-Hub" sotto -->
    <?php 
    // // Limita alle ricette: il componente imposta già <input type="hidden" name="post_type" value="ricetta">
    get_template_part('parts/thub-search', null, ['variant' => 'stacked']); 
    ?>

  </div>
</main>

<?php 
// // Footer del tema. Il binder unico per il microfono dovrebbe essere in footer.php
// // (così evita duplicazioni di script tra home e header delle pagine interne).
get_footer(); 