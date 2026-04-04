@props([
    'sidebar' => false,
])

@if($sidebar)
    <a {{ $attributes }} class="flex items-center gap-2">
        <span class="flex aspect-square size-8 items-center justify-center rounded-md bg-primary text-primary-content">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </span>
        <span class="font-semibold">Laravel Starter Kit</span>
    </a>
@else
    <a {{ $attributes }} class="flex items-center gap-2">
        <span class="flex aspect-square size-8 items-center justify-center rounded-md bg-primary text-primary-content">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </span>
        <span class="font-semibold">Laravel Starter Kit</span>
    </a>
@endif
