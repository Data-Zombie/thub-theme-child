/* [THUB_ACCOUNT_JS] Evidenziazione attiva “al volo” (cosmetica) */
(function(){
  document.addEventListener('click', function(e){
    const a = e.target.closest('.thub-account__link');
    if(!a) return;
    document.querySelectorAll('.thub-account__item.is-active')
      .forEach(li => li.classList.remove('is-active'));
    a.closest('li')?.classList.add('is-active');
  }, true);
})();