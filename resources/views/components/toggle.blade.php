@props(['name', 'checked' => false, 'label'])

{{-- On/Off switch from the Figma "Intractable" sheet. --}}
<label class="flex cursor-pointer items-center justify-between gap-3" x-data="{ on: @js((bool) $checked) }">
    <span class="text-sm text-ink">{{ $label }}</span>
    <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" x-model="on" {{ $attributes }} @checked($checked)>
    <span class="relative h-6 w-[54px] shrink-0 rounded-full transition peer-focus-visible:ring-2 peer-focus-visible:ring-brand peer-focus-visible:ring-offset-2"
          :class="on ? 'bg-brand' : 'bg-mist'">
        <span class="absolute top-0.5 size-5 rounded-full bg-white shadow transition-all" :class="on ? 'left-[31px]' : 'left-0.5'"></span>
        <span class="absolute top-1/2 -translate-y-1/2 text-[10px] font-medium" :class="on ? 'left-2 text-white' : 'right-2 text-ink/70'" x-text="on ? @js(__('On')) : @js(__('Off'))"></span>
    </span>
</label>
