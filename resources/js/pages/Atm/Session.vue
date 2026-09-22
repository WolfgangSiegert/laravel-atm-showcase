<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { PhArrowLeft, PhClockCounterClockwise, PhHandCoins, PhMoney, PhSignOut, PhWallet } from '@phosphor-icons/vue';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import AtmNumericPad from '../../components/AtmNumericPad.vue';
import { useTheme } from '../../composables/useTheme';
import AppShell from '../../layouts/AppShell.vue';

type Booking = { id: number; receipt_reference: string | null; type: 'deposit' | 'opening' | 'withdrawal'; purpose: string | null; amount_minor: number; balance_after_minor: number; cash_breakdown: Record<string, number> | null; currency: string; created_at: string };
type HistoryFilters = { type: string; sort: 'date' | 'amount'; direction: 'asc' | 'desc' };
const props = defineProps<{ customerName: string; cardReference: string; accountReference: string; expiresAt: string; idleSeconds: number; balanceMinor: number; currency: string; depositKey: string; withdrawalKey: string; maxDepositMinor: number; minWithdrawalMinor: number; maxWithdrawalMinor: number; denominationsMinor: number[]; atmAvailable: boolean; historyFilters: HistoryFilters; transactions: { data: Booking[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null } }>();
const deposit = useForm({ amount: '', purpose: '', idempotency_key: props.depositKey });
const withdrawal = useForm({ withdrawal_amount: '', purpose: '', idempotency_key: props.withdrawalKey });
const { theme } = useTheme();
type AtmPanel = 'menu' | 'balance' | 'withdrawal' | 'deposit' | 'history';
const activePanel = ref<AtmPanel>('menu');
const interactiveSkin = computed(() => theme.value === 'classic' || theme.value === 'touch');
const history = reactive<HistoryFilters>({ ...props.historyFilters });
const money = (minor: number) => new Intl.NumberFormat('de-DE', { style: 'currency', currency: props.currency }).format(minor / 100);
const date = (value: string) => new Intl.DateTimeFormat('de-DE', { dateStyle: 'short', timeStyle: 'short', timeZone: 'Europe/Berlin' }).format(new Date(value));
function submitDeposit() {
    deposit.post('/atm/deposits', {
        preserveScroll: 'errors',
        onSuccess: () => {
            deposit.reset('amount', 'purpose');
            deposit.idempotency_key = props.depositKey;
        },
    });
}
function submitWithdrawal() {
    withdrawal.post('/atm/withdrawals', {
        preserveScroll: 'errors',
        onSuccess: () => {
            withdrawal.reset('withdrawal_amount', 'purpose');
            withdrawal.idempotency_key = props.withdrawalKey;
        },
    });
}
function bookingLabel(booking: Booking) {
    if (booking.type === 'opening') return 'Anfangsbestand';
    if (booking.type === 'withdrawal') return 'Demo-Auszahlung';
    return 'Demo-Einzahlung';
}
function breakdownLabel(breakdown: Record<string, number> | null) {
    if (!breakdown) return '';
    return Object.entries(breakdown)
        .sort(([left], [right]) => Number(right) - Number(left))
        .map(([denomination, quantity]) => `${quantity} × ${money(Number(denomination))}`)
        .join(', ');
}
function applyHistoryFilters() {
    router.get('/atm/session', history, { preserveState: true, preserveScroll: true, replace: true });
}
const logout = useForm({});
function showPanel(panel: AtmPanel) {
    activePanel.value = panel;
    requestAnimationFrame(() => document.querySelector<HTMLElement>('[data-active-atm-panel="true"]')?.focus());
}
let timer: number | undefined;
function checkExpiry() {
    if (Date.now() >= Date.parse(props.expiresAt)) {
        router.visit('/atm/session', { replace: true });
    }
}
function scheduleExpiry() {
    window.clearTimeout(timer);
    timer = window.setTimeout(checkExpiry, Math.max(0, Date.parse(props.expiresAt) - Date.now()) + 100);
}
watch(() => props.expiresAt, scheduleExpiry);
onMounted(() => {
    scheduleExpiry();
    document.addEventListener('visibilitychange', checkExpiry);
});
onUnmounted(() => {
    window.clearTimeout(timer);
    document.removeEventListener('visibilitychange', checkExpiry);
});
</script>

<template>
    <Head title="Sitzung aktiv" />
    <AppShell>
        <section class="atm-session mx-auto max-w-xl" :class="{ 'atm-session--menu': interactiveSkin }" aria-labelledby="session-heading">
            <div class="atm-session__intro">
                <p class="mb-5 text-xs font-semibold uppercase tracking-widest text-green-800">02 / Sitzung aktiv</p>
                <h1 id="session-heading" class="text-4xl font-semibold tracking-tight">Willkommen, {{ customerName }}.</h1>
                <p class="mt-5 leading-relaxed text-stone-600">Dein Demo-Konto im Überblick. Ein- und Auszahlungen bleiben vollständig in dieser Simulation.</p>
            </div>
            <div class="atm-balance mt-8 rounded-xl bg-green-900 p-6 text-white"><p class="text-sm text-green-100">Verfügbares Demo-Guthaben</p><p class="mt-2 text-4xl font-semibold tracking-tight" aria-live="polite">{{ money(balanceMinor) }}</p></div>
            <dl class="atm-account my-8 space-y-4 rounded-xl border border-stone-300 p-6">
                <div><dt class="text-sm text-stone-600">Demo-Karte</dt><dd class="mt-1 font-semibold">{{ cardReference }}</dd></div>
                <div><dt class="text-sm text-stone-600">Zugeordnetes Konto</dt><dd class="mt-1 font-semibold">{{ accountReference }}</dd></div>
            </dl>

            <section v-if="interactiveSkin && activePanel === 'menu'" class="atm-mode-menu" aria-labelledby="atm-mode-menu-heading">
                <div class="atm-mode-menu__heading">
                    <p>Bitte wählen</p>
                    <h2 id="atm-mode-menu-heading">Was möchtest du tun?</h2>
                </div>
                <div class="atm-mode-menu__grid">
                    <button type="button" class="atm-menu-tile" @click="showPanel('withdrawal')"><PhMoney :size="34" weight="duotone" aria-hidden="true" /><span>Geld abheben</span><small>10 € bis 1.000 €</small></button>
                    <button type="button" class="atm-menu-tile" @click="showPanel('deposit')"><PhHandCoins :size="34" weight="duotone" aria-hidden="true" /><span>Geld einzahlen</span><small>Demo-Guthaben buchen</small></button>
                    <button type="button" class="atm-menu-tile" @click="showPanel('balance')"><PhWallet :size="34" weight="duotone" aria-hidden="true" /><span>Kontostand</span><small>Aktuelles Guthaben</small></button>
                    <button type="button" class="atm-menu-tile" @click="showPanel('history')"><PhClockCounterClockwise :size="34" weight="duotone" aria-hidden="true" /><span>Umsätze</span><small>Filtern und sortieren</small></button>
                    <button type="button" :disabled="logout.processing" class="atm-menu-tile atm-menu-tile--exit" @click="logout.delete('/atm/session')"><PhSignOut :size="34" weight="duotone" aria-hidden="true" /><span>Karte zurück</span><small>Sitzung sicher beenden</small></button>
                </div>
            </section>

            <section v-if="interactiveSkin && activePanel === 'balance'" tabindex="-1" data-active-atm-panel="true" class="atm-panel atm-panel--balance" aria-labelledby="balance-heading">
                <button type="button" class="atm-back" @click="showPanel('menu')"><PhArrowLeft :size="20" weight="bold" aria-hidden="true" /> Hauptmenü</button>
                <h2 id="balance-heading">Dein Kontostand</h2>
                <p class="atm-panel__amount">{{ money(balanceMinor) }}</p>
                <p>Verfügbares Demo-Guthaben auf {{ accountReference }}.</p>
            </section>

            <section v-show="!interactiveSkin || activePanel === 'withdrawal'" :tabindex="interactiveSkin ? -1 : undefined" :data-active-atm-panel="interactiveSkin && activePanel === 'withdrawal' ? 'true' : undefined" aria-labelledby="withdrawal-heading" class="atm-panel mb-10 border-t border-stone-300 pt-6">
                <button v-if="interactiveSkin" type="button" class="atm-back" @click="showPanel('menu')"><PhArrowLeft :size="20" weight="bold" aria-hidden="true" /> Hauptmenü</button>
                <h2 id="withdrawal-heading" class="text-xl font-semibold">Auszahlung simulieren</h2>
                <p class="mt-2 text-sm text-stone-600">Von {{ money(minWithdrawalMinor) }} bis {{ money(maxWithdrawalMinor) }} in ganzen Euro. Verfügbare Scheine: {{ denominationsMinor.map(money).join(', ') || 'keine' }}.</p>
                <form class="mt-5 space-y-3" @submit.prevent="submitWithdrawal">
                    <label for="withdrawal-amount" class="block font-semibold">Auszahlungsbetrag in Euro</label>
                    <input id="withdrawal-amount" v-model="withdrawal.withdrawal_amount" type="text" inputmode="numeric" :readonly="interactiveSkin" placeholder="50" maxlength="4" required :disabled="withdrawal.processing || !atmAvailable" :aria-invalid="!!withdrawal.errors.withdrawal_amount" aria-describedby="withdrawal-error" class="w-full rounded-lg border border-stone-400 bg-white p-3">
                    <AtmNumericPad v-if="interactiveSkin" v-model="withdrawal.withdrawal_amount" :max-length="4" :disabled="withdrawal.processing || !atmAvailable" label="Auszahlungsbetrag eingeben" />
                    <label for="withdrawal-purpose" class="block font-semibold">Verwendungszweck <span class="font-normal text-stone-600">(optional)</span></label>
                    <input id="withdrawal-purpose" v-model="withdrawal.purpose" type="text" maxlength="140" placeholder="Zum Beispiel: Reisekasse" :disabled="withdrawal.processing || !atmAvailable" :aria-invalid="!!withdrawal.errors.purpose" aria-describedby="withdrawal-purpose-error" class="w-full rounded-lg border border-stone-400 bg-white p-3">
                    <p v-if="withdrawal.errors.purpose" id="withdrawal-purpose-error" role="alert" class="text-sm text-red-800">{{ withdrawal.errors.purpose }}</p>
                    <p v-if="withdrawal.errors.withdrawal_amount || withdrawal.errors.idempotency_key" id="withdrawal-error" role="alert" class="text-sm text-red-800">{{ withdrawal.errors.withdrawal_amount || withdrawal.errors.idempotency_key }}</p>
                    <p v-else-if="!atmAvailable" id="withdrawal-error" role="status" class="text-sm text-red-800">Der Demo-Automat ist derzeit nicht verfügbar.</p>
                    <button type="submit" :disabled="withdrawal.processing || !atmAvailable" class="w-full rounded-lg bg-green-900 px-5 py-4 font-semibold text-white hover:bg-green-800 disabled:opacity-60">{{ withdrawal.processing ? 'Wird ausgezahlt …' : 'Demo-Auszahlung buchen' }}</button>
                </form>
            </section>
            <section v-show="!interactiveSkin || activePanel === 'deposit'" :tabindex="interactiveSkin ? -1 : undefined" :data-active-atm-panel="interactiveSkin && activePanel === 'deposit' ? 'true' : undefined" aria-labelledby="deposit-heading" class="atm-panel mb-10 border-t border-stone-300 pt-6">
                <button v-if="interactiveSkin" type="button" class="atm-back" @click="showPanel('menu')"><PhArrowLeft :size="20" weight="bold" aria-hidden="true" /> Hauptmenü</button>
                <h2 id="deposit-heading" class="text-xl font-semibold">Einzahlung simulieren</h2>
                <p class="mt-2 text-sm text-stone-600">Von 0,01 € bis {{ money(maxDepositMinor) }} je Buchung. Der Betrag wird deinem Demo-Konto gutgeschrieben.</p>
                <form class="mt-5 space-y-3" @submit.prevent="submitDeposit">
                    <label for="amount" class="block font-semibold">Betrag in Euro</label>
                    <input id="amount" v-model="deposit.amount" type="text" inputmode="decimal" :readonly="interactiveSkin" placeholder="25,50" maxlength="8" required :disabled="deposit.processing" :aria-invalid="!!deposit.errors.amount" aria-describedby="deposit-error" class="w-full rounded-lg border border-stone-400 bg-white p-3">
                    <AtmNumericPad v-if="interactiveSkin" v-model="deposit.amount" allow-decimal :max-length="8" :disabled="deposit.processing" label="Einzahlungsbetrag eingeben" />
                    <label for="deposit-purpose" class="block font-semibold">Verwendungszweck <span class="font-normal text-stone-600">(optional)</span></label>
                    <input id="deposit-purpose" v-model="deposit.purpose" type="text" maxlength="140" placeholder="Zum Beispiel: Demo-Ersparnis" :disabled="deposit.processing" :aria-invalid="!!deposit.errors.purpose" aria-describedby="deposit-purpose-error" class="w-full rounded-lg border border-stone-400 bg-white p-3">
                    <p v-if="deposit.errors.purpose" id="deposit-purpose-error" role="alert" class="text-sm text-red-800">{{ deposit.errors.purpose }}</p>
                    <p v-if="deposit.errors.amount || deposit.errors.idempotency_key" id="deposit-error" role="alert" class="text-sm text-red-800">{{ deposit.errors.amount || deposit.errors.idempotency_key }}</p>
                    <button type="submit" :disabled="deposit.processing" class="w-full rounded-lg bg-green-900 px-5 py-4 font-semibold text-white hover:bg-green-800 disabled:opacity-60">{{ deposit.processing ? 'Wird gebucht …' : 'Demo-Einzahlung buchen' }}</button>
                </form>
            </section>
            <section v-show="!interactiveSkin || activePanel === 'history'" :tabindex="interactiveSkin ? -1 : undefined" :data-active-atm-panel="interactiveSkin && activePanel === 'history' ? 'true' : undefined" aria-labelledby="history-heading" class="atm-panel mb-10 border-t border-stone-300 pt-6">
                <button v-if="interactiveSkin" type="button" class="atm-back" @click="showPanel('menu')"><PhArrowLeft :size="20" weight="bold" aria-hidden="true" /> Hauptmenü</button>
                <h2 id="history-heading" class="text-xl font-semibold">Buchungshistorie</h2>
                <form class="mt-5 grid gap-4 rounded-xl border border-stone-300 p-4 sm:grid-cols-3" aria-label="Buchungshistorie filtern und sortieren" @submit.prevent="applyHistoryFilters">
                    <label class="text-sm font-semibold">Typ
                        <select v-model="history.type" class="mt-2 w-full rounded-lg border border-stone-400 bg-white p-3 font-normal">
                            <option value="">Alle Buchungen</option>
                            <option value="deposit">Einzahlungen</option>
                            <option value="withdrawal">Auszahlungen</option>
                            <option value="opening">Anfangsbestände</option>
                        </select>
                    </label>
                    <label class="text-sm font-semibold">Sortieren nach
                        <select v-model="history.sort" class="mt-2 w-full rounded-lg border border-stone-400 bg-white p-3 font-normal">
                            <option value="date">Datum</option>
                            <option value="amount">Betrag</option>
                        </select>
                    </label>
                    <label class="text-sm font-semibold">Reihenfolge
                        <select v-model="history.direction" class="mt-2 w-full rounded-lg border border-stone-400 bg-white p-3 font-normal">
                            <option value="desc">Absteigend</option>
                            <option value="asc">Aufsteigend</option>
                        </select>
                    </label>
                    <button type="submit" class="rounded-lg bg-stone-800 px-4 py-3 text-sm font-semibold text-white hover:bg-stone-700 sm:col-span-3">Ansicht anwenden</button>
                </form>
                <p v-if="transactions.data.length === 0" class="mt-4 text-stone-600">{{ transactions.total === 0 ? 'Noch keine Buchungen. Dein Demo-Konto startet bei 0,00 €.' : 'Keine Buchungen auf dieser Seite.' }}</p>
                <ol v-else class="mt-4 divide-y divide-stone-300">
                    <li v-for="booking in transactions.data" :key="booking.id" class="py-4">
                        <div class="flex flex-wrap justify-between gap-2 font-semibold"><span>{{ bookingLabel(booking) }}</span><span>{{ booking.type === 'withdrawal' ? '−' : '+' }}{{ money(booking.amount_minor) }}</span></div>
                        <p class="mt-1 text-sm text-stone-600">{{ date(booking.created_at) }} · Kontostand danach: {{ money(booking.balance_after_minor) }}</p>
                        <p v-if="booking.purpose" class="mt-1 text-sm text-stone-700">Verwendungszweck: {{ booking.purpose }}</p>
                        <p v-if="booking.cash_breakdown" class="mt-1 text-sm text-stone-600">Scheine: {{ breakdownLabel(booking.cash_breakdown) }}</p>
                        <Link v-if="booking.receipt_reference" :href="`/atm/receipts/${booking.receipt_reference}`" class="mt-2 inline-block py-2 text-sm font-semibold text-green-900">Beleg ansehen →</Link>
                    </li>
                </ol>
                <nav v-if="transactions.last_page > 1" aria-label="Buchungsseiten" class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
                    <Link v-if="transactions.prev_page_url" :href="transactions.prev_page_url" class="py-3 font-semibold text-green-900">← Neuere</Link>
                    <span>Seite {{ transactions.current_page }} von {{ transactions.last_page }}</span>
                    <Link v-if="transactions.next_page_url" :href="transactions.next_page_url" class="py-3 font-semibold text-green-900">Ältere →</Link>
                </nav>
            </section>
            <p class="atm-session__timeout mb-6 text-sm text-stone-600">Die Sitzung endet nach {{ Math.ceil(idleSeconds / 60) }} Minuten ohne Serveranfrage automatisch.</p>
            <button v-if="!interactiveSkin" type="button" :disabled="logout.processing" class="w-full rounded-lg bg-green-900 px-5 py-4 font-semibold text-white hover:bg-green-800 disabled:opacity-60" @click="logout.delete('/atm/session')">{{ logout.processing ? 'Wird beendet …' : 'Sitzung beenden & Karte zurückgeben' }}</button>
        </section>
    </AppShell>
</template>
