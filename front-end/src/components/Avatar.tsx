/** "AB" from "Ana Beridze"; one letter for a single name. */
const initialsOf = (name: string) =>
  name
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]!.toUpperCase())
    .join('') || '?'

/** Initials in a crimson ring: the signed-in user's chip and page avatar. */
export default function Avatar({ name, size = 'sm' }: { name: string; size?: 'sm' | 'lg' }) {
  return (
    <span
      aria-hidden
      className={`inline-flex shrink-0 items-center justify-center rounded-full bg-[linear-gradient(140deg,#7f1028,#e11d48)] font-display font-semibold text-white ring-1 ring-crimson/50 ${
        size === 'lg' ? 'h-20 w-20 text-2xl' : 'h-7 w-7 text-[11px]'
      }`}
    >
      {initialsOf(name)}
    </span>
  )
}
