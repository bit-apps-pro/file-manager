jQuery(document).ready(function () {
  var $ = jQuery

  if (window.elFinder) {
    elFinder.prototype.commands.emailto = function () {
      var self = this,
          fm = self.fm,
          filter = function (files) {
            return $.grep(files, function (file) {
              return file.mime !== 'directory'
            })
          }

      this.exec = function (hashes) {
        var url = fm.url(hashes[0], 0)
        var filename = url.split('/').pop()
        var emailTo = prompt('Please enter mail address')
        if (emailTo == null) return
        if (!/^(([^<>()[\]\.,;:\s@"]+(\.[^<>()[\]\.,;:\s@"]+)*)|(".+"))@(([^<>()[\]\.,;:\s@"]+\.)+[^<>()[\]\.,;:\s@"]{2,})$/i.test(emailTo)) {
          alert('Please enter a valid email address')
          return
        }
        window.open('mailto:' + emailTo + '?subject=' + filename + '&body=' + url)
      }

      this.getstate = function (select) {
        var sel = this.files(select)
        return sel.length && filter(sel).length === sel.length ? 0 : -1
      }
    }
  }

  function elevateDialogLayers(dialog) {
    function clampFloatingLayerPosition(element) {
      if (!document.querySelector('.elfinder')) return

      var rect = element.getBoundingClientRect()
      var titlebar = element.querySelector('.ui-dialog-titlebar')
      var titlebarRect = titlebar && titlebar.getBoundingClientRect ? titlebar.getBoundingClientRect() : null
      var titlebarHeight = Math.max(28, Math.round((titlebarRect && titlebarRect.height) || 36))

      var computed = window.getComputedStyle(element)
      var parsedTop = Number.parseFloat(element.style.top || computed.top)
      var parsedLeft = Number.parseFloat(element.style.left || computed.left)

      var adminBar = document.getElementById('wpadminbar')
      var adminBarBottom = adminBar
        ? Math.max(0, Math.round(adminBar.getBoundingClientRect().bottom))
        : 0
      var viewportWidth = window.innerWidth || document.documentElement.clientWidth
      var viewportHeight = window.innerHeight || document.documentElement.clientHeight

      var preferredTop = Number.isFinite(parsedTop) ? parsedTop : adminBarBottom + 36
      var preferredLeft = Number.isFinite(parsedLeft) ? parsedLeft : 36

      var minTop = adminBarBottom + 8
      var maxTop = Math.max(minTop, viewportHeight - titlebarHeight - 8)
      var minLeft = 8
      var keepVisibleWidth = Math.max(180, Math.round(Math.min((rect && rect.width) || 640, viewportWidth)))
      var maxLeft = Math.max(minLeft, viewportWidth - keepVisibleWidth - 8)

      var top = Math.min(Math.max(preferredTop, minTop), maxTop)
      var left = Math.min(Math.max(preferredLeft, minLeft), maxLeft)

      element.style.setProperty('top', Math.round(top) + 'px', 'important')
      element.style.setProperty('left', Math.round(left) + 'px', 'important')
      element.style.removeProperty('right')
      element.style.removeProperty('bottom')
    }

    var $quicklooks = $('.elfinder-quicklook')
    $quicklooks.each(function () {
      var alreadyElevated = this.getAttribute('data-elfinder-elevated') === '1'
      var $quicklook = $(this)
      if ($quicklook.parent()[0] !== document.body) {
        $quicklook.appendTo('body')
      }

      $quicklook.css({ position: 'fixed', zIndex: 100001 })
      this.setAttribute('data-elfinder-elevated', '1')
      if (!alreadyElevated) {
        clampFloatingLayerPosition(this)
      }

      if (typeof $quicklook.draggable === 'function') {
        $quicklook.draggable('option', 'containment', false)
        $quicklook.off('drag.elfinderElevate stop.elfinderElevate')
        $quicklook.on('drag.elfinderElevate stop.elfinderElevate', () => {
          clampFloatingLayerPosition(this)
          this.style.setProperty('z-index', '100002', 'important')
        })
      }
    })

    var $dialogs = dialog ? dialog.closest('.ui-dialog.elfinder-dialog') : $('.ui-dialog.elfinder-dialog')

    $dialogs.each(function () {
      var $dialog = $(this)
      if ($dialog.parent()[0] !== document.body) {
        $dialog.appendTo('body')
      }
      $dialog.css({ position: 'fixed', zIndex: 100000 })
      $dialog.attr('data-elfinder-elevated', '1')

      var isEditorDialog = this.classList.contains('elfinder-dialog-edit') || this.classList.contains('elfinder-to-editing')
      if (isEditorDialog) {
        clampFloatingLayerPosition(this)
        this.setAttribute('data-elfinder-initial-pos', '1')

        if (this.getAttribute('data-elfinder-reclamp-bound') !== '1') {
          var dialogEl = this
          var reclampInFlight = false
          var reclamp = function () {
            if (reclampInFlight) return
            reclampInFlight = true
            clampFloatingLayerPosition(dialogEl)
            queueMicrotask(function () { reclampInFlight = false })
          }
          var observer = new MutationObserver(reclamp)
          observer.observe(dialogEl, { attributes: true, attributeFilter: ['style'] })
          window.addEventListener('resize', reclamp)
          $dialog.one('remove', function () {
            observer.disconnect()
            window.removeEventListener('resize', reclamp)
          })
          this.setAttribute('data-elfinder-reclamp-bound', '1')
        }
      }

      if (typeof $dialog.draggable === 'function') {
        $dialog.draggable('option', 'containment', false)
        if (isEditorDialog) {
          $dialog.off('drag.elfinderElevate stop.elfinderElevate')
          $dialog.on('drag.elfinderElevate stop.elfinderElevate', () => {
            clampFloatingLayerPosition(this)
            this.style.setProperty('z-index', '100002', 'important')
          })
        }
      }

      var closeIcon = this.querySelector('.ui-dialog-titlebar-close .ui-icon')
      if (closeIcon) {
        closeIcon.style.setProperty('width', '16px', 'important')
        closeIcon.style.setProperty('height', '16px', 'important')
      }

      if (this.classList.contains('elfinder-dialog-preference')) {
        var closeButton = this.querySelector('.ui-dialog-titlebar-close')
        if (closeButton) {
          closeButton.style.setProperty('display', 'block', 'important')
        }
      }
    })

    $('.ui-widget-overlay').filter(function () {
      var $overlay = $(this)
      return $overlay.nextAll('.ui-dialog.elfinder-dialog').length > 0 || $overlay.prevAll('.ui-dialog.elfinder-dialog').length > 0
    }).each(function () {
      var $overlay = $(this)
      if ($overlay.parent()[0] !== document.body) {
        $overlay.appendTo('body')
      }
      $overlay.css({ position: 'fixed', zIndex: 99999 })
      $overlay.attr('data-elfinder-elevated', '1')
    })
  }

  if (jQuery.ui && jQuery.ui.dialog && jQuery.ui.dialog.prototype && jQuery.ui.dialog.prototype.options) {
    jQuery.ui.dialog.prototype.options.appendTo = 'body'
  }

  var $finderEl = jQuery('#file-manager')
  function computeFillHeight() {
    var el = $finderEl.get(0)
    var elTop = el ? Math.round(el.getBoundingClientRect().top) : 0
    return Math.max(320, window.innerHeight - elTop - 10)
  }
  var configuredHeight = bitapps_fm.options.height
  var heightIsAuto = !configuredHeight || !/^\d+(\.\d+)?(px)?$/.test(String(configuredHeight))
  const finder = $finderEl.elfinder({
    url: bitapps_fm.ajaxURL,
    themes: bitapps_fm.options.themes,
    theme: bitapps_fm.options.theme,
    cssAutoLoad: bitapps_fm.options.cssAutoLoad,
    contextmenu: bitapps_fm.options.contextmenu,
    customData: {
      action: bitapps_fm.action,
      nonce: bitapps_fm.nonce
    },
    lang: bitapps_fm.options.lang,
    requestType: bitapps_fm.options.requestType,
    width: bitapps_fm.options.width,
    height: heightIsAuto ? computeFillHeight() : configuredHeight,
    commandsOptions: bitapps_fm.options.commandsOptions,
    commands: bitapps_fm.options.commands,
    disabled: bitapps_fm.options.disabled,
    rememberLastDir: bitapps_fm.options.rememberLastDir,
    reloadClearHistory: bitapps_fm.options.reloadClearHistory,
    defaultView: bitapps_fm.options.defaultView,
    ui: bitapps_fm.options.ui,
    sortOrder: bitapps_fm.options.sortOrder,
    sortStickFolders: bitapps_fm.options.sortStickFolders,
    dragUploadAllow: bitapps_fm.options.dragUploadAllow,
    fileModeStyle: bitapps_fm.options.fileModeStyle,
    resizable: bitapps_fm.options.resizable,
    handlers: {
      dblclick() {
        const disabled = bitapps_fm?.options?.disabled || []
        if (
          disabled?.includes('dblclick') ||
          disabled?.includes('download') ||
          disabled?.includes('get')
        ) {
          return false
        }
      }
    }
  })

  const fm = finder && finder[0] && finder[0].elfinder
  if (fm && typeof fm.dialog === 'function') {
    const originalDialog = fm.dialog.bind(fm)
    fm.dialog = function(content, options) {
      const normalizedOptions = options && typeof options === 'object'
        ? Object.assign({}, options, { appendTo: 'body', zIndex: 100000 })
        : { appendTo: 'body', zIndex: 100000 }
      const dialog = originalDialog(content, normalizedOptions)
      elevateDialogLayers(dialog)
      return dialog
    }
  }

  elevateDialogLayers()

  if (heightIsAuto) {
    var fitToViewport = function () {
      var el = $finderEl.get(0)
      var elTop = el ? Math.round(el.getBoundingClientRect().top) : 0
      var target = Math.max(320, window.innerHeight - elTop - 8)
      $finderEl.height(target)
      $finderEl.trigger('resize')
    }
    if (fm && typeof fm.bind === 'function') {
      fm.bind('load init open', fitToViewport)
    }
    window.addEventListener('resize', fitToViewport)
    setTimeout(fitToViewport, 300)
  }
})
