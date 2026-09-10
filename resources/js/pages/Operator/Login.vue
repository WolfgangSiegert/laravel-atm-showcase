<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppShell from '../../layouts/AppShell.vue';

const form = useForm({ email: '', password: '' });
function submit() {
    form.post('/operator/session', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Betreiberanmeldung" />
    <AppShell>
        <section class="mx-auto max-w-md" aria-labelledby="operator-login-heading">
            <p class="mb-5 text-xs font-semibold uppercase tracking-widest text-green-800">Betreiberbereich</p>
            <h1 id="operator-login-heading" class="text-4xl font-semibold tracking-tight">Betrieb sicher verwalten.</h1>
            <p class="mt-5 leading-relaxed text-stone-600">Dieser Zugang ist von den öffentlichen Demo-Karten getrennt.</p>
            <form class="mt-8 space-y-4 rounded-xl border border-stone-300 bg-white p-6" @submit.prevent="submit">
                <label for="operator-email" class="block font-semibold">E-Mail-Adresse</label>
                <input id="operator-email" v-model="form.email" type="email" autocomplete="username" required :aria-invalid="!!form.errors.email" class="w-full rounded-lg border border-stone-400 p-3">
                <p v-if="form.errors.email" role="alert" class="text-sm text-red-800">{{ form.errors.email }}</p>
                <label for="operator-password" class="block font-semibold">Passwort</label>
                <input id="operator-password" v-model="form.password" type="password" autocomplete="current-password" required :aria-invalid="!!form.errors.password" class="w-full rounded-lg border border-stone-400 p-3">
                <p v-if="form.errors.password" role="alert" class="text-sm text-red-800">{{ form.errors.password }}</p>
                <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-green-900 px-5 py-4 font-semibold text-white hover:bg-green-800 disabled:opacity-60">{{ form.processing ? 'Anmeldung läuft …' : 'Als Betreiber anmelden' }}</button>
            </form>
        </section>
    </AppShell>
</template>
