<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaigner extends Model
{
    protected $fillable = ['name', 'slug', 'avatar', 'bio', 'location', 'is_verified', 'is_internal'];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean', 'is_internal' => 'boolean'];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function avatarUrl(): string
    {
        return $this->avatar ? asset($this->avatar) : asset('images/logo/icon-green-tile.png');
    }
}
