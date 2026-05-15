import { type JSX } from 'react'

interface IfProps {
  children: JSX.Element | string
  // Else?: JSX.Element | string
  conditions?: any // eslint-disable-line @typescript-eslint/no-explicit-any
  [key: string]: any // eslint-disable-line @typescript-eslint/no-explicit-any
}

export default function If({ children, conditions, ...restProps }: IfProps) {
  const keys = Object.keys(restProps)

  let isConditionsAreTrue = true
  if (Array.isArray(conditions)) {
    isConditionsAreTrue = conditions.every(condition => condition)
  } else if (!Array.isArray(conditions) && !conditions) {
    isConditionsAreTrue = false
  }

  const isTrue = keys.every(key => {
    if (
      (typeof key === 'string' || typeof key === 'number') &&
      restProps &&
      key in restProps &&
      restProps[key]
    ) {
      return true
    }

    return false
  })

  if (isTrue && isConditionsAreTrue) return children

  return null
}
