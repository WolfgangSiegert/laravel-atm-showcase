<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppShell from '../../layouts/AppShell.vue';

type Inventory = { id: number; denominationMinor: number; quantity: number };
type AuditEvent = { id: number; event_type: string; outcome: string; reason_code: string | null; context: Record<string, string | number | boolean | null> | null; created_at: string };
const props = defineProps<{ operatorName: string; atm: { code: string; label: string; status: string; currency: string; totalMinor: number; inventory: Inventory[] }; auditEvents: AuditEvent[] }>();
const page = usePage<{ errors: Record<string, string> }>();
const statusForm = useForm({ status: props.atm.status });
const logout = useForm({});
const adjustments = reactive<Record<number, string>>(Object.fromEntries(props.atm.inventory.map(item => [item.id, ''])));
const money = (minor: number) => new Intl.NumberFormat('de-DE', { style: 'currency', currency: props.atm.currency }).format(minor / 100);
const date = (value: string) => new Intl.DateTimeFormat('de-DE', { dateStyle: 'short', timeStyle: 'short', timeZone: 'Europe/Berlin' }).format(new Date(value));
function updateStatus() {
    statusForm.patch('/operator/atm/status', { preserveScroll: true });
}
function adjust(item: Inventory) {
    router.post(`/operator/inventory/${item.id}/adjust`, { adjustment: adjustments[item.id] }, {
        preserveScroll: true,
        onSuccess: () => { adjustments[item.id] = ''; },
    });
}
function eventLabel(type: string) {
    const labels: Record<string, string> = {
        'operator.login': 'Betreiberanmeldung',
        'operator.logout': 'Betreiberabmeldung',
        'atm.status_changed': 'Automatenstatus geändert',
        'atm.inventory_adjusted': 'Bargeldbestand geändert',
        'atm_session.login': 'Kartensitzung',
        'atm_session.logout': 'Kartensitzung beendet',
        'atm_session.invalidated': 'Kartensitzung ungültig',
        'transaction.deposit': 'Einzahlung',
        'transaction.withdrawal': 'Auszahlung',
    };
    return labels[type] ?? type;
}
</script>

<template>
    <Head title="Betreiberbereich" />
    <AppShell>
        <section aria-labelledby="operator-heading">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div>
                    <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-green-800">Betreiberbereich · {{ operatorName }}</p>
                    <h1 id="operator-heading" class="text-4xl font-semibold tracking-tight">{{ atm.label }}</h1>
                    <p class="mt-3 text-stone-600">{{ atm.code }} · Bestand {{ money(atm.totalMinor) }}</p>
                </div>
                <button type="button" class="rounded-lg border border-green-900 px-4 py-3 font-semibold text-green-900" :disabled="logout.processing" @click="logout.delete('/operator/session')">Abmelden</button>
            </div>

            <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_1.4fr]">
                <div class="min-w-0 space-y-8">
                    <section class="rounded-xl border border-stone-300 bg-white p-6" aria-labelledby="status-heading">
                        <h2 id="status-heading" class="text-xl font-semibold">Automatenstatus</h2>
                        <form class="mt-5 space-y-3" @submit.prevent="updateStatus">
                            <label for="atm-status" class="block font-semibold">Status</label>
                            <select id="atm-status" v-model="statusForm.status" class="w-full rounded-lg border border-stone-400 p-3">
                                <option value="active">Aktiv</option>
                                <option value="maintenance">Außer Betrieb</option>
                            </select>
                            <button type="submit" :disabled="statusForm.processing" class="w-full rounded-lg bg-green-900 px-4 py-3 font-semibold text-white">Status speichern</button>
                        </form>
                    </section>

                    <section class="rounded-xl border border-stone-300 bg-white p-6" aria-labelledby="inventory-heading">
                        <h2 id="inventory-heading" class="text-xl font-semibold">Bargeldbestand</h2>
                        <p v-if="page.props.errors.adjustment" role="alert" class="mt-3 text-sm text-red-800">{{ page.props.errors.adjustment }}</p>
                        <ul class="mt-5 divide-y divide-stone-300">
                            <li v-for="item in atm.inventory" :key="item.id" class="py-5 first:pt-0 last:pb-0">
                                <div class="flex justify-between gap-4"><span class="font-semibold">{{ money(item.denominationMinor) }}</span><span>{{ item.quantity }} Scheine</span></div>
                                <form class="mt-3 flex gap-2" @submit.prevent="adjust(item)">
                                    <label :for="`adjustment-${item.id}`" class="sr-only">Bestandsänderung für {{ money(item.denominationMinor) }}</label>
                                    <input :id="`adjustment-${item.id}`" v-model="adjustments[item.id]" type="number" min="-100" max="100" step="1" required placeholder="z. B. 10 oder -5" class="min-w-0 flex-1 rounded-lg border border-stone-400 p-3">
                                    <button type="submit" class="rounded-lg border border-green-900 px-4 font-semibold text-green-900">Buchen</button>
                                </form>
                            </li>
                        </ul>
                    </section>
                </div>

                <section class="min-w-0 rounded-xl border border-stone-300 bg-white p-6" aria-labelledby="audit-heading">
                    <h2 id="audit-heading" class="text-xl font-semibold">Letzte Audit-Ereignisse</h2>
                    <p class="mt-2 text-sm text-stone-600">Maximal 50 Ereignisse. PINs, Passwörter und IP-Adressen werden nicht gespeichert.</p>
                    <p v-if="auditEvents.length === 0" class="mt-5 text-stone-600">Noch keine Ereignisse.</p>
                    <ol v-else class="mt-5 divide-y divide-stone-300">
                        <li v-for="event in auditEvents" :key="event.id" class="py-4">
                            <div class="flex flex-wrap justify-between gap-2"><span class="font-semibold">{{ eventLabel(event.event_type) }}</span><span :class="event.outcome === 'success' ? 'text-green-800' : 'text-red-800'">{{ event.outcome === 'success' ? 'Erfolgreich' : 'Abgewiesen' }}</span></div>
                            <p class="mt-1 text-sm text-stone-600">{{ date(event.created_at) }}<span v-if="event.reason_code"> · {{ event.reason_code }}</span></p>
                            <p v-if="event.context" class="mt-1 break-all font-mono text-xs text-stone-600">{{ JSON.stringify(event.context) }}</p>
                        </li>
                    </ol>
                </section>
            </div>
        </section>
    </AppShell>
</template>
