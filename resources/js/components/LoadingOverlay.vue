<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';

const visible = ref(false);
let delay: ReturnType<typeof setTimeout> | null = null;

const removeStartListener = router.on('start', () => {
    if (delay) clearTimeout(delay);
    delay = setTimeout(() => { visible.value = true; }, 180);
});
const removeFinishListener = router.on('finish', () => {
    if (delay) clearTimeout(delay);
    delay = null;
    visible.value = false;
});

onBeforeUnmount(() => {
    removeStartListener();
    removeFinishListener();
    if (delay) clearTimeout(delay);
});
</script>

<template>
    <Transition name="loading">
        <div v-if="visible" class="loading-overlay" role="status" aria-live="polite" aria-label="Seite wird geladen">
            <div class="loading-overlay__panel">
                <span class="loading-spinner" aria-hidden="true"></span>
                <span>Bitte einen Moment …</span>
            </div>
        </div>
    </Transition>
</template>
