<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\ReservationConfirmationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReservationConfirmationController extends Controller
{
    public function verify(string $code, ReservationConfirmationService $confirmation): View
    {
        $reservation = $confirmation->findByCode($code);

        $attendance = $reservation
            ? $confirmation->attendanceContext($reservation)
            : null;

        return view('comprovante.verify', [
            'valid' => $reservation !== null,
            'reservation' => $reservation,
            'code' => $code,
            'attendance' => $attendance,
        ]);
    }

    public function markAttendance(string $code, ReservationConfirmationService $confirmation): RedirectResponse
    {
        $reservation = $confirmation->findByCode($code);

        if ($reservation === null) {
            return redirect()
                ->route('reservation.verify', ['code' => $code])
                ->with('attendance_error', 'Comprovante não encontrado.');
        }

        try {
            $confirmation->markAttendance($reservation);
        } catch (\InvalidArgumentException $exception) {
            return redirect()
                ->route('reservation.verify', ['code' => $code])
                ->with('attendance_error', $exception->getMessage());
        }

        return redirect()
            ->route('reservation.verify', ['code' => $code])
            ->with('attendance_success', 'Presença registrada com sucesso!');
    }

    public function downloadPdf(
        Request $request,
        Reservation $reservation,
        ReservationConfirmationService $confirmation,
    ): Response {
        if ($request->user()->id !== $reservation->user_id && ! $request->user()->isAdministrator()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $binary = $confirmation->pdfBinary($reservation);
        $filename = 'comprovante-coworking-'.$reservation->id.'.pdf';

        return response($binary, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}
