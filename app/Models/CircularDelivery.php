<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircularDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'circular_id',
        'user_id',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }

    public function circular(): BelongsTo
    {
        return $this->belongsTo(Circular::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
