<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-base-100">
        <div class="drawer lg:drawer-open">
            <input id="app-sidebar" type="checkbox" class="drawer-toggle" />

            <div class="drawer-content min-h-screen">
                <div class="navbar border-b border-base-300 bg-base-100 px-4">
                    <div class="navbar-start gap-2">
                        <label for="app-sidebar" class="btn btn-ghost btn-square lg:hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </label>
                        <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
                    </div>

                    <div class="navbar-center hidden lg:flex">
                        <ul class="menu menu-horizontal px-1">
                            <li><a href="{{ route('dashboard') }}" wire:navigate>{{ __('Dashboard') }}</a></li>
                        </ul>
                    </div>

                    <div class="navbar-end gap-2">
                        <a class="btn btn-ghost btn-sm hidden md:inline-flex" href="https://github.com/laravel/livewire-starter-kit" target="_blank">{{ __('Repository') }}</a>
                        <a class="btn btn-ghost btn-sm hidden md:inline-flex" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">{{ __('Documentation') }}</a>
                        <x-desktop-user-menu />
                    </div>
                </div>

                {{ $slot }}
            </div>

            <div class="drawer-side z-40">
                <label for="app-sidebar" aria-label="close sidebar" class="drawer-overlay"></label>
                <aside class="min-h-full w-72 bg-base-200 p-4">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    <ul class="menu mt-6 gap-1">
                        <li><a href="{{ route('dashboard') }}" wire:navigate>{{ __('Dashboard') }}</a></li>
                        <li><a href="https://github.com/laravel/livewire-starter-kit" target="_blank">{{ __('Repository') }}</a></li>
                        <li><a href="https://laravel.com/docs/starter-kits#livewire" target="_blank">{{ __('Documentation') }}</a></li>
                    </ul>
                </aside>
            </div>
        </div>
    </body>
</html>
