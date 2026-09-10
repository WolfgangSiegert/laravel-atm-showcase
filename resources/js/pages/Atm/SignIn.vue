<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import AppShell from '../../layouts/AppShell.vue';

defineProps<{ cards: { id: number; demo_reference: string }[]; pinLength: number }>();
const form = useForm({ card_id: '', pin: '' });
const pinInput = ref<HTMLInputElement | null>(null);
function submit() {
    form.post('/atm/session', {
        onError: () => nextTick(() => pinInput.value?.focus()),
        onFinish: () => form.reset('pin'),
    });
}
</script>

<template>
    <Head title="Karte & PIN" />
    <AppShell>
        <section class="mx-auto max-w-lg" aria-labelledby="signin-heading">
            <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-green-800">01 / Karte & PIN</p>
            <h1 id="signin-heading" class="text-4xl font-semibold tracking-tight">Deine Sitzung beginnt hier.</h1>
            <p class="mt-5 text-stone-600">Wähle eine Demo-Karte und gib die zugehörige PIN ein.</p>
            <form v-if="cards.length" class="mt-8 space-y-6" @submit.prevent="submit">
                <div>
                    <label for="card" class="mb-2 block font-semibold">Demo-Karte</label>
                    <select id="card" v-model="form.card_id" required class="w-full rounded-lg border border-stone-400 bg-white p-3" :aria-invalid="!!form.errors.card_id" aria-describedby="card-error" @change="form.clearErrors()">
                        <option value="" disabled>Bitte auswählen</option>
                        <option v-for="card in cards" :key="card.id" :value="String(card.id)">{{ card.demo_reference }}</option>
                    </select>
                    <p v-if="form.errors.card_id" id="card-error" role="alert" class="mt-2 text-sm text-red-800">{{ form.errors.card_id }}</p>
                </div>
                <div>
                    <label for="pin" class="mb-2 block font-semibold">PIN</label>
                    <input id="pin" ref="pinInput" v-model="form.pin" type="password" inputmode="numeric" autocomplete="off" :maxlength="pinLength" :minlength="pinLength" pattern="[0-9]+" required class="w-full rounded-lg border border-stone-400 bg-white p-3 text-xl tracking-[0.35em]" :aria-invalid="!!form.errors.pin" aria-describedby="pin-hint pin-error">
                    <p id="pin-hint" class="mt-2 text-sm text-stone-600">{{ pinLength }} Ziffern. Verwende ausschließlich die Demo-PIN.</p>
                    <p v-if="form.errors.pin" id="pin-error" role="alert" class="mt-3 text-sm text-red-800">{{ form.errors.pin }}</p>
                </div>
                <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-green-900 px-5 py-4 font-semibold text-white hover:bg-green-800 disabled:opacity-60">{{ form.processing ? 'Wird geprüft …' : 'Sitzung starten' }}</button>
            </form>
            <p v-else role="status" class="mt-8 rounded-lg border border-stone-300 p-5">Noch keine Demo-Karten vorhanden. Die Beispieldaten müssen zunächst eingerichtet werden.</p>
            <p class="mt-6 text-sm text-stone-600">Die Demo-Zugangsdaten stehen in der Projekt-README.</p>
            <Link href="/atm" class="mt-5 inline-block py-3 font-semibold text-green-900 hover:underline">← Zur Startseite</Link>
        </section>
    </AppShell>
</template>
