'use strict';

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class SlideshowPageBlocksSlideshow extends MyselfPageBlocks {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "images", void 0);

    _defineProperty(this, "slideshowContainer", void 0);

    _defineProperty(this, "imageOuterContainer", void 0);

    _defineProperty(this, "imageContainer", void 0);

    _defineProperty(this, "titleContainer", void 0);

    _defineProperty(this, "btnLeft", void 0);

    _defineProperty(this, "btnRight", void 0);

    _defineProperty(this, "currentIndex", 0);

    _defineProperty(this, "fadeTo", void 0);
  }

  /**
   * Initialize the block
   */
  initBlock() {
    const self = this;
    self.images = this.config.images;
    self.slideshowContainer = this.blockContainer.find('.slideshow-pageblocks-slideshow-container');
    self.imageOuterContainer = this.blockContainer.find('.slideshow-pageblocks-slideshow-image-outer');
    self.imageContainer = this.blockContainer.find('.slideshow-pageblocks-slideshow-image');
    self.titleContainer = this.blockContainer.find('.slideshow-pageblocks-slideshow-title');
    self.btnLeft = this.blockContainer.find('.slideshow-pageblocks-slideshow-left');
    self.btnRight = this.blockContainer.find('.slideshow-pageblocks-slideshow-right');
    self.updateContainerHeight();
    let resizeTo = null;
    $(window).on("resize", function () {
      if (resizeTo) return;
      resizeTo = setTimeout(function () {
        self.updateContainerHeight();
        resizeTo = null;
      }, 500);
    });
    let autoInterval = null;
    this.imageOuterContainer.on('keydown click swiped-left swiped-right', function (ev) {
      clearInterval(autoInterval);
      let dir = 1;

      if (ev.type.substr(0, 6) === 'swiped') {
        dir = ev.type === 'swiped-right' ? -1 : 1;
      } else if (ev.type !== 'keydown' && $(ev.target).hasClass('slideshow-pageblocks-slideshow-left') || ev.type === 'keydown' && ev.key === 'ArrowLeft') {
        dir = -1;
      } else if (ev.type !== 'keydown' && $(ev.target).hasClass('slideshow-pageblocks-slideshow-right') || ev.type === 'keydown' && ev.key === 'ArrowRight') {
        dir = 1;
      } else {
        return;
      }

      let newIndex = self.currentIndex + dir;
      if (newIndex < 0) newIndex = self.images.length - 1;
      if (newIndex > self.images.length - 1) newIndex = 0;
      self.showImage(newIndex);
    });
    self.showImage(self.currentIndex);

    if (this.config.automatic) {
      autoInterval = setInterval(function () {
        let newIndex = self.currentIndex + 1;
        if (newIndex > self.images.length - 1) newIndex = 0;
        self.showImage(newIndex);
      }, 5000);
    }
  }
  /**
   * Update container height
   */


  updateContainerHeight() {
    let maxImageHeight = 0;
    let containerWidth = this.slideshowContainer.width();
    let containerHeight = window.innerHeight * 0.7;

    for (let i = 0; i < this.images.length; i++) {
      const imageDim = this.images[i].sizes.original.dimensions;
      const h = containerWidth * (1 / imageDim.w * imageDim.h);
      if (h > maxImageHeight) maxImageHeight = h;
    }

    if (maxImageHeight < containerHeight) containerHeight = maxImageHeight;
    this.imageContainer.height(containerHeight);
  }
  /**
   * Show image
   * @param {number} index
   */


  showImage(index) {
    this.currentIndex = index;
    const self = this;
    const imageData = this.images[index];
    if (!imageData) return;
    const containerWidth = this.blockContainer.width(); // find best-fit image to fit into container and screen height

    let useSrc = null;
    let maxHeight = Math.round(window.innerHeight * 0.8);

    for (let dimKey in imageData.sizes) {
      const dimRow = imageData.sizes[dimKey];
      useSrc = dimRow.url;

      if (containerWidth <= dimRow.dimensions.w || dimRow.dimensions.h >= maxHeight) {
        break;
      }
    }

    self.titleContainer.html('<div class="slideshow-pageblocks-slideshow-count">' + (index + 1) + '/' + self.images.length + '</div>');

    if (Myself.isEditModeInner()) {
      self.titleContainer.append($(`<div class="myself-live-editable-text" data-id="${imageData.id}" data-property-name="title" contenteditable="true" data-multiline="1"></div>`).text(imageData.title));
    } else {
      self.titleContainer.append($(`<div class="myself-live-editable-text"></div>`).text(imageData.title));
    }

    FramelixIntersectionObserver.onGetVisible(self.imageOuterContainer, function () {
      self.slideshowContainer.addClass('slideshow-pageblocks-slideshow-loading');
      clearTimeout(self.fadeTo);
      self.fadeTo = setTimeout(function () {
        const img = new Image();
        img.src = useSrc;
        self.imageContainer.css('background-image', 'url(' + useSrc + ')');

        img.onload = function () {
          self.slideshowContainer.removeClass('slideshow-pageblocks-slideshow-loading');
        };
      }, 500);
    });
  }

}