(function($){
  $(document).on('click', '.ppd-reserve-btn', function(e){
    e.preventDefault();
    var $btn = $(this);
    $btn.prop('disabled', true);
    $.post(PPD_RES.ajaxurl, {
      action: 'ppd_add_reservation',
      nonce: PPD_RES.nonce,
      pid: $btn.data('pid'),
      sku: $btn.data('sku'),
      deposit: $btn.data('deposit')
    }).done(function(resp){
      if (resp.success && resp.data.redirect){
        window.location.href = resp.data.redirect;
      } else {
        alert((resp.data && resp.data.message) ? resp.data.message : 'Error al iniciar la reserva.');
        $btn.prop('disabled', false);
      }
    }).fail(function(){
      alert('Error de red.');
      $btn.prop('disabled', false);
    });
  });
})(jQuery);