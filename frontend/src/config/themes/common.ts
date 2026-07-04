import { type AliasToken } from 'antd/es/theme/internal'

const fontFamily =
  "'Outfit',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans',sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol','Noto Color Emoji'"

const commonThemeToken: Partial<AliasToken> = {
  fontFamily,
  borderRadius: 10,
  borderRadiusSM: 8,
  borderRadiusXS: 4,
  colorPrimary: '#006afe',
  colorSuccess: '#00ff7d',
  colorWarning: '#ffc041',
  // Lift all antd popups above the WP admin shell: #wpbody-content is a z-index:9999
  // sticky stacking context that otherwise buries the default z-index:1000 popups.
  zIndexPopupBase: 10000
}

export default commonThemeToken
