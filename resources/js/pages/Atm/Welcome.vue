<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PhBank, PhCreditCard, PhHandTap } from '@phosphor-icons/vue';
import { computed } from 'vue';
import { useTheme } from '../../composables/useTheme';
import AppShell from '../../layouts/AppShell.vue';

defineProps<{ version: string; appName: string }>();
const { theme } = useTheme();
const modeName = computed(() => theme.value === 'classic' ? 'KLASSISCHER GELDAUTOMAT' : theme.value === 'touch' ? 'TOUCHSCREEN' : 'ATM / SIMULATION');
</script>

<template>
    <Head title="Willkommen" />
    <AppShell>
        <div class="welcome-grid grid items-center gap-12 lg:grid-cols-2 lg:gap-20">
            <section class="welcome-copy" aria-labelledby="welcome-heading">
                <p class="mb-6 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-stone-600">
                    <span class="h-2 w-2 rounded-full bg-green-800" aria-hidden="true"></span>
                    Der nächste Schritt · v{{ version }}
                </p>
                <h1 id="welcome-heading" class="text-4xl min-[375px]:text-5xl font-semibold leading-[1.05] tracking-tight sm:text-6xl">Dein Geldautomat.<br><span class="text-green-800">Schritt für Schritt.</span></h1>
                <p class="mt-7 max-w-md text-lg leading-relaxed text-stone-600">Ein vertrauter Ablauf, ein neues Lernprojekt. Der Demo-Automat bucht Guthaben, zahlt passende Scheine aus und erstellt nachvollziehbare Belege.</p>
                <div class="mt-9 border-l-2 border-green-800 pl-5">
                    <p class="font-semibold">Karte wählen. PIN eingeben.</p>
                    <p class="mt-2 max-w-sm text-sm leading-relaxed text-stone-600">Starte eine Demo-Sitzung, simuliere Geldbewegungen und öffne anschließend den jeweiligen Beleg.</p>
                </div>
                <a href="#ausblick" class="mt-8 inline-flex min-h-11 items-center gap-3 text-sm font-semibold text-green-900 hover:underline">Was als Nächstes kommt <span aria-hidden="true">↓</span></a>
            </section>

            <section aria-labelledby="terminal-heading" class="terminal-frame">
                <div class="mb-6 flex items-center justify-between text-xs font-medium uppercase tracking-widest text-stone-500">
                    <span>{{ appName }}</span><span>01 / Willkommen</span>
                </div>
                <div class="terminal-screen">
                    <div class="terminal-screen__meta mb-12 flex items-center justify-between text-xs text-green-100/80">
                        <span>{{ modeName }}</span><span class="rounded-full border border-green-100/30 px-3 py-1">Bereit</span>
                    </div>
                    <PhBank v-if="theme === 'classic'" class="terminal-icon" :size="58" weight="duotone" aria-hidden="true" />
                    <PhHandTap v-else-if="theme === 'touch'" class="terminal-icon" :size="58" weight="duotone" aria-hidden="true" />
                    <PhCreditCard v-else class="terminal-icon" :size="58" weight="duotone" aria-hidden="true" />
                    <h2 id="terminal-heading" class="text-3xl font-medium tracking-tight">Willkommen.</h2>
                    <p class="mt-3 max-w-xs text-sm leading-relaxed text-green-100/80">Beginne deine Sitzung mit der Auswahl einer Demo-Karte.</p>
                    <Link href="/atm/cards" class="mt-9 block w-full rounded-lg bg-lime-200 px-5 py-4 text-left font-semibold text-green-950 hover:bg-lime-100">Karte auswählen <span class="float-right" aria-hidden="true">→</span></Link>
                    <p id="card-status" class="mt-3 text-xs text-green-100/80">Demo-Karten verfügbar · Keine echten Bankdaten</p>
                </div>
                <div class="mx-auto mt-7 h-2 w-32 rounded-full bg-stone-300" aria-hidden="true"></div>
            </section>
        </div>

        <section id="ausblick" aria-labelledby="outlook-heading" class="mt-20 scroll-mt-8 border-t border-stone-300 pt-8 sm:mt-24">
            <div class="mb-8 flex flex-wrap items-baseline justify-between gap-3">
                <h2 id="outlook-heading" class="text-xl font-semibold tracking-tight">Der vollständige Kernablauf</h2>
                <span class="text-xs text-stone-600">Stand · v{{ version }}</span>
            </div>
            <ol class="grid gap-7 sm:grid-cols-3">
                <li v-for="(step, index) in [
                    { title: 'Karte & PIN', text: 'Jetzt verfügbar: Demo-Karte wählen und Sitzung starten.' },
                    { title: 'Konto & Buchungen', text: 'Jetzt verfügbar: Kontostand, Demo-Einzahlungen und Buchungshistorie.' },
                    { title: 'Bargeld & Beleg', text: 'Jetzt verfügbar: Abheben mit begrenztem Scheinbestand und druckbarer Abschlussbeleg.' },
                ]" :key="step.title" class="border-t border-stone-300 pt-4">
                    <span class="font-mono text-xs text-green-800">0{{ index + 1 }} / VERFÜGBAR</span>
                    <h3 class="mt-3 font-semibold">{{ step.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ step.text }}</p>
                </li>
            </ol>
        </section>
    </AppShell>
</template>
