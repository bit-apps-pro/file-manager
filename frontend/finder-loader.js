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
      if ($dialog.hasClass('elfinder-dialog-minimized')) return
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

  function injectDockStyles() {
    var id = 'elfinder-dock-style'
    if (document.getElementById(id)) return
    var style = document.createElement('style')
    style.id = id
    style.textContent = [
      /* ── Dock bar ─────────────────────────────────────────────── */
      '.elfinder-bottomtray {',
      '  position: fixed !important;',
      '  bottom: 0 !important;',
      '  left: 0 !important;',
      '  right: 0 !important;',
      '  width: 100% !important;',
      '  max-width: none !important;',
      '  height: auto !important;',
      '  background: rgba(22, 22, 24, 0.88) !important;',
      '  backdrop-filter: blur(14px) saturate(180%);',
      '  -webkit-backdrop-filter: blur(14px) saturate(180%);',
      '  display: flex !important;',
      '  flex-direction: row;',
      '  align-items: center;',
      '  flex-wrap: wrap;',
      '  gap: 8px;',
      '  padding: 6px 16px 8px;',
      '  z-index: 100001 !important;',
      '  box-sizing: border-box !important;',
      '  box-shadow: 0 -1px 0 rgba(255,255,255,0.06), 0 -4px 24px rgba(0,0,0,0.6);',
      '  border-top: 1px solid rgba(255,255,255,0.07);',
      '}',
      '.elfinder-bottomtray:empty { display: none !important; }',
      /* ── Each minimized pill ──────────────────────────────────── */
      '.elfinder-bottomtray .elfinder-dialog-minimized {',
      '  position: static !important;',
      '  float: none !important;',
      '  display: flex !important;',
      '  align-items: center;',
      '  width: auto !important;',
      '  max-width: 200px !important;',
      '  min-width: 90px;',
      '  height: 34px !important;',
      '  padding: 0 !important;',
      '  margin: 0 !important;',
      '  background: rgba(255,255,255,0.10) !important;',
      '  border: 1px solid rgba(255,255,255,0.18) !important;',
      '  border-radius: 999px !important;',
      '  box-shadow: 0 1px 4px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.12) !important;',
      '  overflow: hidden;',
      '  cursor: pointer;',
      '  transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.1s ease;',
      '}',
      '.elfinder-bottomtray .elfinder-dialog-minimized:hover {',
      '  background: rgba(255,255,255,0.18) !important;',
      '  box-shadow: 0 2px 8px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.18) !important;',
      '  transform: translateY(-2px);',
      '}',
      '.elfinder-bottomtray .elfinder-dialog-minimized:active {',
      '  transform: translateY(0);',
      '}',
      /* ── Titlebar inside pill ─────────────────────────────────── */
      '.elfinder-bottomtray .elfinder-dialog-minimized .ui-dialog-titlebar {',
      '  display: flex !important;',
      '  align-items: center;',
      '  width: 100% !important;',
      '  height: 34px !important;',
      '  padding: 0 10px 0 14px !important;',
      '  margin: 0 !important;',
      '  background: transparent !important;',
      '  border: none !important;',
      '  border-radius: 0 !important;',
      '  gap: 6px;',
      '}',
      /* ── Filename text ────────────────────────────────────────── */
      '.elfinder-bottomtray .elfinder-dialog-minimized .ui-dialog-title,',
      '.elfinder-bottomtray .elfinder-dialog-minimized .elfinder-dialog-title {',
      '  color: rgba(255,255,255,0.92) !important;',
      '  font-size: 11.5px !important;',
      '  font-weight: 500 !important;',
      '  letter-spacing: 0.01em;',
      '  overflow: hidden;',
      '  text-overflow: ellipsis;',
      '  white-space: nowrap;',
      '  flex: 1;',
      '  min-width: 0;',
      '  line-height: 34px !important;',
      '  text-shadow: none !important;',
      '}',
      /* ── Close button ─────────────────────────────────────────── */
      '.elfinder-bottomtray .elfinder-dialog-minimized .ui-dialog-titlebar-close {',
      '  flex-shrink: 0;',
      '  display: flex !important;',
      '  align-items: center !important;',
      '  justify-content: center !important;',
      '  width: 18px !important;',
      '  height: 18px !important;',
      '  min-width: 18px !important;',
      '  min-height: 18px !important;',
      '  padding: 0 !important;',
      '  margin: 0 !important;',
      '  background: rgba(255,255,255,0.12) !important;',
      '  border: none !important;',
      '  border-radius: 50% !important;',
      '  opacity: 0.7;',
      '  transition: background 0.12s, opacity 0.12s;',
      '}',
      '.elfinder-bottomtray .elfinder-dialog-minimized .ui-dialog-titlebar-close:hover {',
      '  background: rgba(255, 75, 75, 0.75) !important;',
      '  opacity: 1;',
      '}',
      '.elfinder-bottomtray .elfinder-dialog-minimized .ui-dialog-titlebar-close .ui-icon {',
      '  width: 10px !important;',
      '  height: 10px !important;',
      '  background-size: 10px 10px !important;',
      '}',
    ].join('\n')
    document.head.appendChild(style)
  }

  function elevateBottomTray() {
    var tray = document.querySelector('.elfinder .elfinder-bottomtray, .elfinder-bottomtray')
    if (!tray || tray.parentNode === document.body) return
    document.body.appendChild(tray)
  }

  function watchForBottomTray() {
    var finderEl = document.querySelector('.elfinder')
    if (!finderEl) return
    elevateBottomTray()
    if (document.querySelector('.elfinder-bottomtray') && document.querySelector('.elfinder-bottomtray').parentNode === document.body) return
    var obs = new MutationObserver(function () {
      if (document.querySelector('.elfinder-bottomtray')) {
        obs.disconnect()
        elevateBottomTray()
      }
    })
    obs.observe(finderEl, { childList: true, subtree: false })
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
  injectDockStyles()

  if (fm && typeof fm.bind === 'function') {
    fm.bind('load', watchForBottomTray)
  }

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
