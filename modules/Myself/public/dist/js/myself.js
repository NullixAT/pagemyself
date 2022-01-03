'use strict';
/**
 * Myself Module Class
 */

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class Myself {
  /**
   * Is page in edit mode (The outer frame)
   * @return {boolean}
   */
  static isEditModeOuter() {
    return $('html').attr('data-edit') === '1';
  }
  /**
   * Is page in edit mode (The inner frame)
   * @return {boolean}
   */


  static isEditModeInner() {
    return window.top !== window ? window.top.document.querySelector('html').getAttribute('data-edit') === '1' : false;
  }
  /**
   * Init late
   */


  static initLate() {
    let domTo = null;
    FramelixDom.addChangeListener('myself-dom', function () {
      if (domTo) return;
      domTo = setTimeout(function () {
        domTo = null;
        $('.myself-block-layout-row[data-background-video], .myself-block-layout-row-column[data-background-video]').each(function () {
          const el = $(this);
          const backgroundVideo = el.attr('data-background-video');
          el.removeAttr('data-background-video');
          el.attr('data-background-video-original', backgroundVideo);
          FramelixIntersectionObserver.onGetVisible(this, function () {
            function updateVideoPosition() {
              const elWidth = el.width();
              const elHeight = el.height();
              const wRatio = 1 / video.videoWidth * elWidth;
              const hRatio = 1 / video.videoHeight * elHeight;
              const minRatio = Math.min(wRatio, hRatio);
              const maxRatio = Math.max(wRatio, hRatio);
              video.width = video.videoWidth * minRatio;
              video.height = video.videoHeight * minRatio;

              if (backgroundSize === 'cover') {
                video.width = video.videoWidth * maxRatio;
                video.height = video.videoHeight * maxRatio;
              }

              video.style.left = elWidth / 2 - video.width / 2 + 'px';
              video.style.top = elHeight / 2 - video.height / 2 + 'px';
            }
            /** @type {HTMLVideoElement} */


            const video = document.createElement('video');
            video.autoplay = true;
            video.loop = true;
            video.muted = true;
            video.src = backgroundVideo;
            video.poster = el.attr('data-background-image') || el.attr('data-background-original') || '';
            el.prepend(video);
            el.addClass('myself-block-layout-background-video');
            video.play();
            const backgroundSize = el.attr('data-background-size') || 'cover';
            video.addEventListener('timeupdate', updateVideoPosition);
            video.addEventListener('play', updateVideoPosition);
            updateVideoPosition();
          });
        });
        $('.myself-block-layout-row[data-background-image], .myself-block-layout-row-column[data-background-image]').each(function () {
          const el = $(this);
          const backgroundImage = el.attr('data-background-image');
          el.removeAttr('data-background-image');
          el.attr('data-background-image-original', backgroundImage);
          FramelixIntersectionObserver.onGetVisible(this, function () {
            if (!el.attr('data-background-video') && !el.attr('data-background-video-original')) {
              el.css('background-image', 'url(' + backgroundImage + ')');
            }
          });
        });
        $('.myself-lazy-load').not('.myself-lazy-load-initialized').addClass('myself-lazy-load-initialized').each(function () {
          const el = $(this);
          const imgAttr = el.attr('data-img');

          if (imgAttr) {
            const parentWidth = el.closest('.myself-lazy-load-parent-anchor').width();
            const images = imgAttr.split(';');
            let useSrc = '';

            for (let i = 0; i < images.length; i++) {
              const img = images[i].split('|');

              if (img.length > 1) {
                useSrc = img[2]; // as soon as we have reached the container size

                if (parentWidth <= parseInt(img[0])) {
                  break;
                }
              } else if (img.length <= 1 && useSrc === '') {
                useSrc = img[0];
              }
            } // no matched image, use the one without dimension


            if (!useSrc) {
              for (let i = 0; i < images.length; i++) {
                const img = images[i].split('|');

                if (img.length <= 1 && useSrc === '') {
                  useSrc = img[0];
                  break;
                }
              }
            }

            el.attr('data-img-src', useSrc);
          }

          FramelixIntersectionObserver.onGetVisible(this, function () {
            const imgAttr = el.attr('data-img-src');

            if (imgAttr) {
              const img = $('<img src="' + imgAttr + '">').attr('alt', el.attr('data-alt'));
              el.replaceWith(img);
            }

            const videoAttr = el.attr('data-video');

            if (videoAttr) {
              const video = $('<video src="' + videoAttr + '" loop autoplay muted></video>');
              video.attr('poster', el.attr('data-poster'));
              el.replaceWith(video);
            }
          });
        });
      }, 500);
    }); // remember edit mode for this device to always show a quick enable edit mode button on the left corner

    if (Myself.isEditModeOuter() && !FramelixLocalStorage.get('myself-edit-mode')) {
      FramelixLocalStorage.set('myself-edit-mode', true);
    }

    if (FramelixLocalStorage.get('myself-edit-mode') && !Myself.isEditModeOuter()) {
      const editModeContainer = $(`<div class="myself-open-edit-mode myself-hide-if-editmode"><button class="framelix-button" data-icon-left="clear"title="__myself_hide_editmode_container__"></button> <a href="?editMode=1" class="framelix-button framelix-button-primary" title="__myself_enable_editmode__" data-icon-left="edit"></a></div>`);
      editModeContainer.on('click', 'button', function () {
        editModeContainer.remove();
      });
      $('.framelix-page').append(editModeContainer);
    }
  }

}

FramelixInit.late.push(Myself.initLate);

class MyselfPageBlocks {
  /**
   * All page block instances
   * @type {MyselfPageBlocks[]}
   */

  /**
   * The block container
   * @type {Cash}
   */

  /**
   * The backend config for this block
   * @type {Object|Array}
   */

  /**
   * Constructor
   * @param {Cash} blockContainer
   * @param {Object|Array} config
   */
  constructor(blockContainer, config) {
    _defineProperty(this, "blockContainer", void 0);

    _defineProperty(this, "config", void 0);

    this.config = config;
    this.blockContainer = blockContainer;
    this.blockContainer.attr('data-instance-id', MyselfPageBlocks.instances.length);
    MyselfPageBlocks.instances.push(this);
  }
  /**
   * Initialize the block
   */


  initBlock() {
    throw new Error('You need to override initBlock() function');
  }

}

_defineProperty(MyselfPageBlocks, "instances", []);