import {
  AnimatePresence,
  motion,
  useMotionValue,
  useMotionValueEvent,
  useReducedMotion,
  useTransform,
} from "framer-motion";
import { ArrowLeft, ArrowRightLeft, Heart, MoveHorizontal } from "lucide-react";
import {
  useEffect,
  useLayoutEffect,
  useRef,
  useState,
  type KeyboardEvent,
  type PointerEvent,
  type WheelEvent,
} from "react";
import { playersApi } from "../../api";
import { fallbackPlayerDetail } from "../../data/fallback";
import { useApiData } from "../../hooks/useApiData";
import { playerName, teamColors } from "../../lib/stats";
import Flag from "../Flag";
import type { ID, StagePlayer } from "../../types";
import PlayerCutout from "../PlayerCutout";
import JerseyBack from "./JerseyBack";
import Watermark from "./Watermark";
import {
  HonoursPanel,
  MarketPanel,
  PerformancePanel,
  TacticalPanel,
} from "./StagePanels";
import {
  createTurntable,
  nearestEquivalent,
  normDeg,
  type Turntable,
} from "./turntable";

interface PlayerStageProps {
  playerId: ID;
  /** List payload for an instant first paint while the full profile loads. */
  initial: StagePlayer | null;
  backLabel: string;
  isFavorite: boolean;
  onToggleFavorite: (p: StagePlayer) => void;
  /** Opens the head-to-head comparison with this player on the left. */
  onCompare: (p: StagePlayer) => void;
  /** True while a dialog (the comparison) is open on top: Esc belongs to it. */
  keysPaused?: boolean;
  onBack: () => void;
}

/** The four views, in rotation order. Angles are internal only — never shown. */
const VIEWS = [
  { angle: 0, side: "Front", title: "Market & contract", Panel: MarketPanel },
  { angle: 90, side: "Right", title: "Performance", Panel: PerformancePanel },
  {
    angle: 180,
    side: "Back",
    title: "Shirt, honours & transfers",
    Panel: HonoursPanel,
  },
  {
    angle: 270,
    side: "Left",
    title: "Tactical role & fitness",
    Panel: TacticalPanel,
  },
] as const;

const BAR_HEIGHT = 56; // px, the sticky back bar
const DRAG_DEG_PER_PX = 0.5;
const rad = (a: number) => (a * Math.PI) / 180;
/** Hermite ease between two edges, 0 → 1. */
const smoothstep = (e0: number, e1: number, x: number) => {
  const t = Math.min(1, Math.max(0, (x - e0) / (e1 - e0)));
  return t * t * (3 - 2 * t);
};
/** Which of the four views an angle falls in. */
const viewOf = (a: number) => Math.round(normDeg(a) / 90) % 4;

/** Full-viewport showroom: drag or swipe to turn the player through 360°. */
export default function PlayerStage({
  playerId,
  initial,
  backLabel,
  isFavorite,
  onToggleFavorite,
  onCompare,
  keysPaused = false,
  onBack,
}: PlayerStageProps) {
  const reduceMotion = useReducedMotion() ?? false;
  const detailQ = useApiData<StagePlayer | null>(
    (signal) => playersApi.show(playerId, { signal }),
    {
      fallback: fallbackPlayerDetail(playerId) ?? initial,
      initial,
      key: playerId,
    },
  );
  const player = detailQ.data;

  /* ---------- Rotation: physics engine + pointer/keyboard input ---------- */
  const angle = useMotionValue(0);
  const engine = useRef<Turntable | null>(null);
  useEffect(() => {
    const t = createTurntable(angle, reduceMotion);
    engine.current = t;
    return () => t.dispose();
  }, [angle, reduceMotion]);

  // React only re-renders when the view changes (4 times per turn), never per frame.
  const [view, setView] = useState(0);
  useMotionValueEvent(angle, "change", (a) => {
    const v = viewOf(a);
    setView((prev) => (prev === v ? prev : v));
  });

  const drag = useRef<{
    x: number;
    start: number;
    lastX: number;
    lastT: number;
    v: number;
  } | null>(null);
  const onPointerDown = (e: PointerEvent<HTMLElement>) => {
    e.currentTarget.setPointerCapture(e.pointerId);
    engine.current?.grab();
    const now = performance.now();
    drag.current = {
      x: e.clientX,
      start: engine.current?.target() ?? 0,
      lastX: e.clientX,
      lastT: now,
      v: 0,
    };
  };
  const onPointerMove = (e: PointerEvent<HTMLElement>) => {
    const d = drag.current;
    if (!d) return;
    const now = performance.now();
    const dt = Math.max(1, now - d.lastT);
    // Smoothed pointer velocity in degrees/ms, carried into momentum on release.
    d.v = 0.7 * (((e.clientX - d.lastX) * DRAG_DEG_PER_PX) / dt) + 0.3 * d.v;
    d.lastX = e.clientX;
    d.lastT = now;
    engine.current?.dragTo(d.start + (e.clientX - d.x) * DRAG_DEG_PER_PX);
  };
  const onPointerUp = () => {
    const d = drag.current;
    drag.current = null;
    // A pointer that paused before letting go shouldn't fling.
    engine.current?.release(d && performance.now() - d.lastT < 90 ? d.v : 0);
  };
  // Two-finger horizontal swipe on trackpads.
  const onWheel = (e: WheelEvent<HTMLElement>) => {
    if (Math.abs(e.deltaX) <= Math.abs(e.deltaY)) return;
    engine.current?.nudge(-e.deltaX * 0.35);
  };
  const step = (dir: 1 | -1) => {
    const t = engine.current?.target() ?? 0;
    engine.current?.turnTo(Math.round(t / 90) * 90 + dir * 90);
  };
  const onKeyDown = (e: KeyboardEvent<HTMLElement>) => {
    if (e.key !== "ArrowLeft" && e.key !== "ArrowRight") return;
    e.preventDefault();
    step(e.key === "ArrowLeft" ? -1 : 1);
  };

  /* Drag track: the thumb follows the angle continuously; the track maps x → angle. */
  const trackRef = useRef<HTMLDivElement>(null);
  // Shifted by 45° so each view owns a quarter of the track, centred on its label.
  const thumbLeft = useTransform(
    angle,
    (a) => `${(normDeg(a + 45) / 360) * 100}%`,
  );
  const trackDrag = useRef(false);
  const trackAngle = (clientX: number) => {
    const r = trackRef.current!.getBoundingClientRect();
    const f = Math.min(1, Math.max(0, (clientX - r.left) / r.width));
    return nearestEquivalent(f * 360 - 45, engine.current?.target() ?? 0);
  };
  const onTrackDown = (e: PointerEvent<HTMLDivElement>) => {
    e.currentTarget.setPointerCapture(e.pointerId);
    trackDrag.current = true;
    engine.current?.turnTo(trackAngle(e.clientX));
  };
  const onTrackMove = (e: PointerEvent<HTMLDivElement>) => {
    if (trackDrag.current) engine.current?.turnTo(trackAngle(e.clientX));
  };
  const onTrackUp = () => {
    trackDrag.current = false;
  };

  // Esc returns to the squad, unless a dialog on top (the comparison) owns the key.
  useEffect(() => {
    if (keysPaused) return;
    const onKey = (e: globalThis.KeyboardEvent) => {
      if (e.key === "Escape") onBack();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [onBack, keysPaused]);

  // Lock page scroll while the stage is open.
  useEffect(() => {
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = prev;
    };
  }, []);

  /* ---------- Angle-driven visuals ---------- */
  const team = player?.current_team ?? null;
  const kit = teamColors(team);
  // Dark arena: text is always off-white; the club colour lives in the light.
  const fg = "#f4f6f8";
  const glow = (pct: number) =>
    `color-mix(in srgb, ${kit.primary} ${pct}%, transparent)`;
  const rim = `color-mix(in srgb, ${kit.primary} 45%, white)`; // horizon line

  /*
   * A flat cut-out seen edge-on would vanish at the side views, so the turn is drawn
   * as a turntable: the figure narrows with |cos| (never below ~40%), leans in
   * perspective, dips slightly in scale, darkens into a rim-lit silhouette at the
   * sides, and cross-fades from the photo (front hemisphere) to the shirt (back).
   */
  const sin = useTransform(angle, (a) => Math.sin(rad(a)));
  const cos = useTransform(angle, (a) => Math.cos(rad(a)));
  const width = useTransform(cos, (c) => 0.42 + 0.58 * Math.abs(c));
  const scale = useTransform(cos, (c) => 0.96 + 0.04 * Math.abs(c));
  const lean = useTransform(sin, (s) => s * -20);
  /*
   * Over-under dissolve around the side view: the shirt (top layer) eases in over a
   * still-opaque photo, then the photo eases out beneath it. The composite is never
   * see-through (no ghosting), and both layers are deep rim-lit silhouettes there.
   */
  const backOpacity = useTransform(cos, (c) => 1 - smoothstep(-0.07, 0.07, c));
  const frontOpacity = useTransform(cos, (c) => smoothstep(-0.22, -0.05, c));

  // Depth parallax: far layers drift against the turn, near layers with it.
  /*
   * The watermark is fitted to the stage area beside the data panel (the left grid
   * column), so it never runs behind the panel. Its parallax drift scales with that
   * width and is reserved as padding, so the drift can't push it across either.
   */
  const stageCol = useRef<HTMLDivElement>(null);
  const [stageBounds, setStageBounds] = useState<{ left: number; width: number } | null>(null);
  const drift = useMotionValue(90);
  const hasPlayer = !!player;
  useLayoutEffect(() => {
    const el = stageCol.current;
    if (!el) return;
    const measure = () => {
      const r = el.getBoundingClientRect();
      setStageBounds((b) => (b && b.left === r.left && b.width === r.width ? b : { left: r.left, width: r.width }));
      drift.set(Math.min(90, r.width * 0.06));
    };
    measure();
    const ro = new ResizeObserver(measure);
    ro.observe(el);
    window.addEventListener("resize", measure); // the column can move without resizing
    return () => {
      ro.disconnect();
      window.removeEventListener("resize", measure);
    };
  }, [hasPlayer, drift]);
  const bgX = useTransform([sin, drift], ([s, d]: number[]) => s * -d); // watermark (far)
  const bgTurn = useTransform(sin, (s) => s * 7);
  const haloX = useTransform(sin, (s) => s * -34); // club halo (mid)
  const coneX = useTransform(sin, (s) => s * -22); // spotlight (mid)
  const floorX = useTransform(sin, (s) => s * 10); // floor pool (near)
  const shadowX = useTransform(sin, (s) => s * 36);
  const shadowScale = useTransform(cos, (c) => 0.5 + 0.5 * Math.abs(c));
  const depthX = useTransform(sin, (s) => s * 16); // player (nearest)
  const haloScale = useTransform(cos, (c) => 0.9 + 0.12 * Math.abs(c));

  // Rim light in the club's primary colour that swings round with the angle; side views
  // fall into shadow. One drop-shadow only: filters re-render every frame while turning.
  const rimKey = `color-mix(in srgb, ${kit.primary} 50%, white)`;
  const litFilter = ([c, s]: number[]) =>
    `brightness(${(0.2 + 0.8 * Math.pow(Math.abs(c), 0.8)).toFixed(3)}) drop-shadow(${(-s * 7).toFixed(1)}px -2px 4px ${rimKey})`;
  const frontFilter = useTransform([cos, sin], litFilter);
  const backFilter = useTransform([cos, sin], litFilter);

  if (!player) {
    return (
      <div className="fixed inset-0 z-50 flex items-center justify-center bg-canvas">
        <p className="label">
          {detailQ.source === "fallback"
            ? "Player not found."
            : "Loading player…"}
        </p>
        <button
          type="button"
          onClick={onBack}
          className="absolute top-4 left-5 flex items-center gap-2 text-sm"
        >
          <ArrowLeft className="h-4 w-4" aria-hidden /> {backLabel}
        </button>
      </div>
    );
  }

  const surname = player.last_name || player.first_name;
  const number = player.shirt_number ?? team?.pivot.shirt_number ?? null;
  const word = (
    (team?.short_name ?? team?.name ?? surname).trim().split(/\s+/).pop() ??
    surname
  ).toUpperCase();
  const current = VIEWS[view];
  const Panel = current.Panel;

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      transition={{ duration: 0.35 }}
      className="fixed inset-0 z-50 overflow-y-auto lg:overflow-hidden"
      style={{ background: "#070608", color: fg }}
      role="dialog"
      aria-modal="true"
      aria-label={`${playerName(player)} — 360° profile`}
    >
      {/* ---------- Backdrop: club-coloured spotlight in a dark arena ---------- */}
      <div
        aria-hidden
        className="pointer-events-none fixed inset-0 overflow-hidden"
      >
        <div
          className="absolute inset-0"
          style={{
            background: `radial-gradient(48% 58% at 38% 50%, ${glow(42)} 0%, ${glow(12)} 45%, transparent 75%)`,
          }}
        />
        <Watermark
          word={word}
          color={`color-mix(in srgb, ${kit.primary} 65%, white)`}
          opacity={0.12}
          bounds={stageBounds}
          drift={drift.get()}
          x={bgX}
          rotateY={bgTurn}
        />
        <div
          className="absolute inset-0"
          style={{
            background: `linear-gradient(to bottom, color-mix(in srgb, ${kit.primary} 16%, #070608), #050607 70%)`,
            clipPath: "polygon(0 76%, 100% 66%, 100% 100%, 0 100%)",
          }}
        />
        <svg
          className="absolute inset-0 h-full w-full"
          preserveAspectRatio="none"
          viewBox="0 0 100 100"
        >
          <line
            x1="0"
            y1="76"
            x2="100"
            y2="66"
            stroke={rim}
            strokeOpacity="0.45"
            strokeWidth="0.15"
            vectorEffect="non-scaling-stroke"
          />
        </svg>
        <div
          className="absolute inset-0"
          style={{
            background:
              "radial-gradient(120% 90% at 40% 45%, transparent 55%, rgba(0,0,0,0.7) 100%)",
          }}
        />
      </div>

      {/* ---------- Back bar (56px) ---------- */}
      <header
        className="sticky top-0 z-20 border-b border-line bg-[rgba(7,6,8,0.6)] backdrop-blur-md"
        style={{ height: BAR_HEIGHT }}
      >
        {/* Same centred container as every other view */}
        <div className="mx-auto flex h-full w-full max-w-[1340px] items-center justify-between gap-4 px-6">
          <button
            type="button"
            onClick={onBack}
            className="group flex items-center gap-2 text-sm font-medium"
          >
            <ArrowLeft
              className="h-4 w-4 transition-transform group-hover:-translate-x-0.5"
              aria-hidden
            />
            {backLabel}
          </button>
          <p className="hidden text-[13px] opacity-70 sm:block">
            {team?.name ?? "Free agent"} · 360° profile
          </p>
          <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => onCompare(player)}
            aria-label={`Compare ${playerName(player)} with another player`}
            className="flex h-9 items-center gap-2 rounded-full border border-white/20 px-3.5 text-[13px] font-medium transition hover:border-crimson/60"
          >
            <ArrowRightLeft className="h-3.5 w-3.5" aria-hidden />
            <span className="hidden sm:inline">Compare</span>
          </button>
          <button
            type="button"
            onClick={() => onToggleFavorite(player)}
            aria-pressed={isFavorite}
            aria-label={
              isFavorite ? "Remove from favorites" : "Add to favorites"
            }
            className="flex h-9 w-9 items-center justify-center rounded-full border border-white/20 transition hover:border-crimson/60"
          >
            <Heart
              className="h-4 w-4"
              fill={isFavorite ? "currentColor" : "none"}
              aria-hidden
            />
          </button>
          </div>
        </div>
      </header>

      <div className="relative z-10 mx-auto grid w-full max-w-[1340px] px-6 lg:h-[calc(100dvh-56px)] lg:grid-cols-[minmax(0,1fr)_380px]">
        {/* ---------- Turntable: exactly one viewport tall, nothing clipped ---------- */}
        <div ref={stageCol} className="relative flex h-[calc(100dvh-56px)] flex-col overflow-hidden">
          <div className="relative z-10 shrink-0 pt-5">
            <p className="flex items-center gap-2 text-[13px] opacity-75">
              <Flag
                code={player.country?.code}
                name={player.country?.name ?? player.nationality ?? "Unknown"}
                className="h-3 w-4 shrink-0 rounded-[2px] ring-1 ring-white/15"
              />
              {player.nationality ?? player.country.name}
              {player.primary_position && ` · ${player.primary_position}`}
            </p>
            <h2 className="font-display mt-1.5 text-4xl leading-[0.95] font-semibold tracking-tight sm:text-6xl">
              {player.first_name && (
                <span className="block text-xl font-medium tracking-normal opacity-80 sm:text-2xl">
                  {player.first_name}
                </span>
              )}
              {surname}
            </h2>
          </div>

          {/* Drag surface: takes whatever height is left */}
          <div
            tabIndex={0}
            role="slider"
            aria-label="Rotate player"
            aria-valuemin={0}
            aria-valuemax={3}
            aria-valuenow={view}
            aria-valuetext={`${current.side} view: ${current.title}`}
            onPointerDown={onPointerDown}
            onPointerMove={onPointerMove}
            onPointerUp={onPointerUp}
            onPointerCancel={onPointerUp}
            onWheel={onWheel}
            onKeyDown={onKeyDown}
            className="relative min-h-0 flex-1 cursor-grab touch-none outline-none select-none active:cursor-grabbing focus-visible:ring-2 focus-visible:ring-crimson/40 focus-visible:ring-inset"
            style={{ perspective: 1200 }}
          >
            {/* Club-coloured halo behind the player (mid layer) */}
            <motion.div
              aria-hidden
              className="pointer-events-none absolute bottom-[14%] left-1/2 aspect-square h-[70%] -translate-x-1/2 rounded-full will-change-transform"
              style={{
                x: haloX,
                scale: haloScale,
                background: `radial-gradient(closest-side, ${glow(30)}, ${glow(10)} 55%, transparent)`,
              }}
            />
            {/* Overhead spotlight cone (mid layer) */}
            <motion.div
              aria-hidden
              className="pointer-events-none absolute inset-x-0 top-0 bottom-[6%] mx-auto w-[min(70vw,560px)] blur-2xl will-change-transform"
              style={{
                x: coneX,
                background: `linear-gradient(to bottom, rgba(255,255,255,0.10), ${glow(10)} 55%, transparent)`,
                clipPath: "polygon(42% 0, 58% 0, 86% 100%, 14% 100%)",
              }}
            />
            {/* Soft floor spotlight beneath the boots (near layer) */}
            <motion.div
              aria-hidden
              className="absolute bottom-[3%] left-1/2 h-20 w-[min(70vw,560px)] -translate-x-1/2 rounded-[50%] blur-xl will-change-transform"
              style={{
                x: floorX,
                background: `radial-gradient(closest-side, rgba(255,255,255,0.10), ${glow(40)} 45%, transparent)`,
              }}
            />
            <motion.div
              aria-hidden
              className="absolute bottom-[5%] left-1/2 h-7 w-[min(46vw,340px)] -translate-x-1/2 rounded-[50%] blur-md will-change-transform"
              style={{
                x: shadowX,
                scaleX: shadowScale,
                background:
                  "radial-gradient(closest-side, rgba(0,0,0,0.55), rgba(0,0,0,0))",
              }}
            />
            {/* The figure is sized from this surface, not the viewport, so it can't overflow. */}
            <motion.div
              className="absolute inset-x-0 bottom-[6%] mx-auto aspect-[3/4] h-[90%] max-w-[78vw] will-change-transform"
              style={{
                scaleX: width,
                scaleY: scale,
                rotateY: lean,
                x: depthX,
                originY: 1,
              }}
            >
              <motion.div
                className="absolute inset-0 will-change-[opacity,filter]"
                style={{ opacity: frontOpacity, filter: frontFilter }}
              >
                <PlayerCutout
                  player={player}
                  team={team}
                  shirtNumber={number}
                  className="pointer-events-none h-full w-full"
                />
              </motion.div>
              <motion.div
                className="absolute inset-x-[8%] top-[22%] bottom-0 will-change-[opacity,filter]"
                style={{ opacity: backOpacity, filter: backFilter }}
              >
                <JerseyBack
                  surname={surname}
                  number={number}
                  primary={kit.primary}
                  secondary={kit.secondary}
                  className="h-full w-full"
                />
              </motion.div>
            </motion.div>
          </div>

          {/* ---------- Rotation bar: drag track + view cues (no degrees) ---------- */}
          <div className="relative z-10 shrink-0 pt-2 pb-5">
            <div className="mx-auto w-full max-w-md">
              <div
                ref={trackRef}
                onPointerDown={onTrackDown}
                onPointerMove={onTrackMove}
                onPointerUp={onTrackUp}
                onPointerCancel={onTrackUp}
                className="group relative h-7 cursor-grab touch-none active:cursor-grabbing"
                aria-hidden
              >
                <div className="absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-white/15 transition-colors duration-300 group-hover:bg-white/30" />
                {[25, 50, 75].map((p) => (
                  <span
                    key={p}
                    className="absolute top-1/2 h-2 w-px -translate-y-1/2 bg-white/20"
                    style={{ left: `${p}%` }}
                  />
                ))}
                <motion.span
                  className="absolute top-1/2 h-3 w-10 -translate-x-1/2 -translate-y-1/2 rounded-full bg-ink shadow-[0_0_18px_-2px_rgba(225,29,72,0.7)] transition-[width,height,box-shadow] duration-200 will-change-[left] group-hover:w-12 group-active:h-3.5 group-active:w-14 group-active:shadow-[0_0_26px_0_rgba(225,29,72,0.85)]"
                  style={{ left: thumbLeft }}
                />
              </div>
              {/* Quick-flip cues: just Front and Back (the scrubber covers the profiles) */}
              <div
                className="mt-1.5 flex justify-center gap-2"
                role="group"
                aria-label="Quick flip"
              >
                {[VIEWS[0], VIEWS[2]].map((v) => {
                  const active = view === VIEWS.indexOf(v);
                  return (
                    <button
                      key={v.side}
                      type="button"
                      onClick={() => engine.current?.turnTo(v.angle)}
                      aria-pressed={active}
                      className={`rounded-full border px-3.5 py-1 text-xs font-medium transition-colors duration-300 ${
                        active
                          ? "border-crimson/50 text-ink"
                          : "border-line text-ink-mute hover:border-white/20 hover:text-ink-soft"
                      }`}
                    >
                      {v.side}
                    </button>
                  );
                })}
              </div>
              <p className="mt-1 flex items-center justify-center gap-1.5 text-[11px] text-ink-mute">
                <MoveHorizontal className="h-3.5 w-3.5" aria-hidden /> Drag or
                swipe to rotate
              </p>
            </div>
          </div>
        </div>

        {/* ---------- View-synced data layer ---------- */}
        <aside
          className="relative border-t py-6 lg:h-full lg:overflow-y-auto lg:border-t-0 lg:border-l lg:pl-8"
          style={{
            borderColor: "rgba(190,24,60,0.18)",
            background: "rgba(17,12,15,0.82)",
            backdropFilter: "blur(14px)",
          }}
          aria-live="polite"
        >
          <AnimatePresence mode="wait" initial={false}>
            <motion.section
              key={view}
              initial={{ opacity: 0, x: 24 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: -24 }}
              transition={{ duration: 0.28, ease: [0.22, 1, 0.36, 1] }}
            >
              <p className="text-[12.5px] opacity-65">{current.side} view</p>
              <h3 className="font-display mt-1 mb-6 text-2xl font-medium">
                {current.title}
              </h3>
              <Panel player={player} />
            </motion.section>
          </AnimatePresence>
        </aside>
      </div>
    </motion.div>
  );
}
