import type { MotionValue } from 'framer-motion'

/**
 * Turntable physics for the 360° stage, in one requestAnimationFrame loop.
 *
 * Input (drag, swipe, scrubber, keys) only moves a *target* angle. The rendered angle
 * follows it through a damped spring, so the player has weight: it lags a touch behind
 * the hand, carries momentum, and settles without overshooting into a wobble. A released
 * drag keeps the target gliding with exponentially decaying velocity (the "flick").
 * React never re-renders per frame — only the motion value changes.
 */

/** Spring while the pointer is down: tight, so the player stays under the hand. */
const DRAG_STIFFNESS = 260
/** Spring after release / for programmatic turns: softer, for a weighty settle. */
const FREE_STIFFNESS = 110
/** Damping ratio: just under critical — no visible bounce, but not sluggish. */
const DAMPING_RATIO = 0.92
/** Flick momentum half-life ≈ 0.69 × this (ms). */
const MOMENTUM_TAU = 420
/** Fastest release spin, degrees per millisecond. */
const MAX_VELOCITY = 2.2
/** Fixed integration step (s) — keeps the spring stable at any frame rate. */
const STEP = 1 / 240

export const normDeg = (a: number) => ((a % 360) + 360) % 360

/** `deg` moved to the equivalent angle closest to `from` (so turns take the short way round). */
export const nearestEquivalent = (deg: number, from: number) => from + ((((deg - from) % 360) + 540) % 360) - 180

export interface Turntable {
  /** Pointer down: stop any momentum and hold the current target. */
  grab(): void
  /** While dragging: the angle the pointer is asking for. */
  dragTo(target: number): void
  /** Pointer up: hand over the release velocity (degrees/ms) as momentum. */
  release(velocity: number): void
  /** Relative nudge (trackpad swipe, wheel). */
  nudge(delta: number): void
  /** Glide to an absolute angle, the short way round. */
  turnTo(angle: number): void
  target(): number
  dispose(): void
}

export function createTurntable(angle: MotionValue<number>, reduceMotion: boolean): Turntable {
  let target = angle.get()
  let x = angle.get() // rendered angle (deg)
  let v = 0 // rendered angular velocity (deg/s)
  let momentum = 0 // target glide after a flick (deg/ms)
  let dragging = false
  let raf = 0
  let last = 0
  let carry = 0 // leftover time for the fixed-step integrator

  const frame = (now: number) => {
    const dtMs = last ? Math.min(64, now - last) : 16.667
    last = now

    // 1. Flick momentum moves the target, decaying exponentially.
    if (!dragging && momentum !== 0) {
      target += momentum * dtMs
      momentum *= Math.exp(-dtMs / MOMENTUM_TAU)
      if (Math.abs(momentum) < 0.0015) momentum = 0
    }

    // 2. The rendered angle follows the target through a damped spring.
    if (reduceMotion) {
      x = target
      v = 0
    } else {
      const k = dragging ? DRAG_STIFFNESS : FREE_STIFFNESS
      const c = 2 * DAMPING_RATIO * Math.sqrt(k)
      carry += dtMs / 1000
      while (carry >= STEP) {
        const a = k * (target - x) - c * v
        v += a * STEP
        x += v * STEP
        carry -= STEP
      }
    }

    const settled = !dragging && momentum === 0 && Math.abs(target - x) < 0.02 && Math.abs(v) < 0.5
    if (settled) {
      x = target
      v = 0
    }
    angle.set(x)

    if (settled) {
      raf = 0
      last = 0
      carry = 0
    } else raf = requestAnimationFrame(frame)
  }

  const run = () => {
    if (!raf) raf = requestAnimationFrame(frame)
  }

  return {
    grab() {
      dragging = true
      momentum = 0
      run()
    },
    dragTo(t) {
      target = t
      run()
    },
    release(vel) {
      dragging = false
      momentum = reduceMotion ? 0 : Math.max(-MAX_VELOCITY, Math.min(MAX_VELOCITY, vel))
      run()
    },
    nudge(delta) {
      momentum = 0
      target += delta
      run()
    },
    turnTo(a) {
      momentum = 0
      target = nearestEquivalent(a, target)
      run()
    },
    target: () => target,
    dispose() {
      cancelAnimationFrame(raf)
      raf = 0
    },
  }
}
