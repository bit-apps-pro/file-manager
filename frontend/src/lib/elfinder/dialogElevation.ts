import { type JQueryWithUi } from '@lib/elfinder/types'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function elevateDialogLayers(jq: JQueryWithUi, dialogLike?: any): void {
  const getActiveFinderRect = (): DOMRect | null => {
    const candidates = Array.from(document.querySelectorAll('.elfinder')) as HTMLElement[]
    let bestRect: DOMRect | null = null
    let bestArea = 0

    candidates.forEach(candidate => {
      const styles = window.getComputedStyle(candidate)
      if (styles.display === 'none' || styles.visibility === 'hidden') {
        return
      }

      const rect = candidate.getBoundingClientRect()
      const area = rect.width * rect.height
      if (rect.width < 200 || rect.height < 120 || area <= 0) {
        return
      }

      if (area > bestArea) {
        bestArea = area
        bestRect = rect
      }
    })

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

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
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
    if ($dialog.hasClass('elfinder-dialog-minimized')) return
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
        let reclampInFlight = false
        const reclamp = (): void => {
          if (reclampInFlight) return
          reclampInFlight = true
          clampFloatingLayerPosition(this)
          queueMicrotask(() => {
            reclampInFlight = false
          })
        }
        const observer = new MutationObserver(reclamp)
        observer.observe(this, { attributes: true, attributeFilter: ['style'] })
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

export function injectDockStyles(): void {
  const id = 'elfinder-dock-style'
  if (document.getElementById(id)) return
  const style = document.createElement('style')
  style.id = id
  style.textContent = `
    .elfinder-bottomtray {
      position: fixed !important;
      bottom: 0 !important;
      left: 0 !important;
      right: 0 !important;
      width: 100% !important;
      max-width: none !important;
      height: auto !important;
      background: rgba(22, 22, 24, 0.88) !important;
      backdrop-filter: blur(14px) saturate(180%);
      -webkit-backdrop-filter: blur(14px) saturate(180%);
      display: flex !important;
      flex-direction: row;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
      padding: 6px 16px 8px;
      z-index: 100001 !important;
      box-sizing: border-box !important;
      box-shadow: 0 -1px 0 rgba(255,255,255,0.06), 0 -4px 24px rgba(0,0,0,0.6);
      border-top: 1px solid rgba(255,255,255,0.07);
    }
    .elfinder-bottomtray:empty { display: none !important; }
    .elfinder-bottomtray .elfinder-dialog-minimized {
      position: static !important;
      float: none !important;
      display: flex !important;
      align-items: center;
      width: auto !important;
      max-width: 200px !important;
      min-width: 90px;
      height: 34px !important;
      padding: 0 !important;
      margin: 0 !important;
      background: rgba(255,255,255,0.10) !important;
      border: 1px solid rgba(255,255,255,0.18) !important;
      border-radius: 999px !important;
      box-shadow: 0 1px 4px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.12) !important;
      overflow: hidden;
      cursor: pointer;
      transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.1s ease;
    }
    .elfinder-bottomtray .elfinder-dialog-minimized:hover {
      background: rgba(255,255,255,0.18) !important;
      box-shadow: 0 2px 8px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.18) !important;
      transform: translateY(-2px);
    }
    .elfinder-bottomtray .elfinder-dialog-minimized:active { transform: translateY(0); }
    .elfinder-bottomtray .elfinder-dialog-minimized .ui-dialog-titlebar {
      display: flex !important;
      align-items: center;
      width: 100% !important;
      height: 34px !important;
      padding: 0 10px 0 14px !important;
      margin: 0 !important;
      background: transparent !important;
      border: none !important;
      border-radius: 0 !important;
      gap: 6px;
    }
    .elfinder-bottomtray .elfinder-dialog-minimized .ui-dialog-title,
    .elfinder-bottomtray .elfinder-dialog-minimized .elfinder-dialog-title {
      order: 1;
      color: rgba(255,255,255,0.92) !important;
      font-size: 11.5px !important;
      font-weight: 500 !important;
      letter-spacing: 0.01em;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      flex: 1;
      min-width: 0;
      line-height: 34px !important;
      text-shadow: none !important;
    }
    .elfinder-bottomtray .elfinder-dialog-minimized .ui-dialog-titlebar-close {
      order: 2;
      flex-shrink: 0;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      width: 18px !important;
      height: 18px !important;
      min-width: 18px !important;
      min-height: 18px !important;
      padding: 0 !important;
      margin: 0 !important;
      background: rgba(255,255,255,0.12) !important;
      border: none !important;
      border-radius: 50% !important;
      opacity: 0.7;
      transition: background 0.12s, opacity 0.12s;
    }
    .elfinder-bottomtray .elfinder-dialog-minimized .ui-dialog-titlebar-close:hover {
      background: rgba(255, 75, 75, 0.75) !important;
      opacity: 1;
    }
    .elfinder-bottomtray .elfinder-dialog-minimized .ui-dialog-titlebar-close .ui-icon {
      width: 10px !important;
      height: 10px !important;
    }
  `
  document.head.appendChild(style)
}

function elevateBottomTray(): void {
  const tray = document.querySelector<HTMLElement>('.elfinder-bottomtray')
  if (!tray || tray.parentElement === document.body) return
  document.body.appendChild(tray)
}

export function watchForBottomTray(): void {
  elevateBottomTray()
  if (document.querySelector('.elfinder-bottomtray')?.parentElement === document.body) return
  const finderEl = document.querySelector('.elfinder')
  if (!finderEl) return
  const obs = new MutationObserver(() => {
    if (document.querySelector('.elfinder-bottomtray')) {
      obs.disconnect()
      elevateBottomTray()
    }
  })
  obs.observe(finderEl, { childList: true })
}
