import { type FinderWithDialog, type JQueryWithUi } from '@lib/elfinder/types'
import { elevateDialogLayers } from '@lib/elfinder/dialogElevation'

export function applyAppendToBodyDefault(jq: JQueryWithUi): void {
  if (jq.ui?.dialog?.prototype?.options) {
    jq.ui.dialog.prototype.options.appendTo = 'body'
  }
}

export function patchToFront(finder: FinderWithDialog): void {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const finderAny = finder as any
  if (typeof finderAny.toFront === 'function') {
    const originalToFront = finderAny.toFront.bind(finder)
    finderAny.toFront = function (target: unknown) {
      originalToFront(target)
      // toFront() computes z-index from node.children('.ui-front') inside .elfinder,
      // but elevated dialogs live in <body>. Fix z-index only on the target element.
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const el: HTMLElement | undefined = (target as any)?.[0] ?? (target instanceof HTMLElement ? target : undefined)
      if (el?.parentElement === document.body) {
        el.style.zIndex = '100000'
      }
    }
  }
}

export function patchFmDialog(finder: FinderWithDialog, jq: JQueryWithUi): void {
  const originalDialog = finder.dialog.bind(finder)
  finder.dialog = function overrideDialog(content: unknown, options?: unknown) {
    const normalizedOptions =
      options && typeof options === 'object' && !Array.isArray(options)
        ? { ...(options as Record<string, unknown>), appendTo: 'body', zIndex: 100000 }
        : { appendTo: 'body', zIndex: 100000 }
    const $dialog = originalDialog(content, normalizedOptions)
    const $wrapper = $dialog.closest('.ui-dialog')
    // Store finder so elfinderdialog('open') can recover fm even when the
    // dialog is in <body> and .closest('.elfinder') returns empty.
    $wrapper.data('elfinder-fm', finder)
    elevateDialogLayers(jq, $dialog)
    return $dialog
  }
}

export function patchElfinderDialogPlugin(jq: JQueryWithUi): void {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const jqAny = jq as any
  const origElfDialog = jqAny.fn.elfinderdialog as ((...args: unknown[]) => unknown) | undefined
  if (origElfDialog && !(origElfDialog as any).__bitfmPatched) {
    const patched = function (this: unknown, opts: unknown, fm: unknown, ...rest: unknown[]) {
      if (typeof opts === 'string' && fm == null) {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const $this = this as any
        const stored =
          $this.data?.('elfinder-fm') ||
          $this.closest?.('.ui-dialog')?.data?.('elfinder-fm')
        if (stored) fm = stored
      }
      return origElfDialog.call(this, opts, fm, ...rest)
    }
    // Preserve .defaults so Object.assign({}, $.fn.elfinderdialog.defaults, opts)
    // inside the original plugin still receives the correct defaults object.
    ;(patched as typeof jqAny.fn.elfinderdialog).defaults = (origElfDialog as typeof jqAny.fn.elfinderdialog).defaults
    ;(patched as any).__bitfmPatched = true
    jqAny.fn.elfinderdialog = patched
  }
}
