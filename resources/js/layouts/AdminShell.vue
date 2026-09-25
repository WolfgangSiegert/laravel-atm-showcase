<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { PhBank, PhCaretLeft, PhCaretRight, PhChartPieSlice, PhCreditCard, PhListChecks, PhReceipt, PhSignOut } from '@phosphor-icons/vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import LanguageSwitcher from '../components/LanguageSwitcher.vue';
import LoadingOverlay from '../components/LoadingOverlay.vue';
import ToastNotice from '../components/ToastNotice.vue';
import { useI18n } from '../composables/useI18n';

type Section = 'overview' | 'accounts' | 'transactions' | 'audit';
const props = defineProps<{ operatorName: string; operatorRole: 'superadmin' | 'viewer'; activeSection: Section; loggingOut?: boolean }>();
const emit = defineEmits<{ navigate: [section: Section]; logout: [] }>();
const { t } = useI18n();

const mobileNavigation = ref<HTMLElement | null>(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);
let navigationResizeObserver: ResizeObserver | undefined;

function updateScrollIndicators() {
    const navigationElement = mobileNavigation.value;

    if (!navigationElement) return;

    const scrollEnd = navigationElement.scrollWidth - navigationElement.clientWidth;
    canScrollLeft.value = navigationElement.scrollLeft > 1;
    canScrollRight.value = scrollEnd > 1 && navigationElement.scrollLeft < scrollEnd - 1;
}

function scrollNavigation(direction: -1 | 1) {
    const navigationElement = mobileNavigation.value;

    if (!navigationElement) return;

    navigationElement.scrollBy({ left: direction * Math.max(180, navigationElement.clientWidth * 0.7), behavior: 'smooth' });
}

onMounted(() => {
    nextTick(updateScrollIndicators);

    if (mobileNavigation.value) {
        navigationResizeObserver = new ResizeObserver(updateScrollIndicators);
        navigationResizeObserver.observe(mobileNavigation.value);
    }
});

onBeforeUnmount(() => navigationResizeObserver?.disconnect());

watch(
    () => props.activeSection,
    () => nextTick(() => {
        mobileNavigation.value?.querySelector<HTMLElement>('[aria-current="page"]')?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
        updateScrollIndicators();
    }),
);

const navigation = computed(() => [
    { id: 'overview' as const, label: t('Übersicht'), icon: PhChartPieSlice },
    { id: 'accounts' as const, label: t('Konten & Karten'), icon: PhCreditCard },
    { id: 'transactions' as const, label: t('Transaktionen'), icon: PhReceipt },
    { id: 'audit' as const, label: t('Audit-Protokoll'), icon: PhListChecks },
]);
</script>

<template>
    <div class="admin-shell">
        <a href="#admin-content" class="skip-link">{{ t('Zum Inhalt') }}</a>
        <aside class="admin-sidebar">
            <div class="admin-sidebar__brand">
                <span><PhBank :size="28" weight="duotone" /></span>
                <div><strong>LERN-Bank</strong><small>ATM Control Center</small></div>
            </div>
            <nav class="admin-nav" :aria-label="t('Admin-Navigation')">
                <p>{{ t('Verwaltung') }}</p>
                <button v-for="item in navigation" :key="item.id" type="button" :class="{ 'admin-nav__item--active': activeSection === item.id }" :aria-current="activeSection === item.id ? 'page' : undefined" class="admin-nav__item" @click="emit('navigate', item.id)">
                    <component :is="item.icon" :size="21" weight="duotone" /><span>{{ item.label }}</span>
                </button>
            </nav>
            <div class="admin-sidebar__footer">
                <div class="admin-user-avatar">{{ operatorName.charAt(0).toUpperCase() }}</div>
                <div class="admin-sidebar__user"><strong>{{ operatorName }}</strong><small>{{ operatorRole === 'superadmin' ? 'Superadmin' : t('Read-only-Gast') }}</small></div>
                <button type="button" :disabled="loggingOut" :aria-label="t('Abmelden')" @click="emit('logout')"><PhSignOut :size="21" /></button>
            </div>
        </aside>
        <div class="admin-workspace">
            <header class="admin-topbar">
                <div><p class="admin-eyebrow">{{ t('LERN-Bank · Demo-System') }}</p><strong>ATM Administration</strong></div>
                <div class="admin-topbar__actions"><span class="admin-live"><i></i> {{ t('System online') }}</span><LanguageSwitcher /><Link href="/atm" class="admin-button admin-button--quiet">{{ t('Zum Automaten') }}</Link></div>
            </header>
            <div class="admin-mobile-nav-shell" :class="{ 'admin-mobile-nav-shell--left': canScrollLeft, 'admin-mobile-nav-shell--right': canScrollRight }">
                <nav ref="mobileNavigation" class="admin-mobile-nav" :aria-label="t('Mobile Admin-Navigation')" @scroll.passive="updateScrollIndicators">
                    <button v-for="item in navigation" :key="item.id" type="button" :class="{ 'admin-mobile-nav__item--active': activeSection === item.id }" :aria-current="activeSection === item.id ? 'page' : undefined" @click="emit('navigate', item.id)"><component :is="item.icon" :size="20" /><span>{{ item.label }}</span></button>
                </nav>
                <button v-show="canScrollLeft" type="button" class="admin-mobile-nav__scroll admin-mobile-nav__scroll--left" :aria-label="t('Menü nach links scrollen')" @click="scrollNavigation(-1)"><PhCaretLeft :size="18" weight="bold" aria-hidden="true" /></button>
                <button v-show="canScrollRight" type="button" class="admin-mobile-nav__scroll admin-mobile-nav__scroll--right" :aria-label="t('Menü nach rechts scrollen')" @click="scrollNavigation(1)"><PhCaretRight :size="18" weight="bold" aria-hidden="true" /></button>
            </div>
            <main id="admin-content" tabindex="-1" class="admin-content"><slot /></main>
        </div>
        <ToastNotice />
        <LoadingOverlay />
    </div>
</template>
