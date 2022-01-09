class MyselfThemeHello {

  /**
   * Init late
   */
  static initLate () {
    const sidebar = $('.myself-themes-hello-sidebar')
    Framelix.syncScroll(sidebar, $('html'))
  }
}

FramelixInit.late.push(MyselfThemeHello.initLate)