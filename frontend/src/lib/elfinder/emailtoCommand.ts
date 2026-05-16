import { type ElFinderConstructor, type FinderWithDialog } from '@lib/elfinder/types'

export function registerEmailtoCommand(): void {
  const elFinderCtor = (window as typeof window & { elFinder?: ElFinderConstructor }).elFinder
  if (!elFinderCtor) return
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  elFinderCtor.prototype.commands.emailto = function emailtoCommand(this: any) {
    const { fm }: { fm: FinderWithDialog } = this
    const filter = (files: Array<{ mime: string }>) => files.filter(f => f.mime !== 'directory')

    this.exec = (hashes: string[]) => {
      const url = String(fm.url(hashes[0], 0))
      const filename = url.split('/').pop() ?? ''
      // eslint-disable-next-line no-alert
      const emailTo = prompt('Please enter mail address')
      if (emailTo == null) return
      if (
        !/^(([^<>()[\].,;:\s@"]+(\.[^<>()[\].,;:\s@"]+)*)|(".+"))@(([^<>()[\].,;:\s@"]+\.)+[^<>()[\].,;:\s@"]{2,})$/i.test(
          emailTo
        )
      ) {
        // eslint-disable-next-line no-alert
        alert('Please enter a valid email address')
        return
      }
      window.open(`mailto:${emailTo}?subject=${encodeURIComponent(filename)}&body=${encodeURIComponent(url)}`)
    }

    this.getstate = (select: unknown) => {
      const sel: Array<{ mime: string }> = this.files(select)
      return sel.length && filter(sel).length === sel.length ? 0 : -1
    }
  }
}
