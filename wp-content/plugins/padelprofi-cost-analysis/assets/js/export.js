jQuery(document).ready(function($) {
    'use strict';
    
    const exportForm = $('#padelprofi-export-form');
    const progressContainer = $('#padelprofi-export-progress');
    const progressBar = $('.padelprofi-progress-fill');
    const progressText = $('.padelprofi-progress-text');
    const submitButton = exportForm.find('button[type="submit"]');
    
    // Manejar cambios en el select de "Todos los estados"
    $('#order_status').on('change', function() {
        const selectedValues = $(this).val();
        if (selectedValues && selectedValues.includes('all')) {
            $(this).find('option').not('[value="all"]').prop('selected', false);
            $(this).find('option[value="all"]').prop('selected', true);
        }
    });
    
    // Prevenir que se seleccionen otros estados si "Todos" está seleccionado
    $('#order_status option').not('[value="all"]').on('click', function() {
        $('#order_status option[value="all"]').prop('selected', false);
    });
    
    // Manejar el envío del formulario
    exportForm.on('submit', function(e) {
        e.preventDefault();
        
        // Siempre exportar CSV con descarga directa
        exportCSV();
    });
    
    function exportCSV() {
        // Mostrar indicador de progreso
        showProgress(padelprofiExport.i18n.processing);
        submitButton.addClass('exporting');
        
        // Crear un formulario temporal para envío POST directo
        const form = $('<form>', {
            method: 'POST',
            action: padelprofiExport.ajax_url,
            style: 'display: none;'
        });
        
        // Añadir action y nonce
        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'padelprofi_export_excel'
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: padelprofiExport.nonce
        }));
        
        // Copiar todos los campos del formulario
        $('#date_from').length && form.append($('<input>', {
            type: 'hidden',
            name: 'date_from',
            value: $('#date_from').val()
        }));
        
        $('#date_to').length && form.append($('<input>', {
            type: 'hidden',
            name: 'date_to',
            value: $('#date_to').val()
        }));
        
        $('#export_format').length && form.append($('<input>', {
            type: 'hidden',
            name: 'export_format',
            value: $('#export_format').val()
        }));
        
        // Manejar el select múltiple de estados
        const selectedStatuses = $('#order_status').val();
        if (selectedStatuses && selectedStatuses.length > 0) {
            selectedStatuses.forEach(function(status) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'order_status[]',
                    value: status
                }));
            });
        }
        
        // Checkbox de incluir productos
        if ($('input[name="include_products"]').is(':checked')) {
            form.append($('<input>', {
                type: 'hidden',
                name: 'include_products',
                value: '1'
            }));
        }
        
        // Añadir al body y enviar
        $('body').append(form);
        form.submit();
        
        // Limpiar después de 1 segundo y ocultar progreso
        setTimeout(function() {
            form.remove();
            hideProgress();
            submitButton.removeClass('exporting');
            showSuccessMessage('Descarga iniciada. El archivo se está generando...');
        }, 1000);
    }
    
    function showProgress(message) {
        progressContainer.slideDown();
        progressText.text(message);
        updateProgress(0);
    }
    
    function updateProgress(percent) {
        progressBar.css('width', percent + '%');
        progressText.text(padelprofiExport.i18n.processing + ' ' + Math.round(percent) + '%');
    }
    
    function hideProgress() {
        progressContainer.slideUp();
        updateProgress(0);
    }
    
    function showSuccessMessage(message) {
        const notice = $('<div>', {
            class: 'notice notice-success is-dismissible',
            html: '<p>' + message + '</p>'
        });
        
        $('.padelprofi-export-wrap h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    function showErrorMessage(message) {
        const notice = $('<div>', {
            class: 'notice notice-error is-dismissible',
            html: '<p>' + message + '</p>'
        });
        
        $('.padelprofi-export-wrap h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 8000);
    }
});
