import { type ReactElement } from 'react'
import { cloneElement, useState } from 'react'

import { type SegmentedProps } from 'antd'
import { Segmented } from 'antd'
import { type SegmentedValue } from 'antd/es/segmented'
import { type HTMLMotionProps } from 'framer-motion'
import { AnimatePresence, motion } from 'framer-motion'

interface TabsType {
  // options: (number | string)[]
  children: any // eslint-disable-line @typescript-eslint/no-explicit-any
  defaultValue?: string
  block?: boolean
  onChange?: (value: SegmentedValue) => void
  panelProps?: HTMLMotionProps<'div'>
  className?: string
}

const findIndexByValue = (value: string | number, arr: SegmentedProps['options']) =>
  arr.findIndex(l => (typeof l === 'object' && l.value === value) || l === value)

export default function Tabs({
  panelProps,
  onChange,
  defaultValue,
  options,
  children,
  ...props
}: TabsType & SegmentedProps & React.RefAttributes<HTMLDivElement>) {
  const firstOption = typeof options[0] === 'object' ? options[0].value : options[0]
  const [tabValue, setTabValue] = useState<SegmentedValue>(
    defaultValue || (firstOption as SegmentedValue)
  )
  const tabIndex = findIndexByValue(tabValue, options)
  const [prevTabIndex, setPrevTabIndex] = useState<number>(tabIndex)
  const [pref, setPref] = useState<HTMLDivElement | null>(null)
  const directionNum = prevTabIndex < tabIndex ? 1 : -1

  const ActiveTabPanel = cloneElement(
    children?.find(
      (c: ReactElement) => (c as React.ReactElement<{ value: SegmentedValue }>).props.value === tabValue
    ),
    {
      panelRef: (r: HTMLDivElement | null) => setPref(r)
    }
  )

  const segmentOnchange = (v: SegmentedValue) => {
    setPrevTabIndex(findIndexByValue(tabValue, options))
    setTabValue(v)
    onChange?.(v)
  }

  const tabPanelHeight = pref?.clientHeight

  return (
    <>
      <Segmented
        css={{ marginBottom: '0.7rem !important' }}
        value={tabValue}
        options={options}
        onChange={segmentOnchange}
        {...props} // eslint-disable-line react/jsx-props-no-spreading
      />

      <AnimatePresence initial={false} mode="wait" custom={directionNum}>
        <motion.div
          key={tabValue}
          custom={directionNum}
          variants={{
            enter: (direction: number) => ({
              x: direction > 0 ? 10 : -10,
              opacity: 0,
              height: tabPanelHeight
            }),
            center: {
              height: 'auto',
              zIndex: 1,
              x: 0,
              opacity: 1
            },
            exit: (direction: number) => ({
              zIndex: 0,
              x: direction < 0 ? 10 : -10,
              opacity: 0
            })
          }}
          initial="enter"
          animate="center"
          exit="exit"
          transition={{
            x: { type: 'spring', stiffness: 1000, damping: 50 },
            opacity: { duration: 0.2 }
          }}
          {...panelProps} // eslint-disable-line react/jsx-props-no-spreading
        >
          {ActiveTabPanel}
        </motion.div>
      </AnimatePresence>
    </>
  )
}
