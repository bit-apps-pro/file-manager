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

type CmModeInfo = { name: string; mode: string; ext?: string[]; mime: string; mimes?: string[] }

// Matches CodeMirror mode/meta.js shape, restricted to the 16 modes WP core ships
// in /wp-includes/js/codemirror/codemirror.min.js.
const WP_CM_MODES: CmModeInfo[] = [
  { name: 'C', mode: 'clike', ext: ['c', 'h', 'ino'], mime: 'text/x-csrc' },
  { name: 'C++', mode: 'clike', ext: ['cpp', 'cxx', 'cc', 'hpp'], mime: 'text/x-c++src' },
  { name: 'Java', mode: 'clike', ext: ['java'], mime: 'text/x-java' },
  { name: 'C#', mode: 'clike', ext: ['cs'], mime: 'text/x-csharp' },
  { name: 'CSS', mode: 'css', ext: ['css'], mime: 'text/css' },
  { name: 'SCSS', mode: 'css', ext: ['scss'], mime: 'text/x-scss' },
  { name: 'SASS', mode: 'sass', ext: ['sass'], mime: 'text/x-sass' },
  { name: 'Diff', mode: 'diff', ext: ['diff', 'patch'], mime: 'text/x-diff' },
  { name: 'HTML', mode: 'htmlmixed', ext: ['html', 'htm'], mime: 'text/html' },
  { name: 'HTTP', mode: 'http', mime: 'message/http' },
  {
    name: 'JavaScript',
    mode: 'javascript',
    ext: ['js', 'mjs'],
    mime: 'text/javascript',
    mimes: ['text/javascript', 'application/javascript', 'application/x-javascript']
  },
  { name: 'JSON', mode: 'javascript', ext: ['json', 'map'], mime: 'application/json' },
  { name: 'JSX', mode: 'jsx', ext: ['jsx'], mime: 'text/jsx' },
  { name: 'Markdown', mode: 'markdown', ext: ['md', 'markdown'], mime: 'text/x-markdown' },
  { name: 'GFM', mode: 'gfm', mime: 'text/x-gfm' },
  { name: 'Nginx', mode: 'nginx', mime: 'text/x-nginx-conf' },
  {
    name: 'PHP',
    mode: 'php',
    ext: ['php', 'php3', 'php4', 'php5', 'php7', 'phtml'],
    mime: 'application/x-httpd-php',
    mimes: ['application/x-httpd-php', 'application/x-httpd-php-open', 'text/x-php']
  },
  { name: 'Shell', mode: 'shell', ext: ['sh', 'bash'], mime: 'application/x-sh' },
  { name: 'SQL', mode: 'sql', ext: ['sql'], mime: 'text/x-sql' },
  { name: 'XML', mode: 'xml', ext: ['xml', 'svg', 'xsl'], mime: 'application/xml' },
  { name: 'YAML', mode: 'yaml', ext: ['yml', 'yaml'], mime: 'text/x-yaml' }
]

const WP_CM_PATH = /\/wp-includes\/js\/codemirror\//

type CmGlobal = Record<string, unknown> & {
  modeInfo?: unknown
  autoLoadMode?: unknown
  findModeByMIME?: (mime: string) => CmModeInfo | null
  findModeByExtension?: (ext: string) => CmModeInfo | null
  findModeByName?: (name: string) => CmModeInfo | null
  requireMode?: (mode: unknown, cb?: () => void) => void
}

type LoadScriptFn = (urls: string[], cb?: (cm?: unknown) => void, ...rest: unknown[]) => unknown
type LoadCssFn = (url: string) => unknown

export function patchCodeMirror(finder: FinderWithDialog): void {
  const w = window as typeof window & {
    wp?: { CodeMirror?: CmGlobal }
    CodeMirror?: CmGlobal
  }
  const cm = w.wp?.CodeMirror
  if (!cm) return
  w.CodeMirror = cm

  if (!cm.modeInfo) {
    cm.modeInfo = WP_CM_MODES
    cm.findModeByMIME = mime => {
      const m = mime.toLowerCase()
      return WP_CM_MODES.find(i => i.mime === m || i.mimes?.includes(m)) ?? null
    }
    cm.findModeByExtension = ext => {
      const e = ext.toLowerCase()
      return WP_CM_MODES.find(i => i.ext?.includes(e)) ?? null
    }
    cm.findModeByName = name => {
      const n = name.toLowerCase()
      return WP_CM_MODES.find(i => i.name.toLowerCase() === n) ?? null
    }
  }

  // Modes are pre-bundled in WP core's codemirror.min.js — no dynamic loads needed.
  if (typeof cm.autoLoadMode !== 'function') {
    cm.autoLoadMode = () => {}
    cm.requireMode = (_m, cb) => cb?.()
  }

  // Short-circuit elFinder's CodeMirror asset loads so it doesn't 404 on
  // addon/mode/loadmode.min.js, mode/meta.min.js, etc. that WP doesn't ship.
  const fAny = finder as unknown as { loadScript?: LoadScriptFn; loadCss?: LoadCssFn }
  const origLoadScript = fAny.loadScript?.bind(finder)
  if (origLoadScript) {
    fAny.loadScript = (urls, cb, ...rest) => {
      if (Array.isArray(urls) && urls.every(u => WP_CM_PATH.test(String(u)))) {
        cb?.(w.CodeMirror)
        return
      }
      return origLoadScript(urls, cb, ...rest)
    }
  }
  const origLoadCss = fAny.loadCss?.bind(finder)
  if (origLoadCss) {
    fAny.loadCss = url => {
      if (typeof url === 'string' && WP_CM_PATH.test(url)) return
      return origLoadCss(url)
    }
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
