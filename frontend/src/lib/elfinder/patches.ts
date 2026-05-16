import { elevateDialogLayers } from '@lib/elfinder/dialogElevation'
import { type FinderWithDialog, type JQueryWithUi } from '@lib/elfinder/types'

export function applyAppendToBodyDefault(jq: JQueryWithUi): void {
  if (jq.ui?.dialog?.prototype?.options) {
    jq.ui.dialog.prototype.options.appendTo = 'body'
  }
}

export function patchToFront(finder: FinderWithDialog): void {
  const finderAny = finder as FinderWithDialog & {
    toFront?: (target: unknown) => void
  }
  if (typeof finderAny.toFront === 'function') {
    const originalToFront = finderAny.toFront.bind(finder)
    finderAny.toFront = function patchedToFront(target: unknown) {
      originalToFront(target)
      // toFront() computes z-index from node.children('.ui-front') inside .elfinder,
      // but elevated dialogs live in <body>. Fix z-index only on the target element.
      const el: HTMLElement | undefined =
        (target as { 0?: HTMLElement } | null)?.[0] ??
        (target instanceof HTMLElement ? target : undefined)
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

const BITFM_PATCHED = Symbol('bitfmPatched')

type ElfinderdialogFn = ((...args: unknown[]) => unknown) & {
  defaults?: unknown
  [BITFM_PATCHED]?: boolean
}

export function patchElfinderDialogPlugin(jq: JQueryWithUi): void {
  const jqAny = jq as JQueryWithUi & { fn: Record<string, ElfinderdialogFn> }
  const origElfDialog = jqAny.fn.elfinderdialog as ElfinderdialogFn | undefined
  if (origElfDialog && !origElfDialog[BITFM_PATCHED]) {
    const patched: ElfinderdialogFn = function patchedElfinderdialog(
      this: unknown,
      opts: unknown,
      fm: unknown,
      ...rest: unknown[]
    ) {
      let resolvedFm = fm
      if (typeof opts === 'string' && resolvedFm == null) {
        const $this = this as {
          data?: (key: string) => unknown
          closest?: (selector: string) => { data?: (key: string) => unknown }
        }
        resolvedFm = $this.data?.('elfinder-fm') || $this.closest?.('.ui-dialog')?.data?.('elfinder-fm')
      }
      return origElfDialog.call(this, opts, resolvedFm, ...rest)
    }
    // Preserve .defaults so Object.assign({}, $.fn.elfinderdialog.defaults, opts)
    // inside the original plugin still receives the correct defaults object.
    patched.defaults = origElfDialog.defaults
    patched[BITFM_PATCHED] = true
    jqAny.fn.elfinderdialog = patched
  }
}
