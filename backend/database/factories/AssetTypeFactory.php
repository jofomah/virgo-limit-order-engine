<?php

namespace Database\Factories;

use App\Models\AssetType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetTypeFactory extends Factory
{
    protected $model = AssetType::class;

    public function definition(): array
    {
        // Same assets as your seeder (keeps dev/test consistent with prod)
        $assets = [
            ['symbol' => 'BTC', 'name' => 'Bitcoin',  'atomic_scale' => 8],
            ['symbol' => 'ETH', 'name' => 'Ethereum', 'atomic_scale' => 18],
            ['symbol' => 'USDT', 'name' => 'Tether',  'atomic_scale' => 6],
            ['symbol' => 'BNB', 'name' => 'BNB',      'atomic_scale' => 8],
            ['symbol' => 'ADA', 'name' => 'Cardano',  'atomic_scale' => 6],
            ['symbol' => 'SOL', 'name' => 'Solana',   'atomic_scale' => 9],
        ];

        $asset = $this->faker->randomElement($assets);

        return [
            'symbol'       => $asset['symbol'],
            'name'         => $asset['name'],
            'atomic_scale' => $asset['atomic_scale'],
        ];
    }

    /**
     * State helper for custom symbols or custom scales.
     */
    public function symbol(string $symbol, ?int $scale = null, ?string $name = null): self
    {
        return $this->state(fn () => [
            'symbol'       => strtoupper($symbol),
            'name'         => $name ?? ucfirst(strtolower($symbol)),
            'atomic_scale' => $scale ?? 8,
        ]);
    }

    /**
     * Predefined asset states (matching Seeder)
     */
    public function btc(): self  { return $this->symbol('BTC', 8, 'Bitcoin'); }
    public function eth(): self  { return $this->symbol('ETH', 18, 'Ethereum'); }
    public function usdt(): self { return $this->symbol('USDT', 6, 'Tether'); }
    public function bnb(): self  { return $this->symbol('BNB', 8, 'BNB'); }
    public function ada(): self  { return $this->symbol('ADA', 6, 'Cardano'); }
    public function sol(): self  { return $this->symbol('SOL', 9, 'Solana'); }
}
