import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { HashRouter } from 'react-router-dom'

import config from '@config/config'
import '@resource/styles/global.css'
import '@resource/styles/plugin.css'
import '@resource/styles/utilities.sass'
import '@resource/styles/variables.css'
import '@resource/styles/wp-css-reset.css'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'
import 'antd/dist/reset.css'

import AppRoutes from './AppRoutes'

const { BASE_URL } = config

if (!window.location.hash && BASE_URL) {
  window.location = BASE_URL
}

const queryClient = new QueryClient()
const elm = document.getElementById('bit-fm-root')
if (elm) {
  const root = createRoot(elm)

  root.render(
    <StrictMode>
      <QueryClientProvider client={queryClient}>
        <HashRouter window={window}>
          <AppRoutes />
        </HashRouter>
        <ReactQueryDevtools initialIsOpen={false} buttonPosition="bottom-right" />
      </QueryClientProvider>
    </StrictMode>
  )
}
