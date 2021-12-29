/**
 * MyselfBlockLayoutEditor
 */
class MyselfBlockLayoutEditor {

  /**
   * Api Url
   * @type {string}
   */
  static apiUrl

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
    // swap all config data except column settings (to keep column layout) but swap pageblock and other data
    function swapColumns (columnA, columnB) {
      const config = MyselfBlockLayoutEditor.current.config
      const columnIdA = columnA.attr('data-id')
      const columnIdB = columnB.attr('data-id')
      const rowA = columnA.closest('.myself-block-layout-row')
      const rowB = columnB.closest('.myself-block-layout-row')
      const rowIdA = rowA.attr('data-id')
      const rowIdB = rowB.attr('data-id')
      const configA = config.rows[rowIdA].columns[columnIdA]
      const configB = config.rows[rowIdB].columns[columnIdB]
      const settingsA = configA.settings
      const settingsB = configB.settings
      // re-swap settings to keep column layout config
      configB.settings = settingsA
      configA.settings = settingsB
      config.rows[rowIdA].columns[columnIdA] = configB
      config.rows[rowIdB].columns[columnIdB] = configA
      dragEl = null
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
    instance.config = await FramelixApi.callPhpMethod(MyselfBlockLayoutEditor.apiUrl, { 'action': 'fetch-settings' })
    instance.modal = await FramelixModal.show(`
      <div class="framelix-alert framelix-alert-primary">
          ${FramelixLang.get('__myself_pageblocks_editor_info__')}
      </div>
      <div class="framelix-spacer"></div>
      <div class="myself-block-layout-editor"></div>
    `, null, true)
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
    self.config = await FramelixApi.callPhpMethod(MyselfBlockLayoutEditor.apiUrl, { 'action': 'fetch-settings' })
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
    for (let i = 0; i < self.config.rows.length; i++) {
      const configRow = self.config.rows[i]
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
      self.config.rows.push({
        'settings': {},
        'columns': [
          { 'pageBlockId': pageBlockId, 'settings': {} }
        ]
      })
    }
    // no rows exist, create a few empty rows
    if (!self.config.rows.length) {
      for (let i = 0; i <= 3; i++) {
        self.config.rows.push({ 'columns': [{ 'settings': { 'pageBlockId': null } }] })
      }
    }

    const fixedRowsEl = $(`<div class="myself-block-layout-fixed-rows"></div>`)
    container.append(fixedRowsEl)
    const rowsEl = $(`<div class="myself-block-layout-rows"></div>`)
    container.append(rowsEl)

    for (let pageBlockId in fixedPageBlocks) {
      const pageBlockData = fixedPageBlocks[pageBlockId]
      const row = $(`<div class="myself-block-layout-row myself-block-layout-row-fixed"></div>`)
      let title = '<div class="myself-block-layout-row-column-title-sub" title="__myself_blocklayout_fixedplacememt_desc__">' + FramelixLang.get('__myself_blocklayout_fixedplacememt__') + '</div>'
      title += FramelixLang.get(pageBlockData.title)
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

    for (let i = 0; i < self.config.rows.length; i++) {
      const configRow = self.config.rows[i]
      const row = $(`<div class="myself-block-layout-row"></div>`)
      row.attr('data-columns', configRow.columns.length)
      for (let j = 0; j < configRow.columns.length; j++) {
        const configColumn = configRow.columns[j]
        const empty = !configColumn.pageBlockId
        const pageBlockData = empty ? null : self.config.allPageBlocks[configColumn.pageBlockId]
        let title = pageBlockData ? pageBlockData.title : FramelixLang.get('__myself_blocklayout_empty__')
        const grow = configColumn.settings.grow || 1
        const blockColumn = $(`<div class="myself-block-layout-row-column" draggable="true" data-grow="${grow}" data-id="${j}" style="flex-grow: ${grow}">
            <div class="myself-block-layout-row-column-title ${empty ? 'myself-block-layout-create-new-page-block' : ''}">
            ${FramelixLang.get(title)}
            </div>
            <div class="myself-block-layout-row-column-actions">
              <button class="framelix-button framelix-button-trans" data-icon-left="vertical_align_center"
                      title="__myself_blocklayout_shrink__" data-action="shrink"></button>
              <button class="framelix-button framelix-button-trans" data-icon-left="expand"
                      title="__myself_blocklayout_grow__" data-action="grow"></button>
              <button class="framelix-button framelix-button-primary" data-icon-left="settings"
                      title="__myself_blocklayout_settings_column__" data-action="column-settings"></button>
              <button class="framelix-button framelix-button-trans" data-icon-left="clear"
                      title="__myself_blocklayout_remove_column__" data-action="column-remove"></button>
            </div>
        </div>`)
        if (empty) blockColumn.attr('data-empty', '1')
        row.append(blockColumn)
      }
      row.append(`<div class="myself-block-layout-row-column myself-block-layout-row-column-new">
            <div class="myself-block-layout-row-column-title"></div>
            <div class="myself-block-layout-row-column-actions">
              <button class="framelix-button framelix-button-primary" data-icon-left="settings"
                      title="__myself_blocklayout_settings_row__" data-action="row-settings"></button>
              <button class="framelix-button framelix-button-success" data-icon-left="add"
                      title="__myself_blocklayout_add_column__" data-action="column-add"></button>
              <button class="framelix-button framelix-button-trans myself-block-layout-sort" data-icon-left="swap_vert" title="__myself_blocklayout_sort_row__"></button>
            </div>
        </div>`)
      row.attr('data-id', i)
      rowsEl.append(row)
    }
    container.append(`<div class="myself-block-layout-row framelix-responsive-grid-2">        
        <button class="myself-block-layout-row-column framelix-button framelix-button-success" data-icon-left="save" data-action="save-layout">${FramelixLang.get('__myself_blocklayout_save__')}</button>
        <button class="myself-block-layout-row-column framelix-button framelix-button-primary" data-icon-left="add"
                        title="__myself_blocklayout_add_row__" data-action="row-add"></button>
    </div>`)

    await FramelixDom.includeCompiledFile('Framelix', 'js', 'sortablejs', 'Sortable')
    new Sortable(rowsEl[0], {
      'handle': '.myself-block-layout-sort',
      'onSort': function () {
        const rows = []
        rowsEl.find('.myself-block-layout-row[data-id]').each(function () {
          const rowId = $(this).attr('data-id')
          if (self.config.rows[rowId]) {
            rows.push(self.config.rows[rowId])
          }
        })
        self.config.rows = rows
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
        const modal = await FramelixModal.callPhpMethod(MyselfBlockLayoutEditor.apiUrl, {
          'action': 'select-new-page-block'
        }, null, false, null, true)
        modal.contentContainer.on('click', '.myself-page-block-create-entry', async function () {
          await FramelixApi.callPhpMethod(MyselfBlockLayoutEditor.apiUrl, {
            'action': 'create-page-block',
            'pageBlockClass': $(this).attr('data-page-block-class'),
            'rowId': rowId,
            'columnId': columnId
          })
          const newSettings = await FramelixApi.callPhpMethod(MyselfBlockLayoutEditor.apiUrl, { 'action': 'fetch-settings' })
          self.config.allPageBlocks = newSettings.allPageBlocks
          self.config.rows[rowId].columns[columnId].pageBlockId = newSettings.rows[rowId].columns[columnId].pageBlockId
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
          rowSettings = self.config.rows[rowId].settings
        }
        let columnSettings = {}
        if (columnId !== undefined) {
          pageBlockId = self.config.rows[rowId].columns[columnId].pageBlockId
          columnSettings = self.config.rows[rowId].columns[columnId].settings
        } else {
          pageBlockId = column.attr('data-page-block-id')
        }

        switch (action) {
          case 'save-layout': {
            await FramelixApi.callPhpMethod(MyselfBlockLayoutEditor.apiUrl, {
              'action': action,
              'rows': self.config.rows
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
          }
            break
          case 'row-settings': {
            const modal = await FramelixModal.callPhpMethod(MyselfBlockLayoutEditor.apiUrl, {
              'action': action,
              'settings': rowSettings
            }, true)
            modal.contentContainer.on('click', '.framelix-form-buttons [data-action=\'save\']', async function () {
              const form = FramelixForm.getById('rowsettings')
              if (!(await form.validate())) return
              Object.assign(rowSettings, form.getValues())
              modal.destroy()
              self.render()
            })
          }
            break
          case 'column-settings': {
            const modal = await FramelixModal.callPhpMethod(MyselfBlockLayoutEditor.apiUrl, {
              'action': action,
              'settings': columnSettings,
              'rows': self.config.rows,
              'pageBlockId': pageBlockId,
              'rowId': rowId,
              'columnId': columnId
            }, true)
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
            modal.contentContainer.on(FramelixForm.EVENT_SUBMITTED, function (ev) {
              const target = $(ev.target)
              const tabContent = target.closest('.framelix-tab-content')
              if (tabContent.length) {
                const tabButton = tabContent.closest('.framelix-tabs').children('.framelix-tab-buttons').children('.framelix-tab-button[data-id=\'' + tabContent.attr('data-id') + '\']')
                tabButton.find('.myself-tab-edited').remove()
              }
              settingsChanged = true
            })
            // reload settings from backend as it may have changed for the edited column
            modal.destroyed.then(async function () {
              if (!settingsChanged) return
              self.reload()
            })
          }
            break
          case 'column-add':
            self.config.rows[rowId].columns.push({ 'title': '' })
            self.render()
            break
          case 'column-remove':
            self.config.rows[rowId].columns.splice(columnId, 1)
            if (!self.config.rows[rowId].columns.length) {
              self.config.rows.splice(rowId, 1)
            }
            self.render()
            break
          case 'row-add':
            self.config.rows.push({ 'columns': [{ 'title': '' }] })
            self.render()
            break
        }
      })
    }
  }
}

FramelixInit.late.push(MyselfBlockLayoutEditor.initLate)