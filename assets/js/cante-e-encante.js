/**
 * Comportamento da página de vendas do Cante & Encante.
 * Barra fixa de compra, acordeão do FAQ e entrada dos blocos ao rolar.
 *
 * Thiago Mourão — https://github.com/MouraoBSB — 03/11/2026
 */
(function(){
  var barra=document.getElementById('lp-barra'),tick=false;
  function onScroll(){
    if(tick)return;tick=true;
    requestAnimationFrame(function(){
      var on=window.scrollY>600;
      barra.classList.toggle('on',on);document.body.classList.toggle('lp-barra-on',on);tick=false;
    });
  }
  window.addEventListener('scroll',onScroll,{passive:true});onScroll();

  document.querySelectorAll('.lp-faq-btn').forEach(function(b){
    b.addEventListener('click',function(){
      var open=b.getAttribute('aria-expanded')==='true';
      b.setAttribute('aria-expanded',open?'false':'true');
      document.getElementById(b.getAttribute('aria-controls')).classList.toggle('on',!open);
    });
  });

  var reduz=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var itens=document.querySelectorAll('.lp-rv');
  if(reduz||!('IntersectionObserver' in window)){itens.forEach(function(e){e.classList.add('in')});}
  else{
    var io=new IntersectionObserver(function(ents){
      ents.forEach(function(en){
        if(en.isIntersecting){
          var p=en.target.parentElement,i=p?Array.prototype.indexOf.call(p.children,en.target):0;
          en.target.style.transitionDelay=Math.min(i,5)*60+'ms';
          en.target.classList.add('in');io.unobserve(en.target);
        }
      });
    },{rootMargin:'0px 0px -8% 0px',threshold:.05});
    itens.forEach(function(e){io.observe(e)});
  }
})();
