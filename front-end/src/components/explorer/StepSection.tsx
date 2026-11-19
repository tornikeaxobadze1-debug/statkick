import { motion } from 'framer-motion'
import type { ReactNode } from 'react'

interface StepSectionProps {
  id: string
  /** Optional small caption above the title (no numbering). */
  label?: string
  title: ReactNode
  aside?: ReactNode
  /** Draw the top hairline (off for the first section, which sits right under the header). */
  rule?: boolean
  children: ReactNode
}

/** One stage of the drill-down: hairline rule, optional caption, large title. */
export default function StepSection({ id, label, title, aside, rule = true, children }: StepSectionProps) {
  return (
    <motion.section
      id={id}
      initial={{ opacity: 0, y: 16 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0 }}
      transition={{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }}
      className={`scroll-mt-20 pb-12 ${rule ? 'border-t border-line pt-8' : 'pt-8'}`}
    >
      <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
          {label && <p className="label mb-2">{label}</p>}
          <h2 className="font-display text-3xl font-medium tracking-tight text-ink sm:text-[2.125rem]">{title}</h2>
        </div>
        {aside}
      </header>
      {children}
    </motion.section>
  )
}
