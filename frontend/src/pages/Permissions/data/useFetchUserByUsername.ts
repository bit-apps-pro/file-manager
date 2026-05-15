import request from '@common/helpers/request'
import { type FetchUsersType, type User } from '@pages/Permissions/PermissionsSettingsTypes'
import { keepPreviousData, useInfiniteQuery } from '@tanstack/react-query'

export default function useFetchUserByUsername(search: string) {
  const { data, isPending, fetchNextPage, hasNextPage, isFetching, isFetchingNextPage } =
    useInfiniteQuery<FetchUsersType>({
      refetchOnWindowFocus: false,
      queryKey: ['permissions/user/get', search],
      queryFn: async ({ pageParam, signal }: { pageParam: unknown; signal: AbortSignal }) => {
        const response = await request<FetchUsersType>({
          action: 'permissions/user/get',
          method: 'GET',
          queryParam: { search, page: pageParam as number },
          signal
        })

        return response.data
      },
      initialPageParam: 1,
      placeholderData: keepPreviousData,
      getNextPageParam: (lastPage: FetchUsersType) => {
        const nextPage = Number(lastPage.current) + 1
        return nextPage <= lastPage.pages ? nextPage : undefined
      }
    })

  const users: Array<User> = []
  data?.pages.forEach((queryResponse: FetchUsersType) => users.push(...queryResponse.users))

  return {
    isLoading: isPending,
    fetchNextPage,
    hasNextPage,
    isFetching,
    isFetchingNextPage,
    users,
    total: data?.pages[0]?.total || 0,
    totalPages: data?.pages[0]?.pages || 0
  }
}
