/**
 * Myself Module Class
 */
class Myself {

  /**
   * Is page in edit mode (The outer frame)
   * @return {boolean}
   */
  static isEditModeOuter () {
    return $('html').attr('data-edit') === '1'
  }

  /**
   * Is page in edit mode (The inner frame)
   * @return {boolean}
   */
  static isEditModeInner () {
    return window.top !== window ? window.top.document.querySelector('html').getAttribute('data-edit') === '1' : false
  }

  /**
   * Init late
   */
  static initLate () {
    let domTo = null
    FramelixDom.addChangeListener('myself-dom', function () {
      if (domTo) return
      domTo = setTimeout(function () {
        domTo = null
        $('.myself-lazy-load').not('.myself-lazy-load-initialized').addClass('myself-lazy-load-initialized').each(function () {
          const el = $(this)
          const imgAttr = el.attr('data-img')
          if (imgAttr) {
            const parentWidth = el.closest('.myself-lazy-load-parent-anchor').width()
            const images = imgAttr.split(';')
            let useSrc = ''
            for (let i = 0; i < images.length; i++) {
              const img = images[i].split('|')
              if (img.length > 1) {
                useSrc = img[2]
                // as soon as we have reached the container size
                if (parentWidth <= parseInt(img[0])) {
                  break
                }
              } else if (img.length <= 1 && useSrc === '') {
                useSrc = img[0]
              }
            }
            // no matched image, use the one without dimension
            if (!useSrc) {
              for (let i = 0; i < images.length; i++) {
                const img = images[i].split('|')
                if (img.length <= 1 && useSrc === '') {
                  useSrc = img[0]
                  break
                }
              }
            }
            el.attr('data-img-src', useSrc)
          }
          FramelixIntersectionObserver.onGetVisible(this, function () {
            const imgAttr = el.attr('data-img-src')
            if (imgAttr) {
              const img = $('<img src="' + imgAttr + '">').attr('alt', el.attr('data-alt'))
              el.replaceWith(img)
            }
            const videoAttr = el.attr('data-video')
            if (videoAttr) {
              const video = $('<video src="' + videoAttr + '" loop autoplay muted></video>')
              video.attr('poster', el.attr('data-poster'))
              el.replaceWith(video)
            }
          })
        })
      }, 500)
    })
    // remember edit mode for this device to always show a quick enable edit mode button on the left corner
    if (Myself.isEditModeOuter() && !FramelixLocalStorage.get('myself-edit-mode')) {
      FramelixLocalStorage.set('myself-edit-mode', true)
    }
    if (FramelixLocalStorage.get('myself-edit-mode') && !Myself.isEditModeOuter()) {
      $('.framelix-page').append(`<a href="?editMode=1" class="framelix-button myself-hide-if-editmode" title="__myself_enable_editmode__" data-icon-left="edit" style="position: fixed; left:0;bottom:0; opacity:0.6; margin: 0"></a>`)
    }
  }
}

FramelixInit.late.push(Myself.initLate)