class PageBlockText extends PageBlock {

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
    this.enableTextEditor(this.container.children())
  }
}