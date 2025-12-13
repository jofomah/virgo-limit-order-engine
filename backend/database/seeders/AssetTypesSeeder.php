<?php

namespace Database\Seeders;

use App\Models\AssetType;
use Illuminate\Database\Seeder;

class AssetTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $this->command->line("Seeding Asset Types" . PHP_EOL);
        // Use upsert for idempotency (safe to run multiple times)
        AssetType::upsert([
            // BTC: scale 8 (Satoshis)
            ['symbol' => 'BTC', 'atomic_scale' => 8, 'name' => 'Bitcoin'],
            
            // ETH: scale 18 (Wei)
            ['symbol' => 'ETH', 'atomic_scale' => 18, 'name' => 'Ethereum'],
            
            // USDT: scale 6 (Standard for many stablecoins)
            ['symbol' => 'USDT', 'atomic_scale' => 6, 'name' => 'Tether'],
            
            // BNB: scale 8 (Standard for many cryptos)
            ['symbol' => 'BNB', 'atomic_scale' => 8, 'name' => 'BNB'],
            
            // ADA: scale 6 
            ['symbol' => 'ADA', 'atomic_scale' => 6, 'name' => 'Cardano'],
            
            // SOL: scale 9 (Solana's native token)
            ['symbol' => 'SOL', 'atomic_scale' => 9, 'name' => 'Solana'],
        ], 
        ['symbol'], // Unique by symbol
        ['atomic_scale', 'name', 'updated_at']); // Fields to update on collision

        $this->command->line("Seeding of Asset Types completed!" . PHP_EOL);
    }
}