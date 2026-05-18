<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Booking\CloseBookingDayAction;
use App\Http\Controllers\Controller;
use App\Models\ClosedBookingDay;
use App\Services\BookingCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingCalendarController extends Controller
{
    public function closeDay(Request $request, CloseBookingDayAction $action): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:3', 'max:120'],
            'cancel_reservations' => ['sometimes', 'boolean'],
            'block_new_reservations' => ['sometimes', 'boolean'],
        ]);

        $date = CarbonImmutable::parse($validated['date'], config('app.timezone'))->startOfDay();

        $result = $action->execute(
            $date,
            trim($validated['reason']),
            $request->user(),
            $request->boolean('cancel_reservations', true),
            $request->boolean('block_new_reservations', true),
        );

        $calendar = app(BookingCalendarService::class);

        return response()->json([
            ...$result,
            'day' => $calendar->dayStatus($date),
        ]);
    }

    public function reopenDay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = CarbonImmutable::parse($validated['date'], config('app.timezone'))->startOfDay();
        $deleted = ClosedBookingDay::query()
            ->where('date', $date->toDateString())
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'message' => 'Este dia não estava fechado manualmente pelo administrador.',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        BookingCalendarService::forgetClosedDaysCache();

        $calendar = app(BookingCalendarService::class);

        return response()->json([
            'date' => $date->toDateString(),
            'reopened' => true,
            'day' => $calendar->dayStatus($date),
        ]);
    }

    public function daySummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = CarbonImmutable::parse($validated['date'], config('app.timezone'))->startOfDay();
        $dayEnd = $date->endOfDay();

        $reservationsCount = \App\Models\Reservation::query()
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $date)
            ->count();

        $calendar = app(BookingCalendarService::class);
        $status = $calendar->dayStatus($date);

        return response()->json([
            'reservations_count' => $reservationsCount,
            'day' => $status,
        ]);
    }
}
