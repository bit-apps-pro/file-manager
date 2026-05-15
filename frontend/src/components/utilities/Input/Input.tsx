import { useEffect, useId } from 'react'

import { Input as InputAnt, type InputProps, Typography, theme } from 'antd'

export interface InputPropsType extends InputProps {
  label?: string
  invalidMessage?: string
  helperText?: string
  wrapperClassName?: string
  onRender?: (e?: any) => void // eslint-disable-line @typescript-eslint/no-explicit-any
}

export default function Input({
  label,
  status,
  invalidMessage,
  helperText,
  wrapperClassName,
  onRender,
  ...props
}: InputPropsType) {
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

      <InputAnt
        id={id}
        status={status}
        data-testid="inputComponent"
        {...props} // eslint-disable-line react/jsx-props-no-spreading
      />

      {status === 'error' && invalidMessage && (
        <Typography.Text type="danger">{invalidMessage}</Typography.Text>
      )}
      {!invalidMessage && helperText && <Typography.Text type="secondary">{helperText}</Typography.Text>}
    </div>
  )
}
