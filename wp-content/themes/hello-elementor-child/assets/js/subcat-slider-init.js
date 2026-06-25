jQuery(document).ready(function ($) {
  $('.js-subcat-swiper').each(function () {
    const $slider = $(this);
    const el      = $slider[0];
    const $nextEl = $slider.find('.subcat-slider-button-next');
    const $prevEl = $slider.find('.subcat-slider-button-prev');

    if (el.swiper) { try { el.swiper.destroy(true, true); } catch (e) {} }

    if (!$slider.find('.swiper-slide').length) return;

    new Swiper(el, {
      observer: true,
      observeParents: true,
      loop: false,
      watchOverflow: false, // 👈 siempre generar paginación

      navigation: {
        nextEl: $nextEl[0],
        prevEl: $prevEl[0],
      },

      pagination: {
        el: $slider.find('.subcat-slider-pagination')[0],
        clickable: true,
      },

      breakpoints: {
        0: {
          slidesPerView: 3,
          slidesPerGroup: 3,
          spaceBetween: 5,
          centeredSlides: false
        },
        768: {
          slidesPerView: 'auto',
          slidesPerGroup: 1,
          spaceBetween: 15,
          centeredSlides: false
        }
      }
    });
  });
});
