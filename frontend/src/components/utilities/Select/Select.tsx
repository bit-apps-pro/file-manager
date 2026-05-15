import { useEffect, useId } from 'react'

import { Select as SelectAnt, type SelectProps, Space, Typography, theme } from 'antd'

export interface SelectPropsType extends SelectProps {
  label?: string
  invalidMessage?: string
  helperText?: string
  suffix?: React.ReactNode
  wrapperClassName?: string
  onRender?: (e?: any) => void // eslint-disable-line @typescript-eslint/no-explicit-any
}

export default function Select({
  label,
  className,
  status,
  invalidMessage,
  helperText,
  suffix,
  wrapperClassName,
  onRender,
  ...props
}: SelectPropsType) {
  const id = useId()
  const { token } = theme.useToken()

  useEffect(() => {
    onRender?.()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  return (
    <div className={wrapperClassName}>
      {label && (
        <label htmlFor={id} className="mb-1 d-ib" css={{ color: token.colorText }}>
          {label}
        </label>
      )}

      <Space.Compact className="w-100">
        <SelectAnt
          id={id}
          status={status}
          className={`${className} w-100`}
          data-testid="selectComponent"
          {...props} // eslint-disable-line react/jsx-props-no-spreading
        />
        {suffix}
      </Space.Compact>

      {status === 'error' && invalidMessage && (
        <Typography.Text type="danger">{invalidMessage}</Typography.Text>
      )}
      {!invalidMessage && helperText && <Typography.Text type="secondary">{helperText}</Typography.Text>}
    </div>
  )
}
