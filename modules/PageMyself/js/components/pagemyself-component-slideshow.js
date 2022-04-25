class PageMyselfComponentSlideshow extends PageMyselfComponent {

  /**
   * Current image index
   * @type {number}
   */
  index = 0

  /**
   * Existing images
   * @type {Object[]}
   */
  images = []

  /**
   * The image container
   * @type {Cash}
   */
  imageContainer

  /**
   * Initialize the block
   * @param {Object|Array=} params Parameters passed from the backend
   * @returns {Promise<void>}
   */
  async init (params) {
    const self = this
    this.imageContainer = this.container.find('.slideshow-image')
    this.images = params.images
    if (!this.images.length) return

    this.gotoImage(this.index)

    FramelixIntersectionObserver.onGetVisible(this.imageContainer, function () {
      // implement thumbs
      const container = self.container.find('.slideshow-thumbs')
      for (let i = 0; i < self.images.length; i++) {
        container.append('<div data-index="' + i + '"><div data-background-image="' + self.images[i].url + '"></div></div>')
      }
    })

    this.container.on('click', '.slideshow-btn', function () {
      self.gotoImage(self.index + parseInt($(this).attr('data-dir')))
    })
    this.imageContainer.on('click', '.slideshow-image-inner[data-background-image-original]', function () {
      window.open($(this).attr('data-background-image-original'))
    })
    // on any swipe left/right we close as well
    this.imageContainer.on('swiped-left', function () {
      self.gotoImage(self.index - 1)
    })
    this.imageContainer.on('swiped-right', function () {
      self.gotoImage(self.index + 1)
    })
    this.container.on('click', '.slideshow-thumbs > div', function () {
      self.gotoImage(parseInt($(this).attr('data-index')))
    })
  }

  /**
   * Goto image
   * @param {number} index
   */
  gotoImage (index) {
    const row = this.images[this.getFixedIndex(index)]
    const rowPrev = this.images[this.getFixedIndex(index - 1)]
    const rowNext = this.images[this.getFixedIndex(index + 1)]
    this.index = this.getFixedIndex(index)
    this.imageContainer.empty()
    this.imageContainer.append(`<div class="slideshow-image-inner" data-visible="1"><div class="framelix-loading"></div></div>`)
    this.imageContainer.append(`<div class="slideshow-image-inner" data-background-image="${rowPrev.url}"></div>`)
    this.imageContainer.append(`<div class="slideshow-image-inner" data-background-image="${rowNext.url}"></div>`)
    this.imageContainer.append(`<div class="slideshow-image-inner" data-background-image="${row.url}" data-visible="1"></div>`)
  }

  /**
   * Get existing index if given index is out or range
   * @param {number} index
   * @returns {number}
   */
  getFixedIndex (index) {
    if (index < 0) index = this.images.length - 1
    if (index >= this.images.length) index = 0
    return index
  }

  /**
   * Enable editing of this block
   * @returns {Promise<void>}
   */
  async enableEditing () {
    await super.enableEditing()
  }
}