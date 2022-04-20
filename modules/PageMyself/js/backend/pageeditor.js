class PageMyselfPageEditor {
  /**
   * Editor config
   * @type {{}}
   */
  static config = {}

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
          PageMyselfPageEditor.setIframeUrl($(this).attr('data-url'))
          break
        case 'mobile':
          PageMyselfPageEditor.frame.attr('data-mobile', PageMyselfPageEditor.frame.attr('data-mobile') === '1' ? '0' : '1')
          break
      }
    })
    FramelixFormField.onValueChange(PageMyselfPageEditor.frame, 'jumpToPage', true, function (field) {
      PageMyselfPageEditor.setIframeUrl(field.getValue())
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
   * Set iframe url
   * @param {string} url
   */
  static setIframeUrl (url) {
    const urlNow = new URL(url)
    const urlPrev = new URL(PageMyselfPageEditor.iframeWindow.location.href)
    if (urlNow.pathname === urlPrev.pathname && urlNow.search === urlPrev.search) {
      PageMyselfPageEditor.iframeWindow.location.hash = urlNow.hash
      PageMyselfPageEditor.iframeWindow.location.reload()
    } else {
      PageMyselfPageEditor.iframe.attr('src', url)
    }
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
    PageMyselfPageEditor.iframeHtml.find('.page-blocks').each(function () {
      $(this).before(`
        <div class="pageeditor-block-options" data-placement="${$(this).attr('data-placement')}">
          <div class="pageeditor-block-options-title"></div>
          <button class="framelix-button framelix-button-small add-new-block" data-icon-left="add" title="__pagemyself_pageblock_add__"></button>
        </div>
      `)
    })

    // add new block button
    $(PageMyselfPageEditor.iframeDoc).on('click', '.add-new-block', async function () {
      const placement = $(this).closest('.pageeditor-block-options').attr('data-placement')
      const bellow = $(this).closest('.pageeditor-block-options').attr('data-block-id')
      const data = await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'getPageBlockList'
      })
      const content = $('<div></div>')
      for (let i in data) {
        const row = data[i]
        content.append(`
          <div class="pageeditor-block-list-entry" data-block-class="${row.blockClass}">
            <div><b>${FramelixLang.get(row.title)}</b></div>
            <div>${FramelixLang.get(row.desc)}</div>
          </div>
        `)
      }
      const modal = FramelixModal.show({ bodyContent: content, maxWidth: 900 })
      content.on('click', '.pageeditor-block-list-entry', async function () {
        modal.destroy()
        Framelix.showProgressBar(1)
        const data = await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
          'page': PageMyselfPageEditor.currentPage,
          'action': 'createNewPageBlock',
          'blockClass': this.dataset.blockClass,
          'placement': placement,
          'bellow': bellow
        })
        Framelix.showProgressBar(null)
        PageMyselfPageEditor.setIframeUrl(data.url)
      })
    })

    // delete block button
    $(PageMyselfPageEditor.iframeDoc).on('click', '.delete-block', async function () {
      if (!(await FramelixModal.confirm('__framelix_sure__').confirmed)) {
        return
      }
      await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'deleteBlock',
        'blockId': $(this).closest('.pageeditor-block-options').attr('data-block-id')
      })
      PageMyselfPageEditor.iframeWindow.location.reload()
    })

    // add editing options of existing blocks
    const blockElMap = new Map()
    PageMyselfPageEditor.iframeHtml.find('.page-block').each(function () {
      const block = $(this)
      const blockId = block.attr('data-id')
      const options = $(`
        <div class="pageeditor-block-options" data-block-id="${blockId}">
          <div class="pageeditor-block-options-title"><span class="framelix-loading"></span></div>
          <button class="framelix-button framelix-button-error framelix-button-small delete-block" data-icon-left="delete" title="__pagemyself_pageblock_delete__"></button>   
          <button class="framelix-button framelix-button-small add-new-block" data-icon-left="add" title="__pagemyself_pageblock_add__"></button>   
          <button class="framelix-button framelix-button-small open-help hidden" data-icon-left="info" title="__pagemyself_pageblock_help__"></button>    
          <button class="framelix-button framelix-button-small sort-block" data-icon-left="swap_vert" title="__pagemyself_pageblock_sort__"></button>           
        </div>
      `)
      block.before(options)
      const blockInstance = PageMyselfPageEditor.iframeWindow.eval('PageBlock.instances[' + blockId + ']')
      blockInstance.backendOptionsContainer = options
      blockInstance.enableEditing()
      blockElMap.set(blockId, blockInstance)
    })

    // enable block sorting
    FramelixDom.includeCompiledFile('Framelix', 'js', 'sortablejs', 'Sortable').then(function () {
      PageMyselfPageEditor.iframeHtml.find('.page-blocks').each(function () {
        const el = $(this)
        new Sortable(this, {
          'filter': '.page-block',
          'handle': '.sort-block',
          'onStart': function () {
            PageMyselfPageEditor.iframeHtml.attr('data-sorting', '1')
            blockElMap.forEach(function (blockInstance) {
              //blockInstance.disableEditing()
            })
          },
          'onEnd': function () {
            PageMyselfPageEditor.iframeHtml.removeAttr('data-sorting')
            blockElMap.forEach(function (blockInstance) {
              //blockInstance.enableEditing()
            })
          },
          'onSort': async function () {
            const ids = []
            el.children('.pageeditor-block-options').each(function () {
              ids.push($(this).attr('data-block-id'))
            })
            await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
              'page': PageMyselfPageEditor.currentPage,
              'action': 'updateBlockSort',
              'blockIds': ids
            })
            PageMyselfPageEditor.iframeWindow.location.reload()
          }
        })
      })
    })

    // update block infos
    FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
      'page': PageMyselfPageEditor.currentPage,
      'action': 'getPageBlockInfos',
      'blockIds': Array.from(blockElMap.keys())
    }).then(function (data) {
      for (let id in data) {
        const row = data[id]
        const blockInstance = blockElMap.get(id)
        blockInstance.backendOptionsContainer.find('.pageeditor-block-options-title').text('#' + row.id + ' ' + FramelixLang.get(row.title))
        if (row.help) {
          const openHelp = blockInstance.backendOptionsContainer.find('.open-help')
          openHelp.removeClass('hidden')
          openHelp.on('click', function () {
            FramelixModal.show({ bodyContent: FramelixLang.get(row.help) })
          })
        }
      }
    })

    // bypass resize event into the page frame
    let resizeTo = null
    $(window).off('resize.iframe').on('resize.iframe', function () {
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