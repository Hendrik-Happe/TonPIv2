<div class="navbar bg-base-200 shadow-lg">
    <div class="navbar-start">
        <div class="dropdown">
            <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                </svg>
            </div>
            <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                @auth
                    <li><a href="/" wire:navigate>{{ __('Home') }}</a></li>
                    <li><a href="/playlists" wire:navigate>{{ __('Playlists') }}</a></li>
                @endauth
            </ul>
        </div>
        <a href="/" wire:navigate class="btn btn-ghost text-xl">🎵 TonPI</a>
    </div>
    
    <div class="navbar-center hidden lg:flex">
        @auth
            <ul class="menu menu-horizontal px-1">
                <li><a href="/" wire:navigate>{{ __('Home') }}</a></li>
                <li><a href="/playlists" wire:navigate>{{ __('Playlists') }}</a></li>
            </ul>
        @endauth
    </div>
    
    <div class="navbar-end">
        @guest
            <a href="/login" class="btn btn-primary btn-sm">{{ __('Login') }}</a>
        @else
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
                    <div class="bg-neutral text-neutral-content w-10 rounded-full">
                        <span class="text-xl">{{ substr(Auth::user()->name, 0, 1) }}</span>
                    </div>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                    <li><a href="/settings/profile" wire:navigate>{{ __('Profile') }}</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left">{{ __('Logout') }}</button>
                        </form>
                    </li>
                </ul>
            </div>
        @endguest
    </div>
</div>
