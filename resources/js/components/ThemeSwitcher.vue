<script setup lang="ts">
import { PhBank, PhHandTap, PhMonitor, PhMoonStars, PhSun } from '@phosphor-icons/vue';
import { computed } from 'vue';
import { useI18n } from '../composables/useI18n';
import { useTheme, type Theme } from '../composables/useTheme';

const { theme, setTheme } = useTheme();
const { t } = useI18n();
const themes = computed(() => [
    { value: 'light' as Theme, label: t('Hell'), icon: PhSun },
    { value: 'dark' as Theme, label: t('Dunkel'), icon: PhMoonStars },
    { value: 'retro' as Theme, label: 'Retro', icon: PhMonitor },
    { value: 'classic' as Theme, label: t('Klassik-ATM'), icon: PhBank },
    { value: 'touch' as Theme, label: t('Touchscreen'), icon: PhHandTap },
]);
</script>

<template>
    <div class="theme-switcher" role="group" :aria-label="t('Darstellung wählen')">
        <button
            v-for="option in themes"
            :key="option.value"
            type="button"
            class="theme-switcher__button"
            :class="{ 'theme-switcher__button--active': theme === option.value }"
            :aria-pressed="theme === option.value"
            :aria-label="t(':name-Darstellung', { name: option.label })"
            @click="setTheme(option.value)"
        >
            <component :is="option.icon" :size="17" weight="bold" aria-hidden="true" />
            <span class="theme-switcher__label">{{ option.label }}</span>
        </button>
    </div>
</template>
