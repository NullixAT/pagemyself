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
    if (!this.images.length) {
      return
    }

    this.gotoImage(this.index)

    const thumbsContainer = self.container.find('.slideshow-thumbs')
    if (params.thumbnails) {
      FramelixIntersectionObserver.onGetVisible(this.imageContainer, function () {
        // implement thumbs
        for (let i = 0; i < self.images.length; i++) {
          const row = self.images[i]
          thumbsContainer.append('<div data-index="' + i + '" class="' + (i === self.index ? 'slideshow-thumb-active' : '') + '" data-id="' + row.id + '"><div data-background-image="' + row.url + '"></div></div>')
        }
      })
    } else {
      thumbsContainer.remove()
    }

    this.container.on('click', '.slideshow-btn', function () {
      self.gotoImage(self.index + parseInt($(this).attr('data-dir')))
    })
    this.imageContainer.on('click', '.slideshow-image-inner[data-background-image-original]', function () {
      window.open($(this).attr('data-background-image-original'))
    })
    // on any swipe left/right we close as well
    this.imageContainer.on('swiped-left', function () {
      self.gotoImage(self.index + 1)
    })
    this.imageContainer.on('swiped-right', function () {
      self.gotoImage(self.index - 1)
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
    const thumbsContainer = this.container.find('.slideshow-thumbs')
    const activeImage = thumbsContainer.find('[data-index]').removeClass('slideshow-thumb-active').filter('[data-index="' + this.index + '"]').addClass('slideshow-thumb-active')
    if (activeImage.length) {
      try {
        activeImage[0].scrollIntoView({ inline: 'center', 'block': 'nearest' })
      } catch (e) {}
    }
    this.imageContainer.empty()
    this.imageContainer.append(`<div class="slideshow-image-inner" data-visible="1"><div class="framelix-loading"></div></div>`)
    this.imageContainer.append(`<div class="slideshow-image-inner" data-background-image="${row.url}"></div>`)
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
    if (!this.images.length) {
      this.container.find('.slideshow-image').html('<div class="slideshow-image-inner" data-visible="1"><button class="framelix-button open-block-settings">' + FramelixLang.get('__pagemyself_component_open_settings__') + '</button></div>')
    }
  }
}