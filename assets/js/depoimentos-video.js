/* Depoimentos em vídeo: botão grande de play sobre a capa e um vídeo tocando por vez.
   Sem este script, os controles nativos do vídeo continuam funcionando.
   Thiago Mourão — https://github.com/MouraoBSB — 10/11/2026 */

(function () {
  'use strict';

  var botoes = document.querySelectorAll('[data-dv-play]');

  Array.prototype.forEach.call(botoes, function (botao) {
    var video = document.getElementById(botao.getAttribute('data-dv-play'));
    if (!video) return;

    botao.hidden = false;
    botao.addEventListener('click', function () {
      var tocando = video.play();
      // Navegador que recusa o play (economia de dados, por exemplo): o controle nativo assume.
      if (tocando && typeof tocando.catch === 'function') {
        tocando.catch(function () { botao.hidden = true; });
      }
      video.focus();
    });
    video.addEventListener('play', function () { botao.hidden = true; });
    video.addEventListener('ended', function () { botao.hidden = false; });
  });

  // Ao começar um depoimento, pausa os outros.
  document.addEventListener('play', function (ev) {
    var atual = ev.target;
    if (!atual.closest || !atual.closest('.dv')) return;
    Array.prototype.forEach.call(document.querySelectorAll('.dv video'), function (outro) {
      if (outro !== atual && !outro.paused) outro.pause();
    });
  }, true);
})();
