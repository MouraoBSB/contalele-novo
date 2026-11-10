/* Carrossel de depoimentos: setas, indicadores de posição e aviso para leitores de tela.
   Sem este script, o trilho continua rolando com o dedo, o trackpad ou o teclado.
   Thiago Mourão — https://github.com/MouraoBSB — 10/11/2026 */

(function () {
  'use strict';

  var semMovimento = window.matchMedia('(prefers-reduced-motion: reduce)');

  function iniciar(raiz) {
    var trilho    = raiz.querySelector('.dp-trilho');
    var controles = raiz.querySelector('.dp-controles');
    var aviso     = raiz.querySelector('[data-dp-aviso]');
    var itens     = trilho ? trilho.querySelectorAll('.dp-item') : [];
    if (!trilho || !controles || itens.length < 2) return;

    var anterior = controles.querySelector('[data-dp-ir="-1"]');
    var proximo  = controles.querySelector('[data-dp-ir="1"]');
    var pontos   = controles.querySelector('.dp-pontos');
    var quadro   = 0;

    // Distância entre o início de dois cartões vizinhos (largura + espaço).
    function passo() { return itens[1].offsetLeft - itens[0].offsetLeft; }
    function maximo() { return trilho.scrollWidth - trilho.clientWidth; }
    function posicoes() { return Math.round(maximo() / passo()) + 1; }
    function atual() { return Math.min(Math.round(trilho.scrollLeft / passo()), posicoes() - 1); }

    function desenharPontos(total) {
      if (pontos.children.length === total) return;
      pontos.textContent = '';
      for (var i = 0; i < total; i++) {
        var ponto = document.createElement('span');
        ponto.className = 'dp-ponto';
        pontos.appendChild(ponto);
      }
    }

    function atualizar() {
      quadro = 0;
      var rola = maximo() > 2;
      controles.hidden = !rola;
      if (!rola) return;

      var total = posicoes();
      var i = atual();
      desenharPontos(total);
      for (var n = 0; n < total; n++) {
        pontos.children[n].classList.toggle('dp-ponto--ativo', n === i);
      }
      anterior.disabled = trilho.scrollLeft <= 2;
      proximo.disabled  = trilho.scrollLeft >= maximo() - 2;
    }

    function agendar() {
      if (!quadro) quadro = window.requestAnimationFrame(atualizar);
    }

    controles.addEventListener('click', function (ev) {
      var botao = ev.target.closest('[data-dp-ir]');
      if (!botao || botao.disabled) return;
      var direcao = Number(botao.getAttribute('data-dp-ir'));
      var destino = Math.max(0, Math.min(atual() + direcao, posicoes() - 1));
      if (aviso) aviso.textContent = 'Depoimento ' + (destino + 1) + ' de ' + itens.length;
      trilho.scrollBy({
        left: passo() * direcao,
        behavior: semMovimento.matches ? 'auto' : 'smooth'
      });
    });
    trilho.addEventListener('scroll', agendar, { passive: true });
    window.addEventListener('resize', agendar);

    raiz.classList.add('dp--js');
    atualizar();
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-carrossel]'), iniciar);
})();
