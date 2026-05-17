<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Reservation\CreateReservationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminStoreReservationRequest;
use App\Http\Requests\Admin\UpdateReservationAttendanceRequest;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $query = Reservation::query()
            ->with(['booker:id,name,email'])
            ->orderByDesc('starts_at');

        if (! empty($validated['from'])) {
            $query->where('ends_at', '>', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->where('starts_at', '<', $validated['to']);
        }

        if (! empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        return response()->json($query->limit(500)->get());
    }

    public function store(
        AdminStoreReservationRequest $request,
        CreateReservationAction $action,
    ): JsonResponse {
        $booker = User::query()->findOrFail($request->validated('user_id'));

        $startsAt = CarbonImmutable::parse($request->validated('starts_at'));
        $endsAt = CarbonImmutable::parse($request->validated('ends_at'));

        $reservation = $action->execute(
            $booker,
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

        return response()->json(
            $reservation->load(['booker:id,name,email']),
            JsonResponse::HTTP_CREATED,
        );
    }

    public function updateAttendance(
        UpdateReservationAttendanceRequest $request,
        Reservation $reservation,
    ): JsonResponse {
        $attended = $request->boolean('attended');

        $reservation->attended_at = $attended ? now() : null;
        $reservation->save();

        return response()->json($reservation->load(['booker:id,name,email']));
    }

    public function destroy(Reservation $reservation): Response
    {
        $this->authorize('deleteAsAdministrator', $reservation);

        $reservation->delete();

        return response()->noContent();
    }
}
