class ImageGalleryPageBlocksImageGallery extends MyselfPageBlocks {

  /**
   * The modal window when opened
   * @type {FramelixModal|null}
   */
  modal = null

  /**
   * When modal opened, which image is active
   * @type {Cash|null}
   */
  activeImage = null

  /**
   * Init block
   */
  initBlock () {
    const self = this
    this.blockContainer.find('.imagegallery-pageblocks-imagegallery-image').each(function () {
      const el = $(this)
      FramelixIntersectionObserver.onGetVisible(this, function () {
        const img = new Image()
        img.src = el.attr('data-image')
        img.alt = el.attr('data-title')
        img.onload = function () {
          el.height(img.height)
        }
        el.html(img)
      })
    })
    this.blockContainer.on('click', '.imagegallery-pageblocks-imagegallery-image', function () {
      self.openModalImage($(this))
    })
  }

  /**
   * Open image in popup
   * @param {Cash} imageContainer
   */
  openModalImage (imageContainer) {
    const self = this
    const imagePopupContainer = $('<div class="imagegallery-pageblocks-imagegallery-popup"></div>')
    const image = $('<img>')
    self.activeImage = imageContainer
    image.attr('alt', imageContainer.attr('data-title'))
    image.attr('src', imageContainer.attr('data-large'))
    imagePopupContainer.append($('<div class="imagegallery-pageblocks-imagegallery-popup-title"></div>').html(imageContainer.attr('data-title')))
    imagePopupContainer.append($('<div class="imagegallery-pageblocks-imagegallery-popup-image"></div>').append(image))
    if (self.modal) {
      self.modal.bodyContainer.html(imagePopupContainer)
    } else {
      self.modal = FramelixModal.show({ bodyContent: imagePopupContainer, maximized: true })
      self.modal.destroyed.then(function () {
        self.modal = null
      })
      self.modal.container.on('keydown swiped-left swiped-right', function (ev) {
        if (ev.type === 'swiped-left' || ev.key === 'ArrowLeft') {
          const next = self.activeImage.prev('.imagegallery-pageblocks-imagegallery-image')
          if (!next.length) return
          self.openModalImage(next)
        }
        if (ev.type === 'swiped-right' || ev.key === 'ArrowRight') {
          const next = self.activeImage.next('.imagegallery-pageblocks-imagegallery-image')
          if (!next.length) return
          self.openModalImage(next)
        }
      })
    }
  }
}