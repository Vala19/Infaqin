<x-auth-shell :title="__('Create your account')" :subtitle="__('Join thousands of people turning kindness into a habit.')" image="images/campaigns/handover.jpg">
    <form method="POST" action="{{ url('/register') }}" class="space-y-5" x-data="{ busy: false, pw: '' }" @submit="busy = true">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="name" :label="__('Full name')" autocomplete="name" required maxlength="100" placeholder="Aisyah Rahmawati" />
            <x-field name="username" :label="__('Username')" autocomplete="username" required maxlength="30" placeholder="aisyah" />
        </div>
        <x-field name="email" :label="__('Email address')" type="email" autocomplete="email" required :placeholder="__('you@example.com')" />

        <div>
            <x-field name="password" :label="__('Password')" type="password" autocomplete="new-password" required x-model="pw" />
            <ul class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-xs" aria-label="{{ __('Password requirements') }}">
                @foreach ([__('8+ characters') => 'pw.length >= 8', __('Upper & lower case') => '/[a-z]/.test(pw) && /[A-Z]/.test(pw)', __('A number') => '/\d/.test(pw)', __('A symbol') => '/[^A-Za-z0-9]/.test(pw)'] as $rule => $check)
                    <li class="flex items-center gap-1.5 transition" :class="{{ $check }} ? 'text-brand' : 'text-ink/50'">
                        <x-icon name="check-circle" class="size-3.5" /> {{ $rule }}
                    </li>
                @endforeach
            </ul>
        </div>
        <x-field name="password_confirmation" :label="__('Confirm password')" type="password" autocomplete="new-password" required />

        <label class="flex items-start gap-2 text-sm text-ink">
            <input type="checkbox" name="terms" value="1" class="mt-0.5 size-4 rounded border-mist accent-brand" @checked(old('terms')) required>
            <span>{!! __('I agree to the :terms, including the :privacy.', [
                'terms' => '<a href="'.route('terms').'" target="_blank" class="font-medium text-brand underline-offset-2 hover:underline">'.e(__('Terms and Conditions')).'</a>',
                'privacy' => '<a href="'.route('terms').'#privacy" target="_blank" class="font-medium text-brand underline-offset-2 hover:underline">'.e(__('privacy section')).'</a>',
            ]) !!}</span>
        </label>
        @error('terms') <p class="-mt-3 text-xs text-red-600">{{ $message }}</p> @enderror

        <button type="submit" class="btn-primary h-12 w-full text-base" :disabled="busy">
            <span x-show="!busy">{{ __('Create account') }}</span>
            <span x-cloak x-show="busy">{{ __('Creating your account…') }}</span>
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-ink/70">
        {{ __('Already have an account?') }} <a href="{{ route('login') }}" class="font-semibold text-brand hover:underline">{{ __('Log in') }}</a>
    </p>
</x-auth-shell>
