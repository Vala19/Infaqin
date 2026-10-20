@props(['title'])

@php
    $links = [
        ['admin.dashboard', 'chart', 'Dashboard', 'admin.dashboard'],
        ['admin.news.index', 'newspaper', 'News', 'admin.news.*'],
        ['admin.campaigns.index', 'heart', 'Campaigns', 'admin.campaigns.*'],
        ['admin.donations.index', 'receipt', 'Donations', 'admin.donations.*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · Infaqin Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans" x-data="{ sidebar: false }">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-night text-white transition lg:translate-x-0" :class="sidebar && 'translate-x-0'">
        <a href="{{ route('admin.dashboard') }}" class="flex h-16 items-center gap-2.5 px-6">
            <x-logo variant="icon" class="h-9" />
            <span class="font-bold">Infaqin <span class="font-normal text-mint">Admin</span></span>
        </a>
        <nav class="mt-4 flex-1 space-y-1 px-3" aria-label="Admin">
            @foreach ($links as [$route, $icon, $label, $pattern])
                <a href="{{ route($route) }}" @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition',
                    'bg-brand text-white' => request()->routeIs($pattern),
                    'text-white/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs($pattern),
                ])><x-icon :name="$icon" class="size-5" /> {{ $label }}</a>
            @endforeach
        </nav>
        <div class="space-y-1 border-t border-white/10 p-3">
            <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/70 hover:bg-white/5 hover:text-white"><x-icon name="external" class="size-5" /> View site</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-red-300 hover:bg-white/5"><x-icon name="logout" class="size-5" /> Log out</button>
            </form>
        </div>
    </aside>
    <div x-cloak x-show="sidebar" @click="sidebar = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-mist/60 bg-white/90 px-4 backdrop-blur sm:px-8">
            <button type="button" @click="sidebar = true" class="grid size-10 place-items-center rounded-lg hover:bg-paper lg:hidden" aria-label="Open menu"><x-icon name="menu" class="size-6" /></button>
            <h1 class="flex-1 truncate text-lg font-semibold text-night">{{ $title }}</h1>
            <span class="hidden text-sm text-ink/70 sm:block">{{ auth()->user()->name }}</span>
            <span class="grid size-9 place-items-center rounded-full bg-brand text-xs font-bold text-white">{{ auth()->user()->initials() }}</span>
        </header>

        @include('partials.flash')

        <main class="p-4 sm:p-8">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
