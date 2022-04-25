class PageMyself {

  /**
   * Global config
   * @type {{}}
   */
  static config = {}

  /**
   * Init
   */
  static initLate () {
    window.addEventListener('hashchange', function () {
      PageMyself.onHashChange()

    }, false)
    PageMyself.onHashChange()
    FramelixDom.addChangeListener('pagemyself-dom', function () {
      PageMyself.onDomChange()
    })

  }

  /**
   * On dom change
   */
  static onDomChange () {
    const thumbSizes = [100, 500, 1000, 1500, 1920]
    $('[data-background-video]').each(function () {
      const el = $(this)
      const block = el.closest('.component-block')
      const backgroundVideo = el.attr('data-background-video')
      el.removeAttr('data-background-video')
      el.attr('data-background-video-original', backgroundVideo)
      FramelixIntersectionObserver.onGetVisible(this, function () {
        function updateVideoPosition () {
          const elWidth = block.width()
          const elHeight = block.height()
          const wRatio = 1 / video.videoWidth * elWidth
          const hRatio = 1 / video.videoHeight * elHeight
          const maxRatio = Math.max(wRatio, hRatio)
          video.width = video.videoWidth * maxRatio
          video.height = video.videoHeight * maxRatio
          video.style.left = (elWidth / 2 - video.width / 2) + 'px'
          video.style.top = (elHeight / 2 - video.height / 2) + 'px'
        }

        /** @type {HTMLVideoElement} */
        const video = document.createElement('video')
        video.autoplay = true
        video.loop = true
        video.muted = true
        video.src = backgroundVideo
        video.poster = el.attr('data-background-image') || el.attr('data-background-original') || ''
        el.prepend(video)
        el.addClass('pagemyself-background-video')
        video.play()
        video.addEventListener('timeupdate', updateVideoPosition)
        video.addEventListener('play', updateVideoPosition)
        updateVideoPosition()
      })
    })
    $('[data-background-image]').each(function () {
      const el = $(this)
      let backgroundImage = el.attr('data-background-image')
      const backgroundPosition = el.attr('data-background-position') || 'center'
      el.removeAttr('data-background-image')
      el.attr('data-background-image-original', backgroundImage)
      FramelixIntersectionObserver.onGetVisible(this, function () {
        if (!el.attr('data-background-video') && !el.attr('data-background-video-original')) {
          // for uploaded images, automatically find best fitting thumb size
          if (backgroundImage.match(/\/uploads\/[0-9]+\//)) {
            const url = new URL(backgroundImage)
            let pathSplit = url.pathname.split('/')
            let basename = pathSplit.pop()
            if (basename.startsWith('t-')) {
              basename = basename.split('-', 3).pop()
            }
            const containerSize = Math.max(el.parent().width(), el.parent().height())
            let useThumbSize = 100
            for (let i in thumbSizes) {
              const thumbSize = thumbSizes[i]
              const diff = containerSize - thumbSize
              useThumbSize = thumbSize
              if (diff <= 0) break
            }
            basename = 't-' + useThumbSize + '-' + basename
            pathSplit.push(basename)
            url.pathname = pathSplit.join('/')
            backgroundImage = url.toString()
          }
          el.css('background-image', 'url(' + backgroundImage + ')')
          el.css('background-position', 'center ' + backgroundPosition)
        }
      })
    })
  }

  /**
   * On hash change
   */
  static onHashChange () {
    if (!window.location.hash.startsWith('#jumpto-')) return
    const target = $('.' + window.location.hash.substring(1))
    if (!target.length) return
    let offset = 0
    const el = $('.pagemyself-jumpmark-offset').first()
    if (el.length) {
      const style = window.getComputedStyle(el[0])
      if (style.position === 'sticky' || style.position === 'fixed') {
        offset += parseInt(style.height.replace(/\..*/g, '').replace(/[^0-9]/g, ''))
      }
    }
    Framelix.scrollTo(target, null, offset)
  }
}

FramelixInit.late.push(PageMyself.initLate)