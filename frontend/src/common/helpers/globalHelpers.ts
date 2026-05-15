/* eslint-disable @typescript-eslint/no-explicit-any */
/* eslint-disable no-param-reassign */
export const select = (selector: string): HTMLElement | null => document.querySelector(selector)

export const assign = (obj: any, keyPath: string, value: any) => {
  const lastKeyIndex = keyPath.length - 1
  // eslint-disable-next-line no-plusplus
  for (let i = 0; i < lastKeyIndex; ++i) {
    const key = keyPath[i]
    if (!(key in obj)) {
      obj[key] = {}
    }
    obj = obj[key]
  }
  obj[keyPath[lastKeyIndex]] = value
  return value
}

export const deepCopy = (target: any, map = new WeakMap()) => {
  if (typeof target !== 'object' || target === null) {
    return target
  }
  const forEach = (array: any[], iteratee: any) => {
    let index = -1
    const { length } = array
    // eslint-disable-next-line no-plusplus
    while (++index < length) {
      iteratee(array[index], index)
    }
    return array
  }

  const isArray = Array.isArray(target)
  const cloneTarget: any = isArray ? [] : {}

  if (map.get(target)) {
    return map.get(target)
  }
  map.set(target, cloneTarget)

  if (isArray) {
    forEach(target, (value: any, index: number) => {
      cloneTarget[index] = deepCopy(value, map)
    })
  } else {
    forEach(Object.keys(target), (key: string) => {
      cloneTarget[key] = deepCopy(target[key], map)
    })
  }
  return cloneTarget
}

export const sortArrOfObj = (data: any, sortLabel: string) =>
  data.sort((a: any, b: any) => {
    if (a?.[sortLabel]?.toLowerCase() < b?.[sortLabel]?.toLowerCase()) return -1
    if (a?.[sortLabel]?.toLowerCase() > b?.[sortLabel]?.toLowerCase()) return 1
    return 0
  })

export const dateTimeFormatter = (dateStr: string, format: string) => {
  const newDate = new Date(dateStr)

  if (newDate.toString() === 'Invalid Date') {
    return 'Invalid Date'
  }

  // Day
  const d = newDate.toLocaleDateString('en-US', { day: '2-digit' })
  const j = newDate.toLocaleDateString('en-US', { day: 'numeric' })
  let S: number | string = Number(j)
  if (S % 10 === 1 && S !== 11) {
    S = 'st'
  } else if (S % 10 === 2 && S !== 12) {
    S = 'nd'
  } else if (S % 10 === 3 && S !== 13) {
    S = 'rd'
  } else {
    S = 'th'
  }
  // Weekday
  const l = newDate.toLocaleDateString('en-US', { weekday: 'long' })
  const D = newDate.toLocaleDateString('en-US', { weekday: 'short' })
  // Month
  const m = newDate.toLocaleDateString('en-US', { month: '2-digit' })
  const n = newDate.toLocaleDateString('en-US', { month: 'numeric' })
  const F = newDate.toLocaleDateString('en-US', { month: 'long' })
  const M = newDate.toLocaleDateString('en-US', { month: 'short' })
  // Year
  const Y = newDate.toLocaleDateString('en-US', { year: 'numeric' })
  const y = newDate.toLocaleDateString('en-US', { year: '2-digit' })
  // Time
  const a = newDate.toLocaleTimeString('en-US', { hour12: true }).split(' ')[1].toLowerCase()
  const A = newDate.toLocaleTimeString('en-US', { hour12: true }).split(' ')[1]
  // Hour
  const g = newDate.toLocaleTimeString('en-US', { hour12: true, hour: 'numeric' }).split(' ')[0]
  const h = newDate.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit' }).split(' ')[0]
  const G = newDate.toLocaleTimeString('en-US', { hour12: false, hour: 'numeric' })
  const H = newDate.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit' })
  // Minute
  const i = newDate.toLocaleTimeString('en-US', { minute: '2-digit' })
  // Second
  const s = newDate.toLocaleTimeString('en-US', { second: '2-digit' })
  // Additional
  const T = newDate.toLocaleTimeString('en-US', { timeZoneName: 'short' }).split(' ')[2]
  const c = newDate.toISOString()
  const r = newDate.toUTCString()
  const U = newDate.valueOf()
  let formattedDate = ''
  const allFormatObj = {
    a,
    A,
    c,
    d,
    D,
    F,
    g,
    G,
    h,
    H,
    i,
    j,
    l,
    m,
    M,
    n,
    r,
    s,
    S,
    T,
    U,
    y,
    Y
  }

  const allFormatkeys = Object.keys(allFormatObj) as (keyof typeof allFormatObj)[]
  for (let v = 0; v < format.length; v += 1) {
    if (format[v] === '\\') {
      v += 1
      formattedDate += format[v]
    } else {
      const formatKey = allFormatkeys.find(key => key === format[v])

      const formatDate = formatKey
        ? format[v].replace(formatKey, String(allFormatObj[formatKey]))
        : format[v]
      formattedDate += formatDate
    }
  }

  return formattedDate
}

export const loadScript = (src: string, type: string) =>
  new Promise(resolve => {
    const script = document.createElement('script')
    script.src = src
    script.onload = () => {
      resolve(true)
    }
    script.onerror = () => {
      resolve(false)
    }
    script.id = type
    document.body.appendChild(script)
  })

const cipher = (salt: string) => {
  const textToChars = (text: string) => text.split('').map(c => c.charCodeAt(0))
  const byteHex = (n: number) => {
    const str = `0${Number(n).toString(16)}`
    return str.substring(str.length - 2)
  }

  const applySaltToChar = (code: any) => textToChars(salt).reduce((a: number, b: number) => a ^ b, code) // eslint-disable-line no-bitwise

  // eslint-disable-next-line newline-per-chained-call
  return (text: string) => text?.split('')?.map(textToChars).map(applySaltToChar).map(byteHex).join('')
}

const decipher = (salt: string) => {
  const textToChars = (text: string) => text.split('').map(c => c.charCodeAt(0))
  // eslint-disable-next-line no-bitwise
  const applySaltToChar = (code: any) => textToChars(salt).reduce((a, b) => a ^ b, code)
  return (encoded: string) =>
    encoded
      ?.match(/.{1,2}/g)
      ?.map(hex => parseInt(hex, 16))
      .map(applySaltToChar)
      .map(charCode => String.fromCharCode(charCode))
      .join('')
}

export const bitCipher = cipher('btcd')
export const bitDecipher = decipher('btcd')

export const checkValidEmail = (email: string) => {
  if (/^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(email)) {
    return true
  }
  return false
}

export const getColorPreference = () =>
  window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches

export function removeUnwantedCSS() {
  const conflictStyles = ['bootstrap']
  const styles: StyleSheetList = document.styleSheets

  if (!styles) return
  for (let i = 0; i < styles.length; i += 1) {
    if (styles[i].href !== null) {
      const regex = new RegExp(conflictStyles.join('.*css|'), 'gi')
      if (styles[i].href?.match(regex)) {
        styles[i].disabled = true
      }
    }
  }
}

export function setAppBgFromAdminBg() {
  const bitAppsRootElm = select('#bit-apps-root')
  const wpAdminBarElm = select('#wpadminbar')
  if (bitAppsRootElm && wpAdminBarElm) {
    bitAppsRootElm.style.backgroundColor = window.getComputedStyle(wpAdminBarElm)?.backgroundColor
  }
}

export const debounce = <F extends (...args: any[]) => any>(func: F, waitFor: number) => {
  let timeout: ReturnType<typeof setTimeout> | null = null

  const debounced = (...args: Parameters<F>) => {
    if (timeout !== null) {
      clearTimeout(timeout)
      timeout = null
    }
    timeout = setTimeout(() => func(...args), waitFor)
  }

  return debounced as (...args: Parameters<F>) => ReturnType<F>
}

export const clsx = (arr: Array<string | number | undefined | null | boolean>): string =>
  arr.filter(Boolean).join(' ')

export const lighten = (color: string | undefined, percentage: number): string => {
  if (!color) return 'transparent'

  const newColor = color.replace('#', '')
  const r = parseInt(newColor.substring(0, 2), 16)
  const g = parseInt(newColor.substring(2, 4), 16)
  const b = parseInt(newColor.substring(4, 6), 16)

  const lightenPercentage = percentage / 100
  const newR = Math.round(r + (255 - r) * lightenPercentage)
  const newG = Math.round(g + (255 - g) * lightenPercentage)
  const newB = Math.round(b + (255 - b) * lightenPercentage)

  return `#${newR.toString(16).padStart(2, '0')}${newG.toString(16).padStart(2, '0')}${newB
    .toString(16)
    .padStart(2, '0')}`
}

/**
 * Check if two objects are equal
 *
 * @param obj1 First Object
 * @param obj2 Second Object
 * @returns Boolean
 */
export const isObjectEqual = <T, J>(obj1: T, obj2: J) => JSON.stringify(obj1) === JSON.stringify(obj2)
