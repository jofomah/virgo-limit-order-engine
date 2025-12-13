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
        Schema::table('users', function (Blueprint $table) {
            // Monetary fields stored in bigint (atomic cents)
            $table->string('currency')->default(Currency::USD->value);
            $table->bigInteger('balance_available_units')->default(0);
            $table->bigInteger('balance_locked_units')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['currency', 'balance_available_units', 'balance_locked_units']);
            });
        });
    }
};
