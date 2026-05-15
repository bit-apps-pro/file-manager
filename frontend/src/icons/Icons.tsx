import { type JSX } from 'react'

import CheckCircle from './CheckCircle'
import CloseIcn from './CloseIcn'
import DashboardIcn from './DashboardIcn'
import EditIcon from './EditIcon'
import cls from './Icons.module.css'
import Plus from './Plus'
import SearchIcon from './SearchIcon'
import SunIcn from './SunIcn'

export default function Icons(): JSX.Element {
  const allIcons = [
    <CheckCircle size={20} stroke={2} />,
    <CloseIcn size={20} stroke={2} />,
    <DashboardIcn size={20} stroke={2} />,
    <EditIcon size={20} stroke={2} />,
    <Plus size={20} stroke={2} />,
    <SearchIcon size={20} stroke={2} />,
    <SunIcn size={20} stroke={2} />
  ]
  return (
    <div className={cls.iconsSection}>
      {allIcons.map(v => (
        <div className={cls.icon}>{v}</div>
      ))}
    </div>
  )
}
