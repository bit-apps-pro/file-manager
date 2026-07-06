import { useEffect, useState } from 'react'

import { DeleteFilled } from '@ant-design/icons'
import { __, sprintf } from '@common/helpers/i18nwrap'
import { resolveNotification } from '@common/notificationInstance'
import { type PermissionsSettingsType, type User } from '@pages/Permissions/PermissionsSettingsTypes'
import useDeleteUserPermission from '@pages/Permissions/data/useDeleteUserPermission'
import useFetchPermissionsSettings from '@pages/Permissions/data/useFetchPermissionsSettings'
import useUpdatePermissionsSettings from '@pages/Permissions/data/useUpdatePermissionsSettings'
import {
  Button,
  Card,
  Form,
  Input,
  Popconfirm,
  Radio,
  Select,
  Space,
  Switch,
  Tooltip,
  Typography
} from 'antd'

import AddUserPermissionModal from './AddUserPermissionModal'

function Permissions() {
  const { useForm } = Form
  const { isLoading, permissions, commands, fileTypes, roles, users, refetch } =
    useFetchPermissionsSettings()
  const { updatePermission, isPermissionUpdating } = useUpdatePermissionsSettings()
  const { deletePermission, isUserPermissionDeleting, delInProgressId } = useDeleteUserPermission()

  const [isModalOpen, setIsModalOpen] = useState<boolean>(false)
  const [form] = useForm()

  useEffect(() => {
    form.setFieldsValue(permissions)
  }, [permissions, form])

  const handleSubmit = (changedValues: PermissionsSettingsType) => {
    updatePermission(changedValues).then(response => {
      if (response.code === 'SUCCESS') {
        resolveNotification().success({ message: response.message })
        const updatedFields = form.getFieldsError().map(field => {
          if (field.errors) {
            field.errors = []
          }
          return field
        })
        form.setFields(updatedFields)
      } else {
        const fieldErrors: { name: string[]; errors: string[] }[] = []
        Object.keys(response.data).forEach(field => {
          fieldErrors.push({
            name: field.split('.'),
            errors: response.data[field] as string[]
          })
          resolveNotification().error({
            message: response?.message ?? __('Failed to update permission')
          })
        })
        form.setFields(fieldErrors)
      }
    })
  }

  const handleDelete = (user: User) => {
    deletePermission(user.ID).then(response => {
      if (response.code === 'SUCCESS') {
        resolveNotification().success({ message: response?.message ?? __('User permission removed') })
        refetch()
      } else {
        resolveNotification().error({ message: response?.message ?? __('Failed to remove permission') })
      }
    })
  }

  return (
    <>
      <Card title={__('File Manager Shortcode')} style={{ marginInline: '0.625rem' }}>
        <Typography.Text copyable={{ text: '[file-manager]' }}>[file-manager]</Typography.Text>
      </Card>
      <Form
        form={form}
        onFinish={handleSubmit}
        disabled={isLoading || isPermissionUpdating}
        initialValues={permissions}
        colon={false}
        scrollToFirstError
      >
        <Space direction="vertical" size="middle" style={{ display: 'flex' }} className="px-2">
          <Space style={{ display: 'flex', justifyContent: 'right', paddingBlock: '8px' }}>
            <Form.Item style={{ marginBottom: 0 }}>
              <Button type="primary" htmlType="submit" loading={isPermissionUpdating}>
                {__('Update')}
              </Button>
            </Form.Item>
          </Space>
          <Card>
            <Form.Item
              name="do_not_use_for_admin"
              label={__('Disable this permission inside WordPress dashboard')}
              tooltip={__(
                'When this option is enabled, the File Manager within the WordPress dashboard will operate independently of the permission settings configured for users and user roles.'
              )}
            >
              <Switch />
            </Form.Item>

            <Form.Item
              name="fileType"
              label={__('Allowed MIME types')}
              tooltip={__(
                'Enable only the MIME types you need. Allowing unnecessary types may pose security risks.'
              )}
            >
              <Select mode="multiple">
                {fileTypes?.map(fileType => (
                  <Select.Option key={fileType} value={fileType}>
                    {fileType}
                  </Select.Option>
                ))}
              </Select>
            </Form.Item>

            <Form.Item name="file_size" label="Max upload Size">
              <Input type="number" placeholder={__('Maximum File Size')} addonAfter="MB" />
            </Form.Item>
          </Card>

          <Card title={__('Public folder options')}>
            <Form.Item name="root_folder">
              <Input placeholder={__('Root Folder Path')} />
            </Form.Item>

            <Form.Item name="root_folder_url">
              <Input placeholder={__('Root Folder URL')} />
            </Form.Item>
            <Form.Item name="folder_options" label={__('Folder Options')}>
              <Radio.Group size="large">
                <Radio value="common">{__('Enable a common folder for everyone')}</Radio>
                <Radio value="user">{__('Enable separate folders for each user')}</Radio>
                <Radio value="role">{__('Enable folders for each user role')}</Radio>
              </Radio.Group>
            </Form.Item>
          </Card>

          <Card title={__('Permissions by Roles')}>
            <Space size={20} wrap>
              {roles?.map(role => (
                <Card title={role.toUpperCase()} key={`permissions-for-${role}`}>
                  <Form.Item
                    name={['by_role', role, 'path']}
                    label={__('Path')}
                    /* rules={[
                      {
                        // eslint-disable-next-line no-useless-escape
                        pattern: new RegExp(`^${wpRoot}?(?:\/[^\/]+)*\/?$`),
                        message: __('Folder Path Must be within WordPress root directory')
                      }
                    ]} */
                  >
                    <Input placeholder={__('Root Folder Path')} />
                  </Form.Item>
                  <Form.Item name={['by_role', role, 'commands']} label={__('Enabled Commands')}>
                    <Select mode="multiple">
                      {commands?.map(command => (
                        <Select.Option key={command} value={command}>
                          {command}
                        </Select.Option>
                      ))}
                    </Select>
                  </Form.Item>
                </Card>
              ))}
            </Space>
          </Card>

          <Card
            title={__('Permissions by User')}
            extra={
              <Tooltip title={`${__('Add permission for a user')}`}>
                <Button type="dashed" htmlType="button" onClick={() => setIsModalOpen(true)}>
                  +
                </Button>
              </Tooltip>
            }
          >
            <Space size={20} wrap>
              <AddUserPermissionModal
                isModalOpen={isModalOpen}
                setIsModalOpen={setIsModalOpen}
                commands={commands}
              />
              {users?.map(user => (
                <Card
                  title={user.display_name}
                  key={`permissions-for-${user.ID}`}
                  extra={
                    <Popconfirm
                      title={__('Delete the User Permission')}
                      description={
                        <Typography.Text>
                          {__(
                            sprintf(
                              'Are you sure to delete permission for %s?',
                              `${user.display_name}${user.user_login !== user.display_name ? `(${user.user_login})` : ''}`
                            )
                          )}
                        </Typography.Text>
                      }
                      onConfirm={() => handleDelete(user)}
                      okText="Yes"
                      cancelText="No"
                    >
                      <Button loading={isUserPermissionDeleting && delInProgressId === user.ID}>
                        <DeleteFilled />
                      </Button>
                    </Popconfirm>
                  }
                >
                  <Form.Item
                    name={['by_user', user.ID, 'path']}
                    label={__('Path')}
                    /* rules={[
                      {
                        // eslint-disable-next-line no-useless-escape
                        pattern: new RegExp(`^${wpRoot}?(?:\/[^\/]+)*\/?$`),
                        message: __('Folder Path Must be within WordPress root directory')
                      }
                    ]} */
                  >
                    <Input placeholder={__('Root Folder Path')} />
                  </Form.Item>
                  <Form.Item name={['by_user', user.ID, 'commands']} label={__('Enabled Commands')}>
                    <Select mode="multiple">
                      {commands?.map(command => (
                        <Select.Option key={command} value={command}>
                          {command}
                        </Select.Option>
                      ))}
                    </Select>
                  </Form.Item>
                </Card>
              ))}
            </Space>
          </Card>
          <Card title={__('Guest User Settings')}>
            <Form.Item
              name={['guest', 'path']}
              label={__('Path')}
              /* rules={[
                  // eslint-disable-next-line no-useless-escape
                  pattern: new RegExp(`^${wpRoot}?(?:\/[^\/]+)*\/?$`),
                  message: __('Folder Path Must be within WordPress root directory')
              ]} */
            >
              <Input placeholder={__('Root Folder Path')} />
            </Form.Item>
            <Form.Item name={['guest', 'can_download']} label={__('can download?')}>
              <Switch />
            </Form.Item>
          </Card>
          <Form.Item>
            <Space style={{ display: 'flex', justifyContent: 'center' }}>
              <Form.Item>
                <Button type="primary" htmlType="submit" loading={isPermissionUpdating}>
                  Update
                </Button>
              </Form.Item>
            </Space>
          </Form.Item>
        </Space>
      </Form>
    </>
  )
}

export default Permissions
