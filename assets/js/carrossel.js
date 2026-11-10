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
    prepararTextosLongos(raiz);
    atualizar();
  }

  /* Textos longos começam recolhidos, com botão para ler o depoimento inteiro.
     Leitores de tela continuam lendo o texto todo (só o visual é cortado). */
  function prepararTextosLongos(raiz) {
    var textos = raiz.querySelectorAll('.dp-texto');

    function medir() {
      Array.prototype.forEach.call(textos, function (texto) {
        var botao = texto.nextElementSibling;
        var temBotao = botao && botao.classList.contains('dp-ler');
        if (temBotao && botao.getAttribute('aria-expanded') === 'true') return;

        texto.classList.add('dp-texto--recolhido');
        // Só recolhe se sobrar texto de verdade (mais de ~2 linhas escondidas).
        var longo = texto.scrollHeight > texto.clientHeight * 1.2;
        texto.classList.toggle('dp-texto--recolhido', longo);

        if (longo && !temBotao) {
          botao = document.createElement('button');
          botao.type = 'button';
          botao.className = 'dp-ler';
          botao.setAttribute('aria-controls', texto.id);
          botao.setAttribute('aria-expanded', 'false');
          botao.textContent = 'Ler o depoimento inteiro';
          texto.insertAdjacentElement('afterend', botao);
        } else if (!longo && temBotao) {
          botao.remove();
        }
      });
    }

    raiz.addEventListener('click', function (ev) {
      var botao = ev.target.closest('.dp-ler');
      if (!botao) return;
      var texto = document.getElementById(botao.getAttribute('aria-controls'));
      var abrir = botao.getAttribute('aria-expanded') !== 'true';
      texto.classList.toggle('dp-texto--recolhido', !abrir);
      botao.setAttribute('aria-expanded', abrir ? 'true' : 'false');
      botao.textContent = abrir ? 'Mostrar menos' : 'Ler o depoimento inteiro';
    });

    var espera = 0;
    window.addEventListener('resize', function () {
      window.clearTimeout(espera);
      espera = window.setTimeout(medir, 150);
    });
    medir();
    // A fonte do site chega depois e muda a altura das linhas: mede de novo.
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(medir);
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-carrossel]'), iniciar);
})();
