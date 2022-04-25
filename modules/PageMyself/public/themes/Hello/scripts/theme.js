class Theme extends PageMyselfTheme {
  /**
   * Init late
   */
  static initLate () {
    PageMyselfTheme.enableResponsiveHorizontalNavigation()
  }
}

FramelixInit.late.push(Theme.initLate)