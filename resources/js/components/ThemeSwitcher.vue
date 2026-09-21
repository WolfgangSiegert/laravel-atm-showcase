<script setup lang="ts">
import { useTheme, type Theme } from '../composables/useTheme';

const { theme, setTheme } = useTheme();
const themes: { value: Theme; label: string; symbol: string }[] = [
    { value: 'light', label: 'Hell', symbol: '☀' },
    { value: 'dark', label: 'Dunkel', symbol: '◐' },
    { value: 'retro', label: 'Retro', symbol: '▦' },
];
</script>

<template>
    <div class="theme-switcher" role="group" aria-label="Darstellung wählen">
        <button
            v-for="option in themes"
            :key="option.value"
            type="button"
            class="theme-switcher__button"
            :class="{ 'theme-switcher__button--active': theme === option.value }"
            :aria-pressed="theme === option.value"
            :aria-label="`${option.label}-Darstellung`"
            @click="setTheme(option.value)"
        >
            <span aria-hidden="true">{{ option.symbol }}</span>
            <span class="hidden sm:inline">{{ option.label }}</span>
        </button>
    </div>
</template>
