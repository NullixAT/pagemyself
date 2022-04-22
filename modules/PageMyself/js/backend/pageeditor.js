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

    if (!PageMyselfPageEditor.currentPage) return

    // update editor bar information on frame load
    const pageData = await FramelixApi.callPhpMethod(PageMyselfPageEditor.frame.attr('data-edit-url'), {
      'page': PageMyselfPageEditor.currentPage,
      'action': 'pageData'
    })
    PageMyselfPageEditor.frameTop.find('.pageeditor-frame-top-title').text(PageMyselfPageEditor.iframeDoc.title)

    const themeField = FramelixFormField.getFieldByName(PageMyselfPageEditor.frameTop, 'theme')
    themeField.setValue(pageData.theme)

    // inject editor css into website
    $('head link[href*="pageeditor.min.css"]').each(function () {
      PageMyselfPageEditor.iframeHtml.find('head').append($(this).clone())
    })
    PageMyselfPageEditor.iframeHtml.addClass('pageeditor-website')

    // insert editor containers
    PageMyselfPageEditor.iframeHtml.find('.component-blocks').each(function () {
      $(this).before(`
        <div class="pageeditor-block-options" data-placement="${$(this).attr('data-placement')}">
          <div class="pageeditor-block-options-title"></div>
          <button class="framelix-button framelix-button-small add-new-block" data-icon-left="add" title="__pagemyself_component_add__"></button>
        </div>
      `)
    })

    // add new block button
    $(PageMyselfPageEditor.iframeDoc).on('click', '.add-new-block', async function () {
      const placement = $(this).closest('.pageeditor-block-options').attr('data-placement')
      const bellow = $(this).closest('.pageeditor-block-options').attr('data-block-id')
      const data = await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'getComponentList'
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
          'action': 'createComponentBlock',
          'blockClass': this.dataset.blockClass,
          'placement': placement,
          'bellow': bellow
        })
        Framelix.showProgressBar(null)
        PageMyselfPageEditor.setIframeUrl(data.url)
      })
    })

    // delete block button
    $(PageMyselfPageEditor.iframeDoc).on('click', '.pageeditor-block-options .delete-block', async function () {
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

    // sorting blocks
    $(PageMyselfPageEditor.iframeDoc).on('click', '.pageeditor-block-options  .sort-block-up, .pageeditor-block-options  .sort-block-down', async function () {
      const blockNow = $(this).closest('.pageeditor-block-options').next()
      const blockNext = $(this).hasClass('sort-block-up') ? blockNow.prevUntil('.component-block').last().prev() : blockNow.next().nextUntil('.component-block').last().next()
      await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'updateBlockSort',
        'blockA': blockNow.attr('data-id'),
        'blockB': blockNext.attr('data-id')
      })
      PageMyselfPageEditor.iframeWindow.location.reload()
    })

    // block settings
    $(PageMyselfPageEditor.iframeDoc).on('click', '.pageeditor-block-options .settings', async function () {
      const blockNow = $(this).closest('.pageeditor-block-options')
      await FramelixModal.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'blockSettings',
        'block': blockNow.attr('data-block-id')
      }, { maxWidth: 900 })
    })

    // add editing options of existing blocks
    const blockElMap = new Map()
    PageMyselfPageEditor.iframeHtml.find('.component-block').each(function () {
      const block = $(this)
      const blockId = block.attr('data-id')
      const options = $(`
        <div class="pageeditor-block-options" data-block-id="${blockId}">  
          <button class="framelix-button framelix-button-small settings" data-icon-left="settings" title="__pagemyself_component_settings__"></button> 
          <div class="pageeditor-block-options-title"><span class="framelix-loading"></span></div>   
          <button class="framelix-button framelix-button-small sort-block-down framelix-button-customcolor" data-icon-left="south" style="--color-custom-bg:#2190af; --color-custom-text:white;" title="__pagemyself_component_sort_down__"></button>    
          <button class="framelix-button framelix-button-small sort-block-up framelix-button-customcolor" data-icon-left="north" style="--color-custom-bg:#216daf; --color-custom-text:white;" title="__pagemyself_component_sort_up__"></button>     
          <button class="framelix-button framelix-button-small add-new-block" data-icon-left="add" title="__pagemyself_component_add__"></button>          
        </div>
      `)
      block.before(options)
      const component = PageMyselfPageEditor.iframeWindow.eval('PageMyselfComponent.instances[' + blockId + ']')
      component.backendOptionsContainer = options
      component.enableEditing()
      blockElMap.set(blockId, component)
    })

    PageMyselfPageEditor.iframeHtml.find('.component-blocks').each(function () {
      const childs = $(this).children('.pageeditor-block-options')
      childs.first().addClass('pageeditor-block-options-first')
      childs.last().addClass('pageeditor-block-options-last')
    })

    // update block infos
    FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
      'page': PageMyselfPageEditor.currentPage,
      'action': 'getComponentBlockInfos',
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
            FramelixModal.show({ bodyContent: FramelixLang.get(row.help), maxWidth: 900 })
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