/**
 * Local cut-out renders discovered at build time from src/assets/players/.
 * See src/assets/players/README.md for the naming convention.
 */
const files = import.meta.glob<string>('../assets/players/*.{png,webp,avif}', {
  eager: true,
  query: '?url',
  import: 'default',
})

const BY_SLUG = new Map(
  Object.entries(files).map(([path, url]) => [path.split('/').pop()!.replace(/\.(png|webp|avif)$/i, ''), url]),
)

/** "Rodrigo Hernández" → "rodrigo-hernandez" */
export const photoSlug = (first: string, last: string) =>
  `${first} ${last}`
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')

/**
 * Best available render: the API's transparent `cutout_image_url`, then its
 * `photo_url`, then a cut-out bundled in src/assets/players/.
 */
export function playerPhotoUrl(p: {
  first_name: string
  last_name: string
  photo_url: string | null
  cutout_image_url?: string | null
}): string | null {
  return [p.cutout_image_url, p.photo_url].find(isPhotograph) ?? BY_SLUG.get(photoSlug(p.first_name, p.last_name)) ?? null
}

/**
 * A real image file, not generated placeholder art. The backend stores an inline
 * jersey SVG (`data:` URI) for players without a verified cut-out; those render as
 * the branded silhouette instead.
 */
const isPhotograph = (url: string | null | undefined): url is string => !!url && !url.startsWith('data:')
