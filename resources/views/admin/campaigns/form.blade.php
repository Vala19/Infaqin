@php($editing = $campaign->exists)

<x-layouts.admin :title="$editing ? 'Edit campaign' : 'New campaign'">
    <a href="{{ route('admin.campaigns.index') }}" class="inline-flex items-center gap-1.5 text-sm text-ink/70 hover:text-brand"><x-icon name="arrow-left" class="size-4" /> Back to campaigns</a>

    <form method="POST" action="{{ $editing ? route('admin.campaigns.update', $campaign) : route('admin.campaigns.store') }}" enctype="multipart/form-data"
          class="mt-4 grid gap-6 xl:grid-cols-[1fr_340px]" x-data="{ preview: @js($editing ? $campaign->imageUrl() : null) }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card space-y-5 p-6">
            <x-field name="title" label="Title" :value="$campaign->title" required maxlength="200" />
            <div>
                <label for="summary" class="label">Summary</label>
                <textarea id="summary" name="summary" rows="2" maxlength="300" required class="input @error('summary') input-error @enderror">{{ old('summary', $campaign->summary) }}</textarea>
                @error('summary') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="description" class="label">Story</label>
                <textarea id="description" name="description" rows="12" required class="input leading-relaxed @error('description') input-error @enderror">{{ old('description', $campaign->description) }}</textarea>
                @error('description') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @else <p class="mt-1.5 text-xs text-ink/60">Plain text. Separate paragraphs with a blank line.</p> @enderror
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="target_amount" label="Target (Rp)" type="number" :value="$campaign->target_amount" min="100000" step="1000" required />
                <x-field name="location" label="Location" :value="$campaign->location" maxlength="120" />
            </div>
        </div>

        <div class="space-y-6">
            <div class="card space-y-5 p-6">
                <div>
                    <label for="campaigner_id" class="label">Campaigner</label>
                    <select id="campaigner_id" name="campaigner_id" class="input" required>
                        @foreach ($campaigners as $c)
                            <option value="{{ $c->id }}" @selected((int) old('campaigner_id', $campaign->campaigner_id) === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="category_id" class="label">Category</label>
                    <select id="category_id" name="category_id" class="input">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $campaign->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="status" class="label">Status</label>
                    <select id="status" name="status" class="input">
                        @foreach (\App\Models\Campaign::STATUSES as $status)
                            <option value="{{ $status }}" @selected(old('status', $campaign->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <x-field name="deadline" label="Deadline" type="date" :value="$campaign->deadline?->toDateString()" />
                <x-toggle name="is_urgent" label="Mark as urgent" :checked="(bool) old('is_urgent', $campaign->is_urgent)" />
                <x-toggle name="is_featured" label="Feature in hero carousel" :checked="(bool) old('is_featured', $campaign->is_featured)" />
            </div>

            <div class="card p-6">
                <label for="image" class="label">Campaign image</label>
                <div class="mt-2 aspect-[361/310] overflow-hidden rounded-xl bg-paper ring-1 ring-mist">
                    <img x-show="preview" :src="preview" alt="" class="size-full object-cover">
                    <div x-show="!preview" class="grid size-full place-items-center text-xs text-ink/50">No image — placeholder will be used</div>
                </div>
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp"
                       @change="const f = $event.target.files[0]; if (f) preview = URL.createObjectURL(f)"
                       class="mt-3 block w-full text-sm text-ink file:mr-3 file:rounded-lg file:border-0 file:bg-mint-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-forest hover:file:bg-mint/40">
                @error('image') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @else <p class="mt-1.5 text-xs text-ink/60">JPG, PNG or WebP, max 2 MB.</p> @enderror
            </div>

            <button class="btn-primary h-12 w-full">{{ $editing ? 'Save changes' : 'Create campaign' }}</button>
        </div>
    </form>

    @if ($editing)
        {{-- Open Fund Ledger management --}}
        <section class="mt-10 card p-6" aria-labelledby="ledger-title">
            <h2 id="ledger-title" class="flex items-center gap-2 text-lg font-semibold text-night"><x-figma-icon name="withdraw" class="size-6" /> Open Fund Ledger</h2>
            <p class="mt-1 text-sm text-ink/70">
                Collected {{ rupiah($campaign->collected_amount) }} · withdrawn {{ rupiah($campaign->withdrawals->sum('amount')) }} ·
                available <strong class="text-night">{{ rupiah($campaign->collected_amount - $campaign->withdrawals->sum('amount')) }}</strong>. Every entry is shown publicly.
            </p>

            <form method="POST" action="{{ route('admin.withdrawals.store', $campaign) }}" class="mt-5 grid gap-4 rounded-xl bg-paper p-4 md:grid-cols-[180px_1fr_200px_auto] md:items-end">
                @csrf
                <x-field name="amount" label="Amount (Rp)" type="number" min="1" bag="withdrawal" required />
                <x-field name="description" label="Purpose" maxlength="255" bag="withdrawal" required placeholder="e.g. Purchase of 500 Qur'ans" />
                <x-field name="withdrawn_at" label="Date" type="datetime-local" bag="withdrawal" :value="now()->format('Y-m-d\TH:i')" required />
                <button class="btn-primary h-[42px]">Record</button>
            </form>

            <ul class="mt-5 divide-y divide-mist/60">
                @forelse ($campaign->withdrawals as $w)
                    <li class="flex items-center gap-4 py-3 text-sm">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-night">{{ $w->description }}</p>
                            <p class="text-xs text-ink/60">{{ $w->withdrawn_at->format('d M Y, H:i') }}</p>
                        </div>
                        <span class="font-semibold text-forest">{{ rupiah($w->amount) }}</span>
                        <form method="POST" action="{{ route('admin.withdrawals.destroy', $w) }}" onsubmit="return confirm('Remove this ledger entry?')">
                            @csrf @method('DELETE')
                            <button class="grid size-9 place-items-center rounded-lg text-ink/60 hover:bg-red-50 hover:text-red-600" aria-label="Remove"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </li>
                @empty
                    <li class="py-4 text-sm text-ink/60">No withdrawals recorded yet.</li>
                @endforelse
            </ul>
        </section>
    @endif
</x-layouts.admin>
