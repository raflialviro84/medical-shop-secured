<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'total_price',
        'timestamp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_price' => 'decimal:2',
        'timestamp' => 'datetime',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction details for the transaction.
     */
    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    /**
     * Check if the transaction can be paid.
     */
    public function canBePaid()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the transaction can be shipped.
     */
    public function canBeShipped()
    {
        return $this->status === 'paid';
    }

    /**
     * Check if the transaction can be marked as done.
     */
    public function canBeMarkedAsDone()
    {
        return $this->status === 'shipped';
    }
}
