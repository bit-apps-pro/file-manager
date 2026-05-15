import { type JSX } from 'react'
import { NavLink, useLocation } from 'react-router-dom'

import { $appConfig } from '@common/globalStates'
import Fade from '@utilities/Fade'
import { type GlobalToken } from 'antd/es/theme/interface'
import { motion } from 'framer-motion'
import { useAtomValue } from 'jotai'

import cls from './Sidebar.module.css'
import SidebarNavItemWithTooltip from './SidebarNavItemWithTooltip'

interface SidebarNavProps {
  props: {
    path: string
    label: string | JSX.Element
    icon: JSX.Element
  }
}

const navItemActiveStyle = ({ token }: { token: GlobalToken }) => ({
  background: token.colorText,
  width: '100%',
  height: '100%',
  position: 'absolute' as any, // eslint-disable-line @typescript-eslint/no-explicit-any
  zIndex: -1,
  borderRadius: token.borderRadius,
  inset: 0
})

export default function SidebarNavItem({ props: { path, label, icon } }: SidebarNavProps) {
  const location = useLocation()
  const { isSidebarCollapsed } = useAtomValue($appConfig)

  let isActive = false
  if (path === location.pathname) {
    isActive = true
  } else if (
    location.pathname !== '/' &&
    path !== '/' &&
    location.pathname.match(new RegExp(path, 'gi'))
  ) {
    isActive = true
  }

  const navItemStyle = ({ token }: { token: GlobalToken }) => ({
    color: `${isActive ? token.colorBgContainer : token.colorText}!important`,
    borderRadius: token.borderRadius,
    padding: isSidebarCollapsed ? 11 : '10px 15px',
    '&:hover': {
      color: isActive ? token.colorBgContainer : token.colorText,
      background: `${token.colorFillTertiary}`
    },
    '&:focus': {
      color: isActive ? token.colorBgContainer : token.colorText
    }
  })

  return (
    <SidebarNavItemWithTooltip label={label}>
      <NavLink
        className={`${cls.navItem} ${isActive ? cls.navItemActive : ''}`}
        to={path}
        css={navItemStyle}
      >
        {icon}
        <Fade is={!isSidebarCollapsed} initialDelay={0.2}>
          {label}
        </Fade>
        {isActive && <motion.span css={navItemActiveStyle} layoutId="sidebar-nav-item-active" />}
      </NavLink>
    </SidebarNavItemWithTooltip>
  )
}
