/* Padel Berater Pro — Admin JS */
(function($){
  'use strict';

  var brands   = PR.brands   || [];
  var mappings = PR.mappings || {};
  var products = [];   // cargados por AJAX

  var selState = { gender:'hombre', nivel:'competicion', estilo:'atacante' };

  /* ── Inicializar segmented controls ── */
  function initSegs() {
    $('.pr-seg').each(function(){
      var segId = $(this).attr('id');
      var key   = segId.replace('seg-','');
      // activar el primero por defecto
      $(this).find('.pr-seg-btn').first().addClass('active')
             .siblings().removeClass('active');
      // click handler
      $(this).find('.pr-seg-btn').on('click', function(){
        $(this).addClass('active').siblings().removeClass('active');
        selState[key] = $(this).data('val');
        updateComboLabel();
        applyMappingsToSelects();
      });
    });
    updateComboLabel();
  }

  /* ── Etiqueta del combo activo ── */
  function updateComboLabel() {
    var lbls = PR.labels;
    var txt  = (lbls[selState.gender]||selState.gender) + ' / ' +
               (lbls[selState.nivel] ||selState.nivel)  + ' / ' +
               (lbls[selState.estilo]||selState.estilo);
    $('#pr-combo-label span').text(txt);
    $('#pr-panel-title').text('Schläger für: ' + txt);
  }

  function currentCombo() {
    return selState.gender + '_' + selState.nivel + '_' + selState.estilo;
  }

  /* ── Cargar productos via AJAX ── */
  function loadProducts() {
    $('#pr-loading').show();
    $('#pr-slots').hide();

    $.post(PR.ajax, {
      action:   'pr_get_products',
      nonce:    PR.nonce,
      category: 'padelschlaeger',
    }, function(resp) {
      if (resp.success) {
        products = resp.data;
        populateSelects();
        applyMappingsToSelects();
        updateStats();
        $('#pr-loading').hide();
        $('#pr-slots').show();
      } else {
        $('#pr-loading').html('<p style="color:#dc2626">Fehler beim Laden der Produkte.<br>Stelle sicher dass WooCommerce aktiv ist und die Kategorie "padelschlaeger" existiert.</p>');
      }
    }).fail(function(){
      $('#pr-loading').html('<p style="color:#dc2626">Verbindungsfehler. Bitte Seite neu laden.</p>');
    });
  }

  /* ── Optionen in alle Selects befüllen ── */
  function populateSelects() {
    var opts = '<option value="">— Kein Schläger —</option>';

    // Grouping by brand attribute if possible, otherwise show all
    // Group products by first word of name as heuristic
    var grouped = {};
    products.forEach(function(p){
      var words = p.name.split(' ');
      var grp   = words[0]; // z.B. "Babolat", "Adidas", etc.
      if (!grouped[grp]) grouped[grp] = [];
      grouped[grp].push(p);
    });

    // Build optgroups
    Object.keys(grouped).sort().forEach(function(grp){
      opts += '<optgroup label="' + escHtml(grp) + '">';
      grouped[grp].forEach(function(p){
        var label = p.name + (p.price ? '  ·  ' + p.price : '');
        opts += '<option value="' + p.id + '" data-thumb="' + escHtml(p.thumb) + '" data-price="' + escHtml(p.price) + '">' + escHtml(label) + '</option>';
      });
      opts += '</optgroup>';
    });

    $('.pr-product-select').each(function(){
      $(this).html(opts);
    });
  }

  /* ── Aplicar mappings guardados al combo activo ── */
  function applyMappingsToSelects() {
    var combo = currentCombo();
    var cmap  = (mappings[combo] || {});

    $('.pr-slot').each(function(){
      var bid     = $(this).data('brand');
      var pid     = cmap[bid] || '';
      var $select = $(this).find('.pr-product-select');
      $select.val(pid);
      updatePreview($select);
      $select.toggleClass('has-value', !!pid);
    });
  }

  /* ── Preview bajo el select ── */
  function updatePreview($select) {
    var bid   = $select.data('brand');
    var $prev = $('#prev-' + bid);
    var opt   = $select.find('option:selected');
    var pid   = $select.val();

    if (!pid) {
      $prev.empty();
      return;
    }

    var thumb = opt.data('thumb') || '';
    var price = opt.data('price') || '';
    var name  = opt.text().split('  ·  ')[0];

    var html = '';
    if (thumb) html += '<img src="' + escHtml(thumb) + '" alt="">';
    html += '<div><strong>' + escHtml(name) + '</strong>';
    if (price) html += '<span>' + escHtml(price) + '</span>';
    html += '</div>';
    $prev.html(html);
  }

  /* ── Guardar combo ── */
  function saveCombo() {
    var combo    = currentCombo();
    var brandMap = {};
    $('.pr-product-select').each(function(){
      var bid = $(this).data('brand');
      var pid = $(this).val();
      brandMap[bid] = pid;
    });

    var $btn = $('#pr-save-btn');
    var $msg = $('#pr-save-msg');
    $btn.prop('disabled',true).text('Speichern…');
    $msg.text('').removeClass('ok error');

    $.post(PR.ajax, {
      action:    'pr_save_combo',
      nonce:     PR.nonce,
      combo:     combo,
      brand_map: brandMap,
    }, function(r){
      if (r.success) {
        // Update local mappings cache
        mappings[combo] = {};
        for (var bid in brandMap) {
          if (brandMap[bid]) mappings[combo][bid] = parseInt(brandMap[bid]);
        }
        $msg.text('✓ Gespeichert').addClass('ok');
        updateStats(r.data.filled, r.data.total);
      } else {
        $msg.text('✗ Fehler: ' + r.data).addClass('error');
      }
    }).fail(function(){
      $msg.text('✗ Verbindungsfehler').addClass('error');
    }).always(function(){
      $btn.prop('disabled',false).text('💾 Speichern');
      setTimeout(function(){ $msg.text('').removeClass('ok error'); }, 3000);
    });
  }

  /* ── Stats ── */
  function updateStats(filled, total) {
    if (filled === undefined) {
      // recalculate locally
      filled = 0; total = 0;
      PR.combos.forEach(function(c){
        brands.forEach(function(b){
          total++;
          if (mappings[c] && mappings[c][b.id]) filled++;
        });
      });
    }
    $('#pr-filled').text(filled);
    var pct = total ? Math.round(filled/total*100) : 0;
    $('#pr-progress').css('width', pct + '%');
  }

  /* ── Escape HTML ── */
  function escHtml(str) {
    return (str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── Init ── */
  $(function(){
    if (!$('#pr-slots').length) return; // only on mappings page

    initSegs();
    loadProducts();

    // Select change → preview
    $(document).on('change', '.pr-product-select', function(){
      $(this).toggleClass('has-value', !!$(this).val());
      updatePreview($(this));
    });

    // Save button
    $('#pr-save-btn').on('click', saveCombo);

    // Keyboard shortcut Ctrl+S
    $(document).on('keydown', function(e){
      if ((e.ctrlKey||e.metaKey) && e.key==='s') {
        e.preventDefault();
        saveCombo();
      }
    });
  });

})(jQuery);
