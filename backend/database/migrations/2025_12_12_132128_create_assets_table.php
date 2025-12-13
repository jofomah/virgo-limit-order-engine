<?php

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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Must match the column type defined in the asset_types table
            $table->string('symbol', 16);

            // Asset balances stored in atomic bigint units (satoshis, wei, etc.)
            $table->bigInteger('amount_available_atomic_units')->default(0);
            $table->bigInteger('amount_locked_atomic_units')->default(0);

            $table->timestamps();

            // Enforce that a user only has one record per symbol
            $table->unique(['user_id', 'symbol']);

            // Add the foreign key constraint, referencing the PRIMARY KEY of asset_types
            $table->foreign('symbol')
                ->references('symbol')
                ->on('asset_types')
                ->cascadeOnUpdate()   // If symbol ever changes (rare)
                ->restrictOnDelete(); // Prevent accidental deletion of an asset type
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
