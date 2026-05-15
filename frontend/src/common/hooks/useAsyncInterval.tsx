import { useEffect, useRef, useState } from 'react'

type UseIntervalType = [boolean, () => void, () => void]

export default function useInterval(
  callback: () => void,
  delay: number | null,
  immediate = false
): UseIntervalType {
  const [timer, setTimer] = useState(immediate ? delay : null)
  const savedCallback = useRef(callback)
  const asyncIntervalsRef = useRef<{ run: boolean; id: NodeJS.Timeout | number }[]>([])
  const intervalIndexRef = useRef<number>(0)
  const isRunning = timer !== null

  const clearAsyncInterval = (intervalIndex: number) => {
    setTimer(null)
    const currentInterval = asyncIntervalsRef.current[intervalIndex]
    if (currentInterval.run) {
      clearTimeout(currentInterval.id)
      currentInterval.run = false
    }
  }

  const runAsyncInterval = async (intervalIndex: number) => {
    await savedCallback.current()

    if (typeof timer !== 'number') return
    const currentInterval = asyncIntervalsRef.current[intervalIndex]
    if (currentInterval.run) {
      currentInterval.id = setTimeout(() => runAsyncInterval(intervalIndex), timer)
    }
  }

  const startInterval = () => setTimer(delay)
  const stopInterval = () => clearAsyncInterval(intervalIndexRef.current)

  useEffect(() => {
    savedCallback.current = callback
  }, [callback])

  useEffect(() => {
    if (typeof timer !== 'number') return

    const intervalIndex = asyncIntervalsRef.current.length
    asyncIntervalsRef.current.push({ run: true, id: 0 })
    runAsyncInterval(intervalIndex)
    intervalIndexRef.current = intervalIndex

    return () => clearAsyncInterval(intervalIndexRef.current)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [timer])

  return [isRunning, startInterval, stopInterval]
}
