import { type FinderInstance } from 'elfinder'

export type JQueryWithUi = {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  (target: unknown): any
  fn: Record<string, unknown>
  ui?: {
    dialog?: {
      prototype?: {
        options?: {
          appendTo?: string
        }
      }
    }
  }
}

export type ElFinderConstructor = {
  prototype: { commands: Record<string, () => void> }
}

export type FinderWithDialog = FinderInstance & {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  dialog: (content: unknown, options?: unknown) => any
  toFront: (target: unknown) => void
  theme?: { name?: string }
  changeTheme: (theme: string) => FinderWithDialog
  storage: (key: string, value?: unknown) => FinderWithDialog
  resize: (width: number | string, height: number | string) => void
  bind: (events: string, handler: (...args: unknown[]) => void) => FinderWithDialog
  dblclick?: (data: { file?: string }) => unknown
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  file: (hash: string) => any
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  exec: (cmd: string, ...args: unknown[]) => any
  url: (hash: string, vol?: number) => string
}

export interface BitappsFmOptions {
  themes?: Record<string, unknown>
  theme?: string
  cssAutoLoad?: unknown
  contextmenu?: unknown
  lang?: string
  requestType?: string
  width?: number | string
  height?: number | string
  commandsOptions?: unknown
  commands?: unknown
  disabled?: string[]
  rememberLastDir?: unknown
  reloadClearHistory?: unknown
  defaultView?: string
  ui?: unknown
  sortOrder?: string
  sortStickFolders?: unknown
  dragUploadAllow?: unknown
  fileModeStyle?: unknown
  resizable?: unknown
  cdns?: Record<string, string>
}

export interface BitappsFmGlobals {
  ajaxURL: string
  action: string
  nonce: string
  options: BitappsFmOptions
}
