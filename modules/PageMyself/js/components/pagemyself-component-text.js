class PageMyselfComponentText extends PageMyselfComponent {

  /**
   * Initialize the block
   * @returns {Promise<void>}
   */
  async init () {

  }

  /**
   * Enable editing of this block
   * @returns {Promise<void>}
   */
  async enableEditing () {
    await super.enableEditing()
    this.enableTextEditor(this.container.find('.pagemyself-component-text-text'))
  }
}