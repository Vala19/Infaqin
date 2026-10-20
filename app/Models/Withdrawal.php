<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    protected $fillable = ['campaign_id', 'amount', 'description', 'withdrawn_at'];

    protected function casts(): array
    {
        return ['withdrawn_at' => 'datetime', 'amount' => 'integer'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
