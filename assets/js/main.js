/* Site Conta Lelê — JavaScript base
   Thiago Mourão — https://github.com/MouraoBSB */

(function () {
  'use strict';

  /* ─────────────────────────────────────────────────────────────
     1) Menu mobile
     ───────────────────────────────────────────────────────────── */
  var botao = document.querySelector('.cl-menu-btn');
  var nav   = document.querySelector('.cl-nav');

  if (botao && nav) {
    botao.addEventListener('click', function () {
      var aberto = nav.classList.toggle('cl-nav--aberto');
      botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });

    // Fecha o menu ao clicar num link (mobile).
    nav.addEventListener('click', function (ev) {
      var alvo = ev.target.closest('a');
      if (alvo && nav.classList.contains('cl-nav--aberto')) {
        nav.classList.remove('cl-nav--aberto');
        botao.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ─────────────────────────────────────────────────────────────
     2) Reveal on scroll (elementos com classe .cl-reveal)
     ───────────────────────────────────────────────────────────── */
  var alvos = document.querySelectorAll('.cl-reveal');
  if (alvos.length && 'IntersectionObserver' in window) {
    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add('cl-reveal--on');
          obs.unobserve(en.target);
        }
      });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.1 });
    alvos.forEach(function (el) { obs.observe(el); });
  }

})();
