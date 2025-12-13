<?php

use App\Enums\Currency;
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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            
            // --- References to the Orders ---
            // The two orders involved in the trade. Cascade ensures the trade record is removed if an order is deleted (rare, but defensive).
            $table->foreignId('buy_order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('sell_order_id')->constrained('orders')->cascadeOnDelete();

            // --- Trade Details ---
            $table->string('symbol', 16);
            
            // The quantity of the asset transferred, stored in atomic units
            $table->bigInteger('amount_atomic_units'); 
            
            // The total fiat value of the transaction, stored in cents (BIGINT)
            $table->bigInteger('volume_units');

            $table->string('currency')->default(Currency::USD->value);
            
            // The platform fee charged on this transaction, stored in cents (BIGINT)
            $table->bigInteger('commission_fee_units'); 

            $table->timestamps();

            // --- Indexes and Constraints ---
            // Indexing the symbol for quick lookup of market activity
            $table->index('symbol');
            
            // Indexing the order IDs for quick retrieval of trades belonging to a specific order
            $table->index('buy_order_id');
            $table->index('sell_order_id');
            
            // Foreign key to the global asset types table, ensuring only valid symbols are recorded
            $table->foreign('symbol')->references('symbol')->on('asset_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};