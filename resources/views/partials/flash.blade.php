@foreach (['status' => 'bg-forest', 'error' => 'bg-red-600'] as $key => $color)
    @if (session($key))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-transition.opacity.duration.300ms
             class="fixed top-[84px] right-4 left-4 z-[60] sm:left-auto sm:w-96" role="status" aria-live="polite">
            <div class="{{ $color }} flex items-start gap-3 rounded-xl px-4 py-3 text-sm text-white shadow-xl">
                <x-icon :name="$key === 'status' ? 'check-circle' : 'alert'" class="mt-0.5 size-5 shrink-0" />
                <p class="flex-1">{{ session($key) }}</p>
                <button type="button" @click="show = false" class="text-white/70 hover:text-white" aria-label="Dismiss"><x-icon name="x" class="size-4" /></button>
            </div>
        </div>
    @endif
@endforeach
