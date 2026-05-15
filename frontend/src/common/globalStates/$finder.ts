import { type FinderInstance } from 'elfinder'
import { atom } from 'jotai'

import { type BreadcrumbItemType } from './GlobalStates'

const $finder = atom<FinderInstance>({} as FinderInstance)
export const $finderCurrentPath = atom<BreadcrumbItemType[]>([])
export const $finderViewType = atom('icons')

export default $finder
