/**
 * @vitest-environment jsdom
 */
import { cleanup, render, screen } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'

import AddUserPermissionModal from './AddUserPermissionModal'

// Data hooks hit the network/WP globals; stub them so the modal renders in isolation.
vi.mock('@pages/Permissions/data/useFetchPermissionsSettings', () => ({
  default: () => ({ refetch: vi.fn() })
}))
vi.mock('@pages/Permissions/data/useFetchUserByUsername', () => ({
  default: () => ({ users: [], fetchNextPage: vi.fn(), isFetching: false, isFetchingNextPage: false })
}))
vi.mock('@pages/Permissions/data/useUpdateUserPermission', () => ({
  default: () => ({ isUserPermissionUpdating: false, updateUserPermission: vi.fn() })
}))

describe('AddUserPermissionModal', () => {
  afterEach(cleanup)

  it('shows the file-manager-only subtitle when open', () => {
    render(<AddUserPermissionModal isModalOpen setIsModalOpen={vi.fn()} commands={['download']} />)
    expect(screen.getByText('Only affects actions inside File Manager')).toBeTruthy()
  })
})
