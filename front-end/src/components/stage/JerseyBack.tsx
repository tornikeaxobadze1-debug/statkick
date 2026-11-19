import { useId } from 'react'
import { contrasts, readableOn, shade } from '../../lib/stats'

interface JerseyBackProps {
  surname: string
  number: number | null
  primary: string
  secondary: string
  className?: string
}

/**
 * Back of the club shirt shown on the far side of the 360° turn: club colours,
 * printed surname and number, with cloth shading so it reads as a 3D garment.
 */
export default function JerseyBack({ surname, number, primary, secondary, className = '' }: JerseyBackProps) {
  const uid = useId().replace(/[^a-zA-Z0-9_-]/g, '')
  const ink = contrasts(secondary, primary) ? secondary : readableOn(primary)
  const trim = contrasts(secondary, primary) ? secondary : shade(primary, 0.35)
  const name = surname.toUpperCase()
  // Long names get a tighter print so they stay on the shoulders.
  const nameSize = name.length > 11 ? 20 : name.length > 8 ? 24 : 28

  return (
    <svg viewBox="0 0 300 360" className={className} role="img" aria-label={`Shirt back: ${surname}${number != null ? `, number ${number}` : ''}`}>
      <defs>
        <linearGradient id={`cloth-${uid}`} x1="0" y1="0" x2="1" y2="0">
          <stop offset="0" stopColor="#000" stopOpacity="0.32" />
          <stop offset="0.22" stopColor="#000" stopOpacity="0.05" />
          <stop offset="0.5" stopColor="#fff" stopOpacity="0.1" />
          <stop offset="0.78" stopColor="#000" stopOpacity="0.05" />
          <stop offset="1" stopColor="#000" stopOpacity="0.36" />
        </linearGradient>
        <linearGradient id={`fall-${uid}`} x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stopColor="#fff" stopOpacity="0.08" />
          <stop offset="1" stopColor="#000" stopOpacity="0.25" />
        </linearGradient>
      </defs>

      {/* Shirt body + sleeves */}
      <path
        d="M96 22 Q150 40 204 22 L262 52 Q284 64 292 92 L298 150 L248 162 L242 120 L244 346 Q150 360 56 346 L58 120 L52 162 L2 150 L8 92 Q16 64 38 52 Z"
        fill={primary}
      />
      {/* Sleeve cuffs */}
      <path d="M2 150 L52 162 L53 150 L4 138 Z" fill={trim} />
      <path d="M298 150 L248 162 L247 150 L296 138 Z" fill={trim} />
      {/* Collar */}
      <path d="M96 22 Q150 40 204 22 Q196 34 150 44 Q104 34 96 22 Z" fill={trim} />
      {/* Cloth shading & folds */}
      <path
        d="M96 22 Q150 40 204 22 L262 52 Q284 64 292 92 L298 150 L248 162 L242 120 L244 346 Q150 360 56 346 L58 120 L52 162 L2 150 L8 92 Q16 64 38 52 Z"
        fill={`url(#cloth-${uid})`}
      />
      <path
        d="M96 22 Q150 40 204 22 L262 52 Q284 64 292 92 L298 150 L248 162 L242 120 L244 346 Q150 360 56 346 L58 120 L52 162 L2 150 L8 92 Q16 64 38 52 Z"
        fill={`url(#fall-${uid})`}
      />
      <path d="M110 300 Q130 250 118 200" stroke="#000" strokeOpacity="0.08" strokeWidth="6" fill="none" />
      <path d="M196 310 Q178 260 190 210" stroke="#000" strokeOpacity="0.08" strokeWidth="6" fill="none" />

      {/* Print */}
      <text
        x="150"
        y="104"
        textAnchor="middle"
        fontFamily="'Plus Jakarta Sans', 'DM Sans', sans-serif"
        fontWeight="600"
        fontSize={nameSize}
        letterSpacing="3"
        fill={ink}
      >
        {name}
      </text>
      {number != null && (
        <text
          x="150"
          y="250"
          textAnchor="middle"
          fontFamily="'Plus Jakarta Sans', 'DM Sans', sans-serif"
          fontWeight="600"
          fontSize="132"
          letterSpacing="-4"
          fill={ink}
        >
          {number}
        </text>
      )}
    </svg>
  )
}
