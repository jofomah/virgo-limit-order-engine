<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\AssetType;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $assetType = AssetType::query()->inRandomOrder()->first()
            ?? AssetType::factory()->create();

        $decimals = $assetType->atomic_scale;

        // Atomic amount (e.g., BTC satoshis)
        $decimalAmount = $this->faker->randomFloat(6, 0.001, 3);
        $amountAtomic = bcmul($decimalAmount, bcpow('10', $decimals), 0);

        // Price scaled e.g. 40,000.00000000 with scale 8
        $priceScale = 8;
        $decimalPrice = $this->faker->randomFloat(2, 10, 60000);
        $priceNumerator = bcmul($decimalPrice, bcpow('10', $priceScale), 0);

        return [
            'user_id'             => User::factory(),
            'symbol'              => $assetType->symbol,

            'side'                => $this->faker->randomElement(OrderSide::cases()),
            'status'              => $this->faker->randomElement(OrderStatus::cases()),

            'price_numerator'     => $priceNumerator,
            'price_scale'         => $priceScale,

            'amount_atomic_units' => $amountAtomic,
            'amount_locked_units' => 0, // ALWAYS NON-NULL

            'currency'            => 'USD',
            'idempotency_key'     => Str::uuid()->toString(),
        ];
    }

    public function open(): self
    {
        return $this->state(fn() => ['status' => OrderStatus::OPEN]);
    }

    public function buy(): self
    {
        return $this->state(fn() => ['side' => OrderSide::BUY]);
    }

    public function sell(): self
    {
        return $this->state(fn() => ['side' => OrderSide::SELL]);
    }

    public function forAssetType(AssetType $assetType): self
    {
        return $this->state(fn() => ['symbol' => $assetType->symbol]);
    }

    public function forUser(User $user): self
    {
        return $this->state(fn() => ['user_id' => $user->id]);
    }
}
