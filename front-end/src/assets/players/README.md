# Player cut-out renders

Drop **licensed** transparent PNG/WebP cut-outs here and they are picked up
automatically at build time (no code changes, no 404s):

    src/assets/players/<first>-<last>.png     e.g. bukayo-saka.png, lamine-yamal.webp

The file name is the player's first and last name, lower-cased, accents removed,
spaces → hyphens (see `photoSlug()` in `src/lib/playerPhotos.ts`).

Lookup order for each card: API `photo_url` → file in this folder → illustrated
fallback in the player's kit colours.

Use images you have the rights to (club media kits, a licensed data/photo
provider, or your own renders); photos of real players are copyrighted.
Best results: portrait ~800×1000, transparent background, head near the top,
cropped at the waist.
