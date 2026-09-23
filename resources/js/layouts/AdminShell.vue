<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { PhBank, PhChartPieSlice, PhCreditCard, PhListChecks, PhReceipt, PhSignOut } from '@phosphor-icons/vue';
import LoadingOverlay from '../components/LoadingOverlay.vue';
import ToastNotice from '../components/ToastNotice.vue';

type Section = 'overview' | 'accounts' | 'transactions' | 'audit';
defineProps<{ operatorName: string; activeSection: Section; loggingOut?: boolean }>();
const emit = defineEmits<{ navigate: [section: Section]; logout: [] }>();

const navigation = [
    { id: 'overview' as const, label: 'Übersicht', icon: PhChartPieSlice },
    { id: 'accounts' as const, label: 'Konten & Karten', icon: PhCreditCard },
    { id: 'transactions' as const, label: 'Transaktionen', icon: PhReceipt },
    { id: 'audit' as const, label: 'Audit-Protokoll', icon: PhListChecks },
];
</script>

<template>
    <div class="admin-shell">
        <a href="#admin-content" class="skip-link">Zum Inhalt</a>
        <aside class="admin-sidebar">
            <div class="admin-sidebar__brand">
                <span><PhBank :size="28" weight="duotone" /></span>
                <div><strong>LERN-Bank</strong><small>ATM Control Center</small></div>
            </div>
            <nav class="admin-nav" aria-label="Admin-Navigation">
                <p>Verwaltung</p>
                <button v-for="item in navigation" :key="item.id" type="button" :class="{ 'admin-nav__item--active': activeSection === item.id }" :aria-current="activeSection === item.id ? 'page' : undefined" class="admin-nav__item" @click="emit('navigate', item.id)">
                    <component :is="item.icon" :size="21" weight="duotone" /><span>{{ item.label }}</span>
                </button>
            </nav>
            <div class="admin-sidebar__footer">
                <div class="admin-user-avatar">{{ operatorName.charAt(0).toUpperCase() }}</div>
                <div class="admin-sidebar__user"><strong>{{ operatorName }}</strong><small>Administrator</small></div>
                <button type="button" :disabled="loggingOut" aria-label="Abmelden" @click="emit('logout')"><PhSignOut :size="21" /></button>
            </div>
        </aside>
        <div class="admin-workspace">
            <header class="admin-topbar">
                <div><p class="admin-eyebrow">LERN-Bank · Demo-System</p><strong>ATM Administration</strong></div>
                <div class="admin-topbar__actions"><span class="admin-live"><i></i> System online</span><Link href="/atm" class="admin-button admin-button--quiet">Zum Automaten</Link></div>
            </header>
            <nav class="admin-mobile-nav" aria-label="Mobile Admin-Navigation">
                <button v-for="item in navigation" :key="item.id" type="button" :class="{ 'admin-mobile-nav__item--active': activeSection === item.id }" @click="emit('navigate', item.id)"><component :is="item.icon" :size="20" /><span>{{ item.label }}</span></button>
            </nav>
            <main id="admin-content" tabindex="-1" class="admin-content"><slot /></main>
        </div>
        <ToastNotice />
        <LoadingOverlay />
    </div>
</template>
