<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, watch } from 'vue';
import AppShell from '../../layouts/AppShell.vue';

type Booking = { id: number; receipt_reference: string | null; type: 'deposit' | 'opening' | 'withdrawal'; amount_minor: number; balance_after_minor: number; cash_breakdown: Record<string, number> | null; currency: string; created_at: string };
const props = defineProps<{ customerName: string; cardReference: string; accountReference: string; expiresAt: string; idleSeconds: number; balanceMinor: number; currency: string; depositKey: string; withdrawalKey: string; maxDepositMinor: number; minWithdrawalMinor: number; maxWithdrawalMinor: number; denominationsMinor: number[]; atmAvailable: boolean; transactions: { data: Booking[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null } }>();
const deposit = useForm({ amount: '', idempotency_key: props.depositKey });
const withdrawal = useForm({ withdrawal_amount: '', idempotency_key: props.withdrawalKey });
const money = (minor: number) => new Intl.NumberFormat('de-DE', { style: 'currency', currency: props.currency }).format(minor / 100);
const date = (value: string) => new Intl.DateTimeFormat('de-DE', { dateStyle: 'short', timeStyle: 'short', timeZone: 'Europe/Berlin' }).format(new Date(value));
function submitDeposit() {
    deposit.post('/atm/deposits', {
        preserveScroll: 'errors',
        onSuccess: () => {
            deposit.reset('amount');
            deposit.idempotency_key = props.depositKey;
        },
    });
}
function submitWithdrawal() {
    withdrawal.post('/atm/withdrawals', {
        preserveScroll: 'errors',
        onSuccess: () => {
            withdrawal.reset('withdrawal_amount');
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
const logout = useForm({});
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
        <section class="mx-auto max-w-xl" aria-labelledby="session-heading">
            <p class="mb-5 text-xs font-semibold uppercase tracking-widest text-green-800">02 / Sitzung aktiv</p>
            <h1 id="session-heading" class="text-4xl font-semibold tracking-tight">Willkommen, {{ customerName }}.</h1>
            <p class="mt-5 leading-relaxed text-stone-600">Dein Demo-Konto im Überblick. Ein- und Auszahlungen bleiben vollständig in dieser Simulation.</p>
            <div class="mt-8 rounded-xl bg-green-900 p-6 text-white"><p class="text-sm text-green-100">Verfügbares Demo-Guthaben</p><p class="mt-2 text-4xl font-semibold tracking-tight" aria-live="polite">{{ money(balanceMinor) }}</p></div>
            <dl class="my-8 space-y-4 rounded-xl border border-stone-300 p-6">
                <div><dt class="text-sm text-stone-600">Demo-Karte</dt><dd class="mt-1 font-semibold">{{ cardReference }}</dd></div>
                <div><dt class="text-sm text-stone-600">Zugeordnetes Konto</dt><dd class="mt-1 font-semibold">{{ accountReference }}</dd></div>
            </dl>
            <section aria-labelledby="withdrawal-heading" class="mb-10 border-t border-stone-300 pt-6">
                <h2 id="withdrawal-heading" class="text-xl font-semibold">Auszahlung simulieren</h2>
                <p class="mt-2 text-sm text-stone-600">Von {{ money(minWithdrawalMinor) }} bis {{ money(maxWithdrawalMinor) }} in ganzen Euro. Verfügbare Scheine: {{ denominationsMinor.map(money).join(', ') || 'keine' }}.</p>
                <form class="mt-5 space-y-3" @submit.prevent="submitWithdrawal">
                    <label for="withdrawal-amount" class="block font-semibold">Auszahlungsbetrag in Euro</label>
                    <input id="withdrawal-amount" v-model="withdrawal.withdrawal_amount" type="text" inputmode="numeric" placeholder="50" maxlength="4" required :disabled="withdrawal.processing || !atmAvailable" :aria-invalid="!!withdrawal.errors.withdrawal_amount" aria-describedby="withdrawal-error" class="w-full rounded-lg border border-stone-400 bg-white p-3">
                    <p v-if="withdrawal.errors.withdrawal_amount || withdrawal.errors.idempotency_key" id="withdrawal-error" role="alert" class="text-sm text-red-800">{{ withdrawal.errors.withdrawal_amount || withdrawal.errors.idempotency_key }}</p>
                    <p v-else-if="!atmAvailable" id="withdrawal-error" role="status" class="text-sm text-red-800">Der Demo-Automat ist derzeit nicht verfügbar.</p>
                    <button type="submit" :disabled="withdrawal.processing || !atmAvailable" class="w-full rounded-lg bg-green-900 px-5 py-4 font-semibold text-white hover:bg-green-800 disabled:opacity-60">{{ withdrawal.processing ? 'Wird ausgezahlt …' : 'Demo-Auszahlung buchen' }}</button>
                </form>
            </section>
            <section aria-labelledby="deposit-heading" class="mb-10 border-t border-stone-300 pt-6">
                <h2 id="deposit-heading" class="text-xl font-semibold">Einzahlung simulieren</h2>
                <p class="mt-2 text-sm text-stone-600">Von 0,01 € bis {{ money(maxDepositMinor) }} je Buchung. Der Betrag wird deinem Demo-Konto gutgeschrieben.</p>
                <form class="mt-5 space-y-3" @submit.prevent="submitDeposit">
                    <label for="amount" class="block font-semibold">Betrag in Euro</label>
                    <input id="amount" v-model="deposit.amount" type="text" inputmode="decimal" placeholder="25,50" maxlength="8" required :disabled="deposit.processing" :aria-invalid="!!deposit.errors.amount" aria-describedby="deposit-error" class="w-full rounded-lg border border-stone-400 bg-white p-3">
                    <p v-if="deposit.errors.amount || deposit.errors.idempotency_key" id="deposit-error" role="alert" class="text-sm text-red-800">{{ deposit.errors.amount || deposit.errors.idempotency_key }}</p>
                    <button type="submit" :disabled="deposit.processing" class="w-full rounded-lg bg-green-900 px-5 py-4 font-semibold text-white hover:bg-green-800 disabled:opacity-60">{{ deposit.processing ? 'Wird gebucht …' : 'Demo-Einzahlung buchen' }}</button>
                </form>
            </section>
            <section aria-labelledby="history-heading" class="mb-10 border-t border-stone-300 pt-6">
                <h2 id="history-heading" class="text-xl font-semibold">Buchungshistorie</h2>
                <p v-if="transactions.data.length === 0" class="mt-4 text-stone-600">{{ transactions.total === 0 ? 'Noch keine Buchungen. Dein Demo-Konto startet bei 0,00 €.' : 'Keine Buchungen auf dieser Seite.' }}</p>
                <ol v-else class="mt-4 divide-y divide-stone-300">
                    <li v-for="booking in transactions.data" :key="booking.id" class="py-4">
                        <div class="flex flex-wrap justify-between gap-2 font-semibold"><span>{{ bookingLabel(booking) }}</span><span>{{ booking.type === 'withdrawal' ? '−' : '+' }}{{ money(booking.amount_minor) }}</span></div>
                        <p class="mt-1 text-sm text-stone-600">{{ date(booking.created_at) }} · Kontostand danach: {{ money(booking.balance_after_minor) }}</p>
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
            <p class="mb-6 text-sm text-stone-600">Die Sitzung endet nach {{ Math.ceil(idleSeconds / 60) }} Minuten ohne Serveranfrage automatisch.</p>
            <button type="button" :disabled="logout.processing" class="w-full rounded-lg bg-green-900 px-5 py-4 font-semibold text-white hover:bg-green-800 disabled:opacity-60" @click="logout.delete('/atm/session')">{{ logout.processing ? 'Wird beendet …' : 'Sitzung beenden & Karte zurückgeben' }}</button>
        </section>
    </AppShell>
</template>
