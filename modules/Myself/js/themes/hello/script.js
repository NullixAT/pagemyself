class MyselfThemeHello {

  /**
   * Init late
   */
  static initLate () {
    const sidebar = $('.myself-themes-hello-sidebar')
    Framelix.syncScroll(sidebar, $('html'))
    const navList = $('nav').find(' .myself-pageblocks-navigation-navlist').first()
    const activeLinks = $('.myself-pageblocks-navigation-active-link')
    if (navList.css('display') === 'flex') {
      const groupConfigMap = new Map()
      $(document).on('click mouseenter touchstart', '.myself-pageblocks-navigation-navlist-group,.myself-themes-hello-more', function () {
        let config = groupConfigMap.get(this)
        if (!config) {
          config = {}
          groupConfigMap.set(this, config)
        }
        const el = $(this)
        if (config.popup) return
        let popupContent
        if (el.hasClass('myself-themes-hello-more')) {
          popupContent = navList.clone()
        } else {
          popupContent = el.next('ul').clone()
        }
        config.popup = FramelixPopup.showPopup(el, popupContent, {
          placement: parseInt(popupContent.attr('data-level')) <= 1 ? 'bottom' : 'left',
          color: '#fff'
        })
        config.popup.onDestroy(function () {
          config.popup = null
        })
      })

      const nav = $('.myself-themes-hello-sidebar').find('nav')
      const lastLi = nav.children('ul').children('li').last()
      if (lastLi.length) {
        const boundingRect = lastLi[0].getBoundingClientRect()
        sidebar.attr('data-more', (lastLi.position().left + boundingRect.width - 10) > nav.width() ? '1' : '0')
      }
    } else {
      $(document).on('click', '.myself-pageblocks-navigation-navlist-group', function () {
        $(this).next('ul').toggleClass('myself-pageblocks-navigation-navlist-show')
      })
      activeLinks.parents('.myself-pageblocks-navigation-navlist').addClass('myself-pageblocks-navigation-navlist-show')
    }
    activeLinks.parents('li').children().addClass('myself-pageblocks-navigation-active-link')
  }
}

FramelixInit.late.push(MyselfThemeHello.initLate)