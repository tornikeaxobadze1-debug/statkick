import { motion, type MotionValue } from 'framer-motion'
import { useLayoutEffect, useRef, useState } from 'react'

/** Same scale as the original watermark type: tall on desktop, never taller than ~a third of the screen. */
const FONT = 'clamp(5rem, min(22vw, 34vh), 24rem)'

interface WatermarkProps {
  word: string
  color: string
  opacity: number
  /** Horizontal bounds (viewport px) of the stage area beside the data panel. */
  bounds: { left: number; width: number } | null
  /** Largest parallax drift (px) either way; kept clear as padding so drift never crosses the bounds. */
  drift: number
  x: MotionValue<number>
  rotateY: MotionValue<number>
}

/**
 * The club watermark behind the player, auto-fitted to the stage area: an SVG whose
 * viewBox is the word's measured outline, scaled with `meet`. Long names shrink to the
 * available width; short ones keep the full type height. Absolutely positioned in the
 * fixed backdrop, so it never affects layout.
 */
export default function Watermark({ word, color, opacity, bounds, drift, x, rotateY }: WatermarkProps) {
  const text = useRef<SVGTextElement>(null)
  // Rough first guess (display face, caps, tight tracking) until the real outline is measured.
  const [box, setBox] = useState({ x: 0, y: -74, width: Math.max(1, word.length) * 64, height: 76 })

  useLayoutEffect(() => {
    const measure = () => {
      const b = text.current?.getBBox()
      if (b && b.width > 0) setBox({ x: b.x, y: b.y, width: b.width, height: b.height })
    }
    measure() // before paint: no visible jump
    let live = true
    document.fonts?.ready.then(() => live && measure()) // the web font can change the outline
    return () => {
      live = false
    }
  }, [word])

  if (!bounds) return null
  const pad = drift + Math.max(16, bounds.width * 0.05)
  const width = Math.max(0, bounds.width - 2 * pad)

  return (
    <motion.div
      aria-hidden
      className="absolute top-[18%] will-change-transform"
      style={{ left: bounds.left + pad, width, height: `calc(${FONT} * 0.78)`, x, rotateY, transformPerspective: 1200, opacity }}
    >
      <svg
        className="h-full w-full overflow-visible"
        viewBox={`${box.x} ${box.y} ${box.width} ${box.height}`}
        preserveAspectRatio="xMidYMid meet"
      >
        <text ref={text} x="0" y="0" style={{ fill: color }} fontSize="100" fontWeight="600" letterSpacing="-5" className="font-display">
          {word}
        </text>
      </svg>
    </motion.div>
  )
}
