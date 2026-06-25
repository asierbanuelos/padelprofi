/**
 * Padel Berater Pro — Frontend JS
 * Quiz → AJAX → Resultados por Marca
 */
(function () {
  'use strict';

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  var BRANDS = (window.PRFront || {}).brands || [];
  var AJAX   = (window.PRFront || {}).ajax   || '';
  var NONCE  = (window.PRFront || {}).nonce  || '';

  /* ── Preguntas ── */
  var QUESTIONS = [
    {
      id: 'gender', label: 'Schritt 1 von 3', question: 'Wer bist du?',
      opts: [
        { v:'hombre', icon:'👨', label:'Herr',    desc:'Männlicher Paddelspieler' },
        { v:'mujer',  icon:'👩', label:'Dame',     desc:'Weibliche Paddelspielerin' },
        { v:'junior', icon:'🧒', label:'Junior',   desc:'Unter 18 Jahre' },
      ],
    },
    {
      id: 'nivel', label: 'Schritt 2 von 3', question: 'Wähle dein Spielniveau',
      opts: [
        { v:'competicion',  icon:'🏆', label:'Wettkampf',        desc:'Lizenzierter Spieler, regelmäßige Turniere' },
        { v:'avanzado',     icon:'⚡', label:'Fortgeschritten',   desc:'Gutes Niveau und Erfahrung' },
        { v:'principiante', icon:'🌱', label:'Anfänger',          desc:'Weniger als 2 Jahre Erfahrung' },
      ],
    },
    {
      id: 'estilo', label: 'Schritt 3 von 3', question: 'Wähle deinen Spielstil',
      opts: [
        { v:'atacante', icon:'🔥', label:'Angreifer',         desc:'Kraftvoller Smash, Netzspiel' },
        { v:'control',  icon:'🎯', label:'Kontrollspieler',   desc:'Präzision, Technik, Platzierung' },
        { v:'defensor', icon:'🛡️', label:'Defensivspieler',  desc:'Konstanz und Stabilität' },
      ],
    },
  ];

  var LABELS = {
    hombre:'Herr', mujer:'Dame', junior:'Junior',
    competicion:'Wettkampf', avanzado:'Fortgeschritten', principiante:'Anfänger',
    atacante:'Angreifer', control:'Kontrolle', defensor:'Defensiv',
  };

  /* ── State ── */
  var S = { step:0, answers:{}, done:false, loading:false, results:[] };

  /* ── DOM helpers ── */
  function el(tag, cls, attrs) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (attrs) Object.keys(attrs).forEach(function(k) {
      if (k==='html')      n.innerHTML   = attrs[k];
      else if (k==='text') n.textContent = attrs[k];
      else if (k.slice(0,2)==='on') n.addEventListener(k.slice(2), attrs[k]);
      else n.setAttribute(k, attrs[k]);
    });
    return n;
  }
  function ap(p) {
    Array.prototype.slice.call(arguments,1).forEach(function(c){ if(c) p.appendChild(c); });
    return p;
  }

  /* ── Render ── */
  function render() {
    var root = document.getElementById('padel-recommender');
    if (!root) return;
    root.innerHTML = '';

    var widget = el('div','pr-widget');
    var inner  = el('div','pr-inner');
    ap(widget, inner);
    ap(inner, renderHeader(), renderProgress());

    if (!S.done)        ap(inner, renderQuiz());
    else if (S.loading) ap(inner, renderLoading());
    else                ap(inner, renderResults());

    ap(root, widget);

    // Animate match bars
    if (S.done && !S.loading) {
      requestAnimationFrame(function(){
        requestAnimationFrame(function(){
          root.querySelectorAll('.pr-match-fill[data-w]').forEach(function(b){
            b.style.width = b.getAttribute('data-w');
          });
        });
      });
    }
  }

  /* Header */
  function renderHeader() {
    var logo = el('span','pr-logo');
    logo.innerHTML = 'PADEL<em>PRO</em>';
    var badge = el('span','pr-badge'+(S.done?' done':''),{
      text: S.done ? '✓ Empfehlungen gefunden' : 'Schritt '+(S.step+1)+' von '+QUESTIONS.length,
    });
    return ap(el('div','pr-hdr'), logo, badge);
  }

  /* Progress */
  function renderProgress() {
    var w = el('div','pr-prog');
    QUESTIONS.forEach(function(_,i){
      var f = el('div','pr-prog-f');
      f.style.width = (S.done?100:i<S.step?100:i===S.step?50:0)+'%';
      ap(w, ap(el('div','pr-prog-s'), f));
    });
    return w;
  }

  /* Quiz */
  function renderQuiz() {
    var q     = QUESTIONS[S.step];
    var panel = el('div','pr-panel');
    ap(panel, el('span','pr-qlabel',{text:q.label}), el('span','pr-question',{text:q.question}));
    var opts = el('div','pr-opts');
    q.opts.forEach(function(opt){
      var btn = el('button','pr-opt',{type:'button', onclick:function(){ handleSelect(q.id,opt.v); }});
      var ico = el('span','pr-opt-icon',{text:opt.icon});
      var txt = el('span','');
      ap(txt, el('span','pr-opt-label',{text:opt.label}), el('span','pr-opt-desc',{text:opt.desc}));
      ap(btn, ico, txt, el('span','pr-opt-arrow',{text:'›'}));
      ap(opts, btn);
    });
    return ap(panel, opts);
  }

  /* Loading */
  function renderLoading() {
    return ap(el('div','pr-loading'),
      el('div','pr-spinner-el'),
      el('div','pr-loading-txt',{text:'Deine Empfehlungen werden geladen…'})
    );
  }

  /* Results */
  function renderResults() {
    var wrap = el('div','pr-results');

    // Header
    var hdr = el('div','pr-res-hdr');
    ap(hdr, el('span','pr-res-title',{text:'Deine Schläger-Empfehlungen'}));
    var tags = el('div','pr-tags');
    Object.values(S.answers).forEach(function(v){
      ap(tags, el('span','pr-tag',{text:LABELS[v]||v}));
    });
    ap(hdr, tags);
    ap(wrap, hdr);

    if (!S.results || !S.results.length) {
      var none = el('div','pr-no-results');
      none.innerHTML = '<strong>Keine Empfehlungen konfiguriert</strong>Für dieses Profil wurden noch keine Schläger im Admin-Panel zugeordnet.';
      ap(wrap, none);
    } else {
      // Intro
      var intro = el('p','pr-results-intro');
      intro.innerHTML = 'Hier sind die besten Schläger für dein Profil — <strong>eine Empfehlung pro Marke</strong>:';
      ap(wrap, intro);

      // Cards grid
      var grid = el('div','pr-cards');
      S.results.forEach(function(item, idx){
        ap(grid, renderCard(item, idx));
      });
      ap(wrap, grid);
    }

    ap(wrap, el('button','pr-restart',{
      type:'button', text:'↩  Test neu starten', onclick:handleRestart,
    }));
    return wrap;
  }

  /* Product card */
  function renderCard(item, idx) {
    var card = el('div','pr-card-item');

    // Imagen zone
    var iz = el('div','pr-card-img-zone');
    ap(iz, el('div','pr-card-brand-badge',{text:item.brand_name}));

    if (item.img) {
      var img = el('img','pr-card-img',{src:item.img, alt:item.name, loading:idx===0?'eager':'lazy'});
      img.onerror = function(){
        img.style.display='none';
      };
      ap(iz, img);
    }
    ap(card, iz);

    // Body
    var body = el('div','pr-card-body');
    ap(body, el('span','pr-card-name',{text:item.name}));

    // Precio
    var pr = el('div','pr-card-price-row');
    ap(pr, el('span','pr-card-price',{text:item.price}));
    if (item.price_reg && item.price_reg !== item.price) {
      ap(pr, el('span','pr-card-price-orig',{text:item.price_reg}));
    }
    if (item.discount) {
      ap(pr, el('span','pr-card-disc',{text:item.discount}));
    }
    ap(body, pr);

    ap(body, el('a','pr-card-btn',{
      href: item.link, target:'_blank', rel:'noopener noreferrer',
      text: 'Schläger ansehen →',
    }));

    ap(card, body);
    return card;
  }

  /* ── Handlers ── */
  function handleSelect(qid, value) {
    S.answers[qid] = value;
    if (S.step < QUESTIONS.length-1) {
      S.step++;
      render();
    } else {
      S.done    = true;
      S.loading = true;
      render();
      fetchRecommendations();
    }
  }

  function handleRestart() {
    S.step=0; S.answers={}; S.done=false; S.loading=false; S.results=[];
    render();
  }

  /* ── AJAX: Empfehlungen vom Server holen ── */
  function fetchRecommendations() {
    var data = new FormData();
    data.append('action', 'pr_recommend');
    data.append('nonce',  NONCE);
    data.append('gender', S.answers.gender);
    data.append('nivel',  S.answers.nivel);
    data.append('estilo', S.answers.estilo);

    fetch(AJAX, { method:'POST', body:data })
      .then(function(r){ return r.json(); })
      .then(function(resp){
        S.results = resp.success ? resp.data : [];
        S.loading = false;
        render();
      })
      .catch(function(){
        S.results = [];
        S.loading = false;
        render();
      });
  }

  /* ── Init ── */
  function init() {
    if (document.getElementById('padel-recommender')) render();
  }

})();
