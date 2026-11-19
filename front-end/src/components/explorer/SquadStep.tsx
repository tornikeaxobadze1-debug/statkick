import { playersApi, teamsApi } from '../../api'
import { fallbackPlayers, fallbackTeam } from '../../data/fallback'
import { useApiData } from '../../hooks/useApiData'
import type { ID, Player, Team, TeamDetail } from '../../types'
import { Skeleton } from '../ui'
import ClubDossier from './ClubDossier'
import SquadTable from './SquadTable'

interface SquadStepProps {
  teamId: ID
  /** Standings row from the league step, when available. */
  team: Team | null
  /** League table position, computed from the standings. */
  rank?: { position: number; of: number } | null
  onOpenPlayer: (p: Player) => void
  onComparePlayer?: (p: Player) => void
  favoritePlayerIds?: ID[]
  onTogglePlayerFavorite?: (p: Player) => void
  isTeamFavorite?: boolean
  onToggleTeamFavorite?: () => void
}

export default function SquadStep({
  teamId,
  team,
  rank = null,
  onOpenPlayer,
  onComparePlayer,
  favoritePlayerIds,
  onTogglePlayerFavorite,
  isTeamFavorite,
  onToggleTeamFavorite,
}: SquadStepProps) {
  const playersQ = useApiData((signal) => playersApi.list({ team_id: teamId }, { signal }), {
    fallback: fallbackPlayers(teamId),
    initial: [],
    key: teamId,
  })
  const teamQ = useApiData<TeamDetail | null>((signal) => teamsApi.show(teamId, { signal }), {
    fallback: fallbackTeam(teamId),
    initial: null,
    key: teamId,
  })

  return (
    <>
      {teamQ.data || team ? <ClubDossier
          detail={teamQ.data}
          row={team}
          players={playersQ.data}
          rank={rank}
          isFavorite={isTeamFavorite}
          onToggleFavorite={onToggleTeamFavorite}
        /> : <Skeleton className="mb-14 h-96" />}

      <div className="mb-4 flex items-end justify-between">
        <p className="label">Squad · select a player to open their profile</p>
        <p className="text-xs text-ink-mute">{playersQ.data.length} players</p>
      </div>
      {playersQ.source === 'loading' ? <Skeleton className="h-80" /> : <SquadTable
          players={playersQ.data}
          onOpen={onOpenPlayer}
          onCompare={onComparePlayer}
          favoriteIds={favoritePlayerIds}
          onToggleFavorite={onTogglePlayerFavorite}
        />}
    </>
  )
}
