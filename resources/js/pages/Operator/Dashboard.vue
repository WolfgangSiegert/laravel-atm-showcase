<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import { PhArrowDown, PhArrowUp, PhBank, PhCaretRight, PhCheckCircle, PhCoins, PhCreditCard, PhCurrencyEur, PhFunnel, PhGearSix, PhKey, PhLock, PhLockOpen, PhMagnifyingGlass, PhPencilSimple, PhPlus, PhReceipt, PhShieldCheck, PhTrendUp, PhUserPlus, PhUsers, PhWarning, PhX } from '@phosphor-icons/vue';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import { computed, nextTick, ref } from 'vue';
import AdminShell from '../../layouts/AdminShell.vue';

type Section = 'overview' | 'accounts' | 'transactions' | 'audit';
type Inventory = { id: number; denominationMinor: number; quantity: number };
type AccountCard = { id: number; reference: string; status: string; failedAttempts: number; lockedUntil: string | null; expiresAt: string | null };
type Account = { id: number; reference: string; customer: string; currency: string; balanceMinor: number; status: string; cards: AccountCard[] };
type Customer = { id: number; name: string };
type Transaction = { id: number; receiptReference: string; accountReference: string; customer: string; cardReference: string | null; type: string; purpose: string | null; amountMinor: number; balanceAfterMinor: number; currency: string; createdAt: string };
type AuditEvent = { id: number; event_type: string; outcome: string; reason_code: string | null; context: Record<string, string | number | boolean | null> | null; created_at: string };
type Activity = { date: string; label: string; count: number; amountMinor: number };

const props = defineProps<{
    operatorName: string;
    operatorRole: 'superadmin' | 'viewer';
    canManage: boolean;
    metrics: { accounts: number; activeCards: number; transactions: number; todayVolumeMinor: number };
    atm: { code: string; label: string; status: string; currency: string; totalMinor: number; inventory: Inventory[] };
    activity: Activity[];
    accounts: Account[];
    customers: Customer[];
    transactions: Transaction[];
    auditEvents: AuditEvent[];
}>();

const page = usePage<{ errors: Record<string, string> }>();
const activeSection = ref<Section>('overview');
const accountFilters = ref({
    global: { value: null as string | null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
});
const transactionFilters = ref({
    global: { value: null as string | null, matchMode: FilterMatchMode.CONTAINS },
    type: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
});
const auditFilters = ref({
    global: { value: null as string | null, matchMode: FilterMatchMode.CONTAINS },
    outcome: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
});
const statusDialog = ref<HTMLDialogElement | null>(null);
const inventoryDrawer = ref<HTMLDialogElement | null>(null);
const accountDialog = ref<HTMLDialogElement | null>(null);
const cardDialog = ref<HTMLDialogElement | null>(null);
const accountDrawer = ref<HTMLDialogElement | null>(null);
const selectedInventory = ref<Inventory | null>(null);
const selectedAccount = ref<Account | null>(null);
const cardAccountId = ref('');
const statusForm = useForm({ status: props.atm.status });
const inventoryForm = useForm({ adjustment: '' });
const logout = useForm({});
const accountForm = useForm({ customer_id: '', customer_name: '', account_reference: '', card_reference: '', pin: '' });
const cardForm = useForm({ card_reference: '', pin: '', expires_at: '' });

const money = (minor: number, currency = props.atm.currency) => new Intl.NumberFormat('de-DE', { style: 'currency', currency }).format(minor / 100);
const date = (value: string) => new Intl.DateTimeFormat('de-DE', { dateStyle: 'short', timeStyle: 'short', timeZone: 'Europe/Berlin' }).format(new Date(value));
const maxActivity = computed(() => Math.max(...props.activity.map(item => item.count), 1));
const inventoryTotal = computed(() => props.atm.inventory.reduce((sum, item) => sum + item.quantity, 0));
const lowInventoryCount = computed(() => props.atm.inventory.filter(item => item.quantity < 10).length);
const accountRows = computed(() => props.accounts.map(account => ({
    ...account,
    searchText: [account.customer, account.reference, ...account.cards.map(card => card.reference)].join(' '),
})));

function navigate(section: Section) {
    activeSection.value = section;
    accountFilters.value.global.value = null;
    transactionFilters.value.global.value = null;
    auditFilters.value.global.value = null;
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
function openAccountDialog() {
    accountForm.reset();
    accountForm.clearErrors();
    accountDialog.value?.showModal();
}
function createAccount() {
    accountForm.post('/admin/accounts', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => accountDialog.value?.close(),
        onFinish: () => accountForm.reset('pin'),
    });
}
function openAccountDrawer(account: Account) {
    selectedAccount.value = account;
    accountDrawer.value?.showModal();
}
function openCardDialog(account?: Account) {
    selectedAccount.value = account ?? null;
    cardAccountId.value = String(account?.id ?? props.accounts[0]?.id ?? '');
    cardForm.reset();
    cardForm.clearErrors();
    accountDrawer.value?.close();
    cardDialog.value?.showModal();
}
function createCard() {
    if (!cardAccountId.value) return;
    cardForm.post(`/admin/accounts/${cardAccountId.value}/cards`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => cardDialog.value?.close(),
        onFinish: () => cardForm.reset('pin'),
    });
}
function setAccountStatus(account: Account, status: 'active' | 'blocked') {
    router.patch(`/admin/accounts/${account.id}/status`, { status }, { preserveScroll: true, preserveState: true, onSuccess: () => accountDrawer.value?.close() });
}
function setCardStatus(card: AccountCard, status: 'active' | 'blocked') {
    router.patch(`/admin/cards/${card.id}/status`, { status }, { preserveScroll: true, preserveState: true, onSuccess: () => accountDrawer.value?.close() });
}
function resetCardLock(card: AccountCard) {
    router.post(`/admin/cards/${card.id}/reset-lock`, {}, { preserveScroll: true, preserveState: true, onSuccess: () => accountDrawer.value?.close() });
}
function cardStatus(card: AccountCard) {
    if (card.status === 'blocked') return 'Gesperrt';
    if (card.lockedUntil && new Date(card.lockedUntil).getTime() > Date.now()) return 'PIN-Zeitsperre';
    return 'Aktiv';
}
function eventLabel(type: string) {
    return ({ 'operator.login': 'Admin-Anmeldung', 'operator.guest_login': 'Showcase-Gastzugang', 'operator.logout': 'Admin-Abmeldung', 'atm.status_changed': 'Automatenstatus geändert', 'atm.inventory_adjusted': 'Bargeldbestand geändert', 'account.created': 'Konto angelegt', 'account.status_changed': 'Kontostatus geändert', 'card.created': 'Karte angelegt', 'card.status_changed': 'Kartenstatus geändert', 'card.lock_reset': 'Kartensperre zurückgesetzt', 'atm_session.login': 'Kartensitzung gestartet', 'atm_session.logout': 'Kartensitzung beendet', 'atm_session.invalidated': 'Kartensitzung ungültig', 'transaction.deposit': 'Einzahlung', 'transaction.withdrawal': 'Auszahlung' } as Record<string, string>)[type] ?? type;
}
function transactionLabel(type: string) {
    return ({ deposit: 'Einzahlung', withdrawal: 'Auszahlung', opening: 'Eröffnung' } as Record<string, string>)[type] ?? type;
}
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminShell :operator-name="operatorName" :operator-role="operatorRole" :active-section="activeSection" :logging-out="logout.processing" @navigate="navigate" @logout="logout.delete('/admin/session')">
        <div v-if="!canManage" class="admin-readonly-banner" role="status"><PhShieldCheck :size="21" weight="duotone" /><div><strong>Öffentliche Leseansicht</strong><span>Du kannst Dashboard, Tabellen, Filter und Audit ansehen. Änderungen sind für diesen Zugang gesperrt.</span></div></div>
        <section v-if="activeSection === 'overview'" aria-labelledby="admin-overview-heading">
            <div class="admin-page-heading">
                <div><p class="admin-eyebrow">Dashboard</p><h1 id="admin-overview-heading" tabindex="-1">Guten Tag.</h1><p>Hier ist der aktuelle Zustand deines Demo-Geldautomaten.</p></div>
                <button v-if="canManage" type="button" class="admin-button admin-button--primary" @click="openStatusDialog"><PhGearSix :size="19" /> Automat verwalten</button>
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
                    <button v-if="canManage" type="button" class="admin-text-button" @click="openStatusDialog">Status bearbeiten <PhCaretRight :size="18" /></button>
                </section>
                <section class="admin-card admin-card--wide" aria-labelledby="inventory-heading">
                    <div class="admin-card__header"><div><p class="admin-eyebrow">Cash Management</p><h2 id="inventory-heading">Bargeldkassetten</h2></div><span v-if="page.props.errors.adjustment" class="admin-inline-error"><PhWarning :size="17" /> {{ page.props.errors.adjustment }}</span></div>
                    <DataTable :value="atm.inventory" data-key="id" paginator :rows="5" :rows-per-page-options="[5, 10]" removable-sort scrollable scroll-height="18rem" class="admin-data-table" table-style="min-width: 46rem">
                        <Column field="denominationMinor" header="Stückelung" sortable><template #body="{ data }"><strong>{{ money(data.denominationMinor) }}</strong></template></Column>
                        <Column field="quantity" header="Bestand" sortable><template #body="{ data }">{{ data.quantity }} Scheine</template></Column>
                        <Column header="Wert" sortable sort-field="denominationMinor"><template #body="{ data }">{{ money(data.denominationMinor * data.quantity) }}</template></Column>
                        <Column field="quantity" header="Status" sortable><template #body="{ data }"><span class="admin-status" :class="data.quantity < 10 ? 'admin-status--warning' : 'admin-status--active'"><i></i>{{ data.quantity < 10 ? 'Niedrig' : 'Ausreichend' }}</span></template></Column>
                        <Column v-if="canManage" header="Aktionen" frozen align-frozen="right"><template #body="{ data }"><span class="admin-table__action"><button type="button" aria-label="Bestand bearbeiten" @click="openInventoryDrawer(data)"><PhPencilSimple :size="19" /></button></span></template></Column>
                        <template #empty>Keine Bargeldkassetten vorhanden.</template>
                    </DataTable>
                </section>
            </div>
        </section>

        <section v-else-if="activeSection === 'accounts'" aria-labelledby="admin-accounts-heading">
            <div class="admin-page-heading"><div><p class="admin-eyebrow">Kundenverwaltung</p><h1 id="admin-accounts-heading" tabindex="-1">Konten & Karten</h1><p>{{ canManage ? 'Demo-Konten anlegen, Karten ausgeben und Zugänge sperren.' : 'Demo-Konten, Karten und Zugangsstatus ansehen.' }}</p></div><div v-if="canManage" class="admin-heading-actions"><button type="button" class="admin-button admin-button--quiet" :disabled="accounts.length === 0" @click="openCardDialog()"><PhCreditCard :size="18" /> Neue Karte</button><button type="button" class="admin-button admin-button--primary" @click="openAccountDialog"><PhUserPlus :size="18" /> Neues Konto</button></div></div>
            <div class="admin-toolbar"><label class="admin-search"><PhMagnifyingGlass :size="20" /><span class="sr-only">Konten durchsuchen</span><input v-model="accountFilters.global.value" type="search" placeholder="Name, Konto oder Karte suchen …"></label><label class="admin-filter"><PhFunnel :size="19" /><span class="sr-only">Kontostatus filtern</span><select v-model="accountFilters.status.value"><option :value="null">Alle Status</option><option value="active">Aktiv</option><option value="blocked">Gesperrt</option></select></label><span class="admin-count-badge">{{ accounts.length }} Konten</span></div>
            <section class="admin-card admin-card--table">
                <DataTable v-model:filters="accountFilters" :value="accountRows" data-key="id" :global-filter-fields="['customer', 'reference', 'searchText']" filter-display="menu" paginator :rows="10" :rows-per-page-options="[5, 10, 25]" removable-sort sort-mode="multiple" scrollable scroll-height="min(54vh, 34rem)" state-storage="session" state-key="admin-accounts-v2" class="admin-data-table" table-style="min-width: 62rem">
                    <Column field="customer" header="Kundin/Kunde" sortable><template #body="{ data }"><div class="admin-person"><span>{{ data.customer.charAt(0) }}</span><strong>{{ data.customer }}</strong></div></template></Column>
                    <Column field="reference" header="Konto" sortable><template #body="{ data }"><strong>{{ data.reference }}</strong><small>{{ data.currency }} · Demo-Konto</small></template></Column>
                    <Column header="Karten"><template #body="{ data }"><div class="admin-card-stack"><span v-for="card in data.cards" :key="card.id" class="admin-card-reference"><PhCreditCard :size="18" /> {{ card.reference }}<small :class="{ 'admin-card-reference__warning': card.status !== 'active' || card.failedAttempts }">{{ cardStatus(card) }}<template v-if="card.failedAttempts"> · {{ card.failedAttempts }} Fehlversuch(e)</template></small></span></div></template></Column>
                    <Column field="balanceMinor" header="Saldo" sortable><template #body="{ data }"><strong>{{ money(data.balanceMinor, data.currency) }}</strong></template></Column>
                    <Column field="status" header="Status" sortable filter><template #body="{ data }"><span class="admin-status" :class="data.status === 'active' ? 'admin-status--active' : 'admin-status--blocked'"><i></i>{{ data.status === 'active' ? 'Aktiv' : 'Gesperrt' }}</span></template></Column>
                    <Column v-if="canManage" header="Aktionen" frozen align-frozen="right"><template #body="{ data }"><span class="admin-table__action"><button type="button" :aria-label="`${data.reference} verwalten`" @click="openAccountDrawer(data)"><PhPencilSimple :size="19" /></button></span></template></Column>
                    <template #empty>Keine passenden Konten gefunden.</template>
                </DataTable>
            </section>
        </section>

        <section v-else-if="activeSection === 'transactions'" aria-labelledby="admin-transactions-heading">
            <div class="admin-page-heading"><div><p class="admin-eyebrow">Buchungen</p><h1 id="admin-transactions-heading" tabindex="-1">Transaktionen</h1><p>Die letzten 50 Buchungen über alle Demo-Konten.</p></div><span class="admin-count-badge">{{ transactions.length }} Einträge</span></div>
            <div class="admin-toolbar"><label class="admin-search"><PhMagnifyingGlass :size="20" /><span class="sr-only">Transaktionen durchsuchen</span><input v-model="transactionFilters.global.value" type="search" placeholder="Beleg, Konto, Name oder Zweck …"></label><label class="admin-filter"><PhFunnel :size="19" /><span class="sr-only">Typ filtern</span><select v-model="transactionFilters.type.value"><option :value="null">Alle Typen</option><option value="deposit">Einzahlungen</option><option value="withdrawal">Auszahlungen</option><option value="opening">Eröffnungen</option></select></label></div>
            <section class="admin-card admin-card--table">
                <DataTable v-model:filters="transactionFilters" :value="transactions" data-key="id" :global-filter-fields="['receiptReference', 'accountReference', 'customer', 'cardReference', 'purpose']" filter-display="menu" paginator :rows="10" :rows-per-page-options="[5, 10, 25, 50]" removable-sort sort-mode="multiple" scrollable scroll-height="min(54vh, 34rem)" state-storage="session" state-key="admin-transactions-v2" class="admin-data-table" table-style="min-width: 68rem">
                    <Column field="createdAt" header="Datum" sortable><template #body="{ data }">{{ date(data.createdAt) }}<small>{{ data.receiptReference }}</small></template></Column>
                    <Column field="type" header="Typ" sortable filter><template #body="{ data }"><span class="admin-transaction-type" :class="`admin-transaction-type--${data.type}`"><component :is="data.type === 'withdrawal' ? PhArrowUp : PhArrowDown" :size="16" />{{ transactionLabel(data.type) }}</span></template></Column>
                    <Column field="customer" header="Konto" sortable><template #body="{ data }"><strong>{{ data.customer }}</strong><small>{{ data.accountReference }}</small></template></Column>
                    <Column field="purpose" header="Verwendungszweck" sortable><template #body="{ data }">{{ data.purpose || '—' }}</template></Column>
                    <Column field="amountMinor" header="Betrag" sortable><template #body="{ data }"><strong>{{ data.type === 'withdrawal' ? '−' : '+' }}{{ money(data.amountMinor, data.currency) }}</strong></template></Column>
                    <Column field="balanceAfterMinor" header="Saldo danach" sortable><template #body="{ data }">{{ money(data.balanceAfterMinor, data.currency) }}</template></Column>
                    <template #empty>Keine passenden Transaktionen gefunden.</template>
                </DataTable>
            </section>
        </section>

        <section v-else aria-labelledby="admin-audit-heading">
            <div class="admin-page-heading"><div><p class="admin-eyebrow">Sicherheit</p><h1 id="admin-audit-heading" tabindex="-1">Audit-Protokoll</h1><p>Unveränderbare System- und Verwaltungsereignisse ohne sensible Zugangsdaten.</p></div><span class="admin-count-badge"><PhShieldCheck :size="18" /> {{ auditEvents.length }} Ereignisse</span></div>
            <div class="admin-toolbar"><label class="admin-search"><PhMagnifyingGlass :size="20" /><span class="sr-only">Audit-Ereignisse durchsuchen</span><input v-model="auditFilters.global.value" type="search" placeholder="Ereignis, Ergebnis oder Fehlercode …"></label><label class="admin-filter"><PhFunnel :size="19" /><span class="sr-only">Ergebnis filtern</span><select v-model="auditFilters.outcome.value"><option :value="null">Alle Ergebnisse</option><option value="success">Erfolgreich</option><option value="rejected">Abgewiesen</option></select></label></div>
            <section class="admin-card admin-card--table">
                <DataTable v-model:filters="auditFilters" :value="auditEvents" data-key="id" :global-filter-fields="['event_type', 'outcome', 'reason_code']" filter-display="menu" paginator :rows="10" :rows-per-page-options="[5, 10, 25, 50]" removable-sort sort-mode="multiple" scrollable scroll-height="min(54vh, 34rem)" state-storage="session" state-key="admin-audit-v2" class="admin-data-table" table-style="min-width: 62rem">
                    <Column field="created_at" header="Zeitpunkt" sortable><template #body="{ data }">{{ date(data.created_at) }}</template></Column>
                    <Column field="event_type" header="Ereignis" sortable><template #body="{ data }"><strong>{{ eventLabel(data.event_type) }}</strong><small>{{ data.event_type }}</small></template></Column>
                    <Column field="outcome" header="Ergebnis" sortable filter><template #body="{ data }"><span class="admin-status" :class="data.outcome === 'success' ? 'admin-status--active' : 'admin-status--error'"><i></i>{{ data.outcome === 'success' ? 'Erfolgreich' : 'Abgewiesen' }}</span></template></Column>
                    <Column field="reason_code" header="Fehlercode" sortable><template #body="{ data }">{{ data.reason_code || '—' }}</template></Column>
                    <Column header="Kontext"><template #body="{ data }"><code v-if="data.context">{{ JSON.stringify(data.context) }}</code><span v-else>—</span></template></Column>
                    <template #empty>Keine passenden Audit-Ereignisse gefunden.</template>
                </DataTable>
            </section>
        </section>

        <dialog ref="accountDialog" class="admin-dialog admin-dialog--wide" aria-labelledby="account-dialog-title" @click.self="accountDialog?.close()">
            <form method="dialog" class="admin-dialog__surface" @submit.prevent="createAccount">
                <div class="admin-dialog__header"><span class="admin-metric__icon admin-metric__icon--blue"><PhUserPlus :size="24" weight="duotone" /></span><button type="button" aria-label="Dialog schließen" @click="accountDialog?.close()"><PhX :size="21" /></button></div>
                <p class="admin-eyebrow">Kundenverwaltung</p><h2 id="account-dialog-title">Neues Konto anlegen</h2><p>Ein neues Demo-Konto startet mit 0,00 € und erhält direkt seine erste Karte.</p>
                <div class="admin-form-grid">
                    <label class="admin-form-field admin-form-field--wide"><span>Bestehende Person</span><select v-model="accountForm.customer_id" class="admin-form-input"><option value="">Neue Person anlegen</option><option v-for="customer in customers" :key="customer.id" :value="String(customer.id)">{{ customer.name }}</option></select></label>
                    <label v-if="!accountForm.customer_id" class="admin-form-field admin-form-field--wide"><span>Name der neuen Person</span><input v-model="accountForm.customer_name" type="text" maxlength="80" autocomplete="off" placeholder="z. B. Robin Beispiel" class="admin-form-input"><small v-if="accountForm.errors.customer_name" class="admin-field-error">{{ accountForm.errors.customer_name }}</small></label>
                    <label class="admin-form-field"><span>Kontoreferenz</span><input v-model="accountForm.account_reference" type="text" maxlength="32" required autocomplete="off" placeholder="KONTO-003" class="admin-form-input"><small v-if="accountForm.errors.account_reference" class="admin-field-error">{{ accountForm.errors.account_reference }}</small></label>
                    <label class="admin-form-field"><span>Erste Kartenreferenz</span><input v-model="accountForm.card_reference" type="text" maxlength="32" required autocomplete="off" placeholder="KARTE-003" class="admin-form-input"><small v-if="accountForm.errors.card_reference" class="admin-field-error">{{ accountForm.errors.card_reference }}</small></label>
                    <label class="admin-form-field admin-form-field--wide"><span>Vierstellige PIN</span><input v-model="accountForm.pin" type="password" inputmode="numeric" maxlength="4" required autocomplete="new-password" placeholder="••••" class="admin-form-input admin-form-input--pin"><small>Die PIN wird gehasht gespeichert und anschließend nicht mehr angezeigt.</small><small v-if="accountForm.errors.pin" class="admin-field-error">{{ accountForm.errors.pin }}</small></label>
                </div>
                <div class="admin-dialog__actions"><button type="button" class="admin-button admin-button--quiet" @click="accountDialog?.close()">Abbrechen</button><button type="submit" :disabled="accountForm.processing" class="admin-button admin-button--primary">{{ accountForm.processing ? 'Legt an …' : 'Konto anlegen' }}</button></div>
            </form>
        </dialog>

        <dialog ref="cardDialog" class="admin-dialog" aria-labelledby="card-dialog-title" @click.self="cardDialog?.close()">
            <form method="dialog" class="admin-dialog__surface" @submit.prevent="createCard">
                <div class="admin-dialog__header"><span class="admin-metric__icon admin-metric__icon--cyan"><PhCreditCard :size="24" weight="duotone" /></span><button type="button" aria-label="Dialog schließen" @click="cardDialog?.close()"><PhX :size="21" /></button></div>
                <p class="admin-eyebrow">Kartenausgabe</p><h2 id="card-dialog-title">Neue Karte anlegen</h2><p>Die Karte wird aktiv ausgegeben. Eine gesperrte Kontoverbindung verhindert trotzdem die Anmeldung.</p>
                <div class="admin-form-grid admin-form-grid--single">
                    <label class="admin-form-field"><span>Konto</span><select v-model="cardAccountId" required class="admin-form-input"><option v-for="account in accounts" :key="account.id" :value="String(account.id)">{{ account.reference }} · {{ account.customer }}</option></select></label>
                    <label class="admin-form-field"><span>Kartenreferenz</span><input v-model="cardForm.card_reference" type="text" maxlength="32" required autocomplete="off" placeholder="KARTE-004" class="admin-form-input"><small v-if="cardForm.errors.card_reference" class="admin-field-error">{{ cardForm.errors.card_reference }}</small></label>
                    <label class="admin-form-field"><span>Vierstellige PIN</span><input v-model="cardForm.pin" type="password" inputmode="numeric" maxlength="4" required autocomplete="new-password" placeholder="••••" class="admin-form-input admin-form-input--pin"><small v-if="cardForm.errors.pin" class="admin-field-error">{{ cardForm.errors.pin }}</small></label>
                    <label class="admin-form-field"><span>Ablaufdatum (optional)</span><input v-model="cardForm.expires_at" type="date" class="admin-form-input"><small v-if="cardForm.errors.expires_at" class="admin-field-error">{{ cardForm.errors.expires_at }}</small></label>
                </div>
                <div class="admin-dialog__actions"><button type="button" class="admin-button admin-button--quiet" @click="cardDialog?.close()">Abbrechen</button><button type="submit" :disabled="cardForm.processing" class="admin-button admin-button--primary">{{ cardForm.processing ? 'Legt an …' : 'Karte anlegen' }}</button></div>
            </form>
        </dialog>

        <dialog ref="accountDrawer" class="admin-drawer" aria-labelledby="account-drawer-title" @click.self="accountDrawer?.close()">
            <div v-if="selectedAccount" class="admin-drawer__surface">
                <div class="admin-drawer__header"><div><p class="admin-eyebrow">Konto verwalten</p><h2 id="account-drawer-title">{{ selectedAccount.reference }}</h2><p>{{ selectedAccount.customer }}</p></div><button type="button" aria-label="Seitenleiste schließen" @click="accountDrawer?.close()"><PhX :size="22" /></button></div>
                <div class="admin-account-summary"><div><span>Saldo</span><strong>{{ money(selectedAccount.balanceMinor, selectedAccount.currency) }}</strong></div><div><span>Status</span><strong>{{ selectedAccount.status === 'active' ? 'Aktiv' : 'Gesperrt' }}</strong></div></div>
                <section class="admin-management-section"><div class="admin-management-section__heading"><div><p class="admin-eyebrow">Kontozugang</p><h3>Status verwalten</h3></div><span class="admin-status" :class="selectedAccount.status === 'active' ? 'admin-status--active' : 'admin-status--blocked'"><i></i>{{ selectedAccount.status === 'active' ? 'Aktiv' : 'Gesperrt' }}</span></div><p>Eine Kontosperre verhindert die Anmeldung aller zugehörigen Karten und beendet laufende Sitzungen.</p><button v-if="selectedAccount.status === 'active'" type="button" class="admin-button admin-button--danger" @click="setAccountStatus(selectedAccount, 'blocked')"><PhLock :size="18" /> Konto sperren</button><button v-else type="button" class="admin-button admin-button--success" @click="setAccountStatus(selectedAccount, 'active')"><PhLockOpen :size="18" /> Konto reaktivieren</button></section>
                <section class="admin-management-section"><div class="admin-management-section__heading"><div><p class="admin-eyebrow">Karten</p><h3>{{ selectedAccount.cards.length }} Karte(n)</h3></div><button type="button" class="admin-text-button admin-text-button--compact" @click="openCardDialog(selectedAccount)"><PhPlus :size="17" /> Karte hinzufügen</button></div>
                    <article v-for="card in selectedAccount.cards" :key="card.id" class="admin-managed-card"><div class="admin-managed-card__top"><span><PhCreditCard :size="21" /></span><div><strong>{{ card.reference }}</strong><small>{{ card.expiresAt ? `Gültig bis ${card.expiresAt}` : 'Ohne Ablaufdatum' }}</small></div><span class="admin-status" :class="card.status === 'active' ? 'admin-status--active' : 'admin-status--blocked'"><i></i>{{ cardStatus(card) }}</span></div><p v-if="card.failedAttempts || card.lockedUntil" class="admin-managed-card__notice"><PhWarning :size="17" /> {{ card.failedAttempts }} PIN-Fehlversuch(e)<template v-if="card.lockedUntil"> · gesperrt bis {{ date(card.lockedUntil) }}</template></p><div class="admin-managed-card__actions"><button v-if="card.status === 'active'" type="button" class="admin-button admin-button--quiet" @click="setCardStatus(card, 'blocked')"><PhLock :size="16" /> Sperren</button><button v-else type="button" class="admin-button admin-button--quiet" @click="setCardStatus(card, 'active')"><PhLockOpen :size="16" /> Reaktivieren</button><button v-if="card.failedAttempts || card.lockedUntil" type="button" class="admin-button admin-button--quiet" @click="resetCardLock(card)"><PhKey :size="16" /> PIN-Sperre zurücksetzen</button></div></article>
                </section>
            </div>
        </dialog>

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
