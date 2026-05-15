import request from '@common/helpers/request'
import { useMutation } from '@tanstack/react-query'

export default function useDeleteUserPermission() {
  const { mutateAsync, isPending, variables } = useMutation({
    mutationFn: async (id: number) =>
      request({
        action: 'permissions/user/delete',
        data: { id }
      })
  })

  return {
    deletePermission: (id: number) => mutateAsync(id),
    isUserPermissionDeleting: isPending,
    delInProgressId: variables
  }
}
