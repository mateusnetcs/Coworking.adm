<template>
    <Teleport to="body">
        <div
            class="app-brand-watermark-wrap"
            aria-hidden="true"
        >
            <img
                :src="logoSrc"
                alt=""
                class="app-brand-watermark pointer-events-none select-none object-contain"
            />
        </div>
    </Teleport>
</template>

<script setup>
import logoSrc from '../assets/brazilian-logo.png';
</script>

<style scoped>
.app-brand-watermark-wrap {
    position: fixed;
    inset: 0;
    z-index: 40;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    overflow: hidden;
}

.app-brand-watermark {
    width: min(1100px, 95vw);
    max-height: 88vh;
    opacity: 0.0300;
    mix-blend-mode: multiply;
}
</style>
