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
   * Current blocks data
   * @type {Object<string, Object>}
   */
  static currentBlocksData = {}

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
        case 'themeSettings':
          FramelixModal.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
            'page': PageMyselfPageEditor.currentPage,
            'action': 'themeSettings'
          }, { maxWidth: 900 })
          break
      }
    })
    FramelixFormField.onValueChange(PageMyselfPageEditor.frame, 'jumpToPage', true, function (field) {
      PageMyselfPageEditor.setIframeUrl(field.getValue())
      field.setValue(null)
    })
    FramelixFormField.onValueChange(PageMyselfPageEditor.frame, 'theme', true, async function (field) {
      await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'changeTheme',
        'theme': field.getValue()
      })
      PageMyselfPageEditor.iframeWindow.location.reload()
    })

    // add new block button
    PageMyselfPageEditor.frameTop.on('click', '.add-new-block', async function () {
      $(PageMyselfPageEditor.iframeDoc).find('.insert-component-block-here').parent().remove()
      const blocks = $(PageMyselfPageEditor.iframeDoc).find('.component-blocks[data-placement], .component-block')
      blocks.each(function () {
        const parent = $(this).closest('.component-blocks[data-placement]')
        const btn = $(`<div style="text-align: center; padding:10px;"><button class="framelix-button insert-component-block-here framelix-button-primary" data-placement="${parent.attr('data-placement')}" data-component-block-id="${$(this).attr('data-id')}" data-icon-left="add">${FramelixLang.get('__pagemyself_component_insert_here__')}</button></div>`)
        if ($(this).hasClass('component-blocks')) {
          $(this).prepend(btn)
        } else {
          $(this).after(btn)
        }
        PageMyselfPageEditor.iframeWindow.scrollTo(0, 999999)
      })
    })

    // show block json
    PageMyselfPageEditor.frameTop.on('click', '.dev-show-block-json', function () {
      FramelixModal.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'showBlockJson'
      }, { maxWidth: 900 })
    })

    // import block json
    PageMyselfPageEditor.frameTop.on('click', '.dev-import-block-json', function () {
      const form = new FramelixForm()
      form.id = 'import'

      let field = new FramelixFormFieldTextarea()
      field.name = 'code'
      field.label = 'JSON'
      field.labelDescription = 'WARNING: All existing blocks on this page will be deleted'
      form.addField(field)

      form.addButton('import', 'Import')
      form.render()

      const modal = FramelixModal.show({
        bodyContent: form.container,
        footerContent: form.container.find('.framelix-form-buttons'),
        maxWidth: 900
      })

      modal.footerContainer.on('click', '.framelix-form-buttons [data-action="import"]', async function () {
        await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
          'page': PageMyselfPageEditor.currentPage,
          'action': 'importBlockJson',
          'code': field.getValue()
        })
        window.location.reload()
      })
    })

    // sorting blocks
    $(document).on('click', '.sort-block-up, .sort-block-down', async function () {
      const blockNow = $(this).closest('.pageeditor-block-options')
      const blockNext = $(this).hasClass('sort-block-up') ? blockNow.prev() : blockNow.next()
      await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'updateBlockSort',
        'blockA': blockNow.attr('data-component-block-id'),
        'blockB': blockNext.attr('data-component-block-id')
      })
      PageMyselfPageEditor.iframeWindow.location.reload()
      if (!$(this).hasClass('sort-block-up')) {
        blockNext.after(blockNow)
      } else {
        blockNow.after(blockNext)
      }
    })

    // block list
    $(document).on('click', '.block-list', async function () {
      FramelixModal.destroyAll()
      FramelixModal.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'getBlockSettingsList'
      }, { maxWidth: 900 })
    })

    // block settings
    $(document).on('click', '.block-settings', async function () {
      const blockNow = $(this).closest('.pageeditor-block-options')
      console.log($(this).attr('data-component-block-id'))
      PageMyselfPageEditor.openBlockSettings(PageMyselfPageEditor.currentPage, $(this).attr('data-component-block-id') || blockNow.attr('data-component-block-id'))
    })
  }

  /**
   * Open block settings
   * @param {number|string} pageId
   * @param  {number|string} blockId
   * @returns {Promise<void>}
   */
  static async openBlockSettings (pageId, blockId) {
    const modal = await FramelixModal.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
      'page': pageId,
      'action': 'blockSettings',
      'block': blockId
    }, { maxWidth: 900 })

    // delete block button
    modal.bodyContainer.on('click', '.framelix-form-buttons [data-action="delete-block"]', async function () {
      if (!(await FramelixModal.confirm('__framelix_sure__').confirmed)) {
        return
      }
      await FramelixApi.callPhpMethod(PageMyselfPageEditor.editorJsCallUrl, {
        'page': PageMyselfPageEditor.currentPage,
        'action': 'deleteBlock',
        'componentBlockId': blockId
      })
      modal.destroy()
      window.location.reload()
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

    window.history.pushState('', document.title, window.location.href.replace(/\?.*/ig, '') + '?url=' + encodeURIComponent(PageMyselfPageEditor.iframeWindow.location.href))

    if (!PageMyselfPageEditor.currentPage) return

    // update editor bar information on frame load
    const pageData = await FramelixApi.callPhpMethod(PageMyselfPageEditor.frame.attr('data-edit-url'), {
      'page': PageMyselfPageEditor.currentPage,
      'action': 'pageData'
    })

    const themeField = FramelixFormField.getFieldByName(PageMyselfPageEditor.frameTop, 'theme')
    themeField.setValue(pageData.theme)

    // inject editor css into website
    $('head link[href*="pageeditor.min.css"]').each(function () {
      PageMyselfPageEditor.iframeHtml.find('head').append($(this).clone())
    })
    PageMyselfPageEditor.iframeHtml.addClass('pageeditor-website')

    // quick open block settings
    $(PageMyselfPageEditor.iframeDoc).on('contextmenu', '.component-block', function (ev) {
      if (!ev.ctrlKey) return
      ev.preventDefault()
      ev.stopPropagation()
      ev.stopImmediatePropagation()
      PageMyselfPageEditor.openBlockSettings(PageMyselfPageEditor.currentPage, $(this).attr('data-id'))
    })
    $(PageMyselfPageEditor.iframeDoc).on('click', '.open-block-settings', function () {
      PageMyselfPageEditor.openBlockSettings(PageMyselfPageEditor.currentPage, $(this).closest('.component-block').attr('data-id'))
    })

    // insert new blocks
    $(PageMyselfPageEditor.iframeDoc).on('click', '.insert-component-block-here', async function () {
      const placement = $(this).attr('data-placement')
      const bellow = $(this).attr('data-component-block-id')
      $(PageMyselfPageEditor.iframeDoc).find('.insert-component-block-here').parent().remove()
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

    // enable editing for components
    PageMyselfPageEditor.iframeHtml.find('.component-block').each(function () {
      const block = $(this)
      const componentBlockId = block.attr('data-id')
      const component = PageMyselfPageEditor.iframeWindow.eval('PageMyselfComponent.instances[' + componentBlockId + ']')
      component.enableEditing()
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