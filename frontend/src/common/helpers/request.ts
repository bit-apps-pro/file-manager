import config from '@config/config'

/* eslint-disable no-restricted-syntax */

type MethodType = 'POST' | 'GET'

interface OptionsType {
  method: MethodType
  headers: Record<string, string>
  body?: string | FormData
  signal?: AbortSignal
}

interface QueryParam {
  [key: string]: string | number
}

interface DefaultResponse {
  created_at: string
  updated_at: string
}

interface RequestOptionsType {
  action: string
  data?: Record<string, unknown> | FormData | null | any // eslint-disable-line @typescript-eslint/no-explicit-any
  queryParam?: QueryParam | null
  method?: MethodType
  signal?: AbortSignal
}

export type ApiResponseType = Record<string, string | number>

export interface Response<T> {
  status: 'success' | 'error'
  data: T extends DefaultResponse ? T : T & DefaultResponse
  code: 'SUCCESS' | 'ERROR'
  message: string | undefined
}

export default async function request<T>({
  action,
  data,
  queryParam,
  method = 'POST',
  signal
}: RequestOptionsType): Promise<Response<T>> {
  const { API_BASE, NONCE } = config
  const uri = new URL(`${API_BASE}/${action}`, `${window.location.protocol}//${window.location.host}`)

  // append query params in url
  if (queryParam !== null) {
    for (const key in queryParam) {
      if (key) {
        uri.searchParams.append(key, queryParam[key as keyof QueryParam].toString())
      }
    }
  }

  const options: OptionsType = {
    method,
    headers: { 'x-wp-nonce': NONCE }
  }

  if (method.toLowerCase() === 'post') {
    options.body = data instanceof FormData ? data : JSON.stringify(data)
  }

  options.signal = signal
  return (await fetch(uri, options)
    .then(res => res.text())
    .then(res => {
      try {
        return JSON.parse(res)
      } catch {
        const parsedRes = res.match(/{"code":(?:[^{}]*)*}/)
        return parsedRes ? JSON.parse(parsedRes[0]) : { code: 'ERROR', data: res }
      }
    })) as Response<T>
}
