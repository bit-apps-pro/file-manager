import { type FinderWithDialog } from '@lib/elfinder/types'

/**
 * Compute initial elfinder height from config. Returns the height value to pass
 * to the elfinder constructor and whether auto-resize should be installed.
 */
export function resolveInitialHeight(
  configured: unknown,
  el: HTMLElement | null
): { height: number | string; isAuto: boolean } {
  const isAuto = !configured || !/^\d+(\.\d+)?(px)?$/.test(String(configured))
  if (!isAuto) return { height: configured as number | string, isAuto: false }
  const elTop = el ? Math.round(el.getBoundingClientRect().top) : 0
  return { height: Math.max(320, window.innerHeight - elTop - 10), isAuto: true }
}

/**
 * Bind viewport-fill resize behaviour after the finder is constructed.
 * Caller must only invoke this when isAuto === true.
 */
export function installAutoHeight(
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  $el: any,
  finder: FinderWithDialog
): void {
  const fitToViewport = (): void => {
    const el: HTMLElement | null =
      typeof $el.get === 'function' ? ($el.get(0) as HTMLElement | null) : null
    const elTop = el ? Math.round(el.getBoundingClientRect().top) : 0
    const target = Math.max(320, window.innerHeight - elTop - 8)
    $el.height(target)
    $el.trigger('resize')
  }
  finder.bind('load init open', fitToViewport)
  window.addEventListener('resize', fitToViewport)
  setTimeout(fitToViewport, 300)
}
