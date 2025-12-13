<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    public const ORDER_MATCHED_EVENT = 'OrderMatched';

    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price_numerator',
        'price_scale',
        'amount_atomic_units',
        'amount_locked_units',
        'currency',
        'status',
        'idempotency_key',
    ];

    protected $casts = [
        'amount_atomic_units' => 'integer',
        'price_numerator'     => 'integer',
        'price_scale'         => 'integer',
        'status'              => OrderStatus::class,
        'side'                => OrderSide::class,
    ];

    protected $appends = [
        'price',
        'amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assetType()
    {
        return $this->belongsTo(AssetType::class, 'symbol', 'symbol');
    }

    /**
     * Retrieve asset atomic decimals or fail.
     */
    private function getAssetDecimalsOrFail(): int
    {
        if (! $this->relationLoaded('assetType') || ! $this->assetType) {
            throw new \RuntimeException(
                "Missing 'assetType' relationship for Order ID {$this->id}. 
                Ensure you call Order::with('assetType')."
            );
        }

        $decimals = $this->assetType->atomic_scale;

        if ($decimals <= 0) {
            throw new \UnexpectedValueException(
                "Asset '{$this->symbol}' has invalid atomic scale: {$decimals}."
            );
        }

        return $decimals;
    }

    /**
     * Convert numerator/scale to decimal price.
     */
    public function getPriceAttribute(): string
    {
        $assetDecimals = $this->getAssetDecimalsOrFail();

        if ($this->price_scale <= 0) {
            throw new \UnexpectedValueException(
               "Database error: Order price_scale '{$this->price_scale}' has an invalid or zero scale. Cannot perform conversion."
            );
        }

        $baseNumber = '10';
        $divisor = bcpow($baseNumber, $this->price_scale);

        return bcdiv(
            (string) $this->price_numerator,
            $divisor,
            $assetDecimals
        );
    }

    /**
     * Convert atomic units to decimal asset amount.
     */
    public function getAmountAttribute(): string
    {
        $assetDecimals = $this->getAssetDecimalsOrFail();

        $baseNumber = '10';
        $divisor = bcpow($baseNumber, $assetDecimals);

        return bcdiv(
            (string) $this->amount_atomic_units,
            $divisor,
            $assetDecimals
        );
    }

    public function isOpen(): bool
    {
        return $this->status === OrderStatus::OPEN;
    }
}
