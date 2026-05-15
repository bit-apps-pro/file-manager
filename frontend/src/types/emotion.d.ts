/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @typescript-eslint/consistent-type-imports */
import '@emotion/react'
import { type GlobalToken } from 'antd'

declare module '@emotion/react' {
  export interface Theme {
    theme: import('@ant-design/cssinjs').Theme<
      // @ts-ignore
      import('./internal').SeedToken,
      // @ts-ignore
      import('./interface').MapToken
    >
    token: GlobalToken
    hashId: string
  }
}
