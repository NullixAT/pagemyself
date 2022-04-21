class PageBlock {

  /**
   * Page Block instances
   * @type {Object<string, PageBlock>}
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
   * The backend options container
   * Only isset when enableEditing() is called from the backend
   * @type {Cash}
   */
  backendOptionsContainer

  /**
   * The tinymce editor instance, if enableTextEditor has been called
   */
  editor

  /**
   * Constructor
   * @param {number} id
   */
  constructor (id) {
    PageBlock.instances[id] = this
    this.id = id
    this.container = $('.page-block').filter('[data-id=\'' + id + '\']')
  }

  /**
   * Initialize the block
   * @returns {Promise<void>}
   */
  async init () {

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

    tinymce.init({
      fontsize_formats: fontSizes.join(' '),
      language: ['en', 'de'].indexOf(FramelixLang.lang) > -1 ? FramelixLang.lang : 'en',
      target: container[0],
      menubar: false,
      inline: true,
      plugins: 'image link media table advlist lists code -pagemyself',
      style_formats: [
        {
          title: 'Button Link',
          inline: 'a',
          classes: 'framelix-button framelix-button-primary',
          attributes: { href: '#' }
        }
      ],
      // The following option is used to append style formats rather than overwrite the default style formats.
      style_formats_merge: true,
      file_picker_callback: async function (callback, value, meta) {

      },
      toolbar: 'pagemyself-save-text pagemyself-cancel-text pagemyself-jump-mark pagemyself-icons | undo redo | bold italic underline strikethrough | fontselect fontsizeselect styleselect lineheight | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist checklist | table | forecolor backcolor removeformat | image media pageembed link | code',
      powerpaste_word_import: 'clean',
      powerpaste_html_import: 'clean',
      setup: function (instance) {
        instance.pageBlock = self
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
    return FramelixApi.callPhpMethod(window.top.eval('PageMyselfPageEditor.config.apiRequestUrl'), {
      'action': action,
      'blockId': this.id,
      'params': params
    })
  }
}