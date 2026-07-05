import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'

import config from '@config/config'

const CURRENT_CLASS = 'current'
const DEFAULT_HASH = '#/home'

/**
 * Mirror the active SPA route onto the WordPress admin submenu highlight.
 * WordPress resolves the current menu server-side from the `page` query var
 * alone and never sees the client-side hash route, so it can't keep the right
 * submenu item active on its own — we sync the DOM on every navigation.
 */
export default function useSyncAdminMenu() {
  const { pathname } = useLocation()

  useEffect(() => {
    const slug = config.PLUGIN_SLUG
    if (!slug) return

    const routeAnchor = document.querySelector<HTMLAnchorElement>(`#adminmenu a[href*="page=${slug}#/"]`)
    const menuItem = routeAnchor?.closest('#adminmenu > li')
    if (!menuItem) return

    const activeHash = pathname === '/' ? DEFAULT_HASH : `#${pathname}`
    const anchors = Array.from(menuItem.querySelectorAll<HTMLAnchorElement>('.wp-submenu a'))

    anchors.forEach(anchor => {
      anchor.classList.remove(CURRENT_CLASS)
      anchor.removeAttribute('aria-current')
      anchor.closest('li')?.classList.remove(CURRENT_CLASS)
    })

    const matched = anchors.find(anchor => anchor.hash === activeHash)
    if (matched) {
      matched.classList.add(CURRENT_CLASS)
      matched.setAttribute('aria-current', 'page')
      matched.closest('li')?.classList.add(CURRENT_CLASS)
    }
  }, [pathname])
}
