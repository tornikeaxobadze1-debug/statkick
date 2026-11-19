import { useId, useState } from 'react'
import { playerPhotoUrl } from '../lib/playerPhotos'
import { monogramOf } from '../lib/stats'
import type { PlayerSummary, TeamProfile, TeamSummary } from '../types'

interface PlayerCutoutProps {
  player: Pick<PlayerSummary, 'id' | 'first_name' | 'last_name' | 'photo_url' | 'cutout_image_url'>
  /** Kept for API compatibility with callers; the fallback is brand-styled, not kit-styled. */
  team?: (Pick<TeamSummary, 'short_code'> & TeamProfile) | null
  shirtNumber?: number | null
  /** Sizes/positions the render (callers often pass absolute positioning). */
  className?: string
}

/**
 * The player render: the API cut-out (or a bundled licensed one). A branded
 * silhouette with the player's monogram sits underneath — visible while the photo
 * downloads, and permanently if it's missing or fails — so there is never a broken
 * image or a blank space.
 */
export default function PlayerCutout({ player, className = '' }: PlayerCutoutProps) {
  const photo = playerPhotoUrl(player)
  const [failedSrc, setFailedSrc] = useState<string | null>(null)
  const [loadedSrc, setLoadedSrc] = useState<string | null>(null)
  const usable = photo && failedSrc !== photo
  const loaded = usable && loadedSrc === photo
  const positioned = /\b(absolute|fixed)\b/.test(className)

  return (
    <span className={`${positioned ? '' : 'relative'} block ${className}`}>
      <span aria-hidden={usable ? true : undefined} className={`absolute inset-0 transition-opacity duration-500 ${loaded ? 'opacity-0' : 'opacity-100'}`}>
        <BrandSilhouette initials={monogramOf(player)} label={usable ? undefined : `${player.first_name} ${player.last_name}`.trim()} />
      </span>
      {usable && (
        <img
          src={photo}
          alt={`${player.first_name} ${player.last_name}`.trim()}
          loading="lazy"
          decoding="async"
          draggable={false}
          onLoad={() => setLoadedSrc(photo)}
          onError={() => setFailedSrc(photo)}
          className={`absolute inset-0 h-full w-full object-contain object-bottom transition-opacity duration-500 ${loaded ? 'opacity-100' : 'opacity-0'}`}
        />
      )}
    </span>
  )
}

/** Obsidian head-and-shoulders silhouette with a crimson rim and the player's monogram. */
function BrandSilhouette({ initials, label }: { initials: string; label?: string }) {
  const uid = useId().replace(/[^a-zA-Z0-9_-]/g, '')
  const id = (n: string) => `${n}-${uid}`
  return (
    <svg
      viewBox="0 0 200 240"
      preserveAspectRatio="xMidYMax meet"
      className="h-full w-full"
      role={label ? 'img' : undefined}
      aria-label={label}
      aria-hidden={label ? undefined : true}
    >
      <defs>
        <linearGradient id={id('body')} x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stopColor="#24171d" />
          <stop offset="1" stopColor="#120c10" />
        </linearGradient>
        <linearGradient id={id('rim')} x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stopColor="#fb7185" stopOpacity="0.55" />
          <stop offset="0.5" stopColor="#e11d48" stopOpacity="0.15" />
          <stop offset="1" stopColor="#7f1028" stopOpacity="0.4" />
        </linearGradient>
      </defs>
      {/* Shoulders */}
      <path d="M18 240 C20 196 42 168 78 158 L122 158 C158 168 180 196 182 240 Z" fill={`url(#${id('body')})`} stroke={`url(#${id('rim')})`} strokeWidth="1.5" />
      {/* Neck + head */}
      <path d="M86 122 L114 122 L118 160 Q100 170 82 160 Z" fill={`url(#${id('body')})`} />
      <ellipse cx="100" cy="88" rx="30" ry="36" fill={`url(#${id('body')})`} stroke={`url(#${id('rim')})`} strokeWidth="1.5" />
      {/* Monogram on the chest */}
      <text
        x="100"
        y="216"
        textAnchor="middle"
        fontFamily="'Plus Jakarta Sans', 'DM Sans', sans-serif"
        fontWeight="600"
        fontSize="30"
        letterSpacing="1"
        fill="#f5f0f2"
        fillOpacity="0.5"
      >
        {initials}
      </text>
    </svg>
  )
}
