<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '../layouts/AppShell.vue';

const props = defineProps<{ status: number }>();
const content: Record<number, { title: string; message: string }> = {
    403: { title: 'Zugriff nicht erlaubt', message: 'Für diesen Bereich fehlt die erforderliche Berechtigung.' },
    404: { title: 'Seite nicht gefunden', message: 'Die angeforderte Seite existiert nicht oder wurde verschoben.' },
    419: { title: 'Sitzung abgelaufen', message: 'Bitte starte deine Demo-Sitzung erneut.' },
    429: { title: 'Zu viele Anfragen', message: 'Bitte warte einen Moment und versuche es dann erneut.' },
    500: { title: 'Technischer Fehler', message: 'Die Anfrage konnte nicht verarbeitet werden. Bitte versuche es später erneut.' },
    503: { title: 'Vorübergehend nicht verfügbar', message: 'Die Demo wird gerade gewartet. Bitte versuche es später erneut.' },
};
const error = content[props.status] ?? content[500];
</script>

<template>
    <Head :title="error.title" />
    <AppShell>
        <section class="mx-auto max-w-xl" aria-labelledby="error-heading">
            <p class="mb-5 font-mono text-sm font-semibold text-green-800">FEHLER {{ status }}</p>
            <h1 id="error-heading" class="text-4xl font-semibold tracking-tight">{{ error.title }}</h1>
            <p class="mt-5 text-lg leading-relaxed text-stone-600">{{ error.message }}</p>
            <Link href="/atm" class="mt-8 inline-block rounded-lg bg-green-900 px-5 py-4 font-semibold text-white hover:bg-green-800">Zur Startseite</Link>
        </section>
    </AppShell>
</template>
