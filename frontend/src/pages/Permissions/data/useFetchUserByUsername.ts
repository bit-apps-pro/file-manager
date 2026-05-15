import request from '@common/helpers/request'
import { type FetchUsersType, type User } from '@pages/Permissions/PermissionsSettingsTypes'
import { type QueryFunctionContext, useInfiniteQuery } from '@tanstack/react-query'

export default function useFetchUserByUsername(search: string) {
  async function sendRequest({ pageParam = 1, signal }: QueryFunctionContext<string[], number>) {
    const response = await request<FetchUsersType>({
      action: 'permissions/user/get',
      method: 'GET',
      queryParam: { search, page: pageParam },
      signal
    })

    return response.data
  }

  const { data, isLoading, fetchNextPage, hasNextPage, isFetching, isFetchingNextPage } =
    useInfiniteQuery({
      refetchOnWindowFocus: false,
      queryKey: ['permissions/user/get', search],
      queryFn: sendRequest,
      keepPreviousData: true,
      // enabled: !!search,
      getNextPageParam: lastPage => {
        const nextPage = Number(lastPage.current) + 1
        return nextPage <= lastPage.pages ? nextPage : undefined
      }
    })

  const users: Array<User> = []
  data?.pages.forEach(queryResponse => users.push(...queryResponse.users))

  return {
    isLoading,
    fetchNextPage,
    hasNextPage,
    isFetching,
    isFetchingNextPage,
    users,
    total: data?.pages[0]?.total || 0,
    totalPages: data?.pages[0]?.pages || 0
  }
}
