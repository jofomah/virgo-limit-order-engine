<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\User;
use App\Models\AssetType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        // Choose or create a valid AssetType (BTC, ETH, etc.)
        $assetType = AssetType::query()->inRandomOrder()->first()
            ?? AssetType::factory()->create();

        $symbol = $assetType->symbol;
        $decimals = $assetType->atomic_scale;

        // Generate available balance in atomic units
        $decimalAmount = $this->faker->randomFloat(6, 0.001, 5);
        $availableAtomic = bcmul($decimalAmount, bcpow('10', $decimals), 0);

        // Generate locked amount (less than available)
        $lockedAtomic = (int) ($availableAtomic * 0.2);

        return [
            'user_id'                     => User::factory(),
            'symbol'                      => $symbol,

            'amount_available_atomic_units' => $availableAtomic,
            'amount_locked_atomic_units'    => $lockedAtomic,
        ];
    }

    // ---------------------------------
    // Custom Helpers for Test Control
    // ---------------------------------

    public function symbol(string $symbol): self
    {
        return $this->state(fn () => [
            'symbol' => strtoupper($symbol),
        ]);
    }

    public function forUser(User $user): self
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    public function available(int $atomicUnits): self
    {
        return $this->state(fn () => [
            'amount_available_atomic_units' => $atomicUnits,
        ]);
    }

    public function locked(int $atomicUnits): self
    {
        return $this->state(fn () => [
            'amount_locked_atomic_units' => $atomicUnits,
        ]);
    }

    public function zero(): self
    {
        return $this->state(fn () => [
            'amount_available_atomic_units' => 0,
            'amount_locked_atomic_units'    => 0,
        ]);
    }
}
