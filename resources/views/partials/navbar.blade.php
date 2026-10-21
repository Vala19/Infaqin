@php
    $user = auth()->user();

    $landingLinks = [
        'about' => __('About us'),
        'campaigns' => __('Campaigns'),
        'features' => __('Features'),
        'testimonials' => __('Other say'),
        'contact' => __('Contact us'),
    ];

    $appLinks = $user
        ? [
            ['home', __('Home'), 'home'],
            ['campaigns.index', __('Campaigns'), 'campaigns.*'],
            ['history', __('History'), 'history'],
            ['wishlist', __('Wishlist'), 'wishlist'],
            ['news.index', __('News'), 'news.*'],
        ]
        : [
            ['landing', __('Home'), 'landing'],
            ['campaigns.index', __('Campaigns'), 'campaigns.*'],
            ['news.index', __('News'), 'news.*'],
        ];
@endphp

<header x-data="{ open: false, scrolled: false, section: '' }"
        x-init="
            const onScroll = () => scrolled = window.scrollY > 24;
            onScroll(); window.addEventListener('scroll', onScroll, { passive: true });
            @if ($variant === 'landing')
                const spy = new IntersectionObserver(entries => entries.forEach(e => { if (e.isIntersecting) section = e.target.id }), { rootMargin: '-45% 0px -50% 0px' });
                document.querySelectorAll('[data-section]').forEach(el => spy.observe(el));
            @endif
        "
        @keydown.escape.window="open = false"
        class="fixed inset-x-0 top-0 z-50 transition duration-300"
        :class="(scrolled || open || {{ $overlay ? 'false' : 'true' }}) ? 'bg-night/90 shadow-nav backdrop-blur-md' : 'bg-black/40 backdrop-blur-[8.6px]'">
    <nav class="container-page flex h-[72px] items-center justify-between gap-4" aria-label="{{ __('Main') }}">
        <a href="{{ $user ? route('home') : route('landing') }}" class="flex shrink-0 items-center gap-2.5" aria-label="{{ __('Infaqin home') }}">
            <x-logo variant="icon" class="h-10" />
            <span class="hidden text-xl font-bold tracking-tight text-white sm:inline">Infaqin</span>
        </a>

        {{-- Desktop links --}}
        <div class="hidden items-center gap-1 lg:flex">
            @if ($variant === 'landing')
                @foreach ($landingLinks as $id => $label)
                    <a href="#{{ $id }}" class="nav-link" :class="section === '{{ $id }}' && 'nav-link-active'">{{ $label }}</a>
                @endforeach
            @elseif ($variant === 'app')
                @foreach ($appLinks as [$route, $label, $pattern])
                    <a href="{{ route($route) }}" @class(['nav-link', 'nav-link-active' => request()->routeIs($pattern)]) @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            @endif
        </div>

        <div class="flex items-center gap-2">
            <x-language-switch />

            @auth
                @if ($variant !== 'auth')
                    <a href="{{ route('campaigns.index') }}" class="btn-primary hidden h-9 px-4 text-xs sm:inline-flex">
                        <x-icon name="heart" class="size-4" /> {{ __('Donate now') }}
                    </a>
                @endif

                <div class="relative hidden lg:block" x-data="{ menu: false }" @click.outside="menu = false">
                    <button type="button" @click="menu = !menu" :aria-expanded="menu" aria-haspopup="menu"
                            class="flex items-center gap-2 rounded-full py-1 pr-2 pl-1 text-white transition hover:bg-white/10">
                        <span class="grid size-8 place-items-center rounded-full bg-brand text-xs font-bold">{{ $user->initials() }}</span>
                        <span class="max-w-28 truncate text-sm">{{ Str::before($user->name, ' ') }}</span>
                        <x-icon name="chevron-down" class="size-4 transition" ::class="menu && 'rotate-180'" />
                    </button>
                    <div x-cloak x-show="menu" x-transition.origin.top.right role="menu"
                         class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl bg-white py-1.5 text-sm shadow-xl ring-1 ring-black/5">
                        <div class="border-b border-mist/70 px-4 pt-1.5 pb-2.5">
                            <p class="truncate font-semibold text-night">{{ $user->name }}</p>
                            <p class="truncate text-xs text-ink/60">{{ $user->email }}</p>
                        </div>
                        <a href="{{ route('home') }}" class="flex items-center gap-2.5 px-4 py-2 text-ink hover:bg-mint-50" role="menuitem"><x-icon name="home" class="size-4" /> {{ __('Home') }}</a>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-ink hover:bg-mint-50" role="menuitem"><x-icon name="user" class="size-4" /> {{ __('Profile') }}</a>
                        <a href="{{ route('history') }}" class="flex items-center gap-2.5 px-4 py-2 text-ink hover:bg-mint-50" role="menuitem"><x-icon name="receipt" class="size-4" /> {{ __('Donation history') }}</a>
                        @if ($user->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-ink hover:bg-mint-50" role="menuitem"><x-icon name="grid" class="size-4" /> {{ __('Admin panel') }}</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-mist/70 pt-1">
                            @csrf
                            <button class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-red-600 hover:bg-red-50" role="menuitem"><x-icon name="logout" class="size-4" /> {{ __('Log out') }}</button>
                        </form>
                    </div>
                </div>
            @else
                @if ($variant !== 'auth')
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-4 py-2 text-sm font-medium text-white transition hover:text-mint sm:inline-flex">{{ __('Log in') }}</a>
                    <a href="{{ route('register') }}" class="btn-primary h-9 px-5 text-xs font-bold">{{ __('Get started!') }}</a>
                @else
                    <a href="{{ route('landing') }}" class="flex items-center gap-1.5 text-sm text-white/80 hover:text-mint"><x-icon name="arrow-left" class="size-4" /> {{ __('Back to home') }}</a>
                @endif
            @endauth

            @if ($variant !== 'auth')
                <button type="button" @click="open = !open" class="grid size-10 place-items-center rounded-lg text-white hover:bg-white/10 lg:hidden"
                        :aria-expanded="open" aria-controls="mobile-menu" aria-label="{{ __('Toggle menu') }}">
                    <x-icon name="menu" class="size-6" x-show="!open" />
                    <x-icon name="x" class="size-6" x-cloak x-show="open" />
                </button>
            @endif
        </div>
    </nav>

    {{-- Mobile menu --}}
    @if ($variant !== 'auth')
        <div id="mobile-menu" x-cloak x-show="open" x-collapse class="border-t border-white/10 lg:hidden">
            <div class="container-page flex flex-col gap-1 py-4">
                @if ($variant === 'landing')
                    @foreach ($landingLinks as $id => $label)
                        <a href="#{{ $id }}" @click="open = false" class="nav-link py-3 text-base" :class="section === '{{ $id }}' && 'nav-link-active'">{{ $label }}</a>
                    @endforeach
                @else
                    @foreach ($appLinks as [$route, $label, $pattern])
                        <a href="{{ route($route) }}" @class(['nav-link py-3 text-base', 'nav-link-active' => request()->routeIs($pattern)])>{{ $label }}</a>
                    @endforeach
                @endif

                <div class="mt-3 border-t border-white/10 pt-4">
                    @auth
                        <a href="{{ route('profile.edit') }}" class="nav-link block py-3 text-base">{{ __('Profile') }}</a>
                        @if ($user->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="nav-link block py-3 text-base">{{ __('Admin panel') }}</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="nav-link w-full py-3 text-left text-base text-red-300">{{ __('Log out') }}</button>
                        </form>
                    @else
                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ route('login') }}" class="btn-outline border-white/40 text-white">{{ __('Log in') }}</a>
                            <a href="{{ route('register') }}" class="btn-primary">{{ __('Register') }}</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    @endif
</header>
