/* eslint-disable react/jsx-props-no-spreading */
import { type Meta, type StoryFn } from '@storybook/react'

import Input from './Input'

// eslint-disable-next-line react/function-component-definition
const Template: StoryFn<typeof Input> = args => (
  <div>
    <div className="flx ai-end mb-6">
      <div className="mr-2">
        <Input
          {...args}
          placeholder={args.placeholder || 'large'}
          label={args.title || 'Outline Input'}
          size="large"
        />
      </div>
      <div className="mr-2">
        <Input {...args} placeholder={args.placeholder || 'middle'} size="middle" />
      </div>
      <div className="mr-2">
        <Input {...args} placeholder={args.placeholder || 'small'} size="small" />
      </div>
    </div>
  </div>
)

export const Primary = Template.bind({})
// Primary.args = {
//   title: 'Title here',
// }

export default {
  title: 'Component/Input',
  component: Input,
  argTypes: {
    size: {
      control: false
    },
    placeholder: {
      control: 'text'
    },
    label: {
      control: 'text'
    }
  }
} as Meta<typeof Input>
