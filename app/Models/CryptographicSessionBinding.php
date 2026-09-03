<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptographicSessionBinding extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'public_key',
        'algorithm',
        'curve',
        'digest',
        'revoked_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'public_key' => 'array',
        'revoked_at' => 'datetime',
    ];

    /**
     * User yang memiliki session binding ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}