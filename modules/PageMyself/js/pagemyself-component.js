class PageMyselfComponent {

  /**
   * Instances
   * @type {Object<string, PageMyselfComponent>}
   */
  static instances = {}

  /**
   * The block id
   * @type {number}
   */
  id

  /**
   * The block container
   * @type {Cash}
   */
  container

  /**
   * The tinymce editor instance, if enableTextEditor has been called
   */
  editor

  /**
   * Constructor
   * @param {number} id
   */
  constructor (id) {
    PageMyselfComponent.instances[id] = this
    this.id = id
    this.container = $('.component-block').filter('[data-id=\'' + id + '\']')
  }

  /**
   * Initialize the block
   * @param {Object|Array=} params Parameters passed from the backend
   * @returns {Promise<void>}
   */
  async init (params) {

  }

  /**
   * Disable editing of this block
   * @returns {Promise<void>}
   */
  async disableEditing () {
    this.container.attr('data-editing', '0')
    if (this.editor) this.editor.destroy()
  }

  /**
   * Enable editing of this block
   * @returns {Promise<void>}
   */
  async enableEditing () {
    this.container.attr('data-editing', '1')
  }

  /**
   * Enable live text editor on given container
   * @param {Cash} container
   * @returns {Promise<void>}
   */
  async enableTextEditor (container) {
    if (!container.attr('data-id')) {
      console.error('The container needs a data-id attribute in order to save text attached to this container', container)
      return
    }
    const self = this
    await FramelixDom.includeResource(window.top.eval('PageMyselfPageEditor.config.tinymceUrl'), 'tinymce')
    await FramelixDom.includeResource(window.top.eval('PageMyselfPageEditor.config.tinymcePluginsUrl'), function () {
      return !!(tinymce && tinymce.PluginManager.get('pagemyself'))
    })

    let fontSizes = []
    for (let i = 0.6; i <= 8; i += 0.2) {
      fontSizes.push(i.toFixed(1) + 'rem')
    }
    let font_formats = ''
    const fonts = {
      'Andale Mono': { 'name': 'andale mono,times,sans-serif' },
      'Arial': { 'name': 'arial,helvetica,sans-serif' },
      'Arial Black': { 'name': 'arial black,avant garde,sans-serif' },
      'Book Antiqua': { 'name': 'book antiqua,palatino,sans-serif' },
      'Comic Sans MS': { 'name': 'comic sans ms,sans-serif' },
      'Courier New': { 'name': 'courier new,courier,sans-serif' },
      'Georgia': { 'name': 'georgia,palatino,sans-serif' },
      'Helvetica': { 'name': 'helvetica,sans-serif' },
      'Impact': { 'name': 'impact,chicago,sans-serif' },
      'Symbol': { 'name': 'symbol,sans-serif' },
      'Tahoma': { 'name': 'tahoma,arial,helvetica,sans-serif' },
      'Terminal': { 'name': 'terminal,monaco,sans-serif' },
      'Times New Roman': { 'name': 'times new roman,times,sans-serif' },
      'Trebuchet MS': { 'name': 'trebuchet ms,geneva,sans-serif' },
      'Verdana': { 'name': 'verdana,geneva,sans-serif' },
      'Webdings': { 'name': 'webdings' },
      'Wingdings': { 'name': 'wingdings,zapf dingbats' }
    }
    for (let key in fonts) {
      const row = fonts[key]
      font_formats += key + '=' + row.name + ';'
    }
    tinymce.init({
      'font_formats': font_formats,
      font_size_formats: fontSizes.join(' '),
      language: ['en', 'de'].indexOf(FramelixLang.lang) > -1 ? FramelixLang.lang : 'en',
      target: container[0],
      menubar: false,
      inline: true,
      valid_elements: '*[*]',
      plugins: ['image', 'link', 'media', 'table', 'advlist', 'lists', 'code', '-pagemyself'],
      file_picker_callback: async function (callback, value, meta) {
        const modal = await self.apiRequestInModal('textEditorMediaBrowser')
        modal.bodyContainer.on('change', '.mediabrowser-file[data-url]', function () {
          const checked = $(this).find('input:checked')
          if (checked.length) {
            callback($(this).attr('data-url'))
            modal.destroy()
          }
        })
      },
      toolbar: 'pagemyself-save-text pagemyself-templates | bold italic underline strikethrough | pagemyself-cancel-text | fontfamily fontsize lineheight | alignleft aligncenter alignright alignjustify | image media pageembed link | forecolor backcolor removeformat | outdent indent | numlist bullist checklist | table  | code',
      powerpaste_word_import: 'clean',
      powerpaste_html_import: 'clean',
      image_dimensions: false,
      setup: function (instance) {
        instance.pagemyselfComponent = self
        self.editor = instance
        instance.on('init', function (e) {
          instance.initialContent = instance.getContent()
        })
      }
    })
  }

  /**
   * Make an api request
   * @param {string} action
   * @param {Object=} params
   * @returns {Promise<any>}
   */
  async apiRequest (action, params) {
    const pageMyself = window.top.eval('PageMyself')
    return FramelixApi.callPhpMethod(pageMyself.config.componentApiRequestUrl, {
      'action': action,
      'componentBlockId': this.id,
      'params': params
    })
  }

  /**
   * Make an api request and display result in modal window
   * @param {string} action
   * @param {Object=} params
   * @returns {Promise<FramelixModal>}
   */
  async apiRequestInModal (action, params) {
    const modal = window.top.eval('FramelixModal')
    const pageMyself = window.top.eval('PageMyself')
    return modal.callPhpMethod(pageMyself.config.componentApiRequestUrl, {
      'action': action,
      'componentBlockId': this.id,
      'params': params
    }, { maxWidth: 900 })
  }
}