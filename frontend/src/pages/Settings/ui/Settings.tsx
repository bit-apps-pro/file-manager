import { useEffect } from 'react'

import { __ } from '@common/helpers/i18nwrap'
import { resolveNotification } from '@common/notificationInstance'
import useFetchSettings from '@pages/Settings/data/useFetchSettings'
import useUpdateSettings from '@pages/Settings/data/useUpdateSettings'
import { type SettingsType } from '@pages/Settings/settingsTypes'
import { Button, Card, Form, Input, Select, Space, Switch } from 'antd'

export default function Settings() {
  const { useForm } = Form
  const { settings, themes, languages, defaults } = useFetchSettings()
  const { updateSettings, isSettingsUpdating } = useUpdateSettings()
  const [form] = useForm()

  useEffect(() => {
    form.setFieldsValue(settings)
  }, [settings, form])

  const handleValueChanges = (changedValues: NonNullable<unknown>) => {
    const changedField = changedValues ? Object.keys(changedValues)[0] : null
    if (changedField && form.getFieldError(changedField).length) {
      const fieldData = {
        name: changedField,
        errors: []
      }
      form.setFields([fieldData])
    }
  }

  const handleSubmit = (changedValues: SettingsType) => {
    updateSettings(changedValues).then(response => {
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
        })
        form.setFields(fieldErrors)
      }
    })
  }

  return (
    <Form
      form={form}
      initialValues={defaults}
      colon={false}
      onFinish={handleSubmit}
      onValuesChange={handleValueChanges}
      disabled={isSettingsUpdating}
      scrollToFirstError
    >
      <Space direction="vertical" size="middle" style={{ display: 'flex' }} className="px-2">
        <Space style={{ display: 'flex', justifyContent: 'right', paddingBlock: '8px' }}>
          <Form.Item style={{ marginBottom: 0 }}>
            <Button type="primary" htmlType="submit" loading={isSettingsUpdating}>
              Update
            </Button>
          </Form.Item>
        </Space>
        <Card title={__('URL and Path')}>
          <Form.Item
            label="Root Path"
            name="root_folder_path"
            tooltip={`${__('Root folder path must be correct. Default: ')}${defaults?.root_folder_path}`}
            rules={[
              { required: true, message: __('Root folder is required') }
              // {
              //   // eslint-disable-next-line no-useless-escape
              //   pattern: new RegExp(`^${defaults?.root_folder_path}?(?:\/[^\/]+)*\/?$`),
              //   message: __('Folder Path Must be within WordPress root directory')
              // }
            ]}
          >
            <Input />
          </Form.Item>
          <Form.Item
            label="Root URL"
            name="root_folder_url"
            tooltip={`${__('Root folder URL must be correct. Default: ')}${defaults?.root_folder_url}`}
            rules={[
              {
                // eslint-disable-next-line no-useless-escape
                pattern: new RegExp(`^${defaults?.root_folder_url}(?:\/[^\/]+)*\/?`),
                message: __('The root URL must be within this site')
              }
            ]}
          >
            <Input />
          </Form.Item>
          <Form.Item
            label={__('Root Folder Name')}
            name="root_folder_name"
            rules={[
              {
                pattern: /^[a-z A-Z 0-9 -]*$/,
                message: __('The root drive can contain letters, numbers and -')
              }
            ]}
          >
            <Input />
          </Form.Item>
        </Card>
        <Card title={__('File Manager Settings')}>
          <Form.Item label={__('Select Language')} name="language">
            <Select value={settings?.language}>
              {languages?.map(language => (
                <Select.Option value={language.code} key={language.code}>
                  {language.name}
                </Select.Option>
              ))}
            </Select>
          </Form.Item>
          <Form.Item label={__('Select Theme')} name="theme">
            <Select>
              {themes?.map(fmTheme => (
                <Select.Option value={fmTheme.key} key={fmTheme.key}>
                  {fmTheme.title}
                </Select.Option>
              ))}
            </Select>
          </Form.Item>
          <Card title={__('Size')}>
            <Form.Item
              label="Width"
              name={['size', 'width']}
              tooltip="File Manager window width (in px)"
              rules={[
                {
                  pattern: /^(auto|[0-9]+)$/,
                  message: __('Width can be integer or auto')
                }
              ]}
            >
              <Input />
            </Form.Item>
            <Form.Item
              label="Height"
              name={['size', 'height']}
              tooltip="File Manager window height (in px)"
              rules={[
                {
                  pattern: /^(auto|[0-9]+)$/,
                  message: __('Height can be integer or auto')
                }
              ]}
            >
              <Input />
            </Form.Item>
          </Card>
          <Form.Item
            label={__('Sync with media library')}
            name="wp_media_sync"
            tooltip={__(
              'If this is enabled and target upload path is within Wordpress upload directory then, files uploaded using file manager will be visible in WordPress media library'
            )}
          >
            <Switch />
          </Form.Item>
          <Form.Item label={__('Show Hidden Files')} name="show_hidden_files">
            <Switch />
          </Form.Item>
          <Form.Item
            label={__('Allow Create/Upload Hidden Files/Folders')}
            name="create_hidden_files_folders"
          >
            <Switch />
          </Form.Item>
          <Form.Item label={__('Allow Trash')} name="create_trash_files_folders">
            <Switch />
          </Form.Item>
          <Form.Item label={__('Display UI options')} name="display_ui_options">
            <Select mode="multiple">
              <Select.Option value="toolbar">Toolbar</Select.Option>
              <Select.Option value="places">Places</Select.Option>
              <Select.Option value="tree">Tree</Select.Option>
              <Select.Option value="path">Path</Select.Option>
              <Select.Option value="stat">Stat</Select.Option>
            </Select>
          </Form.Item>
          <Form.Item
            label={__('Remember Last Directory')}
            name="remember_last_dir"
            tooltip={__('Remember last opened dir to open it after reload.')}
          >
            <Switch />
          </Form.Item>
          <Form.Item
            label={__('Clear History On Reload')}
            name="clear_history_on_reload"
            tooltip={__("Clear history's(elFinder) on reload(not browser)")}
          >
            <Switch />
          </Form.Item>
          <Form.Item label={__('Default View Type')} name="default_view_type">
            <Select>
              <Select.Option value="icons">Icons</Select.Option>
              <Select.Option value="list">List</Select.Option>
            </Select>
          </Form.Item>
          <Form.Item
            label="Show Link and Path"
            name="show_url_path"
            tooltip={__('If this is enabled then, Link and path will be shown in file, folder info.')}
          >
            <Switch />
          </Form.Item>
        </Card>
        <Space style={{ display: 'flex', justifyContent: 'center' }}>
          <Form.Item>
            <Button type="primary" htmlType="submit" loading={isSettingsUpdating}>
              Update
            </Button>
          </Form.Item>
        </Space>
      </Space>
    </Form>
  )
}
