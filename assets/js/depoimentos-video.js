/* Depoimentos em vídeo: botão grande de play sobre a capa e um vídeo tocando por vez.
   Sem este script, os controles nativos do vídeo continuam funcionando.
   Thiago Mourão — https://github.com/MouraoBSB — 10/11/2026 */

(function () {
  'use strict';

  var botoes = document.querySelectorAll('[data-dv-play]');

  Array.prototype.forEach.call(botoes, function (botao) {
    var video = document.getElementById(botao.getAttribute('data-dv-play'));
    if (!video) return;

    // Até a primeira reprodução, só o botão amarelo: o celular desenha o próprio play
    // no centro do vídeo quando há controles nativos, e os dois ficavam sobrepostos.
    function prepararCapa() {
      video.controls = false;
      botao.hidden = false;
    }

    function tocar() {
      video.controls = true;
      var tocando = video.play();
      // Navegador que recusa o play (economia de dados, por exemplo): o controle nativo assume.
      if (tocando && typeof tocando.catch === 'function') {
        tocando.catch(function () { botao.hidden = true; });
      }
      video.focus();
    }

    prepararCapa();
    botao.addEventListener('click', tocar);
    video.addEventListener('click', function () {
      if (!video.controls) tocar();
    });
    video.addEventListener('play', function () {
      video.controls = true;
      botao.hidden = true;
    });
    video.addEventListener('ended', prepararCapa);
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
