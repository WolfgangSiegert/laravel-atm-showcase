<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { PhArrowDown, PhArrowUp, PhBank, PhCaretRight, PhCheckCircle, PhCoins, PhCreditCard, PhCurrencyEur, PhFunnel, PhGearSix, PhMagnifyingGlass, PhPencilSimple, PhReceipt, PhShieldCheck, PhTrendUp, PhUsers, PhWarning, PhX } from '@phosphor-icons/vue';
import { computed, nextTick, ref } from 'vue';
import AdminShell from '../../layouts/AdminShell.vue';

type Section = 'overview' | 'accounts' | 'transactions' | 'audit';
type Inventory = { id: number; denominationMinor: number; quantity: number };
type AccountCard = { id: number; reference: string; status: string; failedAttempts: number; lockedUntil: string | null };
type Account = { id: number; reference: string; customer: string; currency: string; balanceMinor: number; status: string; cards: AccountCard[] };
type Transaction = { id: number; receiptReference: string; accountReference: string; customer: string; cardReference: string | null; type: string; purpose: string | null; amountMinor: number; balanceAfterMinor: number; currency: string; createdAt: string };
type AuditEvent = { id: number; event_type: string; outcome: string; reason_code: string | null; context: Record<string, string | number | boolean | null> | null; created_at: string };
type Activity = { date: string; label: string; count: number; amountMinor: number };

const props = defineProps<{
    operatorName: string;
    metrics: { accounts: number; activeCards: number; transactions: number; todayVolumeMinor: number };
    atm: { code: string; label: string; status: string; currency: string; totalMinor: number; inventory: Inventory[] };
    activity: Activity[];
    accounts: Account[];
    transactions: Transaction[];
    auditEvents: AuditEvent[];
}>();

const page = usePage<{ errors: Record<string, string> }>();
const activeSection = ref<Section>('overview');
const search = ref('');
const transactionType = ref('all');
const statusDialog = ref<HTMLDialogElement | null>(null);
const inventoryDrawer = ref<HTMLDialogElement | null>(null);
const selectedInventory = ref<Inventory | null>(null);
const statusForm = useForm({ status: props.atm.status });
const inventoryForm = useForm({ adjustment: '' });
const logout = useForm({});

const money = (minor: number, currency = props.atm.currency) => new Intl.NumberFormat('de-DE', { style: 'currency', currency }).format(minor / 100);
const date = (value: string) => new Intl.DateTimeFormat('de-DE', { dateStyle: 'short', timeStyle: 'short', timeZone: 'Europe/Berlin' }).format(new Date(value));
const maxActivity = computed(() => Math.max(...props.activity.map(item => item.count), 1));
const inventoryTotal = computed(() => props.atm.inventory.reduce((sum, item) => sum + item.quantity, 0));
const lowInventoryCount = computed(() => props.atm.inventory.filter(item => item.quantity < 10).length);
const normalizedSearch = computed(() => search.value.trim().toLowerCase());
const filteredAccounts = computed(() => props.accounts.filter(account => !normalizedSearch.value || [account.reference, account.customer, ...account.cards.map(card => card.reference)].some(value => value.toLowerCase().includes(normalizedSearch.value))));
const filteredTransactions = computed(() => props.transactions.filter(transaction => (transactionType.value === 'all' || transaction.type === transactionType.value) && (!normalizedSearch.value || [transaction.receiptReference, transaction.accountReference, transaction.customer, transaction.purpose ?? ''].some(value => value.toLowerCase().includes(normalizedSearch.value)))));
const filteredAudit = computed(() => props.auditEvents.filter(event => !normalizedSearch.value || [event.event_type, event.outcome, event.reason_code ?? ''].some(value => value.toLowerCase().includes(normalizedSearch.value))));

function navigate(section: Section) {
    activeSection.value = section;
    search.value = '';
    nextTick(() => document.querySelector<HTMLElement>('#admin-content h1')?.focus());
}
function openStatusDialog() {
    statusForm.status = props.atm.status;
    statusDialog.value?.showModal();
}
function updateStatus() {
    statusForm.patch('/admin/atm/status', { preserveScroll: true, onSuccess: () => statusDialog.value?.close() });
}
function openInventoryDrawer(item: Inventory) {
    selectedInventory.value = item;
    inventoryForm.reset();
    inventoryForm.clearErrors();
    inventoryDrawer.value?.showModal();
}
function adjustInventory() {
    if (!selectedInventory.value) return;
    inventoryForm.post(`/admin/inventory/${selectedInventory.value.id}/adjust`, { preserveScroll: true, onSuccess: () => inventoryDrawer.value?.close() });
}
function eventLabel(type: string) {
    return ({ 'operator.login': 'Admin-Anmeldung', 'operator.logout': 'Admin-Abmeldung', 'atm.status_changed': 'Automatenstatus geändert', 'atm.inventory_adjusted': 'Bargeldbestand geändert', 'atm_session.login': 'Kartensitzung gestartet', 'atm_session.logout': 'Kartensitzung beendet', 'atm_session.invalidated': 'Kartensitzung ungültig', 'transaction.deposit': 'Einzahlung', 'transaction.withdrawal': 'Auszahlung' } as Record<string, string>)[type] ?? type;
}
function transactionLabel(type: string) {
    return ({ deposit: 'Einzahlung', withdrawal: 'Auszahlung', opening: 'Eröffnung' } as Record<string, string>)[type] ?? type;
}
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminShell :operator-name="operatorName" :active-section="activeSection" :logging-out="logout.processing" @navigate="navigate" @logout="logout.delete('/admin/session')">
        <section v-if="activeSection === 'overview'" aria-labelledby="admin-overview-heading">
            <div class="admin-page-heading">
                <div><p class="admin-eyebrow">Dashboard</p><h1 id="admin-overview-heading" tabindex="-1">Guten Tag.</h1><p>Hier ist der aktuelle Zustand deines Demo-Geldautomaten.</p></div>
                <button type="button" class="admin-button admin-button--primary" @click="openStatusDialog"><PhGearSix :size="19" /> Automat verwalten</button>
            </div>

            <div class="admin-metrics">
                <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--violet"><PhCurrencyEur :size="24" weight="duotone" /></span><div><p>Bargeldbestand</p><strong>{{ money(atm.totalMinor) }}</strong><small><PhTrendUp :size="15" /> {{ inventoryTotal }} Scheine im Automaten</small></div></article>
                <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--blue"><PhUsers :size="24" weight="duotone" /></span><div><p>Konten</p><strong>{{ metrics.accounts }}</strong><small>{{ metrics.activeCards }} aktive Karten</small></div></article>
                <article class="admin-metric"><span class="admin-metric__icon admin-metric__icon--cyan"><PhReceipt :size="24" weight="duotone" /></span><div><p>Transaktionen</p><strong>{{ metrics.transactions }}</strong><small>{{ money(metrics.todayVolumeMinor) }} heute bewegt</small></div></article>
                <article class="admin-metric"><span class="admin-metric__icon" :class="lowInventoryCount ? 'admin-metric__icon--amber' : 'admin-metric__icon--green'"><PhShieldCheck :size="24" weight="duotone" /></span><div><p>Systemstatus</p><strong>{{ atm.status === 'active' ? 'Aktiv' : 'Wartung' }}</strong><small>{{ lowInventoryCount ? `${lowInventoryCount} Bestandshinweise` : 'Keine offenen Hinweise' }}</small></div></article>
            </div>

            <div class="admin-dashboard-grid">
                <section class="admin-card admin-activity" aria-labelledby="activity-heading">
                    <div class="admin-card__header"><div><p class="admin-eyebrow">Letzte 7 Tage</p><h2 id="activity-heading">Transaktionsaktivität</h2></div><span class="admin-chip">{{ activity.reduce((sum, item) => sum + item.count, 0) }} Buchungen</span></div>
                    <div class="admin-chart" role="img" aria-label="Balkendiagramm der Transaktionen der letzten sieben Tage">
                        <div v-for="item in activity" :key="item.date" class="admin-chart__column"><span class="admin-chart__value">{{ item.count }}</span><div><i :style="{ height: `${Math.max((item.count / maxActivity) * 100, item.count ? 12 : 3)}%` }"></i></div><small>{{ item.label }}</small></div>
                    </div>
                </section>
                <section class="admin-card admin-machine" aria-labelledby="machine-heading">
                    <div class="admin-card__header"><div><p class="admin-eyebrow">Automat</p><h2 id="machine-heading">{{ atm.label }}</h2></div><span class="admin-status" :class="`admin-status--${atm.status}`"><i></i>{{ atm.status === 'active' ? 'Aktiv' : 'Wartung' }}</span></div>
                    <dl class="admin-machine__details"><div><dt>Kennung</dt><dd>{{ atm.code }}</dd></div><div><dt>Währung</dt><dd>{{ atm.currency }}</dd></div><div><dt>Gesamtbestand</dt><dd>{{ money(atm.totalMinor) }}</dd></div></dl>
                    <button type="button" class="admin-text-button" @click="openStatusDialog">Status bearbeiten <PhCaretRight :size="18" /></button>
                </section>
                <section class="admin-card admin-card--wide" aria-labelledby="inventory-heading">
                    <div class="admin-card__header"><div><p class="admin-eyebrow">Cash Management</p><h2 id="inventory-heading">Bargeldkassetten</h2></div><span v-if="page.props.errors.adjustment" class="admin-inline-error"><PhWarning :size="17" /> {{ page.props.errors.adjustment }}</span></div>
                    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Stückelung</th><th>Bestand</th><th>Wert</th><th>Status</th><th><span class="sr-only">Aktionen</span></th></tr></thead><tbody><tr v-for="item in atm.inventory" :key="item.id"><td><strong>{{ money(item.denominationMinor) }}</strong></td><td>{{ item.quantity }} Scheine</td><td>{{ money(item.denominationMinor * item.quantity) }}</td><td><span class="admin-status" :class="item.quantity < 10 ? 'admin-status--warning' : 'admin-status--active'"><i></i>{{ item.quantity < 10 ? 'Niedrig' : 'Ausreichend' }}</span></td><td class="admin-table__action"><button type="button" aria-label="Bestand bearbeiten" @click="openInventoryDrawer(item)"><PhPencilSimple :size="19" /></button></td></tr></tbody></table></div>
                </section>
            </div>
        </section>

        <section v-else-if="activeSection === 'accounts'" aria-labelledby="admin-accounts-heading">
            <div class="admin-page-heading"><div><p class="admin-eyebrow">Kundenverwaltung</p><h1 id="admin-accounts-heading" tabindex="-1">Konten & Karten</h1><p>Demo-Konten, Kartenstatus und aktuelle Salden im Überblick.</p></div><span class="admin-count-badge">{{ filteredAccounts.length }} Konten</span></div>
            <div class="admin-toolbar"><label class="admin-search"><PhMagnifyingGlass :size="20" /><span class="sr-only">Konten durchsuchen</span><input v-model="search" type="search" placeholder="Name, Konto oder Karte suchen …"></label></div>
            <section class="admin-card admin-card--table"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Kundin/Kunde</th><th>Konto</th><th>Karte</th><th>Saldo</th><th>Status</th></tr></thead><tbody>
                <tr v-for="account in filteredAccounts" :key="account.id"><td><div class="admin-person"><span>{{ account.customer.charAt(0) }}</span><strong>{{ account.customer }}</strong></div></td><td><strong>{{ account.reference }}</strong><small>{{ account.currency }} · Demo-Konto</small></td><td><div v-for="card in account.cards" :key="card.id" class="admin-card-reference"><PhCreditCard :size="18" /> {{ card.reference }}<small v-if="card.failedAttempts">{{ card.failedAttempts }} Fehlversuch(e)</small></div></td><td><strong>{{ money(account.balanceMinor, account.currency) }}</strong></td><td><span class="admin-status" :class="`admin-status--${account.status}`"><i></i>{{ account.status === 'active' ? 'Aktiv' : account.status }}</span></td></tr>
                <tr v-if="filteredAccounts.length === 0"><td colspan="5" class="admin-empty">Keine passenden Konten gefunden.</td></tr>
            </tbody></table></div></section>
        </section>

        <section v-else-if="activeSection === 'transactions'" aria-labelledby="admin-transactions-heading">
            <div class="admin-page-heading"><div><p class="admin-eyebrow">Buchungen</p><h1 id="admin-transactions-heading" tabindex="-1">Transaktionen</h1><p>Die letzten 50 Buchungen über alle Demo-Konten.</p></div><span class="admin-count-badge">{{ filteredTransactions.length }} Einträge</span></div>
            <div class="admin-toolbar"><label class="admin-search"><PhMagnifyingGlass :size="20" /><span class="sr-only">Transaktionen durchsuchen</span><input v-model="search" type="search" placeholder="Beleg, Konto, Name oder Zweck …"></label><label class="admin-filter"><PhFunnel :size="19" /><span class="sr-only">Typ filtern</span><select v-model="transactionType"><option value="all">Alle Typen</option><option value="deposit">Einzahlungen</option><option value="withdrawal">Auszahlungen</option><option value="opening">Eröffnungen</option></select></label></div>
            <section class="admin-card admin-card--table"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Datum</th><th>Typ</th><th>Konto</th><th>Verwendungszweck</th><th class="admin-table__number">Betrag</th><th class="admin-table__number">Saldo danach</th></tr></thead><tbody>
                <tr v-for="transaction in filteredTransactions" :key="transaction.id"><td>{{ date(transaction.createdAt) }}<small>{{ transaction.receiptReference }}</small></td><td><span class="admin-transaction-type" :class="`admin-transaction-type--${transaction.type}`"><component :is="transaction.type === 'withdrawal' ? PhArrowUp : PhArrowDown" :size="16" />{{ transactionLabel(transaction.type) }}</span></td><td><strong>{{ transaction.customer }}</strong><small>{{ transaction.accountReference }}</small></td><td>{{ transaction.purpose || '—' }}</td><td class="admin-table__number"><strong>{{ transaction.type === 'withdrawal' ? '−' : '+' }}{{ money(transaction.amountMinor, transaction.currency) }}</strong></td><td class="admin-table__number">{{ money(transaction.balanceAfterMinor, transaction.currency) }}</td></tr>
                <tr v-if="filteredTransactions.length === 0"><td colspan="6" class="admin-empty">Keine passenden Transaktionen gefunden.</td></tr>
            </tbody></table></div></section>
        </section>

        <section v-else aria-labelledby="admin-audit-heading">
            <div class="admin-page-heading"><div><p class="admin-eyebrow">Sicherheit</p><h1 id="admin-audit-heading" tabindex="-1">Audit-Protokoll</h1><p>Unveränderbare System- und Verwaltungsereignisse ohne sensible Zugangsdaten.</p></div><span class="admin-count-badge"><PhShieldCheck :size="18" /> {{ filteredAudit.length }} Ereignisse</span></div>
            <div class="admin-toolbar"><label class="admin-search"><PhMagnifyingGlass :size="20" /><span class="sr-only">Audit-Ereignisse durchsuchen</span><input v-model="search" type="search" placeholder="Ereignis, Ergebnis oder Fehlercode …"></label></div>
            <section class="admin-card admin-card--table"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Zeitpunkt</th><th>Ereignis</th><th>Ergebnis</th><th>Fehlercode</th><th>Kontext</th></tr></thead><tbody>
                <tr v-for="event in filteredAudit" :key="event.id"><td>{{ date(event.created_at) }}</td><td><strong>{{ eventLabel(event.event_type) }}</strong><small>{{ event.event_type }}</small></td><td><span class="admin-status" :class="event.outcome === 'success' ? 'admin-status--active' : 'admin-status--error'"><i></i>{{ event.outcome === 'success' ? 'Erfolgreich' : 'Abgewiesen' }}</span></td><td>{{ event.reason_code || '—' }}</td><td><code v-if="event.context">{{ JSON.stringify(event.context) }}</code><span v-else>—</span></td></tr>
                <tr v-if="filteredAudit.length === 0"><td colspan="5" class="admin-empty">Keine passenden Audit-Ereignisse gefunden.</td></tr>
            </tbody></table></div></section>
        </section>

        <dialog ref="statusDialog" class="admin-dialog" aria-labelledby="status-dialog-title" @click.self="statusDialog?.close()">
            <form method="dialog" class="admin-dialog__surface" @submit.prevent="updateStatus">
                <div class="admin-dialog__header"><span class="admin-metric__icon admin-metric__icon--violet"><PhBank :size="24" weight="duotone" /></span><button type="button" aria-label="Dialog schließen" @click="statusDialog?.close()"><PhX :size="21" /></button></div>
                <p class="admin-eyebrow">Automat verwalten</p><h2 id="status-dialog-title">Betriebsstatus ändern</h2><p>Der Wartungsmodus sperrt weitere Auszahlungen, bis der Automat wieder aktiviert wird.</p>
                <div class="admin-status-options"><label :class="{ 'admin-status-option--selected': statusForm.status === 'active' }"><input v-model="statusForm.status" type="radio" value="active"><PhCheckCircle :size="24" weight="duotone" /><span><strong>Aktiv</strong><small>Der Automat ist vollständig nutzbar.</small></span></label><label :class="{ 'admin-status-option--selected': statusForm.status === 'maintenance' }"><input v-model="statusForm.status" type="radio" value="maintenance"><PhWarning :size="24" weight="duotone" /><span><strong>Wartungsmodus</strong><small>Auszahlungen werden vorübergehend gesperrt.</small></span></label></div>
                <div class="admin-dialog__actions"><button type="button" class="admin-button admin-button--quiet" @click="statusDialog?.close()">Abbrechen</button><button type="submit" :disabled="statusForm.processing" class="admin-button admin-button--primary">{{ statusForm.processing ? 'Speichert …' : 'Status speichern' }}</button></div>
            </form>
        </dialog>

        <dialog ref="inventoryDrawer" class="admin-drawer" aria-labelledby="inventory-drawer-title" @click.self="inventoryDrawer?.close()">
            <form method="dialog" class="admin-drawer__surface" @submit.prevent="adjustInventory">
                <div class="admin-drawer__header"><div><p class="admin-eyebrow">Bestand bearbeiten</p><h2 id="inventory-drawer-title">{{ selectedInventory ? money(selectedInventory.denominationMinor) : '' }}-Kassette</h2></div><button type="button" aria-label="Seitenleiste schließen" @click="inventoryDrawer?.close()"><PhX :size="22" /></button></div>
                <div v-if="selectedInventory" class="admin-drawer__summary"><span><PhCoins :size="28" weight="duotone" /></span><div><small>Aktueller Bestand</small><strong>{{ selectedInventory.quantity }} Scheine</strong><p>{{ money(selectedInventory.quantity * selectedInventory.denominationMinor) }}</p></div></div>
                <label for="inventory-adjustment" class="admin-form-label">Bestandsänderung</label><input id="inventory-adjustment" v-model="inventoryForm.adjustment" type="number" min="-100" max="100" step="1" required placeholder="z. B. 10 oder -5" class="admin-form-input"><p class="admin-form-help">Positive Zahl für Befüllung, negative Zahl für Entnahme. Der neue Bestand darf nicht negativ sein.</p><p v-if="inventoryForm.errors.adjustment" role="alert" class="admin-field-error">{{ inventoryForm.errors.adjustment }}</p>
                <div class="admin-drawer__result"><span>Neuer Bestand</span><strong>{{ selectedInventory ? selectedInventory.quantity + Number(inventoryForm.adjustment || 0) : 0 }} Scheine</strong></div>
                <div class="admin-drawer__actions"><button type="button" class="admin-button admin-button--quiet" @click="inventoryDrawer?.close()">Abbrechen</button><button type="submit" :disabled="inventoryForm.processing" class="admin-button admin-button--primary">{{ inventoryForm.processing ? 'Bucht …' : 'Änderung buchen' }}</button></div>
            </form>
        </dialog>
    </AdminShell>
</template>
