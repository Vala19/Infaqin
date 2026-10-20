<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Comment extends Model
{
    protected $fillable = ['body'];

    protected function casts(): array
    {
        return ['amens_count' => 'integer'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function amenUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'comment_amens')->withPivot('created_at');
    }

    public function authorName(): string
    {
        return $this->donation?->is_anonymous ? 'Hamba Allah' : ($this->user?->name ?? __('Donor'));
    }

    public function authorInitials(): string
    {
        return $this->donation?->is_anonymous ? 'HA' : ($this->user?->initials() ?? 'D');
    }
}
