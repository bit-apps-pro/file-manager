import { resolveNotification } from '@common/notificationInstance'

import {
  type ElFinderEvent,
  type ElFinderToastMode,
  type ElFinderToastOptions,
  type FinderWithDialog
} from './types'

// elFinder's toast modes are already antd notification's method names; the set
// only guards an unexpected runtime mode down to the 'success' default.
const TOAST_MODES = new Set<ElFinderToastMode>(['success', 'info', 'warning', 'error'])

// Toasts elFinder keeps a live handle on (progress nodes, action buttons,
// lifecycle callbacks, sticky) must stay native — antd notification can't be
// updated, closed, or interacted with by the original caller once shown.
const isManagedToast = (options: ElFinderToastOptions): boolean =>
  options.extNode != null ||
  options.button != null ||
  options.onShown != null ||
  options.onHidden != null ||
  options.timeOut === 0

// elFinder's show/hide values are fade timings, not display time — only
// `timeOut` (ms) maps to antd's `duration` (seconds).
const toDuration = (timeOut: unknown): number | undefined =>
  typeof timeOut === 'number' && timeOut > 0 ? timeOut / 1000 : undefined

// elFinder's i18n output is HTML (escaped entities + <span> markup around
// interpolated values) built for innerHTML; antd renders message as text, so
// flatten it to decoded plain text. DOMParser neither executes scripts nor
// loads resources, so this stays XSS-safe.
const toPlainText = (html: string): string =>
  new DOMParser().parseFromString(html, 'text/html').body.textContent ?? ''

/**
 * Route elFinder's transient toasts and error events into antd notification so
 * they match the rest of the plugin UI. Interactive and must-acknowledge
 * dialogs stay native.
 */
export default function bridgeElFinderNotifications(finder: FinderWithDialog): void {
  const nativeToast = finder.toast.bind(finder)

  // Toast callers pass an already-i18n'd display string; the native widget only
  // resolves %token% placeholders and renders it as HTML. Mirror that instead of
  // re-running i18n (which would double-escape), then flatten to plain text.
  const renderToastMsg = (msg: string): string =>
    toPlainText(msg.replace(/%([a-zA-Z0-9]+)%/g, (_, token) => finder.i18n(token)))

  finder.toast = (options: ElFinderToastOptions = {}) => {
    if (isManagedToast(options)) {
      return nativeToast(options)
    }
    // elFinder's own toast default mode is 'success'.
    const mode = options.mode as ElFinderToastMode
    const type: ElFinderToastMode = TOAST_MODES.has(mode) ? mode : 'success'
    resolveNotification()[type]({
      message: renderToastMsg(options.msg ?? ''),
      duration: toDuration(options.timeOut)
    })
    return undefined
  }

  // Returning false stops elFinder's trigger loop before its default handler
  // (elfinder.full.js:1101), suppressing the native error modal. priorityFirst
  // keeps this ahead of that handler, so the bridge must remain the terminal
  // 'error' listener — anything bound later via fm.error() would be skipped.
  finder.bind(
    'error',
    event => {
      const data = (event as ElFinderEvent)?.data
      const error = data?.error
      if (error == null) {
        return undefined
      }
      // Errors flagged modal or carrying custom buttons must block until
      // acknowledged — leave those to the native dialog.
      const opts = data?.opts as { modal?: boolean; buttons?: unknown } | undefined
      if (opts?.modal || opts?.buttons != null) {
        return undefined
      }
      // duration 0 keeps the notice until dismissed, matching the native
      // error dialog it replaces.
      resolveNotification().error({ message: toPlainText(finder.i18n(error)), duration: 0 })
      return false
    },
    true
  )
}
