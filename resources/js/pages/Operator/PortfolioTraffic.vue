<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PhArrowCounterClockwise, PhChartLine, PhEye, PhFingerprint, PhUsersThree } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import AdminShell from '../../layouts/AdminShell.vue';
import { useI18n } from '../../composables/useI18n';

type Section = 'overview' | 'accounts' | 'transactions' | 'audit' | 'portfolio';
type Project = 'portfolio' | 'joinsplit' | 'all';
type Daily = { date: string; label: string; views: number; unique?: number };
type PathRow = { path: string; views: number; unique?: number; source?: string };

const props = defineProps<{
    operatorName: string;
    operatorRole: 'superadmin';
    retentionDays: number;
    report: {
        project: Project;
        filters: { from: string; to: string; earliest: string; latest: string };
        totals: { views: number; unique?: number; repeat?: number; returning?: number };
        daily: Daily[];
        paths: PathRow[];
    };
}>();

const { t } = useI18n();
const from = ref(props.report.filters.from);
const to = ref(props.report.filters.to);
const project = ref<Project>(props.report.project);
const maxViews = computed(() => Math.max(...props.report.daily.map(day => day.views), 1));
const isPortfolio = computed(() => props.report.project === 'portfolio');
const isJoinSplit = computed(() => props.report.project === 'joinsplit');

function navigate(section: Section) {
    if (section !== 'portfolio') router.visit('/admin');
}

function applyPeriod() {
    router.get('/admin/portfolio-traffic', { from: from.value, to: to.value, project: project.value }, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <Head :title="t('Showcase-Traffic')" />
    <AdminShell :operator-name="operatorName" :operator-role="operatorRole" active-section="portfolio" @navigate="navigate" @logout="router.delete('/admin/session')">
        <section aria-labelledby="showcase-traffic-heading">
            <div class="admin-page-heading portfolio-traffic-heading">
                <div>
                    <p class="admin-eyebrow">{{ t('Private Auswertung') }}</p>
                    <h1 id="showcase-traffic-heading" tabindex="-1">{{ t('Showcase-Traffic') }}</h1>
                    <p>{{ t('Aufrufe der freigegebenen Portfolio-Seiten und der JoinSplit-App innerhalb der Aufbewahrungsfrist.') }}</p>
                </div>
                <form class="portfolio-period" @submit.prevent="applyPeriod">
                    <label>{{ t('Projekt') }}<select v-model="project"><option value="portfolio">Portfolio</option><option value="joinsplit">JoinSplit</option><option value="all">{{ t('Gesamtübersicht') }}</option></select></label>
                    <label>{{ t('Von') }}<input v-model="from" type="date" :min="report.filters.earliest" :max="report.filters.latest"></label>
                    <label>{{ t('Bis') }}<input v-model="to" type="date" :min="from" :max="report.filters.latest"></label>
                    <button class="admin-button admin-button--primary" type="submit">{{ t('Auswahl anwenden') }}</button>
                </form>
            </div>

            <div class="admin-metrics portfolio-traffic-metrics">
                <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--blue"><PhEye :size="24" weight="duotone" /></span><div><p>{{ isJoinSplit ? t('App-Aufrufe im Zeitraum') : t('Aufrufe insgesamt') }}</p><strong>{{ report.totals.views }}</strong><small>{{ t('Im gewählten Zeitraum') }}</small></div></article>
                <template v-if="isPortfolio">
                    <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--cyan"><PhFingerprint :size="24" weight="duotone" /></span><div><p>{{ t('Unterschiedliche Kennungen') }}</p><strong>{{ report.totals.unique }}</strong><small>{{ t('Keine exakte Personenzahl') }}</small></div></article>
                    <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--violet"><PhArrowCounterClockwise :size="24" weight="duotone" /></span><div><p>{{ t('Zusätzliche Aufrufe') }}</p><strong>{{ report.totals.repeat }}</strong><small>{{ t('Aufrufe minus Kennungen') }}</small></div></article>
                    <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--green"><PhUsersThree :size="24" weight="duotone" /></span><div><p>{{ t('Wiederkehrende Kennungen') }}</p><strong>{{ report.totals.returning }}</strong><small>{{ t('Schon vor dem Zeitraum gesehen') }}</small></div></article>
                </template>
                <template v-else-if="isJoinSplit">
                    <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--cyan"><PhChartLine :size="24" weight="duotone" /></span><div><p>{{ t('Quelle') }}</p><strong>JoinSplit</strong><small>{{ t('Anonym aggregiert') }}</small></div></article>
                    <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--violet"><PhChartLine :size="24" weight="duotone" /></span><div><p>{{ t('Bereich') }}</p><strong><code>/app</code></strong><small>{{ t('Feste Kategorie') }}</small></div></article>
                </template>
            </div>

            <p v-if="isJoinSplit" class="portfolio-traffic-disclaimer portfolio-traffic-disclaimer--notice">{{ t('JoinSplit erfasst ausschließlich anonyme, aggregierte App-Aufrufe pro Tag. Die Werte stehen für Aufrufe, nicht für eindeutige Menschen oder wiederkehrende Besucher.') }}</p>
            <p v-else-if="report.project === 'all'" class="portfolio-traffic-disclaimer portfolio-traffic-disclaimer--notice">{{ t('Die Gesamtübersicht addiert ausschließlich Aufrufe. Projektübergreifende eindeutige oder wiederkehrende Besucher lassen sich daraus nicht ableiten.') }}</p>

            <div class="admin-dashboard-grid portfolio-traffic-grid">
                <section class="admin-card admin-activity" aria-labelledby="showcase-daily-heading">
                    <div class="admin-card__header"><div><p class="admin-eyebrow">{{ t('Europe/Berlin') }}</p><h2 id="showcase-daily-heading">{{ t('Tagesverlauf') }}</h2></div><span class="admin-chip">{{ t(':count Tage', { count: report.daily.length }) }}</span></div>
                    <div class="admin-chart" role="img" :aria-label="t('Balkendiagramm der Showcase-Aufrufe')">
                        <div v-for="day in report.daily" :key="day.date" class="admin-chart__column"><span class="admin-chart__value">{{ day.views }}</span><div><i :style="{ height: `${Math.max((day.views / maxViews) * 100, day.views ? 12 : 3)}%` }"></i></div><small>{{ day.label }}</small></div>
                    </div>
                </section>

                <section class="admin-card admin-card--wide" aria-labelledby="showcase-paths-heading">
                    <div class="admin-card__header"><div><p class="admin-eyebrow">{{ isPortfolio ? t('Allowlist') : t('Quellen') }}</p><h2 id="showcase-paths-heading">{{ isPortfolio ? t('Aufteilung nach Pfad') : t('Aufteilung nach Projekt') }}</h2></div></div>
                    <div class="portfolio-path-table"><table><thead><tr><th v-if="!isPortfolio">{{ t('Quelle') }}</th><th>{{ isPortfolio ? t('Portfolio-Pfad') : t('Bereich') }}</th><th>{{ t('Aufrufe') }}</th><th v-if="isPortfolio">{{ t('Kennungen') }}</th></tr></thead><tbody><tr v-for="row in report.paths" :key="`${row.source ?? 'portfolio'}-${row.path}`"><td v-if="!isPortfolio">{{ row.source }}</td><td><code>{{ row.path }}</code></td><td>{{ row.views }}</td><td v-if="isPortfolio">{{ row.unique }}</td></tr></tbody></table></div>
                </section>
            </div>

            <p v-if="isPortfolio" class="portfolio-traffic-disclaimer">{{ t('Die Kennung wird aus IP-Adresse und einer groben Browser-/Plattformklasse gehasht. Gemeinsame Anschlüsse, Browserwechsel und IP-Wechsel begrenzen die Aussagekraft. Rohwerte werden nicht gespeichert; Ereignisse werden nach :count Tagen opportunistisch gelöscht.', { count: retentionDays }) }}</p>
            <p v-else class="portfolio-traffic-disclaimer">{{ t('Aggregierte JoinSplit-Tageszähler werden nach :count Tagen opportunistisch gelöscht. IP-Adresse, User-Agent, Referrer, Cookies und Besucherkennungen werden nicht in der JoinSplit-Statistik gespeichert.', { count: retentionDays }) }}</p>
        </section>
    </AdminShell>
</template>
