/**
 * MyselfBlockLayoutEditor
 */
class MyselfBlockLayoutEditor {

  /**
   * The current editor instance
   * @type {MyselfBlockLayoutEditor}
   */
  static current

  /**
   * The modal this editor is opened in
   * @type {FramelixModal}
   */
  modal

  /**
   * The current block layout config
   * @type {{}}
   */
  config = {}

  /**
   * Init late
   */
  static initLate () {
    let dragEl = null

    // swap a columnm after drag and drop
    async function swapColumns (columnA, columnB) {
      const columnIdA = columnA.attr('data-id')
      const columnIdB = columnB.attr('data-id')
      const rowA = columnA.closest('.myself-block-layout-row')
      const rowB = columnB.closest('.myself-block-layout-row')
      const rowIdA = rowA.attr('data-id')
      const rowIdB = rowB.attr('data-id')
      await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
        'action': 'column-swap',
        'pageId': MyselfEdit.framePageId,
        'rowIdA': rowIdA,
        'rowIdB': rowIdB,
        'columnIdA': columnIdA,
        'columnIdB': columnIdB,
      })
      dragEl = null
      MyselfBlockLayoutEditor.current.reload()
    }

    $(document).on('dragstart', '.myself-block-layout-row-column[draggable]', function (ev) {
      if (!MyselfBlockLayoutEditor.current) return
      dragEl = $(this)
      $('.myself-block-layout-row-column[draggable]').not(this).toggleClass('myself-block-layout-drop-highlight', true)
    })
    $(document).on('dragenter dragover', '.myself-block-layout-drop-highlight', function (ev) {
      if (!MyselfBlockLayoutEditor.current) return
      $('.myself-block-layout-drop-highlight-strong').toggleClass('myself-block-layout-drop-highlight-strong', false)
      $(this).toggleClass('myself-block-layout-drop-highlight-strong', true)
      ev.preventDefault()
    })
    $(document).on('drop', '.myself-block-layout-drop-highlight', function (ev) {
      if (!MyselfBlockLayoutEditor.current) return
      ev.preventDefault()
      if (dragEl) {
        swapColumns(dragEl, $(this))
      }
    })
    $(document).on('dragend', function (ev) {
      if (!MyselfBlockLayoutEditor.current) return
      $('.myself-block-layout-drop-highlight').toggleClass('myself-block-layout-drop-highlight', false)
      dragEl = null
    })
  }

  /**
   * Open the editor
   * @returns {Promise<MyselfBlockLayoutEditor>}
   */
  static async open () {
    Framelix.showProgressBar(1)
    const instance = new MyselfBlockLayoutEditor()
    instance.config = await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
      'pageId': MyselfEdit.framePageId,
      'action': 'fetch-settings'
    })
    instance.modal = await FramelixModal.show({
      bodyContent: '<div class="myself-block-layout-editor"></div>',
      maximized: true
    })
    instance.modal.contentContainer.addClass('myself-edit-font')
    instance.modal.destroyed.then(function () {
      if (MyselfBlockLayoutEditor.current === instance) {
        MyselfBlockLayoutEditor.current = null
      }
    })
    MyselfBlockLayoutEditor.current = instance
    Framelix.showProgressBar(null)
    instance.render()
    return instance
  }

  /**
   * Reload block layout editor
   * Fetch settings from backend and render block editor
   * @returns {Promise}
   */
  async reload () {
    const self = this
    self.config = await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
      'pageId': MyselfEdit.framePageId,
      'action': 'fetch-settings'
    })
    return self.render()
  }

  /**
   * Render
   */
  async render () {
    const self = this
    const container = this.modal.contentContainer.find('.myself-block-layout-editor')
    container.empty()

    const fixedPageBlocks = {}
    for (let pageBlockId in self.config.allPageBlocks) {
      if (self.config.allPageBlocks[pageBlockId].fixedPlacement) {
        fixedPageBlocks[pageBlockId] = self.config.allPageBlocks[pageBlockId]
      }
    }

    const tabs = new FramelixTabs()

    const rowsEl = $(`<div class="myself-block-layout-rows"></div>`)
    const fixedRowsEl = $(`<div class="myself-block-layout-fixed-rows"></div>`)

    tabs.addTab('rows', '__myself_blocklayout_pagelayout__', function () {
      const el = $(`<div>`)
      el.append(`<div class="framelix-alert framelix-alert-primary">
          ${FramelixLang.get('__myself_pageblocks_editor_info__')}
      </div>
      <div class="framelix-spacer"></div>`)
      el.append(fixedRowsEl)
      // no rows exist, add info
      if (!FramelixObjectUtils.hasKeys(self.config.blockLayout.rows)) {
        el.append(`<div class="framelix-alert framelix-alert-warning">
            ${FramelixLang.get('__myself_blocklayout_pagelayout_empty__')}
        </div>
        <div class="framelix-spacer"></div>`)
      }
      el.append(rowsEl)
      el.append(`<div class="myself-block-layout-row framelix-responsive-grid-2">        
          <button class="myself-block-layout-row-column framelix-button framelix-button-primary" data-icon-left="add"
                          title="__myself_blocklayout_add_row__" data-action="row-add"></button>
      </div>`)
      return el
    })

    tabs.addTab('template', '__myself_blocklayout_templates__', function () {
      const el = $(`<div>`)
      el.append(`<div class="framelix-alert framelix-alert-primary">
          ${FramelixLang.get('__myself_blocklayout_templates_desc__')}
      </div>
      <div class="framelix-spacer"></div>`)
      // rows exist, warning
      if (!FramelixObjectUtils.hasKeys(self.config.blockLayout.rows)) {
        el.append(`<div class="framelix-alert framelix-alert-error">
            ${FramelixLang.get('__myself_blocklayout_templates_warning__')}
        </div>
        <div class="framelix-spacer"></div>`)
      }
      el.append(`<div class="myself-template-picker">
        <ul class="myself-template-picker-list"></ul>
        <div class="myself-template-picker-preview">
          <div class="myself-template-picker-preview-image"></div>
          <div class="myself-template-picker-preview-desc"></div>
            <button class="framelix-button framelix-button-primary framelix-button-block" data-icon-left="check">${FramelixLang.get('__myself_blocklayout_templates_use__')}</button>
        </div>
      </div>`)
      const list = el.find('.myself-template-picker-list')
      const preview = el.find('.myself-template-picker-preview')
      for (let templateFilename in self.config.templates) {
        const template = self.config.templates[templateFilename]
        const li = $(`<li></li>`)
        li.attr('data-id', templateFilename)
        li.append(`<div class="myself-template-picker-list-label">${FramelixLang.get(template.label)}</div>`)
        li.append(`<div class="myself-template-picker-list-desc">${FramelixStringUtils.cut(FramelixLang.get(template.description), 200)}</div>`)
        list.append(li)
      }
      list.on('click', 'li', function (ev) {
        const li = $(this)
        const id = li.attr('data-id')
        const template = self.config.templates[id]
        const templateEditorData = self.config.templatesEditorData[id]
        preview.attr('data-id', id)
        preview.find('.myself-template-picker-preview-image').css('background-image', `url(${templateEditorData.thumbnailUrl})`)
        preview.find('.myself-template-picker-preview-desc').html(FramelixLang.get(template.description))
      })
      preview.on('click', 'button', async function () {
        if (FramelixObjectUtils.hasKeys(self.config.blockLayout.rows)) {
          if (!await FramelixModal.confirm('__myself_blocklayout_templates_warning__').confirmed) {
            return
          }
        }
        await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
          'pageId': MyselfEdit.framePageId,
          'action': 'insert-template',
          'id': preview.attr('data-id')
        })
        location.reload()
      })
      list.children().first().trigger('click')
      return el
    })

    if (self.config.devMode) {
      tabs.addTab('forDevs', '__myself_for_devs__', function () {
        const el = $(`<div>`)
        el.append(`<div class="framelix-alert">${FramelixLang.get('__myself_blocklayout_open_template_editor_info__')}</div>`)
        el.append(`<button class="framelix-button framelix-button-small framelix-button-primary">${FramelixLang.get('__myself_blocklayout_open_template_editor__')}</button>`)
        el.on('click', 'button', function () {
          FramelixModal.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
            'pageId': MyselfEdit.framePageId,
            'action': 'template-editor',
            'blockLayout': JSON.stringify(self.config.blockLayout)
          })
        })
        return el
      })
    }
    container.append(tabs.container)
    tabs.render()

    for (let pageBlockId in fixedPageBlocks) {
      const pageBlockData = fixedPageBlocks[pageBlockId]
      const row = $(`<div class="myself-block-layout-row myself-block-layout-row-fixed"></div>`)
      let title = '<div class="myself-block-layout-row-column-title-block">'
      if (pageBlockData) {
        title += '<div class="myself-block-layout-row-column-title-sub">' + FramelixLang.get(pageBlockData.blockName) + ' (' + pageBlockData.fixedPlacement + ')</div>'
      }
      title += FramelixLang.get(pageBlockData.title) + '</div>'
      const blockColumn = $(`<div class="myself-block-layout-row-column" data-page-block-id="${pageBlockId}">
            <div class="myself-block-layout-row-column-title" title="__myself_blocklayout_themeblock__">${title}</div>
            <div class="myself-block-layout-row-column-actions">
              <button class="framelix-button framelix-button-primary" data-icon-left="settings"
                      title="__myself_blocklayout_settings_column__" data-action="column-settings"></button>
            </div>
        </div>`)
      row.append(blockColumn)
      fixedRowsEl.append(row)
    }

    for (let rowKey in self.config.blockLayout.rows) {
      const configRow = self.config.blockLayout.rows[rowKey]
      const row = $(`<div class="myself-block-layout-row"></div>`)
      row.attr('data-columns', configRow.columns.length)
      for (let columnKey in configRow.columns) {
        const configColumn = configRow.columns[columnKey]
        const empty = !configColumn.pageBlockId
        const pageBlockData = empty ? null : self.config.allPageBlocks[configColumn.pageBlockId]
        let title = '<div class="myself-block-layout-row-column-title-block">'
        if (pageBlockData) {
          title += '<div class="myself-block-layout-row-column-title-sub">' + FramelixLang.get(pageBlockData.blockName) + '</div>'
        }
        if (pageBlockData && pageBlockData.flagDraft) {
          title += '<div class="myself-block-layout-row-column-title-sub">' + FramelixLang.get('__myself_pageblock_edit_internal_draft__') + '</div>'
        }
        title += !empty ? `<span class="myself-block-layout-row-column-title-text">${FramelixLang.get(pageBlockData.title)}</span>` : FramelixLang.get('__myself_blocklayout_empty__')
        title += '</div>'
        const grow = configColumn.settings.grow || 1
        const blockColumn = $(`<div class="myself-block-layout-row-column" data-status="${(pageBlockData ? pageBlockData.status : []).join(',')}" draggable="true" data-grow="${grow}" data-id="${columnKey}" data-page-block-id="${configColumn.pageBlockId}" style="flex-grow: ${grow};">
            <div class="myself-block-layout-row-column-title ${empty ? 'myself-block-layout-create-new-page-block' : ''}">${title}</div>
            <div class="myself-block-layout-row-column-actions">
              <button class="framelix-button" data-icon-left="vertical_align_center"
                      title="__myself_blocklayout_shrink__" data-action="shrink"></button>
              <button class="framelix-button" data-icon-left="expand"
                      title="__myself_blocklayout_grow__" data-action="grow"></button>
              <button class="framelix-button framelix-button-primary" data-icon-left="settings"
                      title="__myself_blocklayout_settings_column__" data-action="column-settings"></button>
              <button class="framelix-button" data-icon-left="clear"
                      title="__myself_blocklayout_remove_column__" data-action="column-remove"></button>
            </div>
        </div>`)
        if (empty) blockColumn.attr('data-empty', '1')
        row.append(blockColumn)
        const titleEl = blockColumn.find('.myself-block-layout-row-column-title-text')
        if (titleEl.length) {
          // fix whitespace and limit length
          titleEl[0].innerHTML = FramelixStringUtils.cut(titleEl[0].innerText.trim(), 200)
        }
      }
      row.append(`<div class="myself-block-layout-row-column myself-block-layout-row-column-new">
            <div class="myself-block-layout-row-column-title"></div>
            <div class="myself-block-layout-row-column-actions">
              <button class="framelix-button framelix-button-primary" data-icon-left="settings"
                      title="__myself_blocklayout_settings_row__" data-action="row-settings"></button>
              <button class="framelix-button framelix-button-success" data-icon-left="add"
                      title="__myself_blocklayout_add_column__" data-action="column-add"></button>
              <button class="framelix-button myself-block-layout-sort" data-icon-left="swap_vert" title="__myself_blocklayout_sort_row__"></button>
            </div>
        </div>`)
      row.attr('data-id', rowKey)
      rowsEl.append(row)
    }

    await FramelixDom.includeCompiledFile('Framelix', 'js', 'sortablejs', 'Sortable')
    new Sortable(rowsEl[0], {
      'handle': '.myself-block-layout-sort',
      'onSort': async function () {
        const rowIds = []
        rowsEl.find('.myself-block-layout-row[data-id]').each(function () {
          const rowId = $(this).attr('data-id')
          rowIds.push(rowId)
        })
        await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
          'pageId': MyselfEdit.framePageId,
          'action': 'rows-sort',
          'rowIds': rowIds
        })
        self.reload()
      }
    })

    if (!container.attr('data-initialized')) {
      container.attr('data-initialized', 1)
      container.on('click', '.myself-block-layout-create-new-page-block', async function () {
        const row = $(this).closest('.myself-block-layout-row')
        const rowId = row.attr('data-id')
        const column = $(this).closest('.myself-block-layout-row-column')
        const columnId = column.attr('data-id')
        const modal = await FramelixModal.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
          'pageId': MyselfEdit.framePageId,
          'action': 'select-new-page-block'
        }, { maximized: true })
        modal.contentContainer.on('click', '.myself-page-block-create-entry', async function () {
          await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
            'pageId': MyselfEdit.framePageId,
            'action': 'create-page-block',
            'pageBlockClass': $(this).attr('data-page-block-class'),
            'rowId': rowId,
            'columnId': columnId
          })
          const newSettings = await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
            'pageId': MyselfEdit.framePageId,
            'action': 'fetch-settings'
          })
          self.config.allPageBlocks = newSettings.allPageBlocks
          self.config.blockLayout.rows[rowId].columns[columnId].pageBlockId = newSettings.blockLayout.rows[rowId].columns[columnId].pageBlockId
          self.render()
          modal.destroy()
        })
      })
      container.on('click', '[data-action]', async function (ev) {
        const row = $(this).closest('.myself-block-layout-row')
        const rowId = row.attr('data-id')
        const column = $(this).closest('.myself-block-layout-row-column')
        const columnId = column.attr('data-id')
        const pageBlockId = column.attr('data-page-block-id')
        const action = $(this).attr('data-action')

        switch (action) {
          case 'grow':
          case 'shrink':
            await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
              'pageId': MyselfEdit.framePageId,
              'action': action,
              'rowId': rowId,
              'columnId': columnId
            })
            self.reload()
            break
          case 'row-settings': {
            const modal = await FramelixModal.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
              'pageId': MyselfEdit.framePageId,
              'action': action,
              'rowId': rowId
            }, { maximized: true })
            modal.contentContainer.on(FramelixForm.EVENT_SUBMITTED, function () {
              self.reload()
              modal.destroy()
            })
          }
            break
          case 'column-settings': {
            const modal = await FramelixModal.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
              'pageId': MyselfEdit.framePageId,
              'action': action,
              'pageBlockId': pageBlockId,
              'rowId': rowId,
              'columnId': columnId
            }, { maximized: true })
            modal.contentContainer.on(FramelixForm.EVENT_SUBMITTED, function () {
              self.reload()
              modal.destroy()
            })
          }
            break
          case 'column-add':
            await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
              'pageId': MyselfEdit.framePageId,
              'action': action,
              'rowId': rowId
            })
            self.reload()
            break
          case 'column-remove':
            if (await FramelixModal.confirm('__myself_blocklayout_column_remove__').confirmed) {
              await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
                'pageId': MyselfEdit.framePageId,
                'action': action,
                'rowId': rowId,
                'columnId': columnId
              })
              self.reload()
            }
            break
          case 'row-add':
            await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
              'pageId': MyselfEdit.framePageId,
              'action': action
            })
            self.reload()
            break
        }
      })
    }
  }
}

FramelixInit.late.push(MyselfBlockLayoutEditor.initLate)