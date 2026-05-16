<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'starts_at',
        'ends_at',
        'course_period',
        'activity',
        'companions',
        'attended_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'attended_at' => 'immutable_datetime',
            'companions' => 'array',
        ];
    }

    public function isAttended(): bool
    {
        return $this->attended_at !== null;
    }

    public function booker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
