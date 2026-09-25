<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '../layouts/AppShell.vue';
import { computed } from 'vue';
import { useI18n } from '../composables/useI18n';

const props = defineProps<{ status: number }>();
const { t } = useI18n();
const content = computed<Record<number, { title: string; message: string }>>(() => ({
    403: { title: t('Zugriff nicht erlaubt'), message: t('Für diesen Bereich fehlt die erforderliche Berechtigung.') },
    404: { title: t('Seite nicht gefunden'), message: t('Die angeforderte Seite existiert nicht oder wurde verschoben.') },
    419: { title: t('Sitzung abgelaufen'), message: t('Bitte starte deine Demo-Sitzung erneut.') },
    429: { title: t('Zu viele Anfragen'), message: t('Bitte warte einen Moment und versuche es dann erneut.') },
    500: { title: t('Technischer Fehler'), message: t('Die Anfrage konnte nicht verarbeitet werden. Bitte versuche es später erneut.') },
    503: { title: t('Vorübergehend nicht verfügbar'), message: t('Die Demo wird gerade gewartet. Bitte versuche es später erneut.') },
}));
const error = computed(() => content.value[props.status] ?? content.value[500]);
</script>

<template>
    <Head :title="error.title" />
    <AppShell>
        <section class="mx-auto max-w-xl" aria-labelledby="error-heading">
            <p class="mb-5 font-mono text-sm font-semibold text-green-800">{{ t('FEHLER :status', { status }) }}</p>
            <h1 id="error-heading" class="text-4xl font-semibold tracking-tight">{{ error.title }}</h1>
            <p class="mt-5 text-lg leading-relaxed text-stone-600">{{ error.message }}</p>
            <Link href="/atm" class="mt-8 inline-block rounded-lg bg-green-900 px-5 py-4 font-semibold text-white hover:bg-green-800">{{ t('Zur Startseite') }}</Link>
        </section>
    </AppShell>
</template>
