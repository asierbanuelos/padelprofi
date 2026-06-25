/**
 * Babolat Schläger Empfehlung v4
 * - Bilder direkt hardcodiert (kein API-Fehler mehr)
 * - Vollständig auf Deutsch
 * - Links via WordPress Post-ID: /?p=ID
 */
(function () {
  'use strict';

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  var SHOP = ((window.BabolatRecConfig || {}).shopUrl || '').replace(/\/$/, '')
             || window.location.origin;

  function productUrl(id) { return SHOP + '/?p=' + id; }

  /* ════════════════════════════════════
     PRODUKTKATALOG — alle Daten statisch
  ════════════════════════════════════ */
  var PRODUCTS = [
    {
      id: 33562,
      name: 'Babolat Air Viper 2.6 2026',
      short: 'Air Viper 2.6',
      serie: 'AIR',
      color: '#4ade80',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2025/11/121763-pala-babolat-air-viper-150176-100-1500x1500-1.jpg.webp',
      precio: '169,95 €',
      precioOrig: '190,00 €',
      disc: '-11%',
      nivel: 'principiante',
      estilo: 'atacante',
      gender: 'all',
      forma: 'Diamant',
      desc: 'Leicht und explosiv für den angreifenden Einsteiger. Hoher Balance-Punkt für kraftvolle Smashes ab dem ersten Spiel.',
    },
    {
      id: 33563,
      name: 'Babolat Air Veron 2.6 2026',
      short: 'Air Veron 2.6',
      serie: 'AIR',
      color: '#4ade80',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2025/11/babolat-air-veron-2026-padelprofi-1.webp',
      precio: '159,95 €',
      precioOrig: '180,00 €',
      disc: '-11%',
      nivel: 'principiante',
      estilo: 'control',
      gender: 'all',
      forma: 'Tropfen',
      desc: 'Ausgewogene Tropfenform für den technischen Einsteiger. Komfortabel am Netz und an der Grundlinie.',
    },
    {
      id: 33564,
      name: 'Babolat Air Origin 2026',
      short: 'Air Origin 2026',
      serie: 'AIR',
      color: '#4ade80',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2025/11/Green_Modern_and_Elegant_Christmas_Greeting_Instagram_Post_10.png.webp',
      precio: '139,95 €',
      precioOrig: '160,00 €',
      disc: '-13%',
      nivel: 'principiante',
      estilo: 'allround',
      gender: 'junior',
      forma: 'Rund',
      desc: 'Der perfekte Einstieg in die Babolat-Welt. Rund, komfortabel und sehr verzeihin für die Jüngsten.',
    },
    {
      id: 33565,
      name: 'Babolat Air Vertuo 2.6 2026',
      short: 'Air Vertuo 2.6',
      serie: 'AIR',
      color: '#4ade80',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2025/11/121762-pala-babolat-air-vertuo-150184-100-1500x1500-1.jpg.webp',
      precio: '159,95 €',
      precioOrig: '180,00 €',
      disc: '-11%',
      nivel: 'principiante',
      estilo: 'defensor',
      gender: 'all',
      forma: 'Rund',
      desc: 'Großer Sweetspot und niedriger Balance-Punkt. Maximale Kontrolle von der Grundlinie für Einsteiger.',
    },
    {
      id: 33566,
      name: 'Babolat Counter Viper 2.6 2026',
      short: 'Counter Viper 2.6',
      serie: 'COUNTER',
      color: '#60a5fa',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2025/11/0090760319500000_9615da6a-6702-4154-a3d2-21b4164e7447.jpg.webp',
      precio: '219,95 €',
      precioOrig: '250,00 €',
      disc: '-12%',
      nivel: 'avanzado',
      estilo: 'atacante',
      gender: 'all',
      forma: 'Diamant',
      desc: 'Carbon-Rahmen und EVA-Kern für maximale Schlagkraft. Jeder Smash wird zum entscheidenden Punkt.',
    },
    {
      id: 33567,
      name: 'Babolat Counter Veron 2.6 2026',
      short: 'Counter Veron 2.6',
      serie: 'COUNTER',
      color: '#60a5fa',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2025/11/df654g654fd.webp',
      precio: '209,95 €',
      precioOrig: '240,00 €',
      disc: '-13%',
      nivel: 'avanzado',
      estilo: 'control',
      gender: 'all',
      forma: 'Tropfen',
      desc: 'Ausgewogener Balance-Punkt für präzises Spiel. Perfekt für Volleys und Druckbälle auf fortgeschrittenem Niveau.',
    },
    {
      id: 33568,
      name: 'Babolat Counter Vertuo 2.6 2026',
      short: 'Counter Vertuo 2.6',
      serie: 'COUNTER',
      color: '#60a5fa',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2025/11/BabolatCounterVertuo2026_1.webp',
      precio: '209,95 €',
      precioOrig: '240,00 €',
      disc: '-13%',
      nivel: 'avanzado',
      estilo: 'defensor',
      gender: 'all',
      forma: 'Rund',
      desc: 'Große Schlagfläche für geduldiges Spiel von der Grundlinie. Warte auf den Fehler des Gegners.',
    },
    {
      id: 33569,
      name: 'Babolat Counter Origin 2.6 2026',
      short: 'Counter Origin 2.6',
      serie: 'COUNTER',
      color: '#60a5fa',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2025/11/Green_Modern_and_Elegant_Christmas_Greeting_Instagram_Post_13.png.webp',
      precio: '189,95 €',
      precioOrig: '220,00 €',
      disc: '-14%',
      nivel: 'avanzado',
      estilo: 'allround',
      gender: 'junior',
      forma: 'Tropfen',
      desc: 'Fortgeschrittene Leistung im agilen Format. Ideal für Junioren, die anfangen zu spielen.',
    },
    {
      id: 35445,
      name: 'Babolat Technical Viper Juan Lebrón 3.0 2026',
      short: 'Technical Viper 3.0',
      serie: 'TECHNICAL',
      color: '#fe6100',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2026/01/babolat-viper-juan-lebron-2026-padelschlaeger-5.webp',
      precio: '379,95 €',
      precioOrig: '450,00 €',
      disc: '-16%',
      nivel: 'competicion',
      estilo: 'atacante',
      gender: 'all',
      forma: 'Diamant',
      desc: 'Der Schläger von Juan Lebrón. 3K-Carbon-Multilayer, extreme Diamantform. Unschlagbare Power am Netz.',
    },
    {
      id: 35459,
      name: 'Babolat Technical Veron Juan Lebrón 3.0 2026',
      short: 'Technical Veron 3.0',
      serie: 'TECHNICAL',
      color: '#fe6100',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2026/01/babolat-viper-veron-juan-lebron-2026-padelschlaeger-1.webp',
      precio: '239,95 €',
      precioOrig: '300,00 €',
      disc: '-20%',
      nivel: 'competicion',
      estilo: 'control',
      gender: 'all',
      forma: 'Tropfen',
      desc: 'Spin-Blade-Oberfläche für verheerende Effekte. Die Wahl des technischen Wettkampfspielers der Elite.',
    },
    {
      id: 35453,
      name: 'Babolat Technical Viper SOFT Juan Lebrón 3.0 2026',
      short: 'Technical Viper SOFT',
      serie: 'TECHNICAL',
      color: '#fe6100',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2026/01/babolat-viper-soft-juan-lebron-2026-padelschlaeger-1.webp',
      precio: '299,95 €',
      precioOrig: '340,00 €',
      disc: '-12%',
      nivel: 'competicion',
      estilo: 'defensor',
      gender: 'all',
      forma: 'Tropfen',
      desc: 'SOFT-Kern für maximalen Komfort im Wettkampf. Null Fehler und höchste Konstanz auf höchstem Niveau.',
    },
    {
      id: 33570,
      name: 'Babolat Dyna Energy 2026',
      short: 'Dyna Energy 2026',
      serie: 'DYNA ENERGY',
      color: '#f472b6',
      img: 'https://padelprofideutschland.de/wp-content/uploads/2024/09/image-17.png',
      precio: '149,95 €',
      precioOrig: '170,00 €',
      disc: '-12%',
      nivel: 'principiante',
      estilo: 'allround',
      gender: 'mujer',
      forma: 'Rund',
      desc: 'Leicht und vielseitig, speziell für den weiblichen Einstieg entwickelt. Komfort und Kontrolle vom ersten Schlag an.',
    },
  ];

  /* ════════════════════════════════════
     SCORING
  ════════════════════════════════════ */
  var NS = {
    principiante: { principiante:5, avanzado:2, competicion:0 },
    avanzado:     { principiante:1, avanzado:5, competicion:2 },
    competicion:  { principiante:0, avanzado:2, competicion:5 },
  };
  var ES = {
    atacante: { atacante:5, control:2, defensor:0, allround:2 },
    control:  { atacante:1, control:5, defensor:2, allround:3 },
    defensor: { atacante:0, control:2, defensor:5, allround:3 },
  };

  function score(p, a) {
    var s = ((NS[a.nivel]   || {})[p.nivel]   || 0)
           +((ES[a.estilo]  || {})[p.estilo]  || 0);
    if (a.gender==='mujer'  && p.gender==='mujer')  s+=4;
    if (a.gender==='mujer'  && p.gender==='all')    s+=1;
    if (a.gender==='junior' && p.gender==='junior') s+=4;
    if (a.gender==='junior' && p.gender==='all')    s+=1;
    if (a.gender==='hombre' && p.gender==='all')    s+=2;
    if (a.gender==='hombre' && p.gender==='mujer')  s-=3;
    if (a.gender==='hombre' && p.gender==='junior') s-=2;
    return s;
  }

  function getTop3(ans) {
    return PRODUCTS
      .map(function(p) { return { p:p, s:score(p,ans) }; })
      .sort(function(a,b) { return b.s - a.s; })
      .slice(0, 3);
  }

  /* ════════════════════════════════════
     FRAGEN — Deutsch
  ════════════════════════════════════ */
  var QUESTIONS = [
    {
      id: 'gender', label: 'Schritt 1 von 3', question: 'Wer bist du?',
      opts: [
        { v:'hombre', icon:'👨', label:'Herr',    desc:'Männlicher Paddelspieler' },
        { v:'mujer',  icon:'👩', label:'Dame',    desc:'Weibliche Paddelspielerin' },
        { v:'junior', icon:'🧒', label:'Junior',  desc:'Unter 18 Jahre' },
      ],
    },
    {
      id: 'nivel', label: 'Schritt 2 von 3', question: 'Wähle dein Spielniveau',
      opts: [
        { v:'competicion',  icon:'🏆', label:'Wettkampf',    desc:'Lizenzierter Spieler, regelmäßige Turniere' },
        { v:'avanzado',     icon:'⚡', label:'Fortgeschritten', desc:'Gutes Niveau und Erfahrung' },
        { v:'principiante', icon:'🌱', label:'Anfänger',      desc:'Weniger als 2 Jahre Erfahrung' },
      ],
    },
    {
      id: 'estilo', label: 'Schritt 3 von 3', question: 'Wähle deinen Spielstil',
      opts: [
        { v:'atacante', icon:'🔥', label:'Angreifer',       desc:'Ich suche den Smash, ich schließe am Netz ab' },
        { v:'control',  icon:'🎯', label:'Kontrollspieler', desc:'Präzision, Technik und Positionierung' },
        { v:'defensor', icon:'🛡️', label:'Defensivspieler', desc:'Konstanz und Stabilität von der Grundlinie' },
      ],
    },
  ];

  var LABELS = {
    hombre:'Herr', mujer:'Dame', junior:'Junior',
    competicion:'Wettkampf', avanzado:'Fortgeschritten', principiante:'Anfänger',
    atacante:'Angreifer', control:'Kontrolle', defensor:'Defensiv',
  };

  /* ════════════════════════════════════
     SVG FALLBACK (falls Bild nicht lädt)
  ════════════════════════════════════ */
  function paddleSVG(color, forma, size) {
    var w=size||100, h=Math.round(size*1.5)||150;
    var rx,ry,cy;
    if(forma==='Diamant'){rx=w*.33;ry=h*.37;cy=h*.40;}
    else if(forma==='Rund'){rx=w*.40;ry=h*.37;cy=h*.41;}
    else{rx=w*.37;ry=h*.38;cy=h*.41;}
    var cx=w/2, ht=cy+ry-3, hh=h-ht-2, hx=cx-w*.12, hw=w*.24;
    var holes='';
    [cx-rx*.5,cx,cx+rx*.5].forEach(function(hcx){
      [cy-ry*.45,cy,cy+ry*.42].forEach(function(hcy){
        var dx=(hcx-cx)/rx,dy=(hcy-cy)/ry;
        if(dx*dx+dy*dy<0.72) holes+='<circle cx="'+hcx+'" cy="'+hcy+'" r="'+(w*.038)+'" fill="#0a0a0a" opacity="0.5"/>';
      });
    });
    return '<svg xmlns="http://www.w3.org/2000/svg" width="'+w+'" height="'+h+'" viewBox="0 0 '+w+' '+h+'">'
      +'<defs><radialGradient id="pg'+w+'" cx="50%" cy="38%" r="60%"><stop offset="0%" stop-color="'+color+'" stop-opacity="0.92"/><stop offset="100%" stop-color="'+color+'" stop-opacity="0.28"/></radialGradient>'
      +'<filter id="gf'+w+'"><feGaussianBlur stdDeviation="2.5" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>'
      +'<rect x="'+hx+'" y="'+ht+'" width="'+hw+'" height="'+hh+'" rx="'+(hw*.3)+'" fill="#1a1a1a"/>'
      +'<rect x="'+(hx+hw*.12)+'" y="'+(ht+3)+'" width="'+(hw*.76)+'" height="'+(hh-6)+'" rx="'+(hw*.22)+'" fill="#222"/>'
      +[.2,.37,.54,.71,.86].map(function(t){var y=ht+hh*t;return '<line x1="'+hx+'" y1="'+y+'" x2="'+(hx+hw)+'" y2="'+y+'" stroke="#333" stroke-width="1"/>';}).join('')
      +'<rect x="'+(hx-hw*.18)+'" y="'+(ht-4)+'" width="'+(hw*1.36)+'" height="8" rx="4" fill="#222"/>'
      +'<ellipse cx="'+cx+'" cy="'+cy+'" rx="'+rx+'" ry="'+ry+'" fill="url(#pg'+w+')" filter="url(#gf'+w+')"/>'
      +'<ellipse cx="'+cx+'" cy="'+cy+'" rx="'+rx+'" ry="'+ry+'" fill="none" stroke="'+color+'" stroke-width="1.4"/>'
      +'<line x1="'+cx+'" y1="'+(cy-ry+4)+'" x2="'+cx+'" y2="'+ht+'" stroke="'+color+'" stroke-width="0.7" opacity="0.18"/>'
      +holes+'</svg>';
  }

  /* ════════════════════════════════════
     STATE
  ════════════════════════════════════ */
  var S = { step:0, answers:{}, done:false, top3:[] };

  /* ════════════════════════════════════
     DOM HELPERS
  ════════════════════════════════════ */
  function el(tag, cls, attrs) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (attrs) Object.keys(attrs).forEach(function(k) {
      if (k==='html')       n.innerHTML   = attrs[k];
      else if (k==='text')  n.textContent = attrs[k];
      else if (k.slice(0,2)==='on') n.addEventListener(k.slice(2), attrs[k]);
      else n.setAttribute(k, attrs[k]);
    });
    return n;
  }
  function ap(p) {
    Array.prototype.slice.call(arguments,1).forEach(function(c){ if(c!=null) p.appendChild(c); });
    return p;
  }

  /* ════════════════════════════════════
     RENDER
  ════════════════════════════════════ */
  function render() {
    var root = document.getElementById('babolat-recommender-root');
    if (!root) return;
    root.innerHTML = '';

    var wrap  = el('div','br-wrap');
    var inner = el('div','br-inner');
    ap(wrap, inner);
    ap(inner, renderHeader(), renderProgress());

    if (!S.done) ap(inner, renderQuiz());
    else         ap(inner, renderResults());

    ap(root, wrap);

    if (S.done) {
      requestAnimationFrame(function(){
        requestAnimationFrame(function(){
          root.querySelectorAll('.br-match-bar[data-w]').forEach(function(b){
            b.style.width = b.getAttribute('data-w');
          });
        });
      });
    }
  }

  /* Header */
  function renderHeader() {
    var logo = el('span','br-logo');
    logo.innerHTML = 'BABO<em>LAT</em>';
    var pill = el('span','br-pill'+(S.done?' is-done':''), {
      text: S.done ? '⭐ Top 3 gefunden' : 'Schritt '+(S.step+1)+' von '+QUESTIONS.length,
    });
    return ap(el('div','br-header'), logo, pill);
  }

  /* Progress */
  function renderProgress() {
    var w = el('div','br-prog');
    QUESTIONS.forEach(function(_,i){
      var fill = el('div','br-prog-fill');
      fill.style.width = (S.done?100:i<S.step?100:i===S.step?50:0)+'%';
      ap(w, ap(el('div','br-prog-seg'), fill));
    });
    return w;
  }

  /* Quiz */
  function renderQuiz() {
    var q     = QUESTIONS[S.step];
    var panel = el('div','br-panel');
    ap(panel, el('span','br-qlabel',{text:q.label}), el('span','br-question',{text:q.question}));
    var opts = el('div','br-opts');
    q.opts.forEach(function(opt){
      var btn = el('button','br-opt',{ type:'button', onclick:function(){ handleSelect(q.id,opt.v); } });
      var ico = el('span','br-opt-icon',{text:opt.icon});
      var txt = el('span','');
      ap(txt, el('span','br-opt-label',{text:opt.label}), el('span','br-opt-desc',{text:opt.desc}));
      ap(btn, ico, txt, el('span','br-opt-arrow',{text:'›'}));
      ap(opts, btn);
    });
    return ap(panel, opts);
  }

  /* Results */
  function renderResults() {
    var wrap = el('div','br-results');
    ap(wrap, el('span','br-res-title',{text:'Deine Babolat Schläger'}));

    var tags = el('div','br-tags');
    Object.values(S.answers).forEach(function(v){
      ap(tags, el('span','br-tag',{text:LABELS[v]||v}));
    });
    ap(wrap, tags);

    var maxS = S.top3[0] ? S.top3[0].s : 1;
    if (S.top3[0]) ap(wrap, renderFeatured(S.top3[0], maxS));

    var rest = S.top3.slice(1);
    if (rest.length) {
      ap(wrap, el('span','br-alts-lbl',{text:'Wir empfehlen auch'}));
      var grid = el('div','br-alts');
      rest.forEach(function(item,i){ ap(grid, renderAlt(item, i+2)); });
      ap(wrap, grid);
    }

    ap(wrap, el('button','br-restart',{
      type:'button', text:'↩  Test neu starten', onclick:handleRestart
    }));
    return wrap;
  }

  /* Tarjeta destacada */
  function renderFeatured(item, maxS) {
    var p   = item.p;
    var pct = maxS>0 ? Math.round((item.s/maxS)*100) : 0;

    var card = el('div','br-featured');
    ap(card, el('div','br-badge',{text:'⭐ Dein bester Match'}));

    /* Imagen */
    var iz = el('div','br-img-zone');
    if (p.img) {
      var img = el('img','br-product-img',{ src:p.img, alt:p.name, loading:'eager' });
      /* Fallback al SVG si la imagen falla */
      img.onerror = function() {
        var sw = document.createElement('div');
        sw.classList.add('br-svg-pad');
        sw.innerHTML = paddleSVG(p.color, p.forma, 120);
        if (img.parentNode) img.parentNode.replaceChild(sw, img);
      };
      ap(iz, img);
    } else {
      var sw = document.createElement('div');
      sw.classList.add('br-svg-pad');
      sw.innerHTML = paddleSVG(p.color, p.forma, 120);
      ap(iz, sw);
    }
    ap(card, iz);

    /* Body */
    var body = el('div','br-feat-body');

    /* Barra match */
    var fill  = el('div','br-match-bar');
    fill.setAttribute('data-w', pct+'%');
    fill.style.width = '0%';
    var track = el('div','br-match-track');
    ap(track, fill);
    ap(body, ap(el('div','br-match-row'),
      el('span','br-match-lbl',{text:'Übereinstimmung'}),
      track,
      el('span','br-match-pct',{text:pct+'%'})
    ));

    var sEl = el('span','br-serie',{text:p.serie});
    sEl.style.color = p.color;
    ap(body, sEl);
    ap(body, el('span','br-pname',{text:p.name}));
    ap(body, el('span','br-pdesc',{text:p.desc}));

    /* Footer */
    var footer = el('div','br-feat-footer');
    var pb = el('div','br-price-block');
    ap(pb,
      el('span','br-price',     {text:p.precio}),
      el('span','br-price-orig',{text:p.precioOrig}),
      el('span','br-disc',      {text:p.disc})
    );
    ap(footer, pb, el('a','br-btn primary',{
      href:productUrl(p.id), target:'_blank', rel:'noopener noreferrer',
      text:'Schläger ansehen →',
    }));
    ap(body, footer);
    ap(card, body);
    return card;
  }

  /* Tarjeta alternativa */
  function renderAlt(item, rankNum) {
    var p    = item.p;
    var card = el('div','br-alt-card');
    ap(card, el('div','br-badge is-alt',{text:'#'+rankNum}));

    var iz = el('div','br-alt-img-zone');
    if (p.img) {
      var img = el('img','br-alt-product-img',{ src:p.img, alt:p.name, loading:'lazy' });
      img.onerror = function() {
        var sw = document.createElement('div');
        sw.classList.add('br-alt-svg');
        sw.innerHTML = paddleSVG(p.color, p.forma, 70);
        if (img.parentNode) img.parentNode.replaceChild(sw, img);
      };
      ap(iz, img);
    } else {
      var sw = document.createElement('div');
      sw.classList.add('br-alt-svg');
      sw.innerHTML = paddleSVG(p.color, p.forma, 70);
      ap(iz, sw);
    }
    ap(card, iz);

    var body = el('div','br-alt-body');
    var sEl = el('span','br-alt-serie',{text:p.serie});
    sEl.style.color = p.color;
    ap(body, sEl);
    ap(body, el('span','br-alt-name', {text:p.short}));
    ap(body, el('span','br-alt-price',{text:p.precio}));
    ap(body, el('a','br-alt-btn',{
      href:productUrl(p.id), target:'_blank', rel:'noopener noreferrer',
      text:'Ansehen →',
    }));
    ap(card, body);
    return card;
  }

  /* ════════════════════════════════════
     HANDLERS
  ════════════════════════════════════ */
  function handleSelect(qid, value) {
    S.answers[qid] = value;
    if (S.step < QUESTIONS.length-1) {
      S.step++;
    } else {
      S.done = true;
      S.top3 = getTop3(S.answers);
    }
    render();
  }

  function handleRestart() {
    S.step=0; S.answers={}; S.done=false; S.top3=[];
    render();
  }

  /* ════════════════════════════════════
     INIT
  ════════════════════════════════════ */
  function init() {
    if (document.getElementById('babolat-recommender-root')) render();
  }

})();
