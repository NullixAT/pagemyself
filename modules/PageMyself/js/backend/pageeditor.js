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
  static iframe

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
   * @type {string}
   */
  static currentPage

  /**
   * Editor js call url
   * @type {string}
   */
  static editorJsCallUrl

  /**
   * Late init
   */
  static initLate () {
    PageMyselfPageEditor.frame = $('.pageeditor-frame')
    if (!PageMyselfPageEditor.frame.length) return
    PageMyselfPageEditor.editorJsCallUrl = PageMyselfPageEditor.frame.attr('data-edit-url')
    PageMyselfPageEditor.iframe = PageMyselfPageEditor.frame.find('iframe')
    PageMyselfPageEditor.frameTop = $('.pageeditor-frame-top')
    PageMyselfPageEditor.iframe.on('load', function () {
      PageMyselfPageEditor.onIframeLoad()
    })
    PageMyselfPageEditor.updateFrameSize()
    $(window).on('resize', function () {
      PageMyselfPageEditor.updateFrameSize()
    })
    $('button[data-modal-url]').on('click', function () {
      FramelixModal.callPhpMethod($(this).attr('data-modal-url'), { 'page': PageMyselfPageEditor.iframeHtml.attr('data-page') })
    })
    $('button[data-frame-action]').on('click', function () {
      switch ($(this).attr('data-frame-action')) {
        case 'back':
          PageMyselfPageEditor.iframeWindow.history.back()
          break
        case 'reload':
          PageMyselfPageEditor.iframeWindow.location.reload()
          break
        case 'loadurl':
          PageMyselfPageEditor.iframe.attr('src', $(this).attr('data-url'))
          break
        case 'mobile':
          PageMyselfPageEditor.frame.attr('data-mobile', PageMyselfPageEditor.frame.attr('data-mobile') === '1' ? '0' : '1')
          break
      }
    })
    FramelixFormField.onValueChange(PageMyselfPageEditor.frame, 'jumpToPage', true, function (field) {
      PageMyselfPageEditor.iframe.attr('src', field.getValue())
      field.setValue(null)
    })
    FramelixFormField.onValueChange(PageMyselfPageEditor.frame, 'pageLayout', true, async function (field) {
      await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'changeLayout',
        'layout': field.getValue()
      })
      PageMyselfPageEditor.iframeWindow.location.reload()
    })
  }

  /**
   * On iframe load
   */
  static async onIframeLoad () {
    PageMyselfPageEditor.iframeWindow = $('.pageeditor-frame iframe')[0].contentWindow
    PageMyselfPageEditor.iframeDoc = PageMyselfPageEditor.iframeWindow.document
    PageMyselfPageEditor.iframeHtml = $(PageMyselfPageEditor.iframeDoc).find('html').first()
    PageMyselfPageEditor.currentPage = PageMyselfPageEditor.iframeHtml.attr('data-page')
    PageMyselfPageEditor.frame.attr('data-page', PageMyselfPageEditor.currentPage)
    PageMyselfPageEditor.frameTop.find('.pageeditor-address').html(`<a href="${PageMyselfPageEditor.iframeWindow.location.href}" target="_blank" title="__pagemyself_pageeditor_page_open__">${PageMyselfPageEditor.iframeWindow.location.href}</a>`)

    // update editor bar information on frame load
    const pageData = await FramelixApi.callPhpMethod(PageMyselfPageEditor.frame.attr('data-edit-url'), {
      'page': PageMyselfPageEditor.currentPage,
      'action': 'pageData'
    })
    PageMyselfPageEditor.frameTop.find('.pageeditor-frame-top-title').text(PageMyselfPageEditor.iframeDoc.title)

    const pageLayoutField = FramelixFormField.getFieldByName(PageMyselfPageEditor.frameTop, 'pageLayout')
    pageLayoutField.setValue(pageData.layout)

    // inject editor css into website
    $('head link[href*="pageeditor.min.css"]').each(function () {
      PageMyselfPageEditor.iframeHtml.find('head').append($(this).clone())
    })
    PageMyselfPageEditor.iframeHtml.addClass('pageeditor-website')

    // insert editor containers
    PageMyselfPageEditor.iframeHtml.find('.pageeditor-anchor').each(function () {
      $(this).after(`<div class="pageeditor-block-options" data-type="${$(this).attr('data-type')}"><button class="framelix-button framelix-button-small add-new-block">Add a new block</button></div>`)
      $(this).remove()
    })

    // add new block button
    $(PageMyselfPageEditor.iframeDoc).on('click', '.add-new-block', async function () {
      const data = await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'newPageBlock'
      })
      const content = $('<div></div>')
      for (let i in data) {
        const row = data[i]
        content.append(`
          <div class="pageeditor-block-list-entry">
            <div><b>${FramelixLang.get(row.title)}</b></div>
            <div>${FramelixLang.get(row.desc)}</div>
          </div>
        `)
      }
      FramelixModal.show({ bodyContent: content, maxWidth: 900 })
    })

    // bypass resize event into the page frame
    let resizeTo = null
    $(window).on('resize', function () {
      if (resizeTo) return
      resizeTo = setTimeout(function () {
        resizeTo = null
        $(PageMyselfPageEditor.iframeWindow).trigger('resize')
      }, 100)
    })
  }

  /**
   * Update frame size
   */
  static updateFrameSize () {
    const frame = $('.pageeditor-frame')
    let reduceHeight = frame.offset().top + 150
    frame.height(window.innerHeight - reduceHeight)
  }
}

FramelixInit.late.push(PageMyselfPageEditor.initLate)