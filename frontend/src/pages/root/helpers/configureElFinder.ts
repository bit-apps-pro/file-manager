import { type RefObject } from 'react'

import config, { getOptionVariable } from '@config/config'
import { type FinderInstance } from 'elfinder'

type JQueryWithUi = {
  (target: unknown): any
  ui?: {
    dialog?: {
      prototype?: {
        options?: {
          appendTo?: string
        }
      }
    }
  }
}

type ElFinderConstructor = {
  prototype: { commands: Record<string, () => void> }
}

type FinderWithDialog = FinderInstance & {
  dialog: (content: unknown, options?: unknown) => any
  theme?: {
    name?: string
  }
  changeTheme: (theme: string) => FinderWithDialog
  storage: (key: string, value?: unknown) => FinderWithDialog
  resize: (width: number | string, height: number | string) => void
  bind: (events: string, handler: (...args: unknown[]) => void) => FinderWithDialog
}

function elevateDialogLayers(jq: JQueryWithUi, dialogLike?: any): void {
  const getActiveFinderRect = (): DOMRect | null => {
    const candidates = Array.from(document.querySelectorAll('.elfinder')) as HTMLElement[]
    let bestRect: DOMRect | null = null
    let bestArea = 0

    for (const candidate of candidates) {
      const styles = window.getComputedStyle(candidate)
      if (styles.display === 'none' || styles.visibility === 'hidden') {
        continue
      }

      const rect = candidate.getBoundingClientRect()
      const area = rect.width * rect.height
      if (rect.width < 200 || rect.height < 120 || area <= 0) {
        continue
      }

      if (area > bestArea) {
        bestArea = area
        bestRect = rect
      }
    }

    return bestRect
  }

  const clampFloatingLayerPosition = (element: HTMLElement): void => {
    const finderRect = getActiveFinderRect()
    if (!finderRect) {
      return
    }

    const rect = element.getBoundingClientRect()
    const titlebar = element.querySelector('.ui-dialog-titlebar') as HTMLElement | null
    const titlebarHeight = Math.max(28, Math.round(titlebar?.getBoundingClientRect().height ?? 36))

    const computed = window.getComputedStyle(element)
    const parsedTop = Number.parseFloat(element.style.top || computed.top)
    const parsedLeft = Number.parseFloat(element.style.left || computed.left)

    const adminBar = document.getElementById('wpadminbar')
    const adminBarBottom = adminBar
      ? Math.max(0, Math.round(adminBar.getBoundingClientRect().bottom))
      : 0
    const viewportWidth = window.innerWidth || document.documentElement.clientWidth
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight

    const preferredTop = Number.isFinite(parsedTop) ? parsedTop : adminBarBottom + 36
    const preferredLeft = Number.isFinite(parsedLeft) ? parsedLeft : 36

    const minTop = adminBarBottom + 8
    const maxTop = Math.max(minTop, viewportHeight - titlebarHeight - 8)
    const minLeft = 8
    const keepVisibleWidth = Math.max(180, Math.round(Math.min(rect.width || 640, viewportWidth)))
    const maxLeft = Math.max(minLeft, viewportWidth - keepVisibleWidth - 8)

    const top = Math.min(Math.max(preferredTop, minTop), maxTop)
    const left = Math.min(Math.max(preferredLeft, minLeft), maxLeft)

    element.style.setProperty('top', `${Math.round(top)}px`, 'important')
    element.style.setProperty('left', `${Math.round(left)}px`, 'important')
    element.style.removeProperty('right')
    element.style.removeProperty('bottom')
  }

  const quicklooks = jq('.elfinder-quicklook')
  quicklooks.each(function moveQuicklookToBody(this: HTMLElement) {
    const alreadyElevated = this.getAttribute('data-elfinder-elevated') === '1'
    const $quicklook = jq(this)
    if ($quicklook.parent()[0] !== document.body) {
      $quicklook.appendTo('body')
    }

    $quicklook.css({ position: 'fixed', zIndex: 100001 })
    this.setAttribute('data-elfinder-elevated', '1')
    if (!alreadyElevated) {
      clampFloatingLayerPosition(this)
    }

    const draggableApi = ($quicklook as any).draggable
    if (typeof draggableApi === 'function') {
      $quicklook.draggable('option', 'containment', false)
      $quicklook.off('drag.elfinderElevate stop.elfinderElevate')
      $quicklook.on('drag.elfinderElevate stop.elfinderElevate', () => {
        clampFloatingLayerPosition(this)
        this.style.setProperty('z-index', '100002', 'important')
      })
    }
  })

  const dialogs = dialogLike
    ? dialogLike.closest('.ui-dialog.elfinder-dialog')
    : jq('.ui-dialog.elfinder-dialog')

  dialogs.each(function moveDialogToBody(this: HTMLElement) {
    const $dialog = jq(this)
    if ($dialog.parent()[0] !== document.body) {
      $dialog.appendTo('body')
    }
    $dialog.css({ position: 'fixed', zIndex: 100000 })
    $dialog.attr('data-elfinder-elevated', '1')

    const isEditorDialog =
      this.classList.contains('elfinder-dialog-edit') || this.classList.contains('elfinder-to-editing')

    if (isEditorDialog) {
      clampFloatingLayerPosition(this)
      this.setAttribute('data-elfinder-initial-pos', '1')

      if (this.getAttribute('data-elfinder-reclamp-bound') !== '1') {
        const dialogEl = this
        let reclampInFlight = false
        const reclamp = (): void => {
          if (reclampInFlight) return
          reclampInFlight = true
          clampFloatingLayerPosition(dialogEl)
          queueMicrotask(() => {
            reclampInFlight = false
          })
        }
        const observer = new MutationObserver(reclamp)
        observer.observe(dialogEl, { attributes: true, attributeFilter: ['style'] })
        window.addEventListener('resize', reclamp)
        $dialog.one('remove', () => {
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

    const closeIcon = this.querySelector('.ui-dialog-titlebar-close .ui-icon') as HTMLElement | null
    if (closeIcon) {
      closeIcon.style.setProperty('width', '16px', 'important')
      closeIcon.style.setProperty('height', '16px', 'important')
    }

    if (this.classList.contains('elfinder-dialog-preference')) {
      const closeButton = this.querySelector('.ui-dialog-titlebar-close') as HTMLElement | null
      if (closeButton) {
        closeButton.style.setProperty('display', 'block', 'important')
      }
    }
  })

  jq('.ui-widget-overlay')
    .filter(function overlayNearElfinderDialog(this: HTMLElement) {
      const $overlay = jq(this)
      return (
        $overlay.nextAll('.ui-dialog.elfinder-dialog').length > 0 ||
        $overlay.prevAll('.ui-dialog.elfinder-dialog').length > 0
      )
    })
    .each(function moveOverlayToBody(this: HTMLElement) {
      const $overlay = jq(this)
      if ($overlay.parent()[0] !== document.body) {
        $overlay.appendTo('body')
      }
      $overlay.css({ position: 'fixed', zIndex: 99999 })
      $overlay.attr('data-elfinder-elevated', '1')
    })
}

export default function configureElFinder(finderRef: RefObject<HTMLDivElement>): FinderInstance {
  const jq = (window as typeof window & { jQuery: JQueryWithUi }).jQuery
  const { AJAX_URL, NONCE, LANG, THEME, ViewType, ACTION } = config
  const themes = getOptionVariable('themes', [])
  themes.default = {
    name: 'Default'
  }

  if (jq.ui?.dialog?.prototype?.options) {
    jq.ui.dialog.prototype.options.appendTo = 'body'
  }

  const elFinderCtor = (window as typeof window & { elFinder?: ElFinderConstructor }).elFinder
  if (elFinderCtor) {
    elFinderCtor.prototype.commands.emailto = function (this: any) {
      const self = this
      const fm = self.fm
      const filter = (files: Array<{ mime: string }>) => files.filter(f => f.mime !== 'directory')

      this.exec = function (hashes: string[]) {
        const url = String(fm.url(hashes[0], 0))
        const filename = url.split('/').pop() ?? ''
        const emailTo = prompt('Please enter mail address')
        if (emailTo == null) return
        if (
          !/^(([^<>()[\]\.,;:\s@"]+(\.[^<>()[\]\.,;:\s@"]+)*)|(".+"))@(([^<>()[\]\.,;:\s@"]+\.)+[^<>()[\]\.,;:\s@"]{2,})$/i.test(
            emailTo
          )
        ) {
          alert('Please enter a valid email address')
          return
        }
        window.open(`mailto:${emailTo}?subject=${filename}&body=${url}`)
      }

      this.getstate = function (select: unknown) {
        const sel: Array<{ mime: string }> = self.files(select)
        return sel.length && filter(sel).length === sel.length ? 0 : -1
      }
    }
  }

  const $el = jq(finderRef.current)
  const configuredHeight = getOptionVariable('height')
  const heightIsAuto = !configuredHeight || !/^\d+(\.\d+)?(px)?$/.test(String(configuredHeight))
  const computeFillHeight = (): number => {
    const el = finderRef.current
    const elTop = el ? Math.round(el.getBoundingClientRect().top) : 0
    return Math.max(320, window.innerHeight - elTop - 10)
  }
  const resolveHeight = (): number | string => (heightIsAuto ? computeFillHeight() : configuredHeight)
  const finder = $el.elfinder({
    url: AJAX_URL,
    customData: {
      action: ACTION,
      nonce: NONCE
    },
    theme: THEME,
    lang: LANG,
    cssAutoLoad: getOptionVariable('cssAutoLoad'),
    contextmenu: getOptionVariable('contextmenu'),
    requestType: getOptionVariable('requestType'),
    themes,
    width: getOptionVariable('width'),
    height: resolveHeight(),
    commandsOptions: getOptionVariable('commandsOptions'),
    disabled: getOptionVariable('disabled'),
    rememberLastDir: getOptionVariable('rememberLastDir'),
    reloadClearHistory: getOptionVariable('reloadClearHistory'),
    defaultView: getOptionVariable('defaultView'),
    ui: getOptionVariable('ui'),
    uiOptions: {
      toolbar: [
        ['back', 'forward'],
        ['reload'],
        ['home', 'up'],
        ['mkfile'],
        ['open', 'download', 'getfile', 'emailto'],
        ['info', 'sort'],
        ['quicklook'],
        ['copy', 'cut', 'paste'],
        ['rm'],
        ['duplicate', 'rename', 'edit', 'resize'],
        ['extract', 'archive'],
        ['fullscreen'],
        ['search']
      ]
    },
    sortOrder: getOptionVariable('sortOrder'),
    sortStickFolders: getOptionVariable('sortStickFolders'),
    dragUploadAllow: getOptionVariable('dragUploadAllow'),
    fileModeStyle: getOptionVariable('fileModeStyle'),
    resizable: getOptionVariable('resizable'),
    handlers: {
      dblclick() {
        const disabled: Array<string> = getOptionVariable('disabled')
        if (
          disabled?.includes('dblclick') ||
          disabled?.includes('download') ||
          disabled?.includes('get')
        ) {
          return false
        }
      }
    }
  })[0].elfinder as FinderWithDialog

  const originalDialog = finder.dialog.bind(finder)
  finder.dialog = function (content: unknown, options?: unknown) {
    const normalizedOptions =
      options && typeof options === 'object' && !Array.isArray(options)
        ? { ...(options as Record<string, unknown>), appendTo: 'body', zIndex: 100000 }
        : { appendTo: 'body', zIndex: 100000 }
    const $dialog = originalDialog(content, normalizedOptions)
    elevateDialogLayers(jq, $dialog)
    return $dialog
  }

  elevateDialogLayers(jq)

  if (heightIsAuto) {
    const fitToViewport = (): void => {
      const el = finderRef.current
      const elTop = el ? Math.round(el.getBoundingClientRect().top) : 0
      const target = Math.max(320, window.innerHeight - elTop - 8)
      $el.height(target)
      $el.trigger('resize')
    }
    finder.bind('load init open', fitToViewport)
    window.addEventListener('resize', fitToViewport)
    setTimeout(fitToViewport, 300)
  }

  if (finder?.theme?.name && finder.theme.name !== THEME) {
    window.location.reload()
  }
  finder.storage('lang', LANG)
  finder?.changeTheme(THEME).storage('theme', THEME)
  finder.storage('view', ViewType)

  return finder
}
