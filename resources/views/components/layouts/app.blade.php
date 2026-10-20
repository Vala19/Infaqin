@props(['title' => null, 'description' => null, 'nav' => 'app', 'overlayNav' => false, 'hideFooter' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $description ?: __('Infaqin — a transparent donation platform connecting generous hearts with people in need.') }}">
    <meta name="theme-color" content="#22b04b">
    <title>{{ $title ? $title.' · Infaqin' : 'Infaqin — '.__('Every rupiah, a reason to hope') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col font-sans">
    <a href="#main" class="sr-only z-[100] rounded-lg bg-brand px-4 py-2 text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">{{ __('Skip to content') }}</a>

    @include('partials.navbar', ['variant' => $nav, 'overlay' => $overlayNav])

    @include('partials.flash')

    <main id="main" class="flex-1 {{ $overlayNav ? '' : 'pt-[72px]' }}">
        {{ $slot }}
    </main>

    @unless ($hideFooter)
        @include('partials.footer')
    @endunless

    @stack('scripts')
</body>
</html>
