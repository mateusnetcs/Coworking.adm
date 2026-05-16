<?php

namespace App\Http\Controllers\Api;

use App\Actions\Reservation\CreateReservationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after:from'],
        ]);

        $reservations = Reservation::query()
            ->with(['booker:id,name,email'])
            ->where('starts_at', '<', $validated['to'])
            ->where('ends_at', '>', $validated['from'])
            ->orderBy('starts_at')
            ->get();

        return response()->json($reservations);
    }

    public function store(StoreReservationRequest $request, CreateReservationAction $action): JsonResponse
    {
        $startsAt = CarbonImmutable::parse($request->validated('starts_at'));
        $endsAt = CarbonImmutable::parse($request->validated('ends_at'));
        $companions = $request->validated('companions') ?? [];

        $reservation = $action->execute(
            $request->user(),
            $startsAt,
            $endsAt,
            (int) $request->validated('course_period'),
            $request->validated('activity'),
            $companions,
        );

        return response()->json($reservation->load(['booker:id,name,email']), JsonResponse::HTTP_CREATED);
    }

    public function destroy(Reservation $reservation): Response
    {
        $reservation->delete();

        return response()->noContent();
    }
}
