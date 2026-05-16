<?php

namespace App\Actions\Reservation;

use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateReservationAction
{
    public function execute(
        User $booker,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        int $coursePeriod,
        string $activity,
        array $companions,
    ): Reservation {
        if ($startsAt->greaterThanOrEqualTo($endsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => ['The end time must be after the start time.'],
            ]);
        }

        try {
            return DB::transaction(function () use ($booker, $startsAt, $endsAt, $coursePeriod, $activity, $companions): Reservation {
                $this->acquireCoworkingLock();

                $overlapExists = Reservation::query()
                    ->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt)
                    ->exists();

                if ($overlapExists) {
                    throw ValidationException::withMessages([
                        'starts_at' => ['This interval overlaps an existing reservation.'],
                    ]);
                }

                return Reservation::query()->create([
                    'user_id' => $booker->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'course_period' => $coursePeriod,
                    'activity' => $activity,
                    'companions' => $companions,
                ]);
            });
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? null;

            if ($sqlState === '23P01') {
                throw ValidationException::withMessages([
                    'starts_at' => ['This interval overlaps an existing reservation.'],
                ]);
            }

            throw $exception;
        }
    }

    private function acquireCoworkingLock(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::selectOne('SELECT pg_advisory_xact_lock(?)', [crc32('coworking_uemasul_single_space')]);

            return;
        }

        if ($driver === 'mysql') {
            DB::selectOne('SELECT GET_LOCK(?, 10)', ['coworking_uemasul_single_space']);
        }
    }
}
