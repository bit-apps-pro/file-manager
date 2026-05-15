import { type JSX } from 'react'

interface AntIconWrapperPropsTypes {
  children: JSX.Element
}

export default function AntIconWrapper({ children }: AntIconWrapperPropsTypes): JSX.Element {
  return (
    <span className="anticon" role="img">
      {children}
    </span>
  )
}
