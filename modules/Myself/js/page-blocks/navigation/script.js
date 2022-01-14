class MyselfPageBlocksNavigation extends MyselfPageBlocks {
  /**
   * Init block
   */
  initBlock () {
    const html = $('html').first()
    const nav = this.blockContainer.find('nav').first()
    const navList = nav.find('.myself-pageblocks-navigation-navlist').first()
    if (!navList.length) return
    const activeLinks = navList.find('.myself-pageblocks-navigation-active-link')
    let layout = nav.attr('data-layout')
    // on small screen, flip to horizontal
    if (layout === 'vertical-flip') {
      layout = html.attr('data-screen-size') === 's' ? 'horizontal' : 'vertical'
    }
    nav.attr('data-layout', layout)
    nav.parent().attr('data-layout', layout)
    nav.closest('[data-navigation-layout-inner]').attr('data-navigation-layout-inner', layout)
    if (layout === 'horizontal') {
      const groupConfigMap = new Map()
      $(document).off('click.navigation').on('click.navigation', '.myself-pageblocks-navigation-navlist-group, .myself-pageblocks-navigation-more', function () {
        let config = groupConfigMap.get(this)
        if (!config) {
          config = {}
          groupConfigMap.set(this, config)
        }
        const el = $(this)
        if (config.popup) return
        let popupContent
        if (el.hasClass('myself-pageblocks-navigation-more')) {
          popupContent = navList.clone()
          popupContent.find('.myself-pageblocks-navigation-more').remove()
          popupContent.find('.myself-pageblocks-navigation-navlist-logo').remove()
          popupContent.find('li').removeClass('hidden')
        } else {
          popupContent = el.next('ul').clone()
        }
        popupContent.addClass('myself-pageblocks-navigation-popup')
        config.popup = FramelixPopup.show(el, popupContent, {
          placement: parseInt(popupContent.attr('data-level')) <= 1 ? 'bottom' : 'left',
          color: '#fff'
        })
        config.popup.destroyed.then(function () {
          config.popup = null
        })
        // hashtag anchors doesn't reload the page, so close popup by hand on click
        config.popup.content.on('click', 'a[href^=\'#\']', function () {
          setTimeout(function () {
            config.popup.destroy()
          }, 50)
        })
      })
      const lis = nav.children('ul').children('li').not('.myself-pageblocks-navigation-more').not('.myself-pageblocks-navigation-navlist-logo')
      const containerBoundingRect = nav[0].getBoundingClientRect()
      let visibleLis = $(lis)
      lis.each(function () {
        const boundingRect = this.getBoundingClientRect()
        const right = boundingRect.right - containerBoundingRect.left
        const visible = right <= containerBoundingRect.width
        if (!visible) {
          visibleLis = visibleLis.not(this)
        }
      })
      lis.not(visibleLis).toggleClass('hidden', true)
      if (visibleLis.length !== lis.length) {
        nav.attr('data-more', '1')
      }
    } else if (layout === 'vertical') {
      this.blockContainer.on('click', '.myself-pageblocks-navigation-navlist-group', function () {
        $(this).next('ul').toggleClass('myself-pageblocks-navigation-navlist-show')
      })
      activeLinks.parents('.myself-pageblocks-navigation-navlist').addClass('myself-pageblocks-navigation-navlist-show')
    }
    activeLinks.parents('li').children().addClass('myself-pageblocks-navigation-active-link')
  }
}