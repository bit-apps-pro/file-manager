import { useState } from 'react'

export default function useCopyToClipboard() {
  const [copied, setCopied] = useState(false)

  const setCopiedAndReset = () => {
    setCopied(true)

    setTimeout(() => {
      setCopied(false)
    }, 2500)
  }

  const copy = (text: string) => {
    if (window.isSecureContext && navigator.clipboard) {
      navigator.clipboard.writeText(text)
      setCopiedAndReset()
      return
    }

    const textArea = document.createElement('textarea')
    textArea.value = text
    document.body.appendChild(textArea)
    textArea.focus()
    textArea.select()
    try {
      document.execCommand('copy')
      setCopiedAndReset()
    } catch (err) {
      // eslint-disable-next-line no-console
      console.error('Unable to copy to clipboard', err)
    }
    document.body.removeChild(textArea)
  }

  return { copied, copy }
}
