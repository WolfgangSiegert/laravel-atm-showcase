<script setup lang="ts">
import { PhBank, PhHandTap, PhMonitor, PhMoonStars, PhSun } from '@phosphor-icons/vue';
import { useTheme, type Theme } from '../composables/useTheme';

const { theme, setTheme } = useTheme();
const themes = [
    { value: 'light' as Theme, label: 'Hell', icon: PhSun },
    { value: 'dark' as Theme, label: 'Dunkel', icon: PhMoonStars },
    { value: 'retro' as Theme, label: 'Retro', icon: PhMonitor },
    { value: 'classic' as Theme, label: 'Klassik-ATM', icon: PhBank },
    { value: 'touch' as Theme, label: 'Touchscreen', icon: PhHandTap },
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
            <component :is="option.icon" :size="17" weight="bold" aria-hidden="true" />
            <span class="theme-switcher__label">{{ option.label }}</span>
        </button>
    </div>
</template>
