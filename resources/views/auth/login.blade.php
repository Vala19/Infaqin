<x-auth-shell :title="__('Welcome back')" :subtitle="__('Log in to continue your journey of giving.')">
    <form method="POST" action="{{ url('/login') }}" class="space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <x-field name="email" :label="__('Email address')" type="email" autocomplete="email" required autofocus :placeholder="__('you@example.com')" />
        <x-field name="password" :label="__('Password')" type="password" autocomplete="current-password" required placeholder="••••••••" />

        <label class="flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" name="remember" value="1" class="size-4 rounded border-mist accent-brand" @checked(old('remember'))>
            {{ __('Keep me signed in') }}
        </label>

        <button type="submit" class="btn-primary h-12 w-full text-base" :disabled="busy">
            <span x-show="!busy">{{ __('Log in') }}</span>
            <span x-cloak x-show="busy">{{ __('Signing in…') }}</span>
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-ink/70">
        {{ __('New to Infaqin?') }} <a href="{{ route('register') }}" class="font-semibold text-brand hover:underline">{{ __('Create an account') }}</a>
    </p>
</x-auth-shell>
