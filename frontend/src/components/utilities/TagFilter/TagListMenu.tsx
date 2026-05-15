import { type ReactNode } from 'react'
import { cloneElement } from 'react'

import LucideIcn from '@icons/LucideIcn'
import { Button, type GlobalToken, Typography } from 'antd'

interface TagListMenuProps {
  isAddable: boolean
  handleOnAdd: () => void
  token: GlobalToken
}

const TagListMenu = ({ isAddable, handleOnAdd, token }: TagListMenuProps) =>
  function Menu(menu: ReactNode) {
    return (
      <div
        css={{
          backgroundColor: token.colorBgElevated,
          borderRadius: token.borderRadiusLG,
          boxShadow: token.boxShadowSecondary
        }}
      >
        <Typography.Title
          level={5}
          className="py-1 px-2"
          css={{
            borderBottom: `1px solid ${token.colorSplit}`,
            marginBottom: `0 !important`,
            fontSize: `14px !important`
          }}
          aria-label="tags list"
        >
          Tags
        </Typography.Title>
        {cloneElement(menu as React.ReactElement<React.HTMLAttributes<HTMLElement>>, {
          style: { boxShadow: 'none' }
        })}
        {isAddable && (
          <div className="p-2" css={{ borderTop: `1px solid ${token.colorSplit}` }}>
            <Button
              block
              icon={<LucideIcn name="Plus" />}
              size="small"
              onClick={handleOnAdd}
              aria-label="add-tag"
            >
              Add Tag
            </Button>
          </div>
        )}
      </div>
    )
  }

export default TagListMenu
