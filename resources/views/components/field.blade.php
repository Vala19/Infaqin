@props(['name', 'label', 'type' => 'text', 'value' => null, 'bag' => 'default', 'hint' => null])

@php
    $error = $errors->getBag($bag)->first($name);
    $id = $attributes->get('id', 'f-'.$name);
@endphp

<div x-data="{ reveal: false }">
    <label for="{{ $id }}" class="label">{{ $label }}</label>
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}"
               @if ($type === 'password') :type="reveal ? 'text' : 'password'" type="password" @else type="{{ $type }}" @endif
               @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
               @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
               {{ $attributes->except('id')->class(['input', 'input-error' => $error, 'pr-11' => $type === 'password']) }}>
        @if ($type === 'password')
            <button type="button" @click="reveal = !reveal" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-ink/50 hover:text-brand"
                    :aria-label="reveal ? @js(__('Hide password')) : @js(__('Show password'))">
                <x-icon name="eye" class="size-5" x-show="!reveal" />
                <x-icon name="eye-off" class="size-5" x-cloak x-show="reveal" />
            </button>
        @endif
    </div>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs text-red-600">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-ink/60">{{ $hint }}</p>
    @endif
</div>
