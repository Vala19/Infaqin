<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Donation extends Model
{
    public const STATUS_PAID = 'paid';

    public const STATUS_PENDING = 'pending';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [self::STATUS_PAID, self::STATUS_PENDING, self::STATUS_EXPIRED, self::STATUS_FAILED];

    /**
     * UI labels (translation keys) per status.
     */
    public const STATUS_LABELS = [
        self::STATUS_PAID => 'Success',
        self::STATUS_PENDING => 'Waiting payment',
        self::STATUS_EXPIRED => 'Expired',
        self::STATUS_FAILED => 'Cancelled',
    ];

    /**
     * Keys are Midtrans payment types; bank transfers become "{bank}_va" (see DonationStatusUpdater::paymentMethod()).
     */
    public const PAYMENT_METHODS = [
        'qris' => 'QRIS',
        'gopay' => 'GoPay',
        'shopeepay' => 'ShopeePay',
        'ovo' => 'OVO',
        'dana' => 'DANA',
        'bca_va' => 'BCA Virtual Account',
        'mandiri_va' => 'Mandiri Virtual Account',
        'bni_va' => 'BNI Virtual Account',
        'bri_va' => 'BRI Virtual Account',
        'permata_va' => 'Permata Virtual Account',
        'cimb_va' => 'CIMB Niaga Virtual Account',
        'credit_card' => 'Credit/debit card',
        'cstore' => 'Indomaret/Alfamart',
        'akulaku' => 'Akulaku',
        'kredivo' => 'Kredivo',
    ];

    public const MIN_AMOUNT = 10_000;

    public const MAX_AMOUNT = 1_000_000_000;

    public const PAYMENT_WINDOW_HOURS = 24;

    protected $fillable = [
        'campaign_id', 'amount', 'donor_name', 'email', 'payment_method',
        'is_anonymous', 'on_behalf_of',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_anonymous' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Donation $donation) {
            $donation->reference ??= self::generateReference();
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'INF-'.strtoupper(Str::random(10));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function comment(): HasOne
    {
        return $this->hasOne(Comment::class);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('created_at', '<', now()->subHours(self::PAYMENT_WINDOW_HOURS));
    }

    public function expiresAt(): ?Carbon
    {
        return $this->created_at?->copy()->addHours(self::PAYMENT_WINDOW_HOURS);
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->expiresAt()?->isPast();
    }

    public function publicName(): string
    {
        return $this->is_anonymous ? 'Hamba Allah' : $this->donor_name;
    }

    public function paymentMethodLabel(): string
    {
        if (! $this->payment_method) {
            return '—';
        }

        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }
}
