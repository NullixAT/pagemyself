'use strict';

class MyselfThemeHello {
  /**
   * Init late
   */
  static initLate() {
    Framelix.syncScroll($('.myself-themes-hello-sidebar'), $('html'));
    const navList = $('nav').find(' .myself-pageblocks-navigation-navlist').first();
    const activeLinks = $('.myself-pageblocks-navigation-active-link');

    if (navList.css('display') === 'flex') {
      const groupConfigMap = new Map();
      $(document).on('click mouseenter touchstart', '.myself-pageblocks-navigation-navlist-group,.myself-themes-hello-more', function () {
        let config = groupConfigMap.get(this);

        if (!config) {
          config = {};
          groupConfigMap.set(this, config);
        }

        const el = $(this);
        if (config.popup) return;
        let popupContent;

        if (el.hasClass('myself-themes-hello-more')) {
          popupContent = navList.clone();
        } else {
          popupContent = el.next('ul').clone();
        }

        config.popup = FramelixPopup.showPopup(el, popupContent, {
          placement: parseInt(popupContent.attr('data-level')) <= 1 ? 'bottom' : 'left',
          color: '#fff'
        });
        config.popup.onDestroy(function () {
          config.popup = null;
        });
      });
    } else {
      $(document).on('click', '.myself-pageblocks-navigation-navlist-group', function () {
        $(this).next('ul').toggleClass('myself-pageblocks-navigation-navlist-show');
      });
      activeLinks.parents('.myself-pageblocks-navigation-navlist').addClass('myself-pageblocks-navigation-navlist-show');
    }

    activeLinks.parents('li').children().addClass('myself-pageblocks-navigation-active-link');
  }

}

FramelixInit.late.push(MyselfThemeHello.initLate);