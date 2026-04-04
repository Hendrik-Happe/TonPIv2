<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-base-100">

        <x-navbar />

        <div class="pb-64">
            {{ $slot }}
        </div>

        <!-- Music Player Footer -->
        <div class="fixed bottom-0 left-0 right-0 z-50 shadow-2xl">
            <livewire:player />
        </div>

        @livewireScripts
        @livewireStyles
    </body>
</html>
