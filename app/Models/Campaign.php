<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_CLOSED];

    /**
     * Statuses shown on public pages; drafts are only visible in the admin panel.
     */
    public const PUBLIC_STATUSES = [self::STATUS_ACTIVE, self::STATUS_CLOSED];

    /**
     * Public type filter: programmes run by Syaamil (internal campaigner) or partner campaigns.
     */
    public const TYPE_PROGRAM = 'program';

    public const TYPE_CAMPAIGN = 'campaign';

    public const TYPES = [self::TYPE_PROGRAM, self::TYPE_CAMPAIGN];

    protected $fillable = [
        'campaigner_id', 'category_id', 'title', 'slug', 'image', 'summary', 'description',
        'location', 'target_amount', 'deadline', 'is_urgent', 'is_featured', 'status',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'is_urgent' => 'boolean',
            'is_featured' => 'boolean',
            'target_amount' => 'integer',
            'collected_amount' => 'integer',
            'donor_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function campaigner(): BelongsTo
    {
        return $this->belongsTo(Campaigner::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    public function wishlistedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlists')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', self::PUBLIC_STATUSES);
    }

    /**
     * Active campaigns whose deadline day has fully passed (closed by campaigns:close).
     */
    public function scopePastDeadline(Builder $query): Builder
    {
        return $query->active()->whereDate('deadline', '<', today());
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->whereHas('campaigner', fn ($c) => $c->where('is_internal', $type === self::TYPE_PROGRAM));
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function progress(): int
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return (int) min(100, floor($this->collected_amount / $this->target_amount * 100));
    }

    public function daysLeft(): ?int
    {
        if (! $this->deadline) {
            return null;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->deadline, false));
    }

    public function categoryLabel(): string
    {
        return $this->category ? __($this->category->name) : '';
    }

    public function isProgram(): bool
    {
        return (bool) $this->campaigner->is_internal;
    }

    public function typeLabel(): string
    {
        return $this->isProgram() ? __('Program') : __('Campaign');
    }

    public function imageUrl(): string
    {
        return $this->image ? asset($this->image) : asset('images/placeholder.svg');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && ($this->deadline === null || $this->deadline->endOfDay()->isFuture());
    }
}
