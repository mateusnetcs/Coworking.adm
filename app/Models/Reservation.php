<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    public const SPACE_COMPUTER = 'computer';

    public const SPACE_MEETING_ROOM = 'meeting_room';

    public const SPACE_BOTH = 'both';

    protected $fillable = [
        'user_id',
        'starts_at',
        'ends_at',
        'course_period',
        'activity',
        'contact_email',
        'phone',
        'institution',
        'space_type',
        'computers',
        'terms_accepted_at',
        'companions',
        'attended_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'attended_at' => 'immutable_datetime',
            'terms_accepted_at' => 'immutable_datetime',
            'companions' => 'array',
            'computers' => 'array',
        ];
    }

    public function isAttended(): bool
    {
        return $this->attended_at !== null;
    }

    public function usesMeetingRoom(): bool
    {
        return in_array($this->space_type, [self::SPACE_MEETING_ROOM, self::SPACE_BOTH], true);
    }

    public function usesComputers(): bool
    {
        return in_array($this->space_type, [self::SPACE_COMPUTER, self::SPACE_BOTH], true);
    }

    public function spaceLabel(): string
    {
        $labels = config('coworking.space_types', []);
        $base = $labels[$this->space_type] ?? $this->space_type;

        if ($this->usesComputers() && ! empty($this->computers)) {
            $pcs = collect($this->computers)
                ->sort()
                ->map(static fn (int $n): string => "Computador {$n}")
                ->implode(', ');

            return $this->space_type === self::SPACE_COMPUTER
                ? $pcs
                : "{$base} ({$pcs})";
        }

        return $base;
    }

    public function booker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
