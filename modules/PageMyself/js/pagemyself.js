class PageMyself {

  /**
   * Init
   */
  static initLate () {
    window.addEventListener('hashchange', function () {
      PageMyself.onHashChange()

    }, false)
    PageMyself.onHashChange()
  }

  /**
   * On hash change
   */
  static onHashChange () {
    if (!window.location.hash.startsWith('#jumpto-')) return
    const target = $('.' + window.location.hash.substring(1))
    if (!target.length) return
    let offset = 0
    const el = $('.pagemyself-jumpmark-offset').first()
    if (el.length) {
      const style = window.getComputedStyle(el[0])
      if (style.position === 'sticky' || style.position === 'fixed') {
        offset += parseInt(style.height.replace(/\..*/g, '').replace(/[^0-9]/g, ''))
      }
    }
    Framelix.scrollTo(target, null, offset)
  }
}

FramelixInit.late.push(PageMyself.initLate)