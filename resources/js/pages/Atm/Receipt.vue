<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '../../layouts/AppShell.vue';

type Receipt = {
    reference: string;
    type: 'deposit' | 'opening' | 'withdrawal';
    purpose: string | null;
    amountMinor: number;
    balanceAfterMinor: number;
    currency: string;
    cashBreakdown: Record<string, number> | null;
    createdAt: string;
    cardReference: string | null;
    accountReference: string;
    atmLabel: string | null;
};

const props = defineProps<{ receipt: Receipt }>();
const money = (minor: number) => new Intl.NumberFormat('de-DE', { style: 'currency', currency: props.receipt.currency }).format(minor / 100);
const date = (value: string) => new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium', timeStyle: 'medium', timeZone: 'Europe/Berlin' }).format(new Date(value));
const typeLabel = props.receipt.type === 'withdrawal' ? 'Demo-Auszahlung' : props.receipt.type === 'deposit' ? 'Demo-Einzahlung' : 'Anfangsbestand';
const breakdown = props.receipt.cashBreakdown
    ? Object.entries(props.receipt.cashBreakdown)
        .sort(([left], [right]) => Number(right) - Number(left))
        .map(([denomination, quantity]) => `${quantity} × ${money(Number(denomination))}`)
        .join(', ')
    : null;
const printReceipt = () => window.print();
</script>

<template>
    <Head title="Demo-Beleg" />
    <AppShell>
        <section class="mx-auto max-w-xl" aria-labelledby="receipt-heading">
            <p class="mb-5 text-xs font-semibold uppercase tracking-widest text-green-800">03 / Vorgang abgeschlossen</p>
            <h1 id="receipt-heading" class="text-4xl font-semibold tracking-tight">{{ typeLabel }} bestätigt.</h1>
            <p class="mt-5 leading-relaxed text-stone-600">Dieser Beleg dokumentiert ausschließlich einen Vorgang innerhalb der Simulation.</p>

            <article class="receipt mt-8 rounded-xl border border-stone-300 bg-white p-6" aria-label="Demo-Beleg">
                <div class="border-b border-dashed border-stone-300 pb-5">
                    <p class="text-sm text-stone-600">{{ typeLabel }}</p>
                    <p class="mt-2 text-4xl font-semibold">{{ props.receipt.type === 'withdrawal' ? '−' : '+' }}{{ money(props.receipt.amountMinor) }}</p>
                </div>
                <dl class="mt-5 space-y-4 text-sm">
                    <div><dt class="text-stone-600">Belegreferenz</dt><dd class="mt-1 break-all font-mono font-semibold">{{ props.receipt.reference }}</dd></div>
                    <div><dt class="text-stone-600">Zeitpunkt</dt><dd class="mt-1 font-semibold">{{ date(props.receipt.createdAt) }}</dd></div>
                    <div v-if="props.receipt.purpose"><dt class="text-stone-600">Verwendungszweck</dt><dd class="mt-1 whitespace-pre-wrap break-words font-semibold">{{ props.receipt.purpose }}</dd></div>
                    <div><dt class="text-stone-600">Konto</dt><dd class="mt-1 font-semibold">{{ props.receipt.accountReference }}</dd></div>
                    <div v-if="props.receipt.cardReference"><dt class="text-stone-600">Karte</dt><dd class="mt-1 font-semibold">{{ props.receipt.cardReference }}</dd></div>
                    <div v-if="props.receipt.atmLabel"><dt class="text-stone-600">Automat</dt><dd class="mt-1 font-semibold">{{ props.receipt.atmLabel }}</dd></div>
                    <div v-if="breakdown"><dt class="text-stone-600">Ausgegebene Scheine</dt><dd class="mt-1 font-semibold">{{ breakdown }}</dd></div>
                    <div class="border-t border-dashed border-stone-300 pt-4"><dt class="text-stone-600">Kontostand danach</dt><dd class="mt-1 text-xl font-semibold">{{ money(props.receipt.balanceAfterMinor) }}</dd></div>
                </dl>
            </article>

            <div class="no-print mt-6 grid gap-3 sm:grid-cols-2">
                <Link href="/atm/session" class="rounded-lg bg-green-900 px-5 py-4 text-center font-semibold text-white hover:bg-green-800">Zurück zum Konto</Link>
                <button type="button" class="rounded-lg border border-green-900 px-5 py-4 font-semibold text-green-900 hover:bg-green-50" @click="printReceipt">Beleg drucken</button>
            </div>
        </section>
    </AppShell>
</template>
