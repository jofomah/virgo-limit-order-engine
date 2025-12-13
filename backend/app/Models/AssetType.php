<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetType extends Model
{
    use HasFactory;

    protected $primaryKey = 'symbol';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'symbol',
        'atomic_scale',
        'name',
    ];

    protected $casts = [
        'atomic_scale' => 'integer',
    ];
}
