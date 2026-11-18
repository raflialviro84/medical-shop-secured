<?php

namespace CryptographicSession\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptographicSessionBinding extends Model
{
    protected $table = 'cryptographic_session_bindings';

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
        'public_key' => 'array',
        'revoked_at' => 'datetime',
    ];

    /**
     * User yang memiliki session binding ini.
     */
    public function user(): BelongsTo
    {
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');

        return $this->belongsTo($userModel, 'user_id');
    }

    /**
     * Daftar replay records untuk binding ini.
     */
    public function replays(): HasMany
    {
        $replayModel = config('cryptographic-session.models.replay', CryptographicProofReplay::class);

        return $this->hasMany($replayModel, 'binding_id');
    }

    /**
     * Scope untuk binding aktif yang belum di-revoke.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }
}

