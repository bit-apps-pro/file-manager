import { type JSX, type ReactElement } from 'react'

import { $appConfig } from '@common/globalStates'
import { Tooltip } from 'antd'
import { useAtomValue } from 'jotai'

type NavTooltip = {
  children: ReactElement
  label: string | JSX.Element
}

export default function SidebarNavItemWithTooltip({ children, label }: NavTooltip) {
  const { isSidebarCollapsed } = useAtomValue($appConfig)

  if (!isSidebarCollapsed) {
    return children
  }

  return (
    <Tooltip title={label} placement="right">
      {children}
    </Tooltip>
  )
}
