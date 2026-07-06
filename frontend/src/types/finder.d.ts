declare module 'elfinder' {
  export interface File {
    hash: string
    write: boolean
    volumeid: string
    i18n: string
    name: string
  }

  export interface CommandOptions {
    _userAction: boolean
    _currentType: string
  }

  export interface FinderInstance {
    id: string
    viewType: string
    changeTheme(theme: string): FinderInstance
    parents(hash: string): Array<string>
    cwd(): File
    file(hash: string, alsoHidden?: Array<string>): File
    bind(event: string, callback: (...args: unknown) => void, priorityFirst?: boolean): void
    storage(name: string, value: string): void
    destroy(): void
    open(): void
    reload(): void
    disable(): void
    enable(): void
    addCommand(commandName: string, commandOptions?: CommandOptions): void
    removeCommand(commandName: string): void
    exec(cmd: string, files?: Array<File> | string, opts?, dstHash?): void
    toast(options: Record<string, unknown>): unknown
  }
}
