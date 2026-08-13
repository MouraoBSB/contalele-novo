/**
 * Comportamento da página de vendas do Conte&Encante.
 * Barra fixa de compra, acordeão do FAQ e entrada dos blocos ao rolar.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 */
(function () {
    'use strict';

    // ── Barra fixa de compra: aparece depois de 600px de rolagem ──────────
    var barra = document.getElementById('lp-barra');
    if (barra) {
        barra.hidden = false;
        var aguardando = false;
        var aoRolar = function () {
            if (aguardando) { return; }
            aguardando = true;
            requestAnimationFrame(function () {
                barra.classList.toggle('lp-barra--on', window.scrollY > 600);
                aguardando = false;
            });
        };
        window.addEventListener('scroll', aoRolar, { passive: true });
        aoRolar();
    }

    // ── FAQ: acordeão com uma resposta aberta por vez ─────────────────────
    var botoes = document.querySelectorAll('.lp-faq__btn');
    botoes.forEach(function (botao) {
        botao.addEventListener('click', function () {
            var jaAberto = botao.getAttribute('aria-expanded') === 'true';
            botoes.forEach(function (outro) {
                outro.setAttribute('aria-expanded', 'false');
                var resp = document.getElementById(outro.getAttribute('aria-controls'));
                if (resp) { resp.classList.remove('lp-faq__resp--on'); }
            });
            if (!jaAberto) {
                botao.setAttribute('aria-expanded', 'true');
                var alvo = document.getElementById(botao.getAttribute('aria-controls'));
                if (alvo) { alvo.classList.add('lp-faq__resp--on'); }
            }
        });
    });

    // ── Entrada dos blocos, com atraso escalonado entre irmãos ────────────
    var itens = document.querySelectorAll('.lp-rv');
    var semMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (semMovimento || !('IntersectionObserver' in window)) {
        itens.forEach(function (item) { item.classList.add('lp-rv--on'); });
        return;
    }

    var observador = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (entrada) {
            if (!entrada.isIntersecting) { return; }
            var pai = entrada.target.parentElement;
            var indice = pai ? Array.prototype.indexOf.call(pai.children, entrada.target) : 0;
            entrada.target.style.transitionDelay = Math.min(indice, 5) * 70 + 'ms';
            entrada.target.classList.add('lp-rv--on');
            observador.unobserve(entrada.target);
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });

    itens.forEach(function (item) { observador.observe(item); });
})();
