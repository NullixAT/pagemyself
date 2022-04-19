class PageMyselfPageEditor {

  /**
   * @type {Cash}
   */
  static frame

  /**
   * @type {Cash}
   */
  static frameTop

  /**
   * @type {Cash}
   */
  static frameBottom

  /**
   * @type {Window}
   */
  static iframeWindow

  /**
   * @type {Document}
   */
  static iframeDoc

  /**
   * @type {Cash}
   */
  static iframeHtml

  /**
   * Late init
   */
  static initLate () {
    PageMyselfPageEditor.frame = $('.pageeditor-frame')
    PageMyselfPageEditor.frameTop = $('.pageeditor-frame-top')
    PageMyselfPageEditor.frameBottom = $('.pageeditor-frame-bottom')
    $('.pageeditor-frame iframe').on('load', function () {
      PageMyselfPageEditor.onIframeLoad()
    })
    PageMyselfPageEditor.updateFrameSize()
    $(window).on('resize', function () {
      PageMyselfPageEditor.updateFrameSize()
    })
  }

  /**
   * On iframe load
   */
  static onIframeLoad () {
    PageMyselfPageEditor.iframeWindow = $('.pageeditor-frame iframe')[0].contentWindow
    PageMyselfPageEditor.iframeDoc = PageMyselfPageEditor.iframeWindow.document
    PageMyselfPageEditor.iframeHtml = $(PageMyselfPageEditor.iframeDoc).find('html').first()
    PageMyselfPageEditor.frame.attr('data-page', PageMyselfPageEditor.iframeHtml.attr('data-page'))
  }

  /**
   * Update frame size
   */
  static updateFrameSize () {
    const frame = $('.pageeditor-frame')
    let reduceHeight = frame.offset().top + 150 + $('.pageeditor-frame-bottom').height()
    frame.height(window.innerHeight - reduceHeight)
  }
}

FramelixInit.late.push(PageMyselfPageEditor.initLate)