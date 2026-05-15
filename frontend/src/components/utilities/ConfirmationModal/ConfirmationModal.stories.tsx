import { useState } from 'react'

import { type Meta, type StoryFn } from '@storybook/react'
import { Button } from 'antd'

import ConfirmationModal from './ConfirmationModal'

// eslint-disable-next-line react/function-component-definition
const Template: StoryFn<typeof ConfirmationModal> = () => {
  const [modalOpen, setModalOpen] = useState<boolean>(false)
  return (
    <>
      <Button onClick={() => setModalOpen(true)}>Confirm Modal</Button>
      <ConfirmationModal
        layoutId="confirmModalId"
        open={modalOpen}
        closeModal={() => setModalOpen(false)}
        confirmDelete={() => setModalOpen(false)}
      />
    </>
  )
}

const Canvas = Template.bind({})

Canvas.args = {
  open: true
}

export { Canvas }

export default {
  title: 'Component/ConfirmationModal',
  component: ConfirmationModal
} as Meta<typeof ConfirmationModal>
