/**
 * Myself Edit Class
 */
class MyselfEdit {

  /**
   * Page block edit url
   * @type {string}
   */
  static pageBlockEditUrl

  /**
   * Theme settings edit url
   * @type {string}
   */
  static themeSettingsEditUrl

  /**
   * Website settings url
   * @type {string}
   */
  static websiteSettingsEditUrl

  /**
   * Url to tinymce folder
   * @type {string}
   */
  static tinymceUrl

  /**
   * Init late
   */
  static initLate () {
    const editFrame = $('.myself-edit-frame-inner iframe')
    let editFrameWindow = editFrame[0].contentWindow
    let editFrameDoc = $(editFrameWindow.document)

    MyselfEdit.bindLiveEditableText(window)

    editFrame.on('load', function () {
      editFrameWindow = editFrame[0].contentWindow
      editFrameDoc = $(editFrameWindow.document)
      editFrameDoc[0].querySelector('html').setAttribute('data-edit-frame', '1')
      let url = new URL(editFrameWindow.location.href)
      url.searchParams.set('editMode', '1')
      window.history.pushState(null, null, url)
      MyselfEdit.bindLiveEditableText(editFrameWindow)
      MyselfEdit.bindLiveEditableWysiwyg(editFrameWindow)
    })
    $(document).on('click', '.myself-open-website-settings', async function () {
      const modal = await FramelixModal.request('post', MyselfEdit.websiteSettingsEditUrl, null, null, false, null, true)
      modal.contentContainer.addClass('myself-edit-font')
      modal.closed.then(function () {
        location.reload()
      })
    })
    $(document).on('click', '.myself-open-theme-settings', async function () {
      const modal = await FramelixModal.request('post', MyselfEdit.themeSettingsEditUrl, null, null, false, null, true)
      modal.contentContainer.addClass('myself-edit-font')
      modal.closed.then(function () {
        location.reload()
      })
    })
    $(document).on('click', '.myself-delete-page-block', async function () {
      if (!(await FramelixModal.confirm('__framelix_sure__').closed).confirmed) return
      const urlParams = {
        'action': null,
        'pageId': null,
        'pageBlockId': null,
        'pageBlockClass': null
      }
      for (let k in urlParams) {
        urlParams[k] = this.dataset[k] || null
      }
      await FramelixRequest.request('post', MyselfEdit.pageBlockEditUrl, {
        'action': 'delete',
        'pageBlockId': $(this).attr('data-page-block-id')
      })
      location.reload()
    })
    $(document).on('click', '.myself-open-layout-block-editor', async function () {
      const instance = await MyselfBlockLayoutEditor.open()
      instance.modal.closed.then(function () {
        editFrameWindow.location.reload()
      })
    })
    $(document).on(FramelixForm.EVENT_SUBMITTED, '.myself-page-block-edit-tabs', function (ev) {
      const target = $(ev.target)
      const tabContent = target.closest('.framelix-tab-content')
      const tabButton = tabContent.closest('.framelix-tabs').children('.framelix-tab-buttons').children('.framelix-tab-button[data-id=\'' + tabContent.attr('data-id') + '\']')
      tabButton.find('.myself-tab-edited').remove()
    })
  }

  /**
   * Bind live editable wysiwyg
   * @param {Window} frame
   */
  static async bindLiveEditableWysiwyg (frame) {
    const frameDoc = frame.document
    const topFrame = frame.top
    if (!frameDoc.myselfLiveEditableText) {
      frameDoc.myselfLiveEditableText = new Map()
    }
    const mediaBrowser = new MyselfFormFieldMediaBrowser()
    await frame.eval('FramelixDom').includeResource(MyselfEdit.tinymceUrl, 'tinymce')
    frame.eval('FramelixDom').addChangeListener('wysiwyg', async function () {
      $(frameDoc).find('.myself-live-editable-wysiwyg:not(.mce-content-body)').each(async function () {
        const container = frame.$(this)
        const originalContent = container.html()
        frame.tinymce.init({
          language: ['en', 'de'].indexOf(FramelixLang.lang) > -1 ? FramelixLang.lang : 'en',
          target: container[0],
          menubar: false,
          inline: true,
          plugins: 'image link media table hr advlist lists code',
          external_plugins: {
            myself: FramelixConfig.compiledFileUrls['Myself']['js']['tinymce']
          },
          file_picker_callback: async function (callback, value, meta) {
            if (!mediaBrowser.signedGetBrowserUrl) {
              mediaBrowser.signedGetBrowserUrl = (await FramelixRequest.request('get', MyselfEdit.pageBlockEditUrl + '?action=getmediabrowserurl').getJson()).content
            }
            await mediaBrowser.render()
            mediaBrowser.openBrowserBtn.trigger('click')
            mediaBrowser.modal.closed.then(function () {
              let url = null
              if (!mediaBrowser.getValue()) {
                callback('')
                return
              }
              const entry = mediaBrowser.selectedEntriesContainer.children().first()
              url = entry.attr('data-url')
              url = url.replace(/\?t=[0-9]+/g, '')
              callback(url)
            })
          },
          toolbar: 'myself-save-text myself-cancel-text | undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist checklist | table | forecolor backcolor removeformat | image media pageembed link | code',

          powerpaste_word_import: 'clean',
          powerpaste_html_import: 'clean',
          setup: function (editor) {
            editor.myself = {
              'container': container,
              'originalContent': originalContent,
              'pageBlockEditUrl': topFrame.eval('MyselfEdit').pageBlockEditUrl
            }
          }
        })
      })
    })
  }

  /**
   * Bind live editable text
   * @param {Window} frame
   */
  static bindLiveEditableText (frame) {
    const frameDoc = frame.document
    const topFrame = frame.top
    if (!frameDoc.myselfLiveEditableText) {
      frameDoc.myselfLiveEditableText = new Map()
    }
    const configMap = frameDoc.myselfLiveEditableText
    $(frameDoc).on('focusin', '.myself-live-editable-text', function () {
      let config = configMap.get(this)
      if (config) {
        return
      }
      config = {}
      configMap.set(this, config)
      const container = frame.$(this)
      const originalContent = container[0].innerText
      config.saveBtn = frame.$(`<button class="framelix-button framelix-button-success framelix-button-small myself-editable-text-save-button" data-icon-left="save" title="__framelix_save__"></button>`)
      config.saveBtn.on('click', async function () {
        Framelix.showProgressBar(-1)
        await FramelixRequest.request('post', topFrame.eval('MyselfEdit').pageBlockEditUrl, { 'action': 'save-editable-content' }, {
          'storableId': container.attr('data-id'),
          'propertyName': container.attr('data-property-name'),
          'arrayKey': container.attr('data-array-key'),
          'content': container[0].innerText
        })
        Framelix.showProgressBar(null)
        FramelixToast.success('__framelix_saved__')
      })
      config.cancelBtn = frame.$(`<button class="framelix-button framelix-button-small myself-editable-text-cancel-button" title="__framelix_cancel__" data-icon-left="clear"></button>`)
      config.cancelBtn.on('click', async function () {
        container[0].innerText = originalContent
      })
      config.popup = frame.eval('FramelixPopup').showPopup(container, frame.$('<div>').append(config.saveBtn).append(config.cancelBtn), { closeMethods: 'manual' })
    }).on('change input blur paste', '.myself-live-editable-text', function (ev) {
      ev.stopPropagation()
      let config = configMap.get(this)
      if (!config) {
        return
      }
      const container = $(this)
      // remove all styles and replace not supported elements
      if (container.attr('data-multiline') !== '1') {
        const newText = this.innerText.replace(/[\r\n]/g, '')
        if (newText !== this.innerText) {
          frame.eval('FramelixToast').error('__myself_storable_liveedit_nomultiline__')
          this.innerText = this.innerText.replace(/[\r\n]/g, '')
        }
      }
      if (ev.type === 'focusout' || ev.type === 'blur') {
        setTimeout(function () {
          config.popup.destroy()
          configMap.delete(container[0])
        }, 100)
      }
    })
  }
}

FramelixInit.late.push(MyselfEdit.initLate)