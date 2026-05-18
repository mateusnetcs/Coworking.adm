<?php

namespace App\Actions\Booking;

use App\Models\ClosedBookingDay;
use App\Models\Reservation;
use App\Models\User;
use App\Services\BookingCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CloseBookingDayAction
{
    /**
     * @return array{
     *     date: string,
     *     reason: string,
     *     cancelled_reservations: int,
     *     blocked: bool
     * }
     */
    public function execute(
        CarbonImmutable $date,
        string $reason,
        User $closedBy,
        bool $cancelReservations = true,
        bool $blockNewReservations = true,
    ): array {
        $timezone = config('app.timezone');
        $day = $date->timezone($timezone)->startOfDay();
        $dayEnd = $day->endOfDay();

        return DB::transaction(function () use ($day, $dayEnd, $reason, $closedBy, $cancelReservations, $blockNewReservations) {
            $cancelledCount = 0;

            if ($cancelReservations) {
                $cancelledCount = Reservation::query()
                    ->where('starts_at', '<', $dayEnd)
                    ->where('ends_at', '>', $day)
                    ->delete();
            }

            if ($blockNewReservations) {
                ClosedBookingDay::query()->updateOrCreate(
                    ['date' => $day->toDateString()],
                    [
                        'reason' => $reason,
                        'closed_by_user_id' => $closedBy->id,
                    ],
                );

                BookingCalendarService::forgetClosedDaysCache();
            }

            return [
                'date' => $day->toDateString(),
                'reason' => $reason,
                'cancelled_reservations' => $cancelledCount,
                'blocked' => $blockNewReservations,
            ];
        });
    }
}
