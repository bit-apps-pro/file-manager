import SearchIcon from '@icons/SearchIcon'
import { type Meta, type StoryFn } from '@storybook/react'

import IconBtn from './IconBtn'

export default {
  title: 'Component/IconButton',
  component: IconBtn
} as Meta<typeof IconBtn>

// eslint-disable-next-line react/function-component-definition
const Template: StoryFn<typeof IconBtn> = () => (
  <div>
    <div className="flx ai-cen mb-4">
      <IconBtn color="primary" size="lg" className="mr-3">
        <SearchIcon size={20} stroke={2} />
      </IconBtn>
      <IconBtn size="lg" className="mr-3">
        <SearchIcon size={20} stroke={2} />
      </IconBtn>
      <IconBtn size="lg" round className="mr-3">
        <SearchIcon size={20} stroke={2} />
      </IconBtn>
      <IconBtn size="md" className="mr-3">
        <SearchIcon size={18} stroke={2} />
      </IconBtn>
      <IconBtn size="sm" className="mr-3">
        <SearchIcon size={16} stroke={2} />
      </IconBtn>
    </div>
    <div className="flx ai-cen mb-4">
      <IconBtn color="primary" variant="outline" size="lg" className="mr-3">
        <SearchIcon size={20} stroke={2} />
      </IconBtn>
      <IconBtn size="lg" variant="outline" className="mr-3">
        <SearchIcon size={20} stroke={2} />
      </IconBtn>
      <IconBtn size="lg" round variant="outline" className="mr-3">
        <SearchIcon size={20} stroke={2} />
      </IconBtn>
      <IconBtn size="md" variant="outline" className="mr-3">
        <SearchIcon size={18} stroke={2} />
      </IconBtn>
      <IconBtn size="sm" variant="outline" className="mr-3">
        <SearchIcon size={16} stroke={2} />
      </IconBtn>
    </div>
    <div className="flx ai-cen mb-4">
      <IconBtn color="primary" variant="ghost" size="lg" className="mr-3">
        <SearchIcon size={20} stroke={2} />
      </IconBtn>
      <IconBtn size="lg" variant="ghost" className="mr-3">
        <SearchIcon size={20} stroke={2} />
      </IconBtn>
      <IconBtn size="lg" round variant="ghost" className="mr-3">
        <SearchIcon size={20} stroke={2} />
      </IconBtn>
      <IconBtn size="md" variant="ghost" className="mr-3">
        <SearchIcon size={18} stroke={2} />
      </IconBtn>
      <IconBtn size="sm" variant="ghost" className="mr-3">
        <SearchIcon size={16} stroke={2} />
      </IconBtn>
    </div>
  </div>
)

export const Primary = Template.bind({})
