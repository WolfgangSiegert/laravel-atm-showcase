<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from '../composables/useI18n';

const page = usePage<{ notice?: string | null }>();
const { t } = useI18n();
const message = ref<string | null>(null);
let timeout: ReturnType<typeof setTimeout> | null = null;

function dismiss() {
    message.value = null;
    if (timeout) clearTimeout(timeout);
    timeout = null;
}

watch(
    () => page.props.notice,
    (notice) => {
        if (!notice) return;
        message.value = notice;
        if (timeout) clearTimeout(timeout);
        timeout = setTimeout(dismiss, 6000);
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (timeout) clearTimeout(timeout);
});
</script>

<template>
    <Transition name="toast">
        <aside v-if="message" class="toast-notice" role="status" aria-live="polite">
            <span class="toast-notice__mark" aria-hidden="true">✓</span>
            <p>{{ message }}</p>
            <button type="button" class="toast-notice__close" :aria-label="t('Benachrichtigung schließen')" @click="dismiss">×</button>
        </aside>
    </Transition>
</template>
