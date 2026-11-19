import { readableOn, teamColors } from '../lib/stats'
import type { TeamProfile, TeamSummary } from '../types'

const SIZES = {
  sm: 'h-7 w-6 text-[8px]',
  md: 'h-11 w-9 text-[10px]',
  lg: 'h-16 w-14 text-sm',
  xl: 'h-24 w-20 text-lg',
}

type CrestTeam = Pick<TeamSummary, 'name' | 'short_code' | 'logo_url'> & TeamProfile

/** Flat shield in the club's colours (or its logo when the API has one). */
export default function TeamCrest({ team, size = 'md' }: { team: CrestTeam; size?: keyof typeof SIZES }) {
  if (team.logo_url) {
    return <img src={team.logo_url} alt={`${team.name} crest`} className={`${SIZES[size]} object-contain`} />
  }
  const c = teamColors(team)
  return (
    <span
      role="img"
      aria-label={`${team.name} crest`}
      className={`${SIZES[size]} relative inline-flex shrink-0 items-center justify-center font-display font-semibold tracking-tight`}
      style={{
        background: `linear-gradient(135deg, ${c.primary} 0 62%, ${c.secondary} 62% 100%)`,
        color: readableOn(c.primary),
        clipPath: 'polygon(50% 0, 100% 10%, 100% 64%, 50% 100%, 0 64%, 0 10%)',
      }}
    >
      <span className="-mt-1">{team.short_code}</span>
    </span>
  )
}
