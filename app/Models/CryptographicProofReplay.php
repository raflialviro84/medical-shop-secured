<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptographicProofReplay extends Model
{
    protected $fillable = [
        'jti',
        'binding_id',
        'issued_at',
        'expires_at',
    ];

    protected $casts = [
        'binding_id' => 'integer',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function binding(): BelongsTo
    {
        return $this->belongsTo(
            CryptographicSessionBinding::class,
            'binding_id'
        );
    }
}