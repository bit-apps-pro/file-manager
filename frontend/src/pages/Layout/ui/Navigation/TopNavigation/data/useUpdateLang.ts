import request from '@common/helpers/request'
import { useMutation } from '@tanstack/react-query'

export default function useUpdateLang() {
  const { mutateAsync } = useMutation({
    mutationFn: async (lang: string) => request({ action: 'language/update', data: { lang } })
  })

  return {
    updateLanguage: (lang: string) => mutateAsync(lang)
  }
}
