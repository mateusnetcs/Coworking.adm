<?php

namespace App\Actions\Reservation;

use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateReservationAction
{
    public function execute(
        User $booker,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $coursePeriod,
        string $activity,
        array $companions,
        string $contactEmail,
        string $phone,
        ?string $institution,
        string $spaceType,
        ?array $computers,
    ): Reservation {
        if ($startsAt->greaterThanOrEqualTo($endsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => ['O horário de término deve ser depois do início.'],
            ]);
        }

        try {
            return DB::transaction(function () use (
                $booker,
                $startsAt,
                $endsAt,
                $coursePeriod,
                $activity,
                $companions,
                $contactEmail,
                $phone,
                $institution,
                $spaceType,
                $computers,
            ): Reservation {
                $this->acquireCoworkingLock();

                $this->assertNoResourceConflict($startsAt, $endsAt, $spaceType, $computers);

                return Reservation::query()->create([
                    'user_id' => $booker->id,
                    'confirmation_code' => (string) Str::uuid(),
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'course_period' => $coursePeriod,
                    'activity' => $activity,
                    'contact_email' => $contactEmail,
                    'phone' => $phone,
                    'institution' => $institution,
                    'space_type' => $spaceType,
                    'computers' => $computers,
                    'terms_accepted_at' => now(),
                    'companions' => $companions,
                ]);
            });
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? null;

            if ($sqlState === '23P01') {
                throw ValidationException::withMessages([
                    'starts_at' => ['Este horário conflita com outra reserva do mesmo recurso.'],
                ]);
            }

            throw $exception;
        }
    }

    public function assertNoResourceConflict(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        string $spaceType,
        ?array $computers,
        ?int $excludeReservationId = null,
    ): void {
        if (in_array($spaceType, [Reservation::SPACE_MEETING_ROOM, Reservation::SPACE_BOTH], true)) {
            $meetingTaken = Reservation::query()
                ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
                ->whereIn('space_type', [Reservation::SPACE_MEETING_ROOM, Reservation::SPACE_BOTH])
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($meetingTaken) {
                throw ValidationException::withMessages([
                    'space_type' => ['A sala de reunião já está reservada neste horário.'],
                ]);
            }
        }

        if (! in_array($spaceType, [Reservation::SPACE_COMPUTER, Reservation::SPACE_BOTH], true)) {
            return;
        }

        foreach ($computers ?? [] as $computerId) {
            $computerTaken = Reservation::query()
                ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
                ->whereIn('space_type', [Reservation::SPACE_COMPUTER, Reservation::SPACE_BOTH])
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->whereJsonContains('computers', (int) $computerId)
                ->exists();

            if ($computerTaken) {
                throw ValidationException::withMessages([
                    'computers' => ["O computador {$computerId} já está reservado neste horário."],
                ]);
            }
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
