@props(['code', 'title', 'message'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ $title }} · Infaqin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-night font-sans text-white">
    <header class="container-page flex h-[72px] items-center">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5" aria-label="{{ __('Infaqin home') }}">
            <x-logo variant="icon" class="h-10" />
            <span class="text-xl font-bold">Infaqin</span>
        </a>
    </header>

    <main class="container-page flex flex-1 flex-col items-center justify-center py-16 text-center">
        {{-- Badge from the Figma "Container / 404" component --}}
        <div class="relative size-36" aria-hidden="true">
            <div class="absolute -top-16 -left-11 size-[230px] rounded-full opacity-20 blur-[24px] bg-[radial-gradient(circle,rgba(34,176,75,0.4)_0%,rgba(17,88,38,0.2)_35%,transparent_70%)]"></div>
            <div class="relative grid size-36 place-items-center rounded-full border-[2.35px] border-brand bg-linear-to-br from-brand/15 to-brand/5 shadow-glow">
                <img src="{{ asset('images/icons/alert.svg') }}" alt="" class="size-[72px]">
            </div>
            <div class="absolute top-[104px] left-1/2 flex h-14 min-w-[89px] -translate-x-1/2 items-center justify-center rounded-full bg-brand px-6 drop-shadow-[0_6px_10px_rgba(34,176,75,0.5)]">
                <span class="text-2xl leading-9 font-bold">{{ $code }}</span>
            </div>
        </div>

        <h1 class="mt-16 text-3xl font-bold tracking-tight sm:text-4xl">{{ $title }}</h1>
        <p class="mt-3 max-w-md text-white/70">{{ $message }}</p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}" class="btn-primary h-11 px-6">{{ __('Back to home') }}</a>
            <a href="{{ url('/campaigns') }}" class="btn h-11 border border-white/25 px-6 text-white hover:bg-white/10">{{ __('Browse campaigns') }}</a>
        </div>
        {{ $slot }}
    </main>

    <footer class="py-6 text-center text-xs text-white/40">&copy; {{ now()->year }} {{ config('infaqin.company.legal_name') }}</footer>
</body>
</html>
