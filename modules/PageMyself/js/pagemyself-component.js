class PageMyselfComponent {

  /**
   * Instances
   * @type {Object<string, PageMyselfComponent>}
   */
  static instances = {}

  /**
   * Additional color map for tinymce to list theme colors as well
   * @type {string[]}
   */
  static additionalColorMap = []

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
    let colorMap = PageMyselfComponent.additionalColorMap.concat([
      '#bfedd2', 'Light Green',
      '#fbeeb8', 'Light Yellow',
      '#f8cac6', 'Light Red',
      '#eccafa', 'Light Purple',
      '#c2e0f4', 'Light Blue',

      '#2dc26b', 'Green',
      '#f1c40f', 'Yellow',
      '#e03e2d', 'Red',
      '#b96ad9', 'Purple',
      '#3598db', 'Blue',

      '#169179', 'Dark Turquoise',
      '#e67e23', 'Orange',
      '#ba372a', 'Dark Red',
      '#843fa1', 'Dark Purple',
      '#236fa1', 'Dark Blue',

      '#ecf0f1', 'Light Gray',
      '#ced4d9', 'Medium Gray',
      '#95a5a6', 'Gray',
      '#7e8c8d', 'Dark Gray',
      '#34495e', 'Navy Blue',

      '#000', 'Black',
      '#fff', 'White'
    ])
    tinymce.init({
      'font_formats': font_formats,
      font_size_formats: fontSizes.join(' '),
      language: ['en', 'de'].indexOf(FramelixLang.lang) > -1 ? FramelixLang.lang : 'en',
      target: container[0],
      menubar: false,
      inline: true,
      valid_elements: '*[*]',
      color_map: colorMap,
      plugins: ['image', 'link', 'media', 'table', 'advlist', 'lists', 'code', '-pagemyself'],
      file_picker_callback: async function (callback, value, meta) {
        /** @type {PageMyselfFormFieldMediaBrowser}  */
        const mediaBrowser = window.top.eval('new PageMyselfFormFieldMediaBrowser()')
        await mediaBrowser.render()
        await mediaBrowser.reload()
        mediaBrowser.modal.destroyed.then(function () {
          $(document).off('change.tinymcefile')
        })
        mediaBrowser.container.on(FramelixFormField.EVENT_CHANGE_USER, function (ev) {
          const id = mediaBrowser.getValue()
          if (!id || !mediaBrowser.modal) return
          const fileEntry = mediaBrowser.modal.bodyContainer.find('.mediabrowser-file[data-id="' + id + '"]')
          callback(fileEntry.attr('data-url'))
          mediaBrowser.modal.destroy()
        })
      },
      toolbar: 'pagemyself-save-text pagemyself-templates | bold italic underline strikethrough | pagemyself-cancel-text | fontfamily fontsize lineheight blocks | alignleft aligncenter alignright alignjustify | image media pageembed link | forecolor backcolor removeformat | outdent indent | numlist bullist checklist | table  | code',
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