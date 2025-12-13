<?php

use App\Enums\Currency;
use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // Link to the user who placed the order
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // The trading pair symbol (e.g., BTC, ETH). Linked to 'asset_types'
            $table->string('symbol', 16); 
            
            // Side of the order (buy or sell)
            $table->string('side', 20);
            
            // --- Price Representation (Integer-Based) ---
            // Price numerator: The full integer value of the price (e.g., 95000000)
            $table->bigInteger('price_numerator');

            // Price scale: The power of 10 used to normalize the numerator (e.g., 1000)
            $table->integer('price_scale');        
            
            // Amount of the asset being traded, stored in atomic units (satoshis, wei)
            $table->bigInteger('amount_atomic_units'); 
            
            // USD funds reserved for BUY orders, stored in cents (BIGINT)
            $table->bigInteger('amount_locked_units')->default(0);

            $table->string('currency')->default(Currency::USD->value);

            // Status: 1=open, 2=filled, 3=cancelled
            $table->tinyInteger('status')->default(OrderStatus::OPEN->value); 
            
            // Idempotency key to prevent duplicate order submissions from network retries
            $table->string('idempotency_key', 64)->nullable()->index();
            
            $table->timestamps();

            // --- Indexes for Matching Engine ---
            // Essential index for the matching job: finds open orders for a specific market, side, and price.
            $table->index(['symbol', 'side', 'status', 'price_numerator', 'created_at']);
            
            // Foreign key to the global asset types table
            $table->foreign('symbol')->references('symbol')->on('asset_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
