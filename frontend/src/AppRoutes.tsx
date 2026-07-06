import { useEffect } from 'react'
import { Route, Routes } from 'react-router-dom'

import { StyleProvider } from '@ant-design/cssinjs'
import { $appConfig } from '@common/globalStates'
import { removeUnwantedCSS, setAppBgFromAdminBg } from '@common/helpers/globalHelpers'
import useSyncAdminMenu from '@common/hooks/useSyncAdminMenu'
import { setNotificationInstance } from '@common/notificationInstance'
import { darkThemeComponentToken, darkThemeToken } from '@config/themes/theme.dark'
import { lightThemeComponentToken, lightThemeToken } from '@config/themes/theme.light'
import loadable from '@loadable/component'
import Layout from '@pages/Layout'
import Root from '@pages/root/Root'
import { ConfigProvider, Flex, Spin, notification, theme } from 'antd'
import { useAtomValue } from 'jotai'

const Support = loadable(() => import('@pages/Support'), { fallback: <Loader /> })
const Logs = loadable(() => import('@pages/Logs'), { fallback: <Loader /> })
const Settings = loadable(() => import('@pages/Settings'), { fallback: <Loader /> })
const Permissions = loadable(() => import('@pages/Permissions'), { fallback: <Loader /> })
const SystemInformation = loadable(() => import('@pages/SystemInformation'), { fallback: <Loader /> })

const { defaultAlgorithm, darkAlgorithm } = theme

const NOTIFICATION_PLACEMENT = 'bottomRight'
// Configure the static notification instance (used by callers outside the tree) once.
notification.config({ placement: NOTIFICATION_PLACEMENT })

export default function AppRoutes() {
  const { isDarkTheme } = useAtomValue($appConfig)
  const themeTokens = isDarkTheme ? darkThemeToken : lightThemeToken
  const componentTokens = isDarkTheme ? darkThemeComponentToken : lightThemeComponentToken
  const themeAlgorithm = isDarkTheme ? darkAlgorithm : defaultAlgorithm

  useSyncAdminMenu()

  useEffect(() => {
    removeUnwantedCSS()
    setAppBgFromAdminBg()
  }, [])

  // No maxCount: it force-destroys the oldest notice, which silently evicts a
  // persistent error the moment transient toasts arrive. `stack` keeps the pile
  // tidy without dropping anything.
  const [notificationApi, contextHolder] = notification.useNotification({
    placement: NOTIFICATION_PLACEMENT
  })

  useEffect(() => {
    setNotificationInstance(notificationApi)
    return () => setNotificationInstance(null)
  }, [notificationApi])
  return (
    <ConfigProvider
      theme={{
        algorithm: themeAlgorithm,
        token: themeTokens,
        components: componentTokens
      }}
    >
      <StyleProvider hashPriority="high">
        {contextHolder}
        <Routes>
          <Route path="/" element={<Layout />}>
            <Route path="/support" element={<Support />} />
            <Route path="/logs" element={<Logs />} />
            <Route path="/settings" element={<Settings />} />
            <Route path="/permissions" element={<Permissions />} />
            <Route path="/system-info" element={<SystemInformation />} />
            <Route path="/home" element={<Root />} />
            <Route index element={<Root />} />
            <Route path="*" element={<Root />} />
          </Route>
        </Routes>
      </StyleProvider>
    </ConfigProvider>
  )
}

function Loader() {
  return (
    <Flex
      align="center"
      justify="center"
      style={{ minWidth: 'calc(100vw - 180px)', minHeight: '100vh' }}
    >
      <Spin size="large" />
    </Flex>
  )
}
