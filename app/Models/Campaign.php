<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    public const CATEGORIES = [
        'education' => 'Education',
        'mosque' => 'Mosque',
        'quran' => "Qur'an",
        'health' => 'Health',
        'disaster' => 'Disaster Relief',
        'orphans' => 'Orphans',
    ];

    public const STATUSES = ['active', 'completed', 'closed'];

    protected $fillable = [
        'campaigner_id', 'title', 'slug', 'category', 'image', 'summary', 'description',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
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
        return __(self::CATEGORIES[$this->category] ?? ucfirst($this->category));
    }

    public function imageUrl(): string
    {
        return $this->image ? asset($this->image) : asset('images/placeholder.svg');
    }

    public function isOpen(): bool
    {
        return $this->status === 'active'
            && ($this->deadline === null || $this->deadline->endOfDay()->isFuture());
    }
}
