<script setup lang="ts">
import { useI18n, type Locale } from '../composables/useI18n';

const { locale, setLocale, t } = useI18n();
const options: { value: Locale; label: string }[] = [
    { value: 'de', label: 'DE' },
    { value: 'en', label: 'EN' },
];
</script>

<template>
    <div class="language-switcher" role="group" :aria-label="t('Sprache wählen')">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            :class="{ 'language-switcher__button--active': locale === option.value }"
            :aria-pressed="locale === option.value"
            :aria-label="option.value === 'de' ? t('Deutsch') : t('Englisch')"
            class="language-switcher__button"
            @click="setLocale(option.value)"
        >{{ option.label }}</button>
    </div>
</template>
