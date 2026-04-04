<details class="dropdown dropdown-end">
    <summary class="btn btn-ghost" data-test="sidebar-menu-button">
        <span class="font-medium">{{ auth()->user()->name }}</span>
    </summary>

    <ul class="menu dropdown-content bg-base-100 rounded-box z-[1] mt-2 w-64 p-2 shadow border border-base-300">
        <li class="menu-title">
            <span class="truncate">{{ auth()->user()->name }}</span>
            <span class="text-xs normal-case opacity-70">{{ auth()->user()->email }}</span>
        </li>
        <li>
            <a href="{{ route('profile.edit') }}" wire:navigate>{{ __('Settings') }}</a>
        </li>
        <li>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button
                    type="submit"
                    class="w-full text-left"
                    data-test="logout-button"
                >
                    {{ __('Log out') }}
                </button>
            </form>
        </li>
    </ul>
</details>
