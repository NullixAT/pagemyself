class SlideshowPageBlocksSlideshow extends MyselfPageBlocks {

  /**
   * Array of all images
   * @type {Object[]}
   */
  images

  /** @type {Cash} */
  imageOuterContainer

  /** @type {Cash} */
  imageContainer

  /** @type {Cash} */
  titleContainer

  /** @type {Cash} */
  btnLeft

  /** @type {Cash} */
  btnRight

  /** @type {number} */
  currentIndex = 0

  /** @type {number} */
  fadeTo

  /**
   * Initialize the block
   */
  initBlock () {
    const self = this
    self.images = this.config.images
    self.imageOuterContainer = this.blockContainer.find('.slideshow-pageblocks-slideshow-image-outer')
    self.imageContainer = this.blockContainer.find('.slideshow-pageblocks-slideshow-image')
    self.titleContainer = this.blockContainer.find('.slideshow-pageblocks-slideshow-title')
    self.btnLeft = this.blockContainer.find('.slideshow-pageblocks-slideshow-left')
    self.btnRight = this.blockContainer.find('.slideshow-pageblocks-slideshow-right')
    self.imageContainer.height(Math.round(window.innerHeight * 0.5))
    if (screen?.orientation?.addEventListener) {
      screen.orientation.addEventListener('change', function () {
        self.imageContainer.height(Math.round(window.innerHeight * 0.5))
      })
    }

    this.imageOuterContainer.on('keydown click swiped-left swiped-right', function (ev) {
      let dir = 1
      if (ev.type.substr(0, 6) === 'swiped') {
        dir = ev.type === 'swiped-right' ? -1 : 1
      } else if ((ev.type !== 'keydown' && $(ev.target).hasClass('slideshow-pageblocks-slideshow-left')) || (ev.type === 'keydown' && ev.key === 'ArrowLeft')) {
        dir = -1
      } else if ((ev.type !== 'keydown' && $(ev.target).hasClass('slideshow-pageblocks-slideshow-right')) || (ev.type === 'keydown' && ev.key === 'ArrowRight')) {
        dir = 1
      } else {
        return
      }
      let newIndex = self.currentIndex + dir
      if (newIndex < 0) newIndex = self.images.length - 1
      if (newIndex > self.images.length - 1) newIndex = 0
      self.showImage(newIndex)
    })
    self.showImage(self.currentIndex)
  }

  /**
   * Show image
   * @param {number} index
   */
  showImage (index) {
    this.currentIndex = index
    const self = this
    const imageData = this.images[index]
    if (!imageData) return
    const containerWidth = this.blockContainer.width()
    // find best-fit image to fit into container and screen height
    let useSrc = null
    let maxHeight = Math.round(window.innerHeight * 0.8)
    for (let dimKey in imageData.sizes) {
      const dimRow = imageData.sizes[dimKey]
      useSrc = dimRow.url
      if (containerWidth <= dimRow.dimensions.w || dimRow.dimensions.h >= maxHeight) {
        break
      }
    }
    self.titleContainer.html('<div class="slideshow-pageblocks-slideshow-count">' + (index + 1) + '/' + (self.images.length) + '</div>')
    if (Myself.isEditModeInner()) {
      self.titleContainer.append($(`<div class="myself-live-editable-text" data-id="${imageData.id}" data-property-name="title" contenteditable="true" data-multiline="1"></div>`).text(imageData.title))
    } else {
      self.titleContainer.append($(`<div class="myself-live-editable-text"></div>`).text(imageData.title))
    }
    FramelixIntersectionObserver.onGetVisible(self.imageOuterContainer, function () {
      self.blockContainer.addClass('slideshow-pageblocks-slideshow-loading')
      clearTimeout(self.fadeTo)
      self.fadeTo = setTimeout(function () {
        const img = new Image()
        img.src = useSrc
        self.imageContainer.css('background-image', 'url(' + useSrc + ')')
        img.onload = function () {
          self.blockContainer.removeClass('slideshow-pageblocks-slideshow-loading')
        }
      }, 500)
    })
  }
}