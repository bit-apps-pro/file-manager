import { notification } from 'antd'

export type NotificationInstance = ReturnType<typeof notification.useNotification>[0]

let instance: NotificationInstance | null = null

export const setNotificationInstance = (api: NotificationInstance | null): void => {
  instance = api
}

// The static `notification` instance renders outside ConfigProvider, so it
// ignores the app's high `zIndexPopupBase` and ends up hidden behind the WP
// admin shell. Callers reachable from React should prefer the context-aware
// instance AppRoutes publishes here; the static fallback only covers the brief
// window before it is set.
export const resolveNotification = (): NotificationInstance => instance ?? notification
