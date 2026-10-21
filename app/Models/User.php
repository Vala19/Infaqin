<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * is_admin and is_active are intentionally not mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'date_of_birth',
        'phone',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function amenedComments(): BelongsToMany
    {
        return $this->belongsToMany(Comment::class, 'comment_amens')->withPivot('created_at');
    }

    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'wishlists')->withTimestamps();
    }

    /**
     * IDs of the campaigns this user saved, used to fill the heart on campaign cards.
     *
     * @return list<int>
     */
    public function wishlistIds(): array
    {
        return $this->wishlist()->pluck('campaigns.id')->all();
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    /**
     * Consecutive weeks (ending this week or last week) with at least one paid donation.
     */
    public function kindnessStreak(): int
    {
        $weeks = $this->donations()
            ->where('status', Donation::STATUS_PAID)
            ->pluck('paid_at')
            ->filter()
            ->map(fn ($date) => $date->copy()->startOfWeek()->toDateString())
            ->unique()
            ->flip();

        $cursor = now()->startOfWeek();

        if (! $weeks->has($cursor->toDateString())) {
            $cursor->subWeek();
        }

        $streak = 0;

        while ($weeks->has($cursor->toDateString())) {
            $streak++;
            $cursor->subWeek();
        }

        return $streak;
    }
}
