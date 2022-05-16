class PageMyselfTheme {
  /**
   * Enables responsive horizontal navigation
   * Does automatically hide navigation entries that not fit into the container
   * In this case it adds a menu button to show all navigation entries that are hidden
   */
  static enableResponsiveHorizontalNavigation () {
    function onResize () {
      const entries = navUl.children()
      entries.filter('.show-more').remove()
      entries.addClass('invisible')
      entries.removeClass('nav-entry-hidden')
      let w = 0
      let i = 0
      const maxW = nav.width()
      let showMore = false
      while ((w === 0 || w > maxW) && i++ <= 30) {
        const unhiddenEntries = entries.not('.nav-entry-hidden')
        if (unhiddenEntries.length <= 1) break
        if (w !== 0) {
          unhiddenEntries.last().addClass('nav-entry-hidden')
          showMore = true
        }
        w = 0
        unhiddenEntries.each(function () {
          w += $(this).width()
        })
      }
      if (showMore) {
        navUl.prepend(`<li class="show-more">
          <span></span>
          <button class="framelix-button framelix-button-trans" data-icon-left="menu"></button>
          <span></span>
        </li>`)
        // check if the show more entry also overlaps
        const unhiddenEntries = navUl.children().not('.nav-entry-hidden')
        w = 0
        unhiddenEntries.each(function () {
          w += $(this).width()
        })
        if (unhiddenEntries.length > 2 && w > maxW) {
          unhiddenEntries.last().addClass('nav-entry-hidden')
        }
      }
      entries.removeClass('invisible')
    }

    const nav = $('.page-nav')
    const navUl = nav.find('ul').first()
    $(document).on('click', '.page-nav .nav-entry-group', function () {
      const ul = $(this).parent().children('ul')
      if (!ul.length) return
      const ulClone = $(this).parent().children('ul').clone()
      ulClone.removeClass('hidden')
      const content = $(`<nav class="page-nav">`).append(ulClone)
      const popup = FramelixPopup.show(this, content)
      popup.popperEl.addClass('popup-nav')
      popup.popperEl.on('click', function () {
        popup.destroy()
      })
    })
    nav.on('click', '.show-more', function () {
      const navPopup = nav.clone()
      navPopup.find('.nav-entry-hidden').removeClass('nav-entry-hidden')
      navPopup.find('ul.hidden').each(function () {
        const group = $(this)
        const parent = $(this).closest('li')
        group.find('li').each(function () {
          $(this).find('.nav-entry').prepend(parent.find('button').text() + ': ')
          parent.after(this)
        })
        parent.remove()
      })
      const modal = FramelixModal.show({ bodyContent: $('<div class="modal-nav"></div>').append(navPopup) })
      modal.bodyContainer.on('click', 'a', async function (ev) {
        ev.preventDefault()
        await modal.destroy()
        window.location.href = this.href
      })
    })
    onResize()
    let resizeTo = null
    $(window).on('resize', function () {
      if (resizeTo) return
      resizeTo = setTimeout(function () {
        onResize()
        resizeTo = null
      }, 100)
    })
  }
}