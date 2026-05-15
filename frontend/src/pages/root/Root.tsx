import { useEffect, useRef, useState } from 'react'

import $finder, { $finderCurrentPath, $finderViewType } from '@common/globalStates/$finder'
import { type BreadcrumbItemType } from '@common/globalStates/GlobalStates'
import { __ } from '@common/helpers/i18nwrap'
import request from '@common/helpers/request'
import config from '@config/config'
import LucideIcn from '@icons/LucideIcn'
import useUpdateViewType from '@pages/Settings/data/useUpdateViewType'
import TelemetryPopup from '@utilities/TelemetryPopup/TelemetryPopup'
import { Breadcrumb, type BreadcrumbProps, Button, Flex, Image, Space, Spin } from 'antd'
import { type FinderInstance } from 'elfinder'
import { useAtom } from 'jotai'

import configureElFinder from './helpers/configureElFinder'
import initThemeChangeHandler from './helpers/initThemeChangeHandler'

export default function Root() {
  const finderRef = useRef<HTMLDivElement>(null)
  const [isOpening, setIsOpening] = useState(false)
  const [elfinder, setFinder] = useAtom($finder)
  const [currentPath, setFinderCurrentPath] = useAtom($finderCurrentPath)
  const [viewType, setFinderViewType] = useAtom($finderViewType)
  const [isTelemetryModalOpen, setIsTelemetryModalOpen] = useState(false)

  const { toggleViewType } = useUpdateViewType()

  const generateFullPath = (finder: FinderInstance) => {
    const parents = finder.parents(finder.cwd().hash)
    const breadcrumbItems = parents.map((hash: string, index: number) => {
      const fileObj = finder.file(hash)
      const fileName = fileObj.i18n ?? fileObj.name
      const item: BreadcrumbItemType = {
        title: fileName
      }

      if (index < parents.length - 1) {
        item.onClick = () => finder?.exec('open', hash, { _userAction: true })
        item.className = 'fm-cursor-pointer'
      }

      return item
    })
    setFinderCurrentPath(breadcrumbItems)
  }

  const changeViewState = (finder: FinderInstance) => {
    setFinderViewType(finder?.viewType)
  }

  const handleToggleViewType = () => {
    elfinder?.exec('view', [], { _userAction: true, _currentType: 'toolbar' })
    toggleViewType(viewType === 'list' ? 'icons' : 'list')
  }

  const openSettings = () => {
    elfinder?.exec('preference', [], { _userAction: true, _currentType: 'toolbar' })
  }

  useEffect(() => {
    if (finderRef) {
      const finder = configureElFinder(finderRef)
      setFinder(finder)
      initThemeChangeHandler(finderRef)

      finder.bind('open searchend parents', () => {
        generateFullPath(finder)
      })

      finder.bind('request.open', () => {
        setIsOpening(true)
      })

      finder.bind('opendone reload sync', () => {
        setIsOpening(false)
      })

      finder.bind('uploadfail', () => {
        finder.toast({
          mode: 'error',
          msg: __('Something went wrong while uploading files.'),
          hideDuration: 5000
        })
      })

      finder.bind('viewchange', () => {
        changeViewState(finder)
      })
    }
    return () => {
      /* if (finder) {
        finder?.destroy()
      } */
      setFinder({} as FinderInstance)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  useEffect(() => {
    request({ action: 'telemetry_popup_disable_check', method: 'GET' }).then(res => {
      setIsTelemetryModalOpen(!res.data)
    })
  }, [])

  return (
    <>
      <div
        style={{
          position: 'relative',
          top: '50%',
          left: '50%',
          zIndex: 1,
          display: isOpening ? 'flex' : 'none',
          width: 'max-content'
        }}
      >
        <Spin size="large" />
      </div>
      <Flex>
        <Flex
          style={{
            flexDirection: 'column',
            width: `${config.BANNER !== null ? '50%' : '100%'}`,
            justifyContent: 'center',
            padding: 10
          }}
        >
          <Breadcrumb items={currentPath as BreadcrumbProps['items']} />
          <Flex style={{ justifyContent: 'space-between' }}>
            <Flex style={{ gap: 15 }}>
              <Button
                type="primary"
                icon={<LucideIcn name="UploadIcon" />}
                onClick={() =>
                  elfinder?.exec('upload', [], { _userAction: true, _currentType: 'toolbar' })
                }
              >
                {__('Upload')}
              </Button>
              <Button
                ghost
                icon={<LucideIcn name="FolderPlusIcon" />}
                onClick={() =>
                  elfinder?.exec('mkdir', [], { _userAction: true, _currentType: 'toolbar' })
                }
              >
                {__('Create Folder')}
              </Button>
            </Flex>
            <Flex style={{ gap: 15 }}>
              <Button icon={<LucideIcn name="Settings" />} onClick={openSettings} />
              <Button
                icon={<LucideIcn name="LayoutGridIcon" />}
                onClick={handleToggleViewType}
                disabled={viewType === 'icons'}
              />
              <Button
                icon={<LucideIcn name="LayoutList" />}
                onClick={handleToggleViewType}
                disabled={viewType === 'list'}
              />
            </Flex>
          </Flex>
        </Flex>
        {config.BANNER !== null && (
          <Space style={{ width: '50%' }}>
            <a
              href={`${config.BANNER.url}/?utm_source=fm&utm_campaign=special_offer`}
              target="_blank"
              rel="external noreferrer"
            >
              <Image alt="adBanner" src={config.BANNER.img} preview={false} />
            </a>
          </Space>
        )}
      </Flex>
      <div id="file-manager" ref={finderRef} style={{ height: '100%' }} />

      {isTelemetryModalOpen ? (
        <TelemetryPopup
          isTelemetryModalOpen={isTelemetryModalOpen}
          setIsTelemetryModalOpen={setIsTelemetryModalOpen}
        />
      ) : (
        ''
      )}
    </>
  )
}
