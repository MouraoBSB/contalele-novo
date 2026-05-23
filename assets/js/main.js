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
     2) Contador para a próxima sexta-feira às 16h00
     ───────────────────────────────────────────────────────────── */
  var contador = document.querySelector('[data-cl-proxima-sexta]');
  if (contador) {
    var elDias  = contador.querySelector('[data-cl-dias]');
    var elHoras = contador.querySelector('[data-cl-horas]');
    var elMin   = contador.querySelector('[data-cl-min]');
    var elSeg   = contador.querySelector('[data-cl-seg]');

    function proximaSexta() {
      var agora = new Date();
      var alvo = new Date(agora);
      // 5 = sexta-feira em getDay()
      var diasAteSexta = (5 - agora.getDay() + 7) % 7;
      alvo.setDate(agora.getDate() + diasAteSexta);
      alvo.setHours(16, 0, 0, 0);
      // Se já passou das 16h da sexta, vai para a próxima.
      if (alvo <= agora) {
        alvo.setDate(alvo.getDate() + 7);
      }
      return alvo;
    }

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function atualizar() {
      var diff = proximaSexta() - new Date();
      if (diff < 0) diff = 0;
      var dias  = Math.floor(diff / 86400000);
      var horas = Math.floor((diff % 86400000) / 3600000);
      var min   = Math.floor((diff % 3600000) / 60000);
      var seg   = Math.floor((diff % 60000) / 1000);
      if (elDias)  elDias.textContent  = pad(dias);
      if (elHoras) elHoras.textContent = pad(horas);
      if (elMin)   elMin.textContent   = pad(min);
      if (elSeg)   elSeg.textContent   = pad(seg);
    }

    atualizar();
    setInterval(atualizar, 1000);
  }

  /* ─────────────────────────────────────────────────────────────
     3) Reveal on scroll (elementos com classe .cl-reveal)
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
