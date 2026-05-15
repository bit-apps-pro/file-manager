import request from '@common/helpers/request'
import { useMutation } from '@tanstack/react-query'

export default function useDeleteLog() {
  const { mutateAsync, isPending } = useMutation({
    mutationFn: async (ids: number[]) => request({ action: 'logs/delete', data: { ids } })
  })

  return {
    deleteLog: (ids: number[]) => mutateAsync(ids),
    isLogDeleting: isPending
  }
}
