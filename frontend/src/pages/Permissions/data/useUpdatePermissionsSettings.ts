import request from '@common/helpers/request'
import { type PermissionsSettingsType } from '@pages/Permissions/PermissionsSettingsTypes'
import { useMutation } from '@tanstack/react-query'

export default function useUpdatePermissionsSettings() {
  const { mutateAsync, isPending } = useMutation({
    mutationFn: async (updatedPermissionsSettings: PermissionsSettingsType) =>
      request<PermissionsSettingsType & Record<string, unknown>>({
        action: 'permissions/update',
        data: updatedPermissionsSettings
      })
  })

  return {
    updatePermission: (updatedPermissionsSettings: PermissionsSettingsType) =>
      mutateAsync(updatedPermissionsSettings),
    isPermissionUpdating: isPending
  }
}
