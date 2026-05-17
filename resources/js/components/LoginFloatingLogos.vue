<template>
    <div
        class="login-floating-logos"
        aria-hidden="true"
        style="position: fixed; inset: 0; z-index: 5; overflow: hidden; pointer-events: none;"
    >
        <div
            v-for="logo in logos"
            :key="logo.id"
            class="login-floating-logos__orb"
            :style="orbStyle(logo)"
        >
            <img
                :src="logoSrc"
                alt=""
                class="login-floating-logos__item"
                :style="imgStyle(logo)"
            />
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import logoSrc from '../assets/brazilian-logo.png';

const REPULSE_RADIUS = 165;
const REPULSE_STRENGTH = 120;

const mouse = ref({ x: -9999, y: -9999 });
const offsets = ref({});

function buildLogos() {
    const items = [];
    const cols = 12;
    const rows = 8;
    let id = 0;

    for (let row = 0; row < rows; row += 1) {
        for (let col = 0; col < cols; col += 1) {
            const jitterX = ((row * 19 + col * 29) % 11) - 5;
            const jitterY = ((row * 27 + col * 11) % 11) - 5;
            const left = col * (100 / (cols - 1)) + jitterX * 0.35;
            const top = row * (100 / (rows - 1)) + jitterY * 0.4;
            const size = 22 + ((row + col) % 6) * 5;

            items.push({
                id: id += 1,
                left: Math.min(98, Math.max(1, left)),
                top: Math.min(98, Math.max(1, top)),
                size,
                dur: 11 + ((row * col) % 12),
                delay: -((row * 2.1 + col * 1.9) % 18),
                dx: 12 + ((row + col) % 5) * 6,
                dy: 10 + ((row * col) % 4) * 6,
                signX: col % 2 === 0 ? 1 : -1,
                signY: row % 2 === 0 ? 1 : -1,
            });
        }
    }

    return items;
}

const logos = buildLogos();

let rafId = null;

function updateOffsets(mx, my) {
    const w = window.innerWidth;
    const h = window.innerHeight;
    const next = {};

    for (const logo of logos) {
        const cx = (logo.left / 100) * w;
        const cy = (logo.top / 100) * h;
        const dx = cx - mx;
        const dy = cy - my;
        const dist = Math.hypot(dx, dy);

        if (dist < REPULSE_RADIUS && dist > 0.5) {
            const ratio = 1 - dist / REPULSE_RADIUS;
            const force = ratio * ratio * REPULSE_STRENGTH;
            next[logo.id] = {
                x: (dx / dist) * force,
                y: (dy / dist) * force,
                glow: ratio,
            };
        } else {
            next[logo.id] = { x: 0, y: 0, glow: 0 };
        }
    }

    offsets.value = next;
}

function onMouseMove(event) {
    mouse.value = { x: event.clientX, y: event.clientY };
    if (rafId !== null) {
        return;
    }
    rafId = requestAnimationFrame(() => {
        rafId = null;
        updateOffsets(mouse.value.x, mouse.value.y);
    });
}

function onMouseLeave() {
    mouse.value = { x: -9999, y: -9999 };
    offsets.value = {};
}

function orbStyle(logo) {
    const o = offsets.value[logo.id] ?? { x: 0, y: 0, glow: 0 };

    return {
        position: 'absolute',
        left: `${logo.left}%`,
        top: `${logo.top}%`,
        width: `${logo.size}px`,
        height: `${logo.size}px`,
        transform: `translate(calc(-50% + ${o.x}px), calc(-50% + ${o.y}px))`,
        zIndex: o.glow > 0.08 ? Math.round(10 + o.glow * 30) : 1,
        pointerEvents: 'none',
    };
}

function imgStyle(logo) {
    const o = offsets.value[logo.id] ?? { x: 0, y: 0, glow: 0 };

    return {
        '--dur': `${logo.dur}s`,
        '--delay': `${logo.delay}s`,
        '--dx': `${logo.dx * logo.signX}px`,
        '--dy': `${logo.dy * logo.signY}px`,
        width: `${logo.size}px`,
        height: 'auto',
        maxWidth: `${logo.size}px`,
        display: 'block',
        opacity: 0.13 + o.glow * 0.6,
        filter: `brightness(${1 + o.glow * 0.95}) saturate(${1 + o.glow * 0.35})`,
    };
}

onMounted(() => {
    window.addEventListener('mousemove', onMouseMove, { passive: true });
    document.addEventListener('mouseleave', onMouseLeave);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseleave', onMouseLeave);
    if (rafId !== null) {
        cancelAnimationFrame(rafId);
    }
});
</script>

<style scoped>
.login-floating-logos {
    position: fixed;
    inset: 0;
    z-index: 5;
    overflow: hidden;
    pointer-events: none;
}

.login-floating-logos__orb {
    position: absolute;
    pointer-events: none;
    transition:
        transform 0.12s ease-out,
        opacity 0.15s ease-out;
    will-change: transform;
}

.login-floating-logos__item {
    display: block;
    width: 100%;
    height: auto;
    mix-blend-mode: multiply;
    animation: login-logo-float var(--dur, 14s) ease-in-out infinite alternate;
    animation-delay: var(--delay, 0s);
    transition:
        opacity 0.15s ease-out,
        filter 0.15s ease-out;
    will-change: transform, opacity, filter;
}

@keyframes login-logo-float {
    0% {
        transform: translate(0, 0) rotate(0deg);
    }
    50% {
        transform: translate(calc(var(--dx, 14px) * 0.5), calc(var(--dy, -12px) * 0.5)) rotate(2deg);
    }
    100% {
        transform: translate(var(--dx, 14px), var(--dy, -12px)) rotate(-2deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .login-floating-logos__item {
        animation: none;
    }

    .login-floating-logos__orb {
        transition: none;
    }
}
</style>
