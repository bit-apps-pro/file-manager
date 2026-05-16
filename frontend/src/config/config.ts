// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
const serverVariables = typeof window.bitapps_fm === 'undefined' ? {} : window.bitapps_fm
const options = serverVariables?.options ?? {}
export function getServerVariable(key: string, fallback?: unknown) {
  if (!(key in serverVariables) || !serverVariables[key]) {
    console.error('🚥🚥🚥 Missing server variable: ', key) // eslint-disable-line no-console
    return fallback
  }
  return serverVariables[key]
}

export function getOptionVariable(key: string, fallback?: unknown) {
  if (!(key in options) || options[key] === undefined || options[key] === null) {
    console.error('🚥🚥🚥 Missing option: ', key) // eslint-disable-line no-console
    return fallback
  }
  return options[key]
}

function getThemes() {
  const themes = getOptionVariable('themes', {})

  const formattedTheme: Array<{
    key: string
    title: string
  }> = []

  Object.keys(themes).map(key => {
    const title = (key.charAt(0).toUpperCase() + key.slice(1)).split('-').join(' ')
    formattedTheme.push({
      title,
      key
    })
  })
  formattedTheme.push({ key: 'default', title: 'Default' })
  return formattedTheme
}

const config = {
  IS_DEV: true,
  PRODUCT_NAME: 'Bit File Manager',
  PLUGIN_SLUG: getServerVariable('pluginSlug'),
  AJAX_URL: getServerVariable('ajaxURL', '://wp-admin/admin-ajax.php'),
  API_BASE: getServerVariable('apiBase', '://wp-admin/admin-ajax.php'),
  ROOT_URL: getServerVariable('rootURL', '://'),
  BASE_URL: getServerVariable('baseURL', ''),
  NONCE: getServerVariable('nonce', ''),
  ACTION: getServerVariable('action', ''),
  TELEMETRY: getServerVariable('telemetry', {}),
  ROUTE_PREFIX: getServerVariable('routePrefix', 'bitapps_fm_'),
  BANNER: null, // getOptionVariable('banner', null),
  SYS_INFO: getServerVariable('sys_info', null),
  THEMES: getThemes(),
  THEME: getOptionVariable('theme', 'default'),
  LANG: getOptionVariable('lang', 'en'),
  ViewType: getOptionVariable('defaultView', 'icons')
}

export default config
