import { installAutoHeight, resolveInitialHeight } from '@lib/elfinder/autoHeight'
import { elevateDialogLayers, injectDockStyles, watchForBottomTray } from '@lib/elfinder/dialogElevation'
import { registerEmailtoCommand } from '@lib/elfinder/emailtoCommand'
import { applyAppendToBodyDefault, patchElfinderDialogPlugin, patchFmDialog, patchToFront } from '@lib/elfinder/patches'
import { type BitappsFmGlobals, type FinderWithDialog, type JQueryWithUi } from '@lib/elfinder/types'

const w = window as typeof window & { jQuery: JQueryWithUi; bitapps_fm: BitappsFmGlobals }

w.jQuery(document).ready(() => {
  const jq = w.jQuery
  const fmConfig = w.bitapps_fm

  registerEmailtoCommand()
  applyAppendToBodyDefault(jq)

  const $finderEl = jq('#file-manager')
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const el = ($finderEl as any).get?.(0) as HTMLElement | null
  const { height, isAuto: heightIsAuto } = resolveInitialHeight(fmConfig.options.height, el)

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const finderResult = ($finderEl as any).elfinder({
    url: fmConfig.ajaxURL,
    themes: fmConfig.options.themes,
    theme: fmConfig.options.theme,
    cssAutoLoad: fmConfig.options.cssAutoLoad,
    contextmenu: fmConfig.options.contextmenu,
    customData: {
      action: fmConfig.action,
      nonce: fmConfig.nonce
    },
    lang: fmConfig.options.lang,
    requestType: fmConfig.options.requestType,
    width: fmConfig.options.width,
    height,
    commandsOptions: fmConfig.options.commandsOptions,
    commands: fmConfig.options.commands,
    disabled: fmConfig.options.disabled,
    rememberLastDir: fmConfig.options.rememberLastDir,
    reloadClearHistory: fmConfig.options.reloadClearHistory,
    defaultView: fmConfig.options.defaultView,
    ui: fmConfig.options.ui,
    sortOrder: fmConfig.options.sortOrder,
    sortStickFolders: fmConfig.options.sortStickFolders,
    dragUploadAllow: fmConfig.options.dragUploadAllow,
    fileModeStyle: fmConfig.options.fileModeStyle,
    resizable: fmConfig.options.resizable,
    handlers: {
      dblclick() {
        const disabled = fmConfig?.options?.disabled ?? []
        if (disabled.includes('dblclick') || disabled.includes('download') || disabled.includes('get')) {
          return false
        }
      }
    }
  })

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const fm = finderResult?.[0]?.elfinder as FinderWithDialog | undefined
  if (!fm) return

  patchToFront(fm)
  patchFmDialog(fm, jq)
  patchElfinderDialogPlugin(jq)
  elevateDialogLayers(jq)
  injectDockStyles()
  fm.bind('load', watchForBottomTray)

  if (heightIsAuto) {
    installAutoHeight($finderEl, fm)
  }
})
