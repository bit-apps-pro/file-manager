import { resolveNotification } from '@common/notificationInstance'
import { afterEach, describe, expect, it, vi } from 'vitest'

import bridgeElFinderNotifications from './notificationBridge'
import { type FinderWithDialog } from './types'

vi.mock('@common/notificationInstance', () => {
  const api = { success: vi.fn(), info: vi.fn(), warning: vi.fn(), error: vi.fn() }
  return { resolveNotification: () => api }
})

const notification = resolveNotification() as unknown as {
  success: ReturnType<typeof vi.fn>
  info: ReturnType<typeof vi.fn>
  warning: ReturnType<typeof vi.fn>
  error: ReturnType<typeof vi.fn>
}

type ErrorHandler = (event: { data?: { error?: unknown; opts?: unknown } }) => unknown

function setup() {
  const nativeToast = vi.fn()
  let errorHandler: ErrorHandler = () => undefined
  const finder = {
    toast: nativeToast,
    i18n: (message: unknown) => String(message),
    bind: (events: string, handler: ErrorHandler, priorityFirst?: boolean) => {
      if (events === 'error' && priorityFirst) {
        errorHandler = handler
      }
      return finder
    }
  } as unknown as FinderWithDialog

  bridgeElFinderNotifications(finder)
  return { finder, nativeToast, getErrorHandler: () => errorHandler }
}

describe('bridgeElFinderNotifications', () => {
  afterEach(() => vi.clearAllMocks())

  it('routes a plain toast to the matching antd notification', () => {
    const { finder } = setup()
    finder.toast({ mode: 'error', msg: 'boom' })
    expect(notification.error).toHaveBeenCalledWith(expect.objectContaining({ message: 'boom' }))
  })

  it("defaults a mode-less toast to elFinder's success mode", () => {
    const { finder } = setup()
    finder.toast({ msg: 'done' })
    expect(notification.success).toHaveBeenCalledWith(expect.objectContaining({ message: 'done' }))
  })

  it('maps timeOut (ms) to antd duration (seconds)', () => {
    const { finder } = setup()
    finder.toast({ mode: 'info', msg: 'hi', timeOut: 5000 })
    expect(notification.info).toHaveBeenCalledWith(expect.objectContaining({ duration: 5 }))
  })

  it('keeps interactive/sticky toasts native', () => {
    const { finder, nativeToast } = setup()
    finder.toast({ mode: 'info', msg: 'wait', button: { text: 'Undo' } })
    finder.toast({ mode: 'info', msg: 'sticky', timeOut: 0 })
    expect(nativeToast).toHaveBeenCalledTimes(2)
    expect(notification.info).not.toHaveBeenCalled()
  })

  it('flattens toast HTML and resolves %token% placeholders without re-i18n', () => {
    const { finder } = setup()
    finder.toast({ mode: 'warning', msg: 'Max <span class="v">5</span> in %cwd%' })
    expect(notification.warning).toHaveBeenCalledWith(
      expect.objectContaining({ message: 'Max 5 in cwd' })
    )
  })

  it('routes an error event to a persistent antd notification and suppresses the native dialog', () => {
    const { getErrorHandler } = setup()
    const result = getErrorHandler()({ data: { error: 'errAccess' } })
    expect(notification.error).toHaveBeenCalledWith({ message: 'errAccess', duration: 0 })
    expect(result).toBe(false)
  })

  it('flattens elFinder HTML (entities + tags) to plain text', () => {
    const { getErrorHandler } = setup()
    getErrorHandler()({
      data: { error: 'You don&#039;t have permission <span class="v">test.php</span>' }
    })
    expect(notification.error).toHaveBeenCalledWith({
      message: "You don't have permission test.php",
      duration: 0
    })
  })

  it('lets must-acknowledge (modal) errors fall through to the native dialog', () => {
    const { getErrorHandler } = setup()
    const result = getErrorHandler()({ data: { error: 'errNetMount', opts: { modal: true } } })
    expect(notification.error).not.toHaveBeenCalled()
    expect(result).toBeUndefined()
  })

  it('ignores empty error events', () => {
    const { getErrorHandler } = setup()
    const result = getErrorHandler()({ data: {} })
    expect(notification.error).not.toHaveBeenCalled()
    expect(result).toBeUndefined()
  })
})
