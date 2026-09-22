import { readonly, ref } from 'vue';

export type Theme = 'light' | 'dark' | 'retro' | 'classic' | 'touch';

const storageKey = 'lern-bank-theme';
const theme = ref<Theme>('light');
let initialized = false;

function isTheme(value: string | null): value is Theme {
    return value === 'light' || value === 'dark' || value === 'retro' || value === 'classic' || value === 'touch';
}

function applyTheme(value: Theme) {
    theme.value = value;
    document.documentElement.dataset.theme = value;
    document.documentElement.style.colorScheme = value === 'dark' || value === 'classic' || value === 'touch' ? 'dark' : 'light';
}

export function initializeTheme() {
    if (initialized || typeof window === 'undefined') return;
    initialized = true;
    let stored: string | null = null;
    try {
        stored = window.localStorage.getItem(storageKey);
    } catch {
        // Storage may be unavailable in strict privacy modes; the theme still works for this page.
    }
    applyTheme(isTheme(stored) ? stored : window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
}

export function useTheme() {
    initializeTheme();

    function setTheme(value: Theme) {
        applyTheme(value);
        try {
            window.localStorage.setItem(storageKey, value);
        } catch {
            // Keep the in-memory preference when storage is unavailable.
        }
    }

    return { theme: readonly(theme), setTheme };
}
