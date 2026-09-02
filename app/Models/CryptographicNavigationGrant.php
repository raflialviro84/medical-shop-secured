<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryptographicNavigationGrant extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'session_id',
        'binding_id',
        'method',
        'path',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}