<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PhArrowCounterClockwise, PhChartLine, PhEye, PhFingerprint, PhUsersThree } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import AdminShell from '../../layouts/AdminShell.vue';
import { useI18n } from '../../composables/useI18n';

type Section = 'overview' | 'accounts' | 'transactions' | 'audit' | 'portfolio';
type Daily = { date: string; label: string; views: number; unique: number };
type PathRow = { path: string; views: number; unique: number };

const props = defineProps<{
    operatorName: string;
    operatorRole: 'superadmin';
    retentionDays: number;
    report: {
        filters: { from: string; to: string; earliest: string; latest: string };
        totals: { views: number; unique: number; repeat: number; returning: number };
        daily: Daily[];
        paths: PathRow[];
    };
}>();

const { t } = useI18n();
const from = ref(props.report.filters.from);
const to = ref(props.report.filters.to);
const maxViews = computed(() => Math.max(...props.report.daily.map(day => day.views), 1));

function navigate(section: Section) {
    if (section !== 'portfolio') router.visit('/admin');
}

function applyPeriod() {
    router.get('/admin/portfolio-traffic', { from: from.value, to: to.value }, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <Head :title="t('Portfolio-Traffic')" />
    <AdminShell :operator-name="operatorName" :operator-role="operatorRole" active-section="portfolio" @navigate="navigate" @logout="router.delete('/admin/session')">
        <section aria-labelledby="portfolio-traffic-heading">
            <div class="admin-page-heading portfolio-traffic-heading">
                <div>
                    <p class="admin-eyebrow">{{ t('Private Auswertung') }}</p>
                    <h1 id="portfolio-traffic-heading" tabindex="-1">{{ t('Portfolio-Traffic') }}</h1>
                    <p>{{ t('Pseudonyme Aufrufe der freigegebenen Portfolio-Seiten innerhalb der Aufbewahrungsfrist.') }}</p>
                </div>
                <form class="portfolio-period" @submit.prevent="applyPeriod">
                    <label>{{ t('Von') }}<input v-model="from" type="date" :min="report.filters.earliest" :max="report.filters.latest"></label>
                    <label>{{ t('Bis') }}<input v-model="to" type="date" :min="from" :max="report.filters.latest"></label>
                    <button class="admin-button admin-button--primary" type="submit">{{ t('Zeitraum anwenden') }}</button>
                </form>
            </div>

            <div class="admin-metrics portfolio-traffic-metrics">
                <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--blue"><PhEye :size="24" weight="duotone" /></span><div><p>{{ t('Aufrufe insgesamt') }}</p><strong>{{ report.totals.views }}</strong><small>{{ t('Im gewählten Zeitraum') }}</small></div></article>
                <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--cyan"><PhFingerprint :size="24" weight="duotone" /></span><div><p>{{ t('Unterschiedliche Kennungen') }}</p><strong>{{ report.totals.unique }}</strong><small>{{ t('Keine exakte Personenzahl') }}</small></div></article>
                <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--violet"><PhArrowCounterClockwise :size="24" weight="duotone" /></span><div><p>{{ t('Zusätzliche Aufrufe') }}</p><strong>{{ report.totals.repeat }}</strong><small>{{ t('Aufrufe minus Kennungen') }}</small></div></article>
                <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--green"><PhUsersThree :size="24" weight="duotone" /></span><div><p>{{ t('Wiederkehrende Kennungen') }}</p><strong>{{ report.totals.returning }}</strong><small>{{ t('Schon vor dem Zeitraum gesehen') }}</small></div></article>
            </div>

            <div class="admin-dashboard-grid portfolio-traffic-grid">
                <section class="admin-card admin-activity" aria-labelledby="portfolio-daily-heading">
                    <div class="admin-card__header"><div><p class="admin-eyebrow">{{ t('Europe/Berlin') }}</p><h2 id="portfolio-daily-heading">{{ t('Tagesverlauf') }}</h2></div><span class="admin-chip">{{ t(':count Tage', { count: report.daily.length }) }}</span></div>
                    <div class="admin-chart" role="img" :aria-label="t('Balkendiagramm der Portfolio-Aufrufe')">
                        <div v-for="day in report.daily" :key="day.date" class="admin-chart__column"><span class="admin-chart__value">{{ day.views }}</span><div><i :style="{ height: `${Math.max((day.views / maxViews) * 100, day.views ? 12 : 3)}%` }"></i></div><small>{{ day.label }}</small></div>
                    </div>
                </section>

                <section class="admin-card admin-card--wide" aria-labelledby="portfolio-paths-heading">
                    <div class="admin-card__header"><div><p class="admin-eyebrow">{{ t('Allowlist') }}</p><h2 id="portfolio-paths-heading">{{ t('Aufteilung nach Pfad') }}</h2></div></div>
                    <div class="portfolio-path-table">
                        <table>
                            <thead><tr><th>{{ t('Portfolio-Pfad') }}</th><th>{{ t('Aufrufe') }}</th><th>{{ t('Kennungen') }}</th></tr></thead>
                            <tbody><tr v-for="row in report.paths" :key="row.path"><td><code>{{ row.path }}</code></td><td>{{ row.views }}</td><td>{{ row.unique }}</td></tr></tbody>
                        </table>
                    </div>
                </section>
            </div>

            <p class="portfolio-traffic-disclaimer">{{ t('Die Kennung wird aus IP-Adresse und einer groben Browser-/Plattformklasse gehasht. Gemeinsame Anschlüsse, Browserwechsel und IP-Wechsel begrenzen die Aussagekraft. Rohwerte werden nicht gespeichert; Ereignisse werden nach :count Tagen opportunistisch gelöscht.', { count: retentionDays }) }}</p>
        </section>
    </AdminShell>
</template>
