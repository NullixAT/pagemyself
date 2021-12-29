/**
 * Media browser
 */
class MyselfFormFieldMediaBrowser extends FramelixFormField {

  /**
   * Allowed file extensions
   * @type {string[]|null}
   */
  allowedExtensions = null

  /**
   * Signed url for the php call to open the browser content
   * @type {string}
   */
  signedGetBrowserUrl

  /**
   * The current modal
   * @type {FramelixModal}
   */
  modal

  /**
   * Open browser btn
   * @type {Cash}
   */
  openBrowserBtn

  /**
   * Browser content container
   * @type {Cash}
   */
  browserContent

  /**
   * Selected info container
   * @type {Cash}
   */
  selectedInfoContainer

  /**
   * Selected entries container
   * @type {Cash}
   */
  selectedEntriesContainer

  /**
   * Unselected entries container
   * @type {Cash}
   */
  unselectedEntriesContainer

  /**
   * Allow multiple select
   * @type {boolean}
   */
  multiple = false

  /**
   * If true, when a user select a folder, all files inside will be unfolded and added to the selection list
   * @type {boolean}
   */
  unfoldSelectedFolders = false

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  async setValue (value, isUserChange = false) {
    this.selectedInfoContainer.empty()
    if (value) {
      const self = this
      if (typeof value !== 'object') value = [value]
      for (let i in value) {
        self.selectedInfoContainer.append('<input type="hidden" name="' + self.name + (self.multiple ? '[]' : '') + '" value="' + value[i] + '">')
      }
    }
    this.selectedInfoContainer.append(FramelixLang.get('__myself_mediabrowser_files_selected__', [FramelixObjectUtils.countKeys(value)]))
    this.triggerChange(this.field, isUserChange)
  }

  /**
   * Get value for this field
   * @return {string[]|string|null}
   */
  getValue () {
    let arr = []
    this.selectedInfoContainer.find('input[type=\'hidden\']').each(function () {
      arr.push(this.value)
    })
    if (!arr.length) return null
    if (!this.multiple) return arr[0]
    return arr
  }

  /**
   * Upload file
   * @param {File} file
   * @param {string=} replaceId The id to replace with
   * @return {Promise<void>}
   */
  async uploadFile (file, replaceId) {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('replaceId', replaceId || '')
    const request = FramelixRequest.request('post', this.signedGetBrowserUrl, null, formData, true)
    await request.finished
    const result = await request.getJson()
    if (result === true) {
      FramelixToast.success('__myself_mediabrowser_uploaded__')
    } else {
      FramelixToast.error(FramelixLang.get('__myself_mediabrowser_upload_failed__', [result + ':' + (this.allowedExtensions ? this.allowedExtensions.join(', ') : '*')]))
    }
  }

  /**
   * Reload current browser context
   */
  async reload () {
    this.browserContent.html('<div class="framelix-loading"></div>')
    const selectedIds = this.getValue()
    this.browserContent.html(await FramelixApi.callPhpMethod(this.signedGetBrowserUrl, {
      'selectedIds': selectedIds
    }))
    this.selectedEntriesContainer = this.browserContent.find('.myself-media-browser-entries[data-type=\'selected\']')
    this.unselectedEntriesContainer = this.browserContent.find('.myself-media-browser-entries[data-type=\'unselected\']')
    await FramelixDom.includeCompiledFile('Framelix', 'js', 'sortablejs', 'Sortable')
    new Sortable(this.selectedEntriesContainer[0])
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    const self = this
    await super.renderInternal()
    this.openBrowserBtn = $('<button class="framelix-button framelix-button-primary" data-icon-left="perm_media" type="button">' + FramelixLang.get('__myself_open_media_browser__') + '</button>')
    this.selectedInfoContainer = $('<div class="myself-media-browser"></div>')
    this.field.append(this.openBrowserBtn)
    this.field.append(this.selectedInfoContainer)

    let replaceId = null
    this.openBrowserBtn.on('click', async function () {
      self.browserContent = $('<div class="myself-media-browser">')
      const buttonsRow = $('<div></div>')
      const saveBtn = $(`<button class="framelix-button framelix-button-success" data-icon-left="check"></button>`).text(FramelixLang.get('__myself_mediabrowser_accept__')).on('click', function () {
        let values = []
        self.selectedEntriesContainer.children(function () {
          values.push($(this).attr('data-id'))
        })
        if (!self.multiple) {
          values = values.shift()
        }
        self.setValue(values)
        self.modal.destroy()
      })
      buttonsRow.append(saveBtn)
      self.modal = FramelixModal.show(self.browserContent, buttonsRow, true)
      self.modal.destroyed.then(function () {
        self.modal = null
        self.browserContent = null
      })
      self.modal.contentContainer.addClass('myself-edit-font')
      self.browserContent.toggleClass('myself-media-browser-multiple', self.multiple)
      self.browserContent.on('click', '.myself-media-browser-entry-options-icon', function (ev) {
        ev.stopPropagation()
        FramelixPopup.destroyAll()
        FramelixPopup.show(this, $(this).next('.myself-media-browser-entry-options').clone().removeClass('hidden'))
      })
      self.browserContent.on('change', '.myself-media-browser-entry-select', async function () {
        let entry = $(this).closest('.myself-media-browser-entry')
        let id = entry.attr('data-id')
        if (this.checked && self.unfoldSelectedFolders && entry.hasClass('myself-media-browser-entry-folder')) {
          this.checked = false
          entry.addClass('framelix-pulse')
          Framelix.showProgressBar(1)
          const entries = await FramelixApi.callPhpMethod(self.signedGetBrowserUrl, {
            'unfoldFolder': id
          })
          entry.removeClass('framelix-pulse')
          Framelix.showProgressBar(null)
          if (entries) {
            const entriesDiv = $('<div>').html(entries)
            const selectedDiv = self.selectedEntriesContainer
            entriesDiv.children().each(function () {
              const id = $(this).attr('data-id')
              if (!selectedDiv.children('[data-id=\'' + id + '\']').length) {
                selectedDiv.append(this)
              }
            })
          }
          return
        }
        const checked = this.checked
        if (checked) {
          if (!self.multiple) {
            self.selectedEntriesContainer.children().each(function () {
              self.unselectedEntriesContainer.append(this)
            })
          }
          self.selectedEntriesContainer.append(entry)
          if (!self.multiple) {
            saveBtn.trigger('click')
          }
        } else {
          self.unselectedEntriesContainer.append(entry)
        }
      })
      self.browserContent.on('click', '.myself-media-browser-entry-selectable', async function (ev) {
        if ($(ev.target).is('.myself-media-browser-entry-select, button, .myself-media-browser-entry-load-url')) return
        const input = $(this).find('.myself-media-browser-entry-select')
        input[0].checked = !input[0].checked
        input.trigger('change')
      })
      self.browserContent.on('click', '.myself-media-browser-entry-load-url', async function (ev) {
        const entry = $(this).closest('.myself-media-browser-entry')
        self.signedGetBrowserUrl = entry.attr('data-load-url')
        self.reload()
      })
      self.modal.container.on('dragover', async function (ev) {
        ev.preventDefault()
      })
      self.modal.container.on('drop', async function (ev) {
        ev.preventDefault()
        if (!ev.dataTransfer.files.length) return
        replaceId = null
        for (let i = 0; i < ev.dataTransfer.files.length; i++) {
          await self.uploadFile(ev.dataTransfer.files[i])
        }
        self.reload()
      })
      self.browserContent.on('click', '.myself-media-browser-entry-create-folder', async function (ev) {
        const newName = await FramelixModal.prompt(null).promptResult
        if (newName) {
          await FramelixApi.callPhpMethod($(this).attr('data-create-folder'), { 'folderName': newName })
          self.reload()
        }
      })
      self.browserContent.on('change', '.myself-media-browser-replace-file', async function (ev) {
        if (!ev.target.files) return
        for (let i = 0; i < ev.target.files.length; i++) {
          await self.uploadFile(ev.target.files[i], $(this).attr('data-replace-id'))
        }
        self.reload()
      })
      self.modal.container.on('change', '.myself-media-browser-entry-upload', async function (ev) {
        if (!ev.target.files) return
        for (let i = 0; i < ev.target.files.length; i++) {
          await self.uploadFile(ev.target.files[i])
        }
        self.reload()
      })
      $(document).off('click.mediabrowser-delete-folder').on('click.mediabrowser-delete-folder', '.myself-media-browser-entry-delete[data-delete-folder-url]', async function (ev) {
        ev.stopPropagation()
        FramelixPopup.destroyAll()
        let result = await FramelixModal.prompt('__myself_mediabrowser_delete_folder_securequestion__').promptResult
        if (result !== null && result.toLowerCase() === 'yes') {
          if (await FramelixApi.callPhpMethod($(this).attr('data-delete-folder-url'))) {
            self.reload()
          }
        }
      })
      $(document).off('click.mediabrowser-delete-file').on('click.mediabrowser-delete-file', '.myself-media-browser-entry-delete[data-delete-file-url]', async function (ev) {
        ev.stopPropagation()
        FramelixPopup.destroyAll()
        if (await FramelixModal.confirm('__framelix_sure__').confirmed) {
          if (await FramelixApi.callPhpMethod($(this).attr('data-delete-file-url'))) {
            self.reload()
          }
        }
      })
      $(document).off('click.mediabrowser-replace').on('click.mediabrowser-replace', '.myself-media-browser-entry-replace', async function (ev) {
        ev.stopPropagation()
        FramelixPopup.destroyAll()
        replaceId = $(this).attr('data-replace-id')
        const label = $('.myself-media-browser-replace-file')
        label.attr('data-replace-id', replaceId)
        label.trigger('click')
      })
      $(document).off('click.mediabrowser-rename').on('click.mediabrowser-rename', '.myself-media-browser-entry-rename', async function (ev) {
        ev.stopPropagation()
        FramelixPopup.destroyAll()
        const newName = await FramelixModal.prompt(null, $(this).attr('data-title')).promptResult
        if (newName) {
          if (await FramelixApi.callPhpMethod($(this).attr('data-rename-url'), { 'newName': newName })) {
            self.reload()
          }
        }
      })
      self.reload()
    })
    await this.setValue(this.defaultValue)
  }
}

FramelixFormField.classReferences['MyselfFormFieldMediaBrowser'] = MyselfFormFieldMediaBrowser