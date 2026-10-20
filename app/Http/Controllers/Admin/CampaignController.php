<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Campaigner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->limit(100)->toString();

        $campaigns = Campaign::with('campaigner')
            ->withCount('withdrawals')
            ->when($term, fn ($query) => $query->where('title', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.campaigns.index', compact('campaigns', 'term'));
    }

    public function create(): View
    {
        return view('admin.campaigns.form', [
            'campaign' => new Campaign(['status' => 'active', 'category' => 'education']),
            'campaigners' => Campaigner::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $campaign = new Campaign($data);
        $campaign->slug = $this->uniqueSlug($data['title']);
        $campaign->image = $this->storeImage($request);
        $campaign->save();

        return redirect()->route('admin.campaigns.edit', $campaign)->with('status', 'Campaign created.');
    }

    public function edit(Campaign $campaign): View
    {
        $campaign->load(['withdrawals' => fn ($q) => $q->latest('withdrawn_at')]);

        return view('admin.campaigns.form', [
            'campaign' => $campaign,
            'campaigners' => Campaigner::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['title'] !== $campaign->title) {
            $campaign->slug = $this->uniqueSlug($data['title'], $campaign->id);
        }

        if ($image = $this->storeImage($request)) {
            $this->deleteImage($campaign->image);
            $campaign->image = $image;
        }

        $campaign->fill($data)->save();

        return redirect()->route('admin.campaigns.edit', $campaign)->with('status', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->deleteImage($campaign->image);
        $campaign->delete();

        return redirect()->route('admin.campaigns.index')->with('status', 'Campaign deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'campaigner_id' => ['required', 'exists:campaigners,id'],
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::in(array_keys(Campaign::CATEGORIES))],
            'summary' => ['required', 'string', 'max:300'],
            'description' => ['required', 'string', 'max:20000'],
            'location' => ['nullable', 'string', 'max:120'],
            'target_amount' => ['required', 'integer', 'min:100000', 'max:100000000000'],
            'deadline' => ['nullable', 'date'],
            'status' => ['required', Rule::in(Campaign::STATUSES)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        unset($data['image']);
        $data['is_urgent'] = $request->boolean('is_urgent');
        $data['is_featured'] = $request->boolean('is_featured');

        return array_map(fn ($value) => is_string($value) ? strip_tags($value) : $value, $data);
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return 'storage/'.$request->file('image')->store('campaigns', 'public');
    }

    private function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'campaign';
        $slug = $base;
        $i = 2;

        while (Campaign::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
