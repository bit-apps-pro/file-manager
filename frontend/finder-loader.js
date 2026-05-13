jQuery(document).ready(function () {
  var $ = jQuery
  const finder = jQuery('#file-manager').elfinder({
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
    height: bitapps_fm.options.height,
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
})
