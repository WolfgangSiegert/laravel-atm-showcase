<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Ein Geldautomat als Lernprojekt. Entdecke die Oberfläche der ATM-Simulation.">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @vite('resources/js/app.ts')
        <x-inertia::head>
            <title>{{ config('app.name') }}</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
        <noscript>Bitte aktiviere JavaScript, um die ATM-Oberfläche zu nutzen.</noscript>
    </body>
</html>
