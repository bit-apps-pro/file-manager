import { type RefObject } from 'react'

import config, { getOptionVariable } from '@config/config'
import { installAutoHeight, resolveInitialHeight } from '@lib/elfinder/autoHeight'
import { elevateDialogLayers, injectDockStyles, watchForBottomTray } from '@lib/elfinder/dialogElevation'
import registerEmailtoCommand from '@lib/elfinder/emailtoCommand'
import {
  applyAppendToBodyDefault,
  patchCodeMirror,
  patchElfinderDialogPlugin,
  patchFmDialog,
  patchToFront
} from '@lib/elfinder/patches'
import { type FinderWithDialog, type JQueryWithUi } from '@lib/elfinder/types'
import { type FinderInstance } from 'elfinder'

export default function configureElFinder(finderRef: RefObject<HTMLDivElement>): FinderInstance {
  const jq = (window as typeof window & { jQuery: JQueryWithUi }).jQuery
  const { AJAX_URL: rawAjaxUrl, NONCE, LANG, THEME, ViewType, ACTION } = config
  // Normalize scheme to match the current page — prevents "Insecure download blocked"
  // when WordPress is behind an SSL-terminating reverse proxy that doesn't set HTTPS headers.
  const AJAX_URL = rawAjaxUrl.replace(/^https?:/, window.location.protocol)
  const themes = getOptionVariable('themes', [])
  themes.default = { name: 'Default' }

  applyAppendToBodyDefault(jq)
  registerEmailtoCommand()

  const $el = jq(finderRef.current)
  const configuredHeight = getOptionVariable('height')
  const { height, isAuto: heightIsAuto } = resolveInitialHeight(configuredHeight, finderRef.current)

  const finder = $el.elfinder({
    url: AJAX_URL,
    customData: {
      action: ACTION,
      nonce: NONCE
    },
    theme: THEME,
    lang: LANG,
    cssAutoLoad: getOptionVariable('cssAutoLoad'),
    // Keep elFinder off the URL hash — the SPA HashRouter owns it. Its own
    // toolbar back/forward uses an internal history stack, so it stays intact.
    useBrowserHistory: false,
    contextmenu: getOptionVariable('contextmenu'),
    requestType: getOptionVariable('requestType'),
    themes,
    width: getOptionVariable('width'),
    height,
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
    cdns: getOptionVariable('cdns'),
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

  patchToFront(finder)
  patchFmDialog(finder, jq)
  patchElfinderDialogPlugin(jq)
  patchCodeMirror(finder)
  elevateDialogLayers(jq)
  injectDockStyles()
  finder.bind('load', watchForBottomTray)

  // Intercept dblclick: open text/code files in the editor instead of downloading
  const origDblclick = finder.dblclick?.bind(finder)
  if (origDblclick) {
    finder.dblclick = (data: { file?: string }) => {
      const hash = data?.file
      if (hash) {
        const file = finder.file(hash) as unknown as
          | { mime: string; name: string; hash: string; [key: string]: unknown }
          | undefined
        const mime: string = file?.mime ?? ''
        const editable =
          mime.startsWith('text/') ||
          mime === 'application/javascript' ||
          mime === 'application/x-javascript' ||
          mime === 'application/json' ||
          mime === 'application/xml' ||
          mime === 'application/x-php' ||
          mime === 'application/x-sh' ||
          mime === 'application/x-perl' ||
          mime === 'application/x-python' ||
          mime === 'application/x-ruby'
        if (editable) {
          finder.exec('edit', hash)
          return finder
        }
      }
      return origDblclick(data)
    }
  }

  if (heightIsAuto) {
    installAutoHeight($el, finder)
  }

  if (finder?.theme?.name && finder.theme.name !== THEME) {
    window.location.reload()
  }
  finder.storage('lang', LANG)
  finder?.changeTheme(THEME).storage('theme', THEME)
  finder.storage('view', ViewType)

  return finder
}
