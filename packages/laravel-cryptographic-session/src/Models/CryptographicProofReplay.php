<?php

namespace CryptographicSession\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptographicProofReplay extends Model
{
    protected $table = 'cryptographic_proof_replays';

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
        $bindingModel = config('cryptographic-session.models.binding', CryptographicSessionBinding::class);

        return $this->belongsTo($bindingModel, 'binding_id');
    }
}

