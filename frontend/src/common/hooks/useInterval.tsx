import { useEffect, useRef, useState } from 'react'

type UseIntervalType = [boolean, () => void, () => void]

export default function useInterval(
  callback: () => void,
  delay: number | null,
  immediate = false
): UseIntervalType {
  const [timer, setTimer] = useState(immediate ? delay : null)
  const intervalRef = useRef<ReturnType<typeof setTimeout> | null>(null)
  const savedCallback = useRef(callback)
  const isRunning = timer !== null

  useEffect(() => {
    savedCallback.current = callback
  }, [callback])

  useEffect(() => {
    if (typeof timer === 'number') {
      intervalRef.current = setInterval(savedCallback.current, timer)
    }
    return () => {
      if (intervalRef.current) clearInterval(intervalRef.current)
    }
  }, [timer])

  const startInterval = () => setTimer(delay)
  const stopInterval = () => setTimer(null)

  return [isRunning, startInterval, stopInterval]
}
