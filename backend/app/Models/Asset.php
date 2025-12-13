<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'symbol',
        'amount_available_atomic_units',
        'amount_locked_atomic_units',
    ];

    protected $casts = [
        'amount_available_atomic_units' => 'integer',
        'amount_locked_atomic_units' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
