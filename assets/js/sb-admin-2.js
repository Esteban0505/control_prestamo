(function($) {
  "use strict"; // Start of use strict

  // Mover #accordionSidebar como hijo directo de body en DOMContentLoaded
  $(document).ready(function() {
    var sidebar = $('#accordionSidebar');
    if (sidebar.length > 0) {
      $('body').append(sidebar);
    }
  });

  // Función de rollback para restaurar la posición original
  window.rollbackSidebarPosition = function() {
    var sidebar = $('#accordionSidebar');
    var wrapper = $('#wrapper');
    if (sidebar.length > 0 && wrapper.length > 0) {
      wrapper.prepend(sidebar);
    }
  };

  // Toggle the side navigation ajustando margin-left del content-wrapper dinámicamente
  $("#sidebarToggle, #sidebarToggleTop").on('click', function(e) {
    var sidebar = $('.sidebar');
    var isToggled = sidebar.hasClass('toggled');

    // Determinar valores de margin-left basados en el ancho de la ventana
    var normalMargin = ($(window).width() >= 768) ? '14rem' : '6.5rem';
    var toggledMargin = ($(window).width() >= 768) ? '6.5rem' : '0';

    if (isToggled) {
      // Mostrar sidebar (remover toggle)
      $('#content-wrapper').css('margin-left', normalMargin + ' !important');
      $('.sidebar .collapse').collapse('show');
    } else {
      // Ocultar sidebar (agregar toggle)
      $('#content-wrapper').css('margin-left', toggledMargin + ' !important');
      $('.sidebar .collapse').collapse('hide');
    }

    // Mantener la clase para compatibilidad
    sidebar.toggleClass("toggled");
  });

  // Close any open menu accordions when window is resized below 768px
  $(window).resize(function() {
    if ($(window).width() < 768) {
      $('.sidebar .collapse').collapse('hide');
    };

    // Toggle the side navigation when window is resized below 480px
    if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled")) {
      $("body").addClass("sidebar-toggled");
      $(".sidebar").addClass("toggled");
      $('.sidebar .collapse').collapse('hide');
    };
  });

  // Prevent the content wrapper from scrolling when the fixed side navigation hovered over
  // MODIFICADO: Solo prevenir si sidebar tiene scroll propio
  $('body.fixed-nav .sidebar').on('mousewheel DOMMouseScroll wheel', function(e) {
    var sidebar = $(this);
    var hasScroll = sidebar[0].scrollHeight > sidebar[0].clientHeight;
    if ($(window).width() > 768 && hasScroll) {
      var e0 = e.originalEvent,
        delta = e0.wheelDelta || -e0.detail;
      this.scrollTop += (delta < 0 ? 1 : -1) * 30;
      e.preventDefault();
    }
  });

  // Scroll to top button appear
  $(document).on('scroll', function() {
    var scrollDistance = $(this).scrollTop();
    if (scrollDistance > 100) {
      $('.scroll-to-top').fadeIn();
    } else {
      $('.scroll-to-top').fadeOut();
    }
  });

  // Smooth scrolling using jQuery easing
  $(document).on('click', 'a.scroll-to-top', function(e) {
    var $anchor = $(this);
    $('html, body').stop().animate({
      scrollTop: ($($anchor.attr('href')).offset().top)
    }, 1000, 'easeInOutExpo');
    e.preventDefault();
  });


})(jQuery); // End of use strict
