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
   * Show save layout button
   * Will be true when layout has changed but not yet saved
   * @type {boolean}
   */
  showSaveLayoutBtn = false

  /**
   * Init late
   */
  static initLate () {
    let dragEl = null

    // swap a columnm after drag and drop
    // swap all config data except column settings (to keep column layout) but swap pageblock and other data
    function swapColumns (columnA, columnB) {
      const config = MyselfBlockLayoutEditor.current.config
      const columnIdA = columnA.attr('data-id')
      const columnIdB = columnB.attr('data-id')
      const rowA = columnA.closest('.myself-block-layout-row')
      const rowB = columnB.closest('.myself-block-layout-row')
      const rowIdA = rowA.attr('data-id')
      const rowIdB = rowB.attr('data-id')
      const configA = config.blockLayout.rows[rowIdA].columns[columnIdA]
      const configB = config.blockLayout.rows[rowIdB].columns[columnIdB]
      const settingsA = configA.settings
      const settingsB = configB.settings
      // re-swap settings to keep column layout config
      configB.settings = settingsA
      configA.settings = settingsB
      config.blockLayout.rows[rowIdA].columns[columnIdA] = configB
      config.blockLayout.rows[rowIdB].columns[columnIdB] = configA
      dragEl = null
      MyselfBlockLayoutEditor.current.showSaveLayoutBtn = true
      MyselfBlockLayoutEditor.current.render()
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
    self.showSaveLayoutBtn = false
    return self.render()
  }

  /**
   * Render
   */
  async render () {
    const self = this
    const container = this.modal.contentContainer.find('.myself-block-layout-editor')
    container.empty()
    // get all unassigned blocks and add it to the end of the list
    let unassignedPageBlocks = Object.assign({}, self.config.allPageBlocks)
    for (let i = 0; i < self.config.blockLayout.rows.length; i++) {
      const configRow = self.config.blockLayout.rows[i]
      if (!FramelixObjectUtils.hasKeys(configRow.settings) || Array.isArray(configRow.settings)) {
        configRow.settings = {}
      }
      for (let j = 0; j < configRow.columns.length; j++) {
        const configColumn = configRow.columns[j]
        if (!FramelixObjectUtils.hasKeys(configColumn.settings) || Array.isArray(configColumn.settings)) {
          configColumn.settings = {}
        }
        if (configColumn.pageBlockId) {
          delete unassignedPageBlocks[configColumn.pageBlockId]
        }
      }
    }
    const fixedPageBlocks = {}
    for (let pageBlockId in unassignedPageBlocks) {
      if (self.config.allPageBlocks[pageBlockId].fixedPlacement) {
        fixedPageBlocks[pageBlockId] = self.config.allPageBlocks[pageBlockId]
        continue
      }
      self.config.blockLayout.rows.push({
        'settings': {},
        'columns': [
          { 'pageBlockId': pageBlockId, 'settings': {} }
        ]
      })
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
      // no rows exist, add info
      if (!self.config.blockLayout.rows.length) {
        el.append(`<div class="framelix-alert framelix-alert-warning">
            ${FramelixLang.get('__myself_blocklayout_pagelayout_empty__')}
        </div>
        <div class="framelix-spacer"></div>`)
      }
      el.append(rowsEl)
      el.append(`<div class="myself-block-layout-row framelix-responsive-grid-2">        
          <button class="myself-block-layout-row-column framelix-button framelix-button-success hidden" data-icon-left="save" data-action="save-layout">${FramelixLang.get('__myself_blocklayout_save__')}</button>
          <button class="myself-block-layout-row-column framelix-button framelix-button-primary" data-icon-left="add"
                          title="__myself_blocklayout_add_row__" data-action="row-add"></button>
      </div>`)
      const saveLayoutBtn = el.find('.framelix-button[data-action=\'save-layout\']')
      if (self.showSaveLayoutBtn) saveLayoutBtn.removeClass('hidden')
      return el
    })

    tabs.addTab('fixedRows', '__myself_blocklayout_themeblocks__', function () {
      const el = $(`<div>`)
      el.append(`<div class="framelix-alert framelix-alert-primary">
          ${FramelixLang.get('__myself_blocklayout_themeblocks_desc__')}
      </div>
      <div class="framelix-spacer"></div>`)
      el.append(fixedRowsEl)
      return el
    })

    tabs.addTab('template', '__myself_blocklayout_templates__', function () {
      const el = $(`<div>`)
      el.append(`<div class="framelix-alert framelix-alert-primary">
          ${FramelixLang.get('__myself_blocklayout_templates_desc__')}
      </div>
      <div class="framelix-spacer"></div>`)
      // rows exist, warning
      if (self.config.blockLayout.rows.length) {
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
        if (self.config.blockLayout.rows.length) {
          if (!await FramelixModal.confirm('__myself_blocklayout_templates_warning__').confirmed) {
            return
          }
        }
        self.config = await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
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
      let title = ''
      if (pageBlockData) {
        title += '<div class="myself-block-layout-row-column-title-sub">' + FramelixLang.get(pageBlockData.blockName) + ' (' + pageBlockData.fixedPlacement + ')</div>'
      }
      title += '<div class="myself-block-layout-row-column-title-block">' + FramelixLang.get(pageBlockData.title) + '</div>'
      const blockColumn = $(`<div class="myself-block-layout-row-column" data-page-block-id="${pageBlockId}">
            <div class="myself-block-layout-row-column-title">${title}</div>
            <div class="myself-block-layout-row-column-actions">
              <button class="framelix-button framelix-button-primary" data-icon-left="settings"
                      title="__myself_blocklayout_settings_column__" data-action="column-settings"></button>
            </div>
        </div>`)
      row.append(blockColumn)
      fixedRowsEl.append(row)
    }

    for (let i = 0; i < self.config.blockLayout.rows.length; i++) {
      const configRow = self.config.blockLayout.rows[i]
      const row = $(`<div class="myself-block-layout-row"></div>`)
      row.attr('data-columns', configRow.columns.length)
      for (let j = 0; j < configRow.columns.length; j++) {
        const configColumn = configRow.columns[j]
        const empty = !configColumn.pageBlockId
        const pageBlockData = empty ? null : self.config.allPageBlocks[configColumn.pageBlockId]
        let title = ''
        if (pageBlockData) {
          title += '<div class="myself-block-layout-row-column-title-sub">' + FramelixLang.get(pageBlockData.blockName) + '</div>'
        }
        if (pageBlockData && pageBlockData.flagDraft) {
          title += '<div class="myself-block-layout-row-column-title-sub">' + FramelixLang.get('__myself_pageblock_edit_internal_draft__') + '</div>'
        }
        title += pageBlockData ? '<div class="myself-block-layout-row-column-title-block">' + FramelixLang.get(pageBlockData.title) + '</div>' : FramelixLang.get('__myself_blocklayout_empty__')
        const grow = configColumn.settings.grow || 1
        const blockColumn = $(`<div class="myself-block-layout-row-column" draggable="true" data-grow="${grow}" data-id="${j}" style="flex-grow: ${grow};">
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
        const titleEl = blockColumn.find('.myself-block-layout-row-column-title-block')
        if (titleEl.length) {
          // fix whitespace
          titleEl[0].innerHTML = titleEl[0].innerText.trim()
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
      row.attr('data-id', i)
      rowsEl.append(row)
    }

    await FramelixDom.includeCompiledFile('Framelix', 'js', 'sortablejs', 'Sortable')
    new Sortable(rowsEl[0], {
      'handle': '.myself-block-layout-sort',
      'onSort': function () {
        const rows = []
        rowsEl.find('.myself-block-layout-row[data-id]').each(function () {
          const rowId = $(this).attr('data-id')
          if (self.config.blockLayout.rows[rowId]) {
            rows.push(self.config.blockLayout.rows[rowId])
          }
        })
        self.showSaveLayoutBtn = true
        self.config.blockLayout.rows = rows
        self.render()
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
          self.showSaveLayoutBtn = false
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
        const action = $(this).attr('data-action')

        let rowSettings = {}
        let pageBlockId = null
        if (rowId) {
          rowSettings = self.config.blockLayout.rows[rowId].settings
        }
        let columnSettings = {}
        if (columnId !== undefined) {
          pageBlockId = self.config.blockLayout.rows[rowId].columns[columnId].pageBlockId
          columnSettings = self.config.blockLayout.rows[rowId].columns[columnId].settings
        } else {
          pageBlockId = column.attr('data-page-block-id')
        }

        switch (action) {
          case 'save-layout': {
            await FramelixApi.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
              'pageId': MyselfEdit.framePageId,
              'action': action,
              'rows': self.config.blockLayout.rows
            })
            location.reload()
          }
            break
          case 'grow':
          case 'shrink': {
            let grow = columnSettings.grow || 1
            if (action === 'grow') {
              grow++
            } else {
              grow--
              if (grow < 1) grow = 1
            }
            columnSettings.grow = grow
            self.render()
            self.showSaveLayoutBtn = true
          }
            break
          case 'row-settings': {
            const modal = await FramelixModal.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
              'pageId': MyselfEdit.framePageId,
              'action': action,
              'settings': rowSettings
            }, { maximized: true })
            modal.contentContainer.on('click', '.framelix-form-buttons [data-action=\'save\']', async function () {
              const form = FramelixForm.getById('rowsettings')
              if (!(await form.validate())) return
              Object.assign(rowSettings, form.getValues())
              self.showSaveLayoutBtn = true
              modal.destroy()
              self.render()
            })
          }
            break
          case 'column-settings': {
            const modal = await FramelixModal.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
              'pageId': MyselfEdit.framePageId,
              'action': action,
              'settings': columnSettings,
              'rows': self.config.blockLayout.rows,
              'pageBlockId': pageBlockId,
              'rowId': rowId,
              'columnId': columnId
            }, { maximized: true })
            modal.contentContainer.on(FramelixFormField.EVENT_CHANGE, function (ev) {
              const target = $(ev.target)
              const tabContent = target.closest('.framelix-tab-content')
              if (tabContent.length) {
                const tabButton = tabContent.closest('.framelix-tabs').children('.framelix-tab-buttons').children('.framelix-tab-button[data-id=\'' + tabContent.attr('data-id') + '\']')
                if (!tabButton.find('.myself-tab-edited').length) {
                  tabButton.prepend('<span class="material-icons myself-tab-edited" title="__myself_unsaved_changes__">warning</span>')
                }
              }
            })
            let settingsChanged = false
            modal.contentContainer.on(FramelixForm.EVENT_SUBMITTED, function (ev, data) {
              const target = $(ev.target)
              const tabContent = target.closest('.framelix-tab-content')
              if (tabContent.length) {
                const tabButton = tabContent.closest('.framelix-tabs').children('.framelix-tab-buttons').children('.framelix-tab-button[data-id=\'' + tabContent.attr('data-id') + '\']')
                tabButton.find('.myself-tab-edited').remove()
              }
              settingsChanged = true
              if (data.submitButtonName === 'saveClose') {
                location.reload()
              }
            })
            // reload settings from backend as it may have changed for the edited column
            modal.destroyed.then(async function () {
              if (!settingsChanged) return
              self.reload()
            })
          }
            break
          case 'column-add':
            self.config.blockLayout.rows[rowId].columns.push({ 'pageBlockId': 0, 'settings': {} })
            self.showSaveLayoutBtn = true
            self.render()
            break
          case 'column-remove':
            self.config.blockLayout.rows[rowId].columns.splice(columnId, 1)
            if (!self.config.blockLayout.rows[rowId].columns.length) {
              self.config.blockLayout.rows.splice(rowId, 1)
            }
            self.showSaveLayoutBtn = true
            self.render()
            break
          case 'row-add':
            self.config.blockLayout.rows.push({ 'columns': [{ 'pageBlockId': 0, 'settings': {} }], 'settings': {} })
            self.showSaveLayoutBtn = true
            self.render()
            break
        }
      })
    }
  }
}

FramelixInit.late.push(MyselfBlockLayoutEditor.initLate)