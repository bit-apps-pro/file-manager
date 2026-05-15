import request from '@common/helpers/request'
import { useMutation } from '@tanstack/react-query'

export default function useUpdateTheme() {
  const { mutateAsync } = useMutation({
    mutationFn: async (theme: string) =>
      request<{ theme: Array<string> }>({ action: 'theme/update', data: { theme } })
  })

  return {
    updateTheme: (theme: string) => mutateAsync(theme)
  }
}
