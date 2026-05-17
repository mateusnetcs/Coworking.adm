<?php

namespace App\Actions\Reservation;

use App\Models\Reservation;
use App\Services\ReservationRulesService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateReservationAction
{
    public function __construct(
        private readonly CreateReservationAction $createAction,
        private readonly ReservationRulesService $rules,
    ) {}

    public function execute(
        Reservation $reservation,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        int $coursePeriod,
        string $activity,
        array $companions,
        string $contactEmail,
        string $phone,
        string $institution,
        string $spaceType,
        ?array $computers,
    ): Reservation {
        if (! $this->rules->canEdit($reservation)) {
            throw ValidationException::withMessages([
                'starts_at' => ['O prazo de '.$this->rules->editWindowLabel().' para editar esta reserva expirou.'],
            ]);
        }

        if ($startsAt->greaterThanOrEqualTo($endsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => ['O horário de término deve ser depois do início.'],
            ]);
        }

        return DB::transaction(function () use (
            $reservation,
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
            $this->createAction->assertNoResourceConflict(
                $startsAt,
                $endsAt,
                $spaceType,
                $computers,
                $reservation->id,
            );

            $reservation->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'course_period' => $coursePeriod,
                'activity' => $activity,
                'contact_email' => $contactEmail,
                'phone' => $phone,
                'institution' => $institution,
                'space_type' => $spaceType,
                'computers' => $computers,
                'companions' => $companions,
            ]);

            return $reservation->fresh(['booker:id,name,email']);
        });
    }
}
