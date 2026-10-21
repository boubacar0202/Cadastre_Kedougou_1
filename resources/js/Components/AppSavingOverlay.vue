<script setup>
import { savingState } from "@/utils/savingOverlay";
</script>

<template>
    <Teleport to="body">
        <Transition name="saving-fade">
            <div
                v-if="savingState.active"
                class="saving-overlay"
                role="status"
                aria-live="polite"
                :aria-label="savingState.label"
            >
                <div class="saving-card">
                    <div class="saving-stage">
                        <span class="saving-orbit" aria-hidden="true"></span>
                        <span class="saving-orbit saving-orbit--inner" aria-hidden="true"></span>
                        <img src="/logo-01.png" alt="" class="saving-logo" />
                    </div>
                    <p class="saving-label">{{ savingState.label }}</p>
                    <span class="saving-bar" aria-hidden="true"></span>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.saving-overlay {
    position: fixed;
    inset: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background: rgba(63, 36, 16, 0.46);
    backdrop-filter: blur(6px);
}

.saving-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.85rem;
    min-width: 240px;
    padding: 1.6rem 1.8rem 1.35rem;
    border: 1px solid rgba(230, 197, 162, 0.95);
    border-radius: 1.5rem;
    background: #fffaf4;
    box-shadow: 0 24px 60px rgba(97, 49, 4, 0.28);
}

.saving-stage {
    position: relative;
    display: grid;
    width: 148px;
    height: 148px;
    place-items: center;
}

.saving-logo {
    position: relative;
    z-index: 1;
    height: 118px;
    width: auto;
    animation: saving-pulse 1.5s ease-in-out infinite;
    filter: drop-shadow(0 10px 16px rgba(97, 49, 4, 0.22));
}

.saving-orbit {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: conic-gradient(
        from 0deg,
        transparent 0 62%,
        rgba(199, 129, 46, 0.2) 74%,
        #e2b657 90%,
        #613104 100%
    );
    mask: radial-gradient(farthest-side, transparent calc(100% - 4px), #000 calc(100% - 3px));
    animation: saving-spin 0.95s linear infinite;
}

.saving-orbit--inner {
    inset: 14px;
    animation-duration: 1.45s;
    animation-direction: reverse;
    opacity: 0.85;
}

.saving-label {
    margin: 0;
    color: #3f2410;
    font-size: 0.98rem;
    font-weight: 600;
    letter-spacing: 0.01em;
}

.saving-bar {
    position: relative;
    width: 148px;
    height: 4px;
    overflow: hidden;
    border-radius: 999px;
    background: rgba(199, 129, 46, 0.18);
}

.saving-bar::after {
    content: "";
    position: absolute;
    top: 0;
    left: -40%;
    width: 40%;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, transparent, #c7812e, #e2b657);
    animation: saving-slide 1.1s ease-in-out infinite;
}

.saving-fade-enter-active,
.saving-fade-leave-active {
    transition: opacity 0.22s ease;
}

.saving-fade-enter-from,
.saving-fade-leave-to {
    opacity: 0;
}

@keyframes saving-spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes saving-pulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.045);
    }
}

@keyframes saving-slide {
    0% {
        left: -40%;
    }
    100% {
        left: 110%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .saving-logo,
    .saving-orbit,
    .saving-bar::after {
        animation: none;
    }
}
</style>
