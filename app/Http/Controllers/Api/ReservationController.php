<?php

namespace App\Http\Controllers\Api;

use App\Actions\Reservation\CreateReservationAction;
use App\Actions\Reservation\UpdateReservationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationRulesService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

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

        return ReservationResource::collection($reservations)->response();
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
            $request->validated('contact_email'),
            $request->validated('phone'),
            $request->validated('institution'),
            $request->validated('space_type'),
            $request->validated('computers') ?: null,
        );

        return (new ReservationResource($reservation->load(['booker:id,name,email'])))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(
        UpdateReservationRequest $request,
        Reservation $reservation,
        UpdateReservationAction $action,
    ): JsonResponse {
        $this->authorize('updateAsOwner', $reservation);

        $startsAt = CarbonImmutable::parse($request->validated('starts_at'));
        $endsAt = CarbonImmutable::parse($request->validated('ends_at'));

        $reservation = $action->execute(
            $reservation,
            $startsAt,
            $endsAt,
            (int) $request->validated('course_period'),
            $request->validated('activity'),
            $request->validated('companions') ?? [],
            $request->validated('contact_email'),
            $request->validated('phone'),
            $request->validated('institution'),
            $request->validated('space_type'),
            $request->validated('computers') ?: null,
        );

        return (new ReservationResource($reservation))->response();
    }

    public function destroy(Request $request, Reservation $reservation, ReservationRulesService $rules): Response
    {
        $this->authorize('deleteAsOwner', $reservation);

        if (! $rules->canCancel($reservation, $request->user())) {
            throw ValidationException::withMessages([
                'reservation' => [$rules->cancelBlockedReason($reservation, $request->user()) ?? 'Não é possível cancelar esta reserva.'],
            ]);
        }

        $reservation->delete();

        return response()->noContent();
    }
}
