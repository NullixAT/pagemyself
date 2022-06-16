/**
 * Media browser
 */
class PageMyselfFormFieldMediaBrowser extends FramelixFormField {

  /**
   * Signed url for api calls
   * @type {string}
   */
  apiUrl

  /**
   * Modal window instance
   * @type {FramelixModal|null}
   */
  modal = null

  /**
   * Allowed file extensions
   * @type {string[]|null}
   */
  allowedExtensions = null

  /**
   * Allow multiple select
   * @type {boolean}
   */
  multiple = false

  /**
   * Current folder
   * @type {number|null}
   */
  currentFolder = null

  /**
   * Get url to id
   * @param {string|number} id
   * @returns {Promise<string>}
   */
  static async getUrlToId (id) {
    return FramelixApi.callPhpMethod({
      phpMethod: 'Framelix\\PageMyself\\Form\\Field\\MediaBrowser',
      'action': ''
    }, { 'action': 'getUrl', 'id': id })
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  async setValue (value, isUserChange = false) {
    if (isUserChange && this.modal) {
      this.modal.footerContainer.find('button').removeClass('hidden')
    }
    this.hiddenFieldsContainer.empty()
    if (value) {
      const self = this
      if (typeof value !== 'object') value = [value]
      for (let i in value) {
        self.hiddenFieldsContainer.append('<input type="hidden" name="' + self.name + (self.multiple ? '[]' : '') + '" value="' + value[i] + '">')
      }
    }
    const count = FramelixObjectUtils.countKeys(value)
    let html = ''
    if (count > 0) {
      html = '<button class="framelix-button framelix-button-small mediabrowser-deselect-files-btn" data-icon-left="clear" title="__pagemyself_mediabrowser_deselect_files__"></button> &nbsp; ' + html
    }
    html += FramelixLang.get('__pagemyself_mediabrowser_files_selected__', [count])
    this.hiddenFieldsContainer.append(html)
    this.triggerChange(this.field, isUserChange)
  }

  /**
   * Get value for this field
   * @return {string[]|string|null}
   */
  getValue () {
    let arr = []
    this.hiddenFieldsContainer.find('input[type=\'hidden\']').each(function () {
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
    formData.append('currentFolder', this.currentFolder)
    formData.append('allowedExtensions', this.allowedExtensions)
    const result = await FramelixApi.callPhpMethod({
      phpMethod: 'Framelix\\PageMyself\\Form\\Field\\MediaBrowser',
      'action': 'upload'
    }, formData)
    if (result === true) {
      FramelixToast.success('__framelix_saved__')
    } else {
      FramelixToast.error(FramelixLang.get('__pagemyself_mediabrowser_upload_failed__', [result + ':' + (this.allowedExtensions ? this.allowedExtensions.join(', ') : '*')]))
    }
  }

  /**
   * Reload
   * @returns {Promise<void>}
   */
  async reload () {
    const self = this
    if (this.modal) await this.modal.destroy()
    this.modal = await FramelixModal.callPhpMethod({
      phpMethod: 'Framelix\\PageMyself\\Form\\Field\\MediaBrowser',
      'action': ''
    }, {
      'action': 'browser',
      'selectedValues': this.getValue(),
      'currentFolder': self.currentFolder
    }, {
      maxWidth: 900,
      noAnimation: true,
      footerContent: $('<button class="framelix-button framelix-button-success hidden" data-icon-left="check">' + FramelixLang.get('__pagemyself_mediabrowser_accept_selection__') + '</button>').on('click', function () {
        self.modal?.destroy()
      })

    })
    this.modal.destroyed.then(function () {
      self.modal = null
    })
    let replaceId = ''
    let lastCheckedEntry = null
    this.modal.bodyContainer.on('change', 'input[type="file"]', async function (ev) {
      if (!ev.target.files) return
      for (let i = 0; i < ev.target.files.length; i++) {
        await self.uploadFile(ev.target.files[i], replaceId)
        replaceId = ''
      }
      self.reload()
    })
    if (self.multiple) {
      // enable fast select with holding shift key
      this.modal.bodyContainer.on('click', 'input[type="checkbox"]', async function (ev) {
        const state = ev.target.checked
        const entry = $(this).closest('.mediabrowser-file, .mediabrowser-folder')
        if (lastCheckedEntry && ev.shiftKey) {
          const entryStart = lastCheckedEntry.index() > entry.index() ? entry : lastCheckedEntry
          const entryEnd = lastCheckedEntry.index() > entry.index() ? lastCheckedEntry : entry

          entryStart.nextUntil(entryEnd).add(entryStart).add(entryEnd).each(function () {
            $(this).find('input').prop('checked', state)
          })
        }
        lastCheckedEntry = entry
        const arr = []
        self.modal.bodyContainer.find('input:checked').each(function () {
          arr.push($(this).closest('.mediabrowser-file, .mediabrowser-folder').attr('data-id'))
        })
        self.setValue(arr, true)
      })
    }
    this.modal.bodyContainer.on('change', 'input[type="checkbox"]', async function (ev) {
      ev.stopPropagation()
      ev.stopImmediatePropagation()
      const arr = []
      if (!self.multiple) {
        if (this.checked) arr.push($(this).closest('.mediabrowser-file, .mediabrowser-folder').attr('data-id'))
      } else {
        self.modal.bodyContainer.find('input:checked').each(function () {
          arr.push($(this).closest('.mediabrowser-file, .mediabrowser-folder').attr('data-id'))
        })
      }
      self.setValue(arr, true)
      if (!self.multiple) {
        if (self.modal) self.modal.destroy()
      }
    })
    this.modal.bodyContainer.on('click', '.delete-file', async function (ev) {
      ev.stopPropagation()
      ev.stopImmediatePropagation()
      if (await FramelixModal.confirm('__framelix_sure__').confirmed) {
        await self.apiRequest('deleteFile', { 'id': $(this).closest('.mediabrowser-file').attr('data-id') })
        self.reload()
      }
    })
    this.modal.bodyContainer.on('click', '.delete-folder', async function (ev) {
      ev.stopPropagation()
      ev.stopImmediatePropagation()
      if (await FramelixModal.confirm('__framelix_sure__').confirmed) {
        await self.apiRequest('deleteFolder', { 'id': $(this).closest('.mediabrowser-folder').attr('data-id') })
        self.reload()
      }
    })
    this.modal.bodyContainer.on('click', '.rename-entry', async function (ev) {
      ev.stopPropagation()
      ev.stopImmediatePropagation()
      let name = await FramelixModal.prompt('__pagemyself_mediabrowser_rename__', $(this).attr('data-default')).promptResult
      if (name) {
        await self.apiRequest('renameEntry', { 'id': $(this).attr('data-id'), 'name': name.trim() })
        self.reload()
      }
    })
    this.modal.bodyContainer.on('click', '.replace-file', async function (ev) {
      ev.stopPropagation()
      const row = $(this).closest('.mediabrowser-file')
      replaceId = row.attr('data-id')
      const input = self.modal.bodyContainer.find('input[type="file"]')
      const accept = input.attr('accept')
      input.attr('accept', '.' + row.attr('data-extension'))
      self.modal.bodyContainer.find('input[type="file"]').closest('label').trigger('click')
      await Framelix.wait(200)
      input.attr('accept', accept)
    })
    this.modal.bodyContainer.on('click', '.mediabrowser-folder', async function (ev) {
      if ($(ev.target).is('input')) return
      ev.stopPropagation()
      self.currentFolder = $(this).attr('data-id')
      if (self.currentFolder === '') self.currentFolder = null
      self.reload()
    })
    this.modal.bodyContainer.on('click', 'a[data-folder-id]', async function (ev) {
      ev.stopPropagation()
      ev.preventDefault()
      self.currentFolder = $(this).attr('data-folder-id')
      if (self.currentFolder === '') self.currentFolder = null
      self.reload()
    })
    this.modal.bodyContainer.on('click', '.create-folder', async function () {
      let name = await FramelixModal.prompt('__pagemyself_mediabrowser_createfolder__', '').promptResult
      if (name) {
        self.currentFolder = await self.apiRequest('createFolder', { 'name': name.trim() })
        self.reload()
      }
    })
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    const self = this
    await super.renderInternal()
    const btn = $('<button class="framelix-button framelix-button-primary" data-icon-left="perm_media" type="button">' + FramelixLang.get('__pagemyself_mediabrowser_open__') + '</button>')
    this.hiddenFieldsContainer = $('<div></div>')
    this.hiddenFieldsContainer.on('click', '.framelix-button-small[data-icon-left]', function () {
      self.setValue(null, true)
    })
    this.field.append(btn)
    this.field.append(this.hiddenFieldsContainer)

    btn.on('click', async function () {
      self.reload()
    })
    await this.setValue(this.defaultValue)
  }

  /**
   * Api request
   * @param {string} action
   * @param {Object=} params
   * @returns {Promise<*>}
   */
  async apiRequest (action, params) {
    params = Object.assign({ 'action': action, 'currentFolder': this.currentFolder }, params || {})
    return FramelixApi.callPhpMethod({
      phpMethod: 'Framelix\\PageMyself\\Form\\Field\\MediaBrowser',
      'action': ''
    }, params)
  }
}

FramelixFormField.classReferences['PageMyselfFormFieldMediaBrowser'] = PageMyselfFormFieldMediaBrowser