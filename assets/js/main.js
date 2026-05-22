/* Site Conta Lelê — JavaScript base
   Thiago Mourão — https://github.com/MouraoBSB */

(function () {
  'use strict';

  // Alterna o menu de navegação no mobile.
  var botao = document.querySelector('.cl-menu-btn');
  var nav = document.querySelector('.cl-nav');

  if (botao && nav) {
    botao.addEventListener('click', function () {
      var aberto = nav.classList.toggle('cl-nav--aberto');
      botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });
  }
})();
