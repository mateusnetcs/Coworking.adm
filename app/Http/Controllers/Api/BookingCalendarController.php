<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingCalendarController extends Controller
{
    public function day(Request $request, BookingCalendarService $calendar): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = CarbonImmutable::parse($validated['date'], config('app.timezone'))->startOfDay();

        return response()->json($calendar->dayStatus($date));
    }
}
