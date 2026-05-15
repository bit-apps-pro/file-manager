import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'

import { __ } from '@common/helpers/i18nwrap'
import space from '@resource/img/space.svg'

export default function Error404() {
  const [sec, setsec] = useState(9)
  const navigate = useNavigate()

  useEffect(() => {
    setTimeout(() => {
      if (sec === 0) {
        navigate('/', { replace: true })
      }
      setsec(sec - 1)
    }, 1000)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [sec])
  return (
    <div className="error-404">
      <div>
        <div className="four">{__('404')}</div>
        <div className="t">{__('Lost In Space')}</div>
        <br />
        {__('Redirecting Home in')} {sec}
        <br />
        <br />
        <Link to="/" className="btn dp-blue btcd-btn-lg">
          {__('Go Home')}
        </Link>
      </div>
      <img src={space} alt="404 not found" />
    </div>
  )
}
