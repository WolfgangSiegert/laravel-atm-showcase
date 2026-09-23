<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { PhArrowLeft, PhBank, PhLockKey, PhShieldCheck } from '@phosphor-icons/vue';

defineProps<{ guestAccessEnabled: boolean }>();
const form = useForm({ email: '', password: '' });
const guestForm = useForm({});
function submit() {
    form.post('/admin/session', { onFinish: () => form.reset('password') });
}
function enterAsGuest() {
    guestForm.post('/admin/guest-session');
}
</script>

<template>
    <Head title="Admin-Anmeldung" />
    <main class="admin-login">
        <section class="admin-login__brand" aria-label="LERN-Bank Administration">
            <a href="/atm" class="admin-login__back"><PhArrowLeft :size="20" /> Zum Geldautomaten</a>
            <div class="admin-login__intro">
                <span class="admin-login__symbol"><PhBank :size="42" weight="duotone" /></span>
                <p>ATM Control Center</p>
                <h1>LERN-Bank<br>Administration</h1>
                <p>Bestände, Konten und Systemereignisse an einem Ort verwalten.</p>
            </div>
            <p class="admin-login__security"><PhShieldCheck :size="22" weight="fill" /> Geschützter Demo-Verwaltungsbereich</p>
        </section>
        <section class="admin-login__panel" aria-labelledby="operator-login-heading">
            <div class="admin-login__form-wrap">
                <span class="admin-login__lock"><PhLockKey :size="28" weight="duotone" /></span>
                <p class="admin-eyebrow">Willkommen zurück</p>
                <h2 id="operator-login-heading">Admin anmelden</h2>
                <p class="admin-login__hint">Nutze die separat eingerichteten Zugangsdaten für den Betrieb.</p>
                <form class="admin-login__form" @submit.prevent="submit">
                    <label for="operator-email">E-Mail-Adresse</label>
                    <input id="operator-email" v-model="form.email" type="email" autocomplete="username" placeholder="admin@lern-bank.test" required :aria-invalid="!!form.errors.email">
                    <p v-if="form.errors.email" role="alert" class="admin-field-error">{{ form.errors.email }}</p>
                    <label for="operator-password">Passwort</label>
                    <input id="operator-password" v-model="form.password" type="password" autocomplete="current-password" placeholder="••••••••••••" required :aria-invalid="!!form.errors.password">
                    <p v-if="form.errors.password" role="alert" class="admin-field-error">{{ form.errors.password }}</p>
                    <button type="submit" :disabled="form.processing" class="admin-button admin-button--primary">
                        {{ form.processing ? 'Anmeldung läuft …' : 'Sicher anmelden' }}
                    </button>
                </form>
                <div v-if="guestAccessEnabled" class="admin-guest-access">
                    <div><p class="admin-eyebrow">Öffentlicher Showcase</p><strong>Dashboard schreibgeschützt ansehen</strong><p>Der Gastzugang zeigt Tabellen, Filter und Systemzustand. Änderungen sind serverseitig gesperrt.</p></div>
                    <button type="button" :disabled="guestForm.processing" class="admin-button admin-button--guest" @click="enterAsGuest">
                        <PhShieldCheck :size="19" /> {{ guestForm.processing ? 'Öffnet …' : 'Als Gast ansehen' }}
                    </button>
                </div>
                <p class="admin-login__footnote">Private Änderungen nur für Superadmins · Anmeldungen werden protokolliert.</p>
            </div>
        </section>
    </main>
</template>
