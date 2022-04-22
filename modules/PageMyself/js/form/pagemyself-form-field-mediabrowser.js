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
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  async setValue (value, isUserChange = false) {
    this.hiddenFieldsContainer.empty()
    if (value) {
      const self = this
      if (typeof value !== 'object') value = [value]
      for (let i in value) {
        self.hiddenFieldsContainer.append('<input type="hidden" name="' + self.name + (self.multiple ? '[]' : '') + '" value="' + value[i] + '">')
      }
    }
    this.hiddenFieldsContainer.append(FramelixLang.get('__pagemyself_mediabrowser_files_selected__', [FramelixObjectUtils.countKeys(value)]))
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
    const request = FramelixRequest.request('post', this.apiUrl, null, formData, true)
    await request.finished
    const result = await request.getJson()
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
    this.modal = await FramelixModal.callPhpMethod(self.apiUrl, {
      'action': 'browser',
      'selectedValues': this.getValue()
    }, {
      maxWidth: 900,
      noAnimation: true
    })
    this.modal.destroyed.then(function () {
      self.modal = null
    })
    let replaceId = ''
    this.modal.bodyContainer.on('change', 'input[type="file"]', async function (ev) {
      if (!ev.target.files) return
      for (let i = 0; i < ev.target.files.length; i++) {
        await self.uploadFile(ev.target.files[i], replaceId)
        replaceId = ''
      }
      self.reload()
    })
    this.modal.bodyContainer.on('change', 'input[type="checkbox"]', async function (ev) {
      const arr = []
      if (!self.multiple) {
        if (this.checked) arr.push($(this).closest('.mediabrowser-file').attr('data-id'))
      } else {
        self.modal.bodyContainer.find('input:checked').each(function () {
          arr.push($(this).closest('.mediabrowser-file').attr('data-id'))
        })
      }
      self.setValue(arr)
      if (!self.multiple) {
        if (self.modal) self.modal.destroy()
      }
    })
    this.modal.bodyContainer.on('click', '.delete-file', async function (ev) {
      ev.stopPropagation()
      if (await FramelixModal.confirm('__framelix_sure__').confirmed) {
        await FramelixApi.callPhpMethod(self.apiUrl, {
          'action': 'deleteFile',
          'id': $(this).closest('.mediabrowser-file').attr('data-id')
        })
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
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    const self = this
    await super.renderInternal()
    const btn = $('<button class="framelix-button framelix-button-primary" data-icon-left="perm_media" type="button">' + FramelixLang.get('__pagemyself_mediabrowser_open__') + '</button>')
    this.hiddenFieldsContainer = $('<div class="pagemyself-media-browser"></div>')
    this.field.append(btn)
    this.field.append(this.hiddenFieldsContainer)

    btn.on('click', async function () {
      self.reload()
    })
    await this.setValue(this.defaultValue)
  }
}

FramelixFormField.classReferences['PageMyselfFormFieldMediaBrowser'] = PageMyselfFormFieldMediaBrowser