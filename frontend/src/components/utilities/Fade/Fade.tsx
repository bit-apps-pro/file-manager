import { type JSX } from 'react'

import { AnimatePresence, motion } from 'framer-motion'

interface PropsTypes {
  is: boolean
  children: JSX.Element | string
  duration?: number | undefined
  initialDelay?: number | undefined
}

export default function Fade({ is, children, duration = 0.3, initialDelay = 0 }: PropsTypes) {
  const variants = {
    hidden: {
      opacity: 0
    },
    visible: {
      opacity: 1,
      transition: {
        delay: initialDelay
      }
    },
    exit: { opacity: 0, transition: { duration } }
  }

  return (
    <AnimatePresence>
      {is && (
        <motion.div
          // initial="hidden"
          // initial={{ opacity: 0, transition: { delay: 0.5 } }}
          initial="hidden"
          animate="visible"
          exit="exit"
          variants={variants}
          // transition={{ duration }}
        >
          {children}
        </motion.div>
      )}
    </AnimatePresence>
  )
}
