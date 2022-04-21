class PageBlockJumpMark extends PageBlock {

  /**
   * Disable editing of this block
   * @returns {Promise<void>}
   */
  async disableEditing () {
    this.container.empty()
  }

  /**
   * Enable editing of this block
   * @returns {Promise<void>}
   */
  async enableEditing () {
    this.container.html(FramelixLang.get('__pagemyself_pageblock_jumpmark_link__') + ':<br/>' + window.location.href.replace(/\#.*/ig, '') + '#block-' + this.id)
  }

}