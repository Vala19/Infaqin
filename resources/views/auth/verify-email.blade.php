<x-auth-shell :title="__('Verify your email')" :subtitle="__('Email verification is required before you can donate.')">
    <div class="flex items-start gap-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-300">
        <x-icon name="mail" class="mt-0.5 size-5 shrink-0" />
        <p>{{ __('We sent a verification link to :email. Open it to confirm your address, then you can continue your donation.', ['email' => $user->email]) }}</p>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <button type="submit" class="btn-primary h-12 w-full text-base" :disabled="busy">{{ __('Resend verification link') }}</button>
    </form>

    <p class="mt-3 text-center text-xs text-ink/60">{{ __('We will email you a secure link that expires in 60 minutes.') }}</p>

    <p class="mt-8 text-center text-sm text-ink/70">
        <a href="{{ route('campaigns.index') }}" class="font-semibold text-brand hover:underline">{{ __('Back to campaigns') }}</a>
    </p>
</x-auth-shell>
