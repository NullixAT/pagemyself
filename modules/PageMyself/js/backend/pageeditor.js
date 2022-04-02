class PageMyselfPageEditor {

  /**
   * @type {Window}
   */
  static iframeWindow

  /**
   * @type {Document}
   */
  static iframeDoc

  static initLate () {
    PageMyselfPageEditor.iframeWindow = $('.pageeditor-frame iframe')[0].contentWindow
    PageMyselfPageEditor.iframeDoc = PageMyselfPageEditor.iframeWindow.document
    PageMyselfPageEditor.updateFrameSize()
    $(window).on('resize', function () {
      PageMyselfPageEditor.updateFrameSize()
    })
  }

  static updateFrameSize () {
    const frame = $('.pageeditor-frame')
    let reduceHeight = frame.offset().top + 150 + $('.pageeditor-frame-bottom').height()
    frame.height(window.innerHeight - reduceHeight)
  }
}

FramelixInit.late.push(PageMyselfPageEditor.initLate)