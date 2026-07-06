import { useState } from 'react'

import { __ } from '@common/helpers/i18nwrap'
import { resolveNotification } from '@common/notificationInstance'
import useDeleteLog from '@pages/Logs/data/useDeleteLog'
import {
  type LogQueryType,
  type LogType,
  type LoggedFileDetailsType
} from '@pages/Logs/data/useFetchLogs'
import useFetchLogs from '@pages/Logs/data/useFetchLogs'
import { Button, Flex, type TableColumnsType, type TableProps, Typography } from 'antd'
import { Col, Row, Space, Table } from 'antd'

type TableRowSelection<T extends object = object> = TableProps<T>['rowSelection']

const columns: TableColumnsType<LogType> = [
  { title: __('Id'), dataIndex: 'id', key: 'id' },
  {
    title: __('User'),
    dataIndex: 'user',
    key: 'user',
    render: user => user?.display_name ?? ''
  },
  { title: __('Command'), dataIndex: 'command', key: 'command' },
  {
    title: __('Details'),
    dataIndex: 'details',
    key: 'details',
    render: (details, _record, index) => (
      <Space>
        <Row>
          <Col>{details?.driver && `Driver: ${details?.driver}`}</Col>
        </Row>
        <hr />
        <Row>
          <Col>
            {details?.files && (
              <>
                <span>Files:</span>
                <li>
                  {details?.files?.map((file: LoggedFileDetailsType) => (
                    <ol key={`${index}-${file?.path}`}>{file?.path}</ol>
                  ))}
                </li>
              </>
            )}
          </Col>
        </Row>
      </Space>
    )
  },
  { title: __('Created'), dataIndex: 'created_at', key: 'created_at' }
]

export default function Logs() {
  const [pagination, setPagination] = useState<LogQueryType>({
    pageNo: 1,
    limit: 20
  } as LogQueryType)
  const { isLoading, isLogsFetching, logs, total, refetch } = useFetchLogs(pagination)
  const { isLogDeleting, deleteLog } = useDeleteLog()
  const [selectedRowKeys, setSelectedRowKeys] = useState<React.Key[]>([])

  const handleDelete = () => {
    deleteLog(selectedRowKeys as number[]).then(res => {
      if (res.code === 'SUCCESS') {
        setSelectedRowKeys([])
        refetch()
        resolveNotification().success({ message: res?.message || 'Log deleted successfully' })
      } else {
        resolveNotification().error({ message: res?.message || 'Failed to delete logs' })
      }
    })
  }

  const onSelectChange = (newSelectedRowKeys: React.Key[]) => {
    setSelectedRowKeys(newSelectedRowKeys)
  }

  const rowSelection: TableRowSelection<LogType> = {
    selectedRowKeys,
    onChange: onSelectChange
  }

  const hasSelected = selectedRowKeys.length > 0
  const onChange = (page: number, pageSize: number) => {
    setPagination({ pageNo: page, limit: pageSize })
  }

  return (
    <Flex gap="middle" vertical>
      <Flex align="center" gap="middle">
        <Button type="primary" onClick={handleDelete} disabled={!hasSelected} loading={isLogDeleting}>
          Delete
        </Button>
        {hasSelected ? <Typography>Selected {selectedRowKeys.length} items</Typography> : null}
      </Flex>
      <Table
        rowKey="id"
        columns={columns}
        rowSelection={rowSelection}
        dataSource={logs}
        loading={isLoading || isLogsFetching}
        pagination={{
          current: pagination.pageNo,
          total,
          pageSize: pagination.limit,
          onChange,
          position: ['bottomRight', 'topLeft']
        }}
      />
    </Flex>
  )
}
