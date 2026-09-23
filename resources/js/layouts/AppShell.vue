<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import LoadingOverlay from '../components/LoadingOverlay.vue';
import ThemeSwitcher from '../components/ThemeSwitcher.vue';
import ToastNotice from '../components/ToastNotice.vue';
import { useTheme } from '../composables/useTheme';

const page = usePage<{ portfolioUrl?: string | null }>();
const { theme } = useTheme();
const portfolioUrl = page.props.portfolioUrl || 'https://github.com/WolfgangSiegert/WolfgangSiegert.github.io';
const hasPortfolioWebsite = Boolean(page.props.portfolioUrl);
</script>

<template>
    <div class="app-shell min-h-screen" :class="`app-shell--${theme}`">
        <a href="#main" class="skip-link">Zum Inhalt</a>
        <header class="app-header border-b border-stone-300">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-5 px-6 py-6 sm:px-10">
                <Link href="/atm" class="flex items-center gap-3 font-bold tracking-tight" aria-label="LERN-Bank Mein Geldautomat – Startseite">
                    <span class="brand-symbol" aria-hidden="true">L<span>↗</span></span>
                    <span>LERN-Bank<span class="block text-xs font-normal tracking-widest text-stone-600">MEIN GELDAUTOMAT</span></span>
                </Link>
                <nav aria-label="Hauptnavigation" class="flex flex-wrap items-center justify-end gap-4 text-sm sm:gap-6">
                    <Link href="/atm" :aria-current="page.url === '/atm' ? 'page' : undefined" class="font-semibold underline decoration-lime-600 underline-offset-8">Automat</Link>
                    <Link href="/admin/login" :aria-current="page.url.startsWith('/admin') ? 'page' : undefined" class="text-stone-600 hover:text-stone-950">Admin</Link>
                    <Link href="/atm#ausblick" class="text-stone-600 hover:text-stone-950">Ausblick ↗</Link>
                    <ThemeSwitcher />
                </nav>
            </div>
        </header>
        <main id="main" tabindex="-1" class="app-main mx-auto max-w-6xl px-6 py-12 sm:px-10 sm:py-20">
            <slot />
        </main>
        <footer class="app-footer mx-auto flex max-w-6xl flex-wrap justify-between gap-3 border-t border-stone-300 px-6 py-6 text-xs text-stone-600 sm:px-10">
            <span>Zum Lernen gebaut · Simulation ohne echte Bankgeschäfte</span>
            <span class="flex flex-wrap gap-x-5 gap-y-2">
                <a href="https://github.com/WolfgangSiegert/laravel-atm-showcase" target="_blank" rel="noopener noreferrer" class="font-semibold hover:underline">GitHub ↗</a>
                <a :href="portfolioUrl" target="_blank" rel="noopener noreferrer" class="font-semibold hover:underline">{{ hasPortfolioWebsite ? 'Portfolio' : 'Portfolio-Code' }} ↗</a>
            </span>
        </footer>
        <ToastNotice />
        <LoadingOverlay />
    </div>
</template>
