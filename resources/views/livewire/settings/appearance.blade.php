<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Appearance settings') }}</h2>

    <x-settings.layout :heading="__('Appearance')" :subheading=" __('Update the appearance settings for your account')">
        <div class="join">
            <input class="join-item btn theme-controller" type="radio" name="appearance_theme" aria-label="{{ __('Light') }}" value="light" />
            <input class="join-item btn theme-controller" type="radio" name="appearance_theme" aria-label="{{ __('Dark') }}" value="dark" />
            <input class="join-item btn theme-controller" type="radio" name="appearance_theme" aria-label="{{ __('System') }}" value="system" />
        </div>
    </x-settings.layout>
</section>
