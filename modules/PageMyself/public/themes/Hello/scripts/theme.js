class Theme {
  /**
   * Init late
   */
  static initLate () {
    const nav = $('.page-nav')
    const navUl = nav.find('ul').first()
    $(document).on('click', '.page-nav button', function () {
      const ul = $(this).parent().children('ul')
      if (!ul.length) return
      const ulClone = $(this).parent().children('ul').clone()
      ulClone.removeClass('hidden')
      const content = $(`<nav class="page-nav">`).append(ulClone)
      const popup = FramelixPopup.show(this, content)
      popup.popperEl.addClass('popup-nav')
    })
    $(document).on('click', '.page-nav .show-more', function () {
      nav.toggleClass('page-nav-wrap')
    })
    Theme.onResize()
    let resizeTo = null
    $(window).on('resize', function () {
      if (resizeTo) return
      resizeTo = setTimeout(function () {
        Theme.onResize()
        resizeTo = null
      }, 100)
    })
  }

  /**
   * On resize
   */
  static onResize () {
    const nav = $('.page-nav')
    const maxW = nav.parent().width()
    const ul = nav.children('ul')
    const showMoreEntry = ul.find('.show-more')
    const entries = ul.children().not(showMoreEntry)
    let w = 0
    let i = 0
    entries.addClass('invisible')
    entries.removeClass('nav-entry-hidden')
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
    showMoreEntry.toggleClass('nav-entry-hidden', !showMore)
    // check if the show more entry also overlaps
    if (showMore) {
      const unhiddenEntries = ul.children().not('.nav-entry-hidden')
      w = 0
      unhiddenEntries.each(function () {
        w += $(this).width()
      })
      if (unhiddenEntries.length > 2 && w > maxW) {
        unhiddenEntries.not(showMoreEntry).last().addClass('nav-entry-hidden')
      }
    }
    entries.removeClass('invisible')
  }
}

FramelixInit.late.push(Theme.initLate)