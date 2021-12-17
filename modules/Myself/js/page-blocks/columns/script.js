class MyselfPageBlocksColumns extends MyselfPageBlocks {
  /**
   * Initialize the block
   */
  initBlock () {
    const self = this
    const medias = self.blockContainer.find('.myself-pageblocks-columns-background-media')

    function updateMediaDimensions () {
      medias.each(function () {
        const column = $(this).closest('.myself-pageblocks-columns-column')
        $(this).width(column.width())
        $(this).height(column.height())
      })
    }

    FramelixDom.addChangeListener(this.constructor.name, function () {
      updateMediaDimensions()
    })
    let resizeTo = null
    $(window).on('resize', function () {
      if (resizeTo) return
      resizeTo = setTimeout(function () {
        resizeTo = null
        updateMediaDimensions()
      }, 100)
    })
  }
}