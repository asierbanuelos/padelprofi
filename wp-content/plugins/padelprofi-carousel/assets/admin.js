/* global ppCarouselAdmin, jQuery */
(function ($) {
	'use strict';

	var productIds = [];

	function init() {
		// Carga los IDs ya guardados desde los chips del DOM
		$('#pp-selected-products .pp-chip').each(function () {
			productIds.push(parseInt($(this).data('id'), 10));
		});
		bindChipRemove($('#pp-selected-products'));
		makeSortable();
		bindSearch();

		// Cierre del dropdown al hacer clic fuera
		$(document).on('click', function (e) {
			if (!$(e.target).closest('#pp-product-search, #pp-search-results').length) {
				$('#pp-search-results').hide().empty();
			}
		});
	}

	function bindSearch() {
		var timer;
		$('#pp-product-search').on('input', function () {
			clearTimeout(timer);
			var term = $.trim($(this).val());
			if (term.length < 1) {
				$('#pp-search-results').hide().empty();
				return;
			}
			timer = setTimeout(function () {
				doSearch(term);
			}, 320);
		});
	}

	function doSearch(term) {
		$.ajax({
			url: ppCarouselAdmin.ajaxUrl,
			method: 'GET',
			data: {
				action: 'pp_search_products',
				nonce:  ppCarouselAdmin.nonce,
				term:   term
			},
			success: function (res) {
				var $results = $('#pp-search-results').empty();
				if (!res.success || !res.data.length) {
					$results.show().html('<div style="padding:10px 12px;color:#888;font-size:13px;">Keine Produkte gefunden</div>');
					return;
				}
				res.data.forEach(function (p) {
					if (productIds.indexOf(p.id) !== -1) return; // ya está
					var $item = $('<div class="pp-result">').html(
						'<strong>' + p.name + '</strong> <span style="color:#aaa;font-size:11px;">#' + p.id + '</span>'
					);
					$item.on('click', function () {
						addProduct(p);
						$('#pp-product-search').val('');
						$results.hide().empty();
					});
					$results.append($item);
				});
				$results.show();
			}
		});
	}

	function addProduct(p) {
		if (productIds.indexOf(p.id) !== -1) return;
		productIds.push(p.id);
		updateHidden();

		var $chip = buildChip(p);
		$('#pp-selected-products').append($chip);
	}

	function buildChip(p) {
		var $chip = $('<div class="pp-chip" data-id="' + p.id + '">').html(
			'<span class="dashicons dashicons-menu" style="font-size:14px;color:#bbb;"></span>' +
			'<span>' + p.name + ' <span style="color:#aaa;">#' + p.id + '</span></span>' +
			'<span class="pp-remove" data-id="' + p.id + '" title="Entfernen">×</span>'
		);
		bindChipRemove($chip);
		return $chip;
	}

	function bindChipRemove($scope) {
		$scope.on('click', '.pp-remove', function () {
			var id = parseInt($(this).data('id'), 10);
			productIds = productIds.filter(function (i) { return i !== id; });
			$(this).closest('.pp-chip').remove();
			updateHidden();
		});
	}

	function updateHidden() {
		$('#pp_carousel_product_ids').val(productIds.join(','));
	}

	function makeSortable() {
		$('#pp-selected-products').sortable({
			items: '.pp-chip',
			tolerance: 'pointer',
			update: function () {
				productIds = [];
				$('#pp-selected-products .pp-chip').each(function () {
					productIds.push(parseInt($(this).data('id'), 10));
				});
				updateHidden();
			}
		});
	}

	$(document).ready(init);

}(jQuery));
