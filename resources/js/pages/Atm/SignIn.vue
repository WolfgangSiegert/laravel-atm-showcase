<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { PhCreditCard, PhShieldCheck } from '@phosphor-icons/vue';
import AtmNumericPad from '../../components/AtmNumericPad.vue';
import { useTheme } from '../../composables/useTheme';
import { useI18n } from '../../composables/useI18n';
import AppShell from '../../layouts/AppShell.vue';

const { cards, pinLength, demoAccess } = defineProps<{ cards: { id: number; demo_reference: string }[]; pinLength: number; demoAccess: Record<string, string> | null }>();
const form = useForm({ card_id: '', pin: '' });
const { theme } = useTheme();
const { t } = useI18n();
const pinInput = ref<HTMLInputElement | null>(null);
function updatePin(value: string) {
    form.pin = value;
    form.clearErrors('pin');
    pinInput.value?.focus();
}
function submit() {
    form.post('/atm/session', {
        onError: () => nextTick(() => pinInput.value?.focus()),
        onFinish: () => form.reset('pin'),
    });
}
</script>

<template>
    <Head :title="t('Karte & PIN')" />
    <AppShell>
        <section class="atm-auth mx-auto max-w-lg" aria-labelledby="signin-heading">
            <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-green-800">01 / {{ t('Karte & PIN') }}</p>
            <h1 id="signin-heading" class="text-4xl font-semibold tracking-tight">{{ t('Deine Sitzung beginnt hier.') }}</h1>
            <p class="mt-5 text-stone-600">{{ t('Wähle eine Demo-Karte und gib die zugehörige PIN ein.') }}</p>
            <form v-if="cards.length" class="atm-auth__form mt-8 space-y-6" @submit.prevent="submit">
                <div>
                    <label for="card" class="mb-2 block font-semibold">{{ t('Demo-Karte') }}</label>
                    <select v-if="theme !== 'classic' && theme !== 'touch'" id="card" v-model="form.card_id" required class="w-full rounded-lg border border-stone-400 bg-white p-3" :aria-invalid="!!form.errors.card_id" aria-describedby="card-error" @change="form.clearErrors()">
                        <option value="" disabled>{{ t('Bitte auswählen') }}</option>
                        <option v-for="card in cards" :key="card.id" :value="String(card.id)">{{ card.demo_reference }}</option>
                    </select>
                    <div v-else class="atm-card-picker" role="radiogroup" :aria-label="t('Demo-Karte')">
                        <button v-for="card in cards" :key="card.id" type="button" role="radio" :aria-checked="form.card_id === String(card.id)" class="atm-card-choice" :class="{ 'atm-card-choice--active': form.card_id === String(card.id) }" @click="form.card_id = String(card.id); form.clearErrors()">
                            <PhCreditCard :size="32" weight="duotone" aria-hidden="true" />
                            <span>{{ card.demo_reference }}</span>
                            <small>{{ t('Demo-Karte auswählen') }}</small>
                        </button>
                    </div>
                    <p v-if="form.errors.card_id" id="card-error" role="alert" class="mt-2 text-sm text-red-800">{{ form.errors.card_id }}</p>
                </div>
                <div>
                    <label for="pin" class="mb-2 block font-semibold">PIN</label>
                    <input id="pin" ref="pinInput" v-model="form.pin" type="password" inputmode="numeric" autocomplete="off" :readonly="theme === 'classic' || theme === 'touch'" :maxlength="pinLength" :minlength="pinLength" pattern="[0-9]+" required class="w-full rounded-lg border border-stone-400 bg-white p-3 text-xl tracking-[0.35em]" :aria-invalid="!!form.errors.pin" aria-describedby="pin-hint pin-error">
                    <p id="pin-hint" class="mt-2 text-sm text-stone-600">{{ t(':count Ziffern. Verwende ausschließlich die Demo-PIN.', { count: pinLength }) }}</p>
                    <p v-if="form.errors.pin" id="pin-error" role="alert" class="mt-3 text-sm text-red-800">{{ form.errors.pin }}</p>
                    <AtmNumericPad :model-value="form.pin" :max-length="pinLength" :disabled="form.processing" :label="t('PIN-Nummernfeld')" :clear-label="t('PIN löschen')" @update:model-value="updatePin" />
                </div>
                <button type="submit" :disabled="form.processing" class="atm-primary-action w-full rounded-lg bg-green-900 px-5 py-4 font-semibold text-white hover:bg-green-800 disabled:opacity-60"><PhShieldCheck :size="22" weight="bold" aria-hidden="true" />{{ form.processing ? t('Wird geprüft …') : t('Sitzung starten') }}</button>
            </form>
            <p v-else role="status" class="mt-8 rounded-lg border border-stone-300 p-5">{{ t('Noch keine Demo-Karten vorhanden. Die Beispieldaten müssen zunächst eingerichtet werden.') }}</p>
            <aside v-if="demoAccess" class="mt-6 rounded-lg border border-green-200 bg-green-50 p-5 text-sm" :aria-label="t('Öffentliche Demo-Zugänge')">
                <p class="font-semibold">{{ t('Zum Ausprobieren') }}</p>
                <p v-for="(pin, reference) in demoAccess" :key="reference" class="mt-2">{{ reference }} · PIN {{ pin }}</p>
                <p class="mt-3">{{ t('Die Konten werden gemeinsam genutzt und regelmäßig zurückgesetzt. Starte mit einer Einzahlung. Verwende ausschließlich erfundene Angaben im Verwendungszweck.') }}</p>
            </aside>
            <p v-else class="mt-6 text-sm text-stone-600">{{ t('Die Demo-Zugangsdaten stehen in der Projekt-README.') }}</p>
            <Link href="/atm" class="mt-5 inline-block py-3 font-semibold text-green-900 hover:underline">{{ t('← Zur Startseite') }}</Link>
        </section>
    </AppShell>
</template>
