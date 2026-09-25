<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { PhArrowLeft, PhBank, PhLockKey, PhShieldCheck } from '@phosphor-icons/vue';
import LanguageSwitcher from '../../components/LanguageSwitcher.vue';
import { useI18n } from '../../composables/useI18n';

defineProps<{ guestAccessEnabled: boolean }>();
const form = useForm({ email: '', password: '' });
const guestForm = useForm({});
const { t } = useI18n();
function submit() {
    form.post('/admin/session', { onFinish: () => form.reset('password') });
}
function enterAsGuest() {
    guestForm.post('/admin/guest-session');
}
</script>

<template>
    <Head :title="t('Admin-Anmeldung')" />
    <main class="admin-login">
        <section class="admin-login__brand" aria-label="LERN-Bank Administration">
            <a href="/atm" class="admin-login__back"><PhArrowLeft :size="20" /> {{ t('Zum Geldautomaten') }}</a>
            <div class="admin-login__intro">
                <span class="admin-login__symbol"><PhBank :size="42" weight="duotone" /></span>
                <p>ATM Control Center</p>
                <h1>LERN-Bank<br>Administration</h1>
                <p>{{ t('Bestände, Konten und Systemereignisse an einem Ort verwalten.') }}</p>
            </div>
            <p class="admin-login__security"><PhShieldCheck :size="22" weight="fill" /> {{ t('Geschützter Demo-Verwaltungsbereich') }}</p>
        </section>
        <section class="admin-login__panel" aria-labelledby="operator-login-heading">
            <div class="admin-login__form-wrap">
                <span class="admin-login__lock"><PhLockKey :size="28" weight="duotone" /></span>
                <LanguageSwitcher />
                <p class="admin-eyebrow">{{ t('Willkommen zurück') }}</p>
                <h2 id="operator-login-heading">{{ t('Admin anmelden') }}</h2>
                <p class="admin-login__hint">{{ t('Nutze die separat eingerichteten Zugangsdaten für den Betrieb.') }}</p>
                <form class="admin-login__form" @submit.prevent="submit">
                    <label for="operator-email">{{ t('E-Mail-Adresse') }}</label>
                    <input id="operator-email" v-model="form.email" type="email" autocomplete="username" placeholder="admin@lern-bank.test" required :aria-invalid="!!form.errors.email">
                    <p v-if="form.errors.email" role="alert" class="admin-field-error">{{ form.errors.email }}</p>
                    <label for="operator-password">{{ t('Passwort') }}</label>
                    <input id="operator-password" v-model="form.password" type="password" autocomplete="current-password" placeholder="••••••••••••" required :aria-invalid="!!form.errors.password">
                    <p v-if="form.errors.password" role="alert" class="admin-field-error">{{ form.errors.password }}</p>
                    <button type="submit" :disabled="form.processing" class="admin-button admin-button--primary">
                        {{ form.processing ? t('Anmeldung läuft …') : t('Sicher anmelden') }}
                    </button>
                </form>
                <div v-if="guestAccessEnabled" class="admin-guest-access">
                    <div><p class="admin-eyebrow">{{ t('Öffentlicher Showcase') }}</p><strong>{{ t('Dashboard schreibgeschützt ansehen') }}</strong><p>{{ t('Der Gastzugang zeigt Tabellen, Filter und Systemzustand. Änderungen sind serverseitig gesperrt.') }}</p></div>
                    <button type="button" :disabled="guestForm.processing" class="admin-button admin-button--guest" @click="enterAsGuest">
                        <PhShieldCheck :size="19" /> {{ guestForm.processing ? t('Öffnet …') : t('Als Gast ansehen') }}
                    </button>
                </div>
                <p class="admin-login__footnote">{{ t('Private Änderungen nur für Superadmins · Anmeldungen werden protokolliert.') }}</p>
            </div>
        </section>
    </main>
</template>
