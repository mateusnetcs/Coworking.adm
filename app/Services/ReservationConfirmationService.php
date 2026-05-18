<?php

namespace App\Services;

use App\Models\Reservation;
use App\Support\PublicAppUrl;
use App\Support\QrCodeGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class ReservationConfirmationService
{
    public function ensureConfirmationCode(Reservation $reservation): Reservation
    {
        if (is_string($reservation->confirmation_code) && $reservation->confirmation_code !== '') {
            return $reservation;
        }

        $reservation->confirmation_code = (string) Str::uuid();
        $reservation->save();

        return $reservation->fresh(['booker:id,name,email']);
    }

    public function verificationUrl(Reservation $reservation): string
    {
        $reservation = $this->ensureConfirmationCode($reservation);

        return PublicAppUrl::base().'/comprovante/'.$reservation->confirmation_code;
    }

    /**
     * @return array{base64: ?string, html: string}
     */
    public function qrCodeForPdf(Reservation $reservation): array
    {
        $url = $this->verificationUrl($reservation);
        $base64 = QrCodeGenerator::tryPngBase64($url, 220, 1, 'M');

        return [
            'base64' => $base64,
            'html' => $base64 === null ? QrCodeGenerator::toHtmlTable($url, 180, 1, 'M') : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(Reservation $reservation): array
    {
        $reservation = $this->ensureConfirmationCode($reservation->loadMissing('booker:id,name,email'));
        $timezone = config('app.timezone');
        $startsAt = CarbonImmutable::parse($reservation->starts_at)->timezone($timezone);
        $endsAt = CarbonImmutable::parse($reservation->ends_at)->timezone($timezone);
        $qrCode = $this->qrCodeForPdf($reservation);

        return [
            'reservation' => $reservation,
            'bookerName' => $reservation->booker?->name ?? 'Reservante',
            'bookerEmail' => $reservation->contact_email,
            'institution' => $reservation->institution,
            'coursePeriod' => $reservation->course_period,
            'activity' => $reservation->activity,
            'spaceLabel' => $this->formatSpace($reservation),
            'companions' => $reservation->companions ?? [],
            'startsAt' => $startsAt,
            'endsAt' => $endsAt,
            'confirmationCode' => $reservation->confirmation_code,
            'verificationUrl' => $this->verificationUrl($reservation),
            'qrCodeBase64' => $qrCode['base64'],
            'qrCodeHtml' => $qrCode['html'],
            'issuedAt' => now()->timezone($timezone),
        ];
    }

    public function pdfBinary(Reservation $reservation): string
    {
        return Pdf::loadView('pdf.reservation-confirmation', $this->buildViewData($reservation))
            ->setPaper('a4', 'portrait')
            ->output();
    }

    public function findByCode(string $code): ?Reservation
    {
        return Reservation::query()
            ->with(['booker:id,name,email'])
            ->where('confirmation_code', $code)
            ->first();
    }

    /**
     * @return array{can_mark: bool, message: string, attended: bool, attended_at: ?CarbonImmutable}
     */
    public function attendanceContext(Reservation $reservation, ?CarbonImmutable $at = null): array
    {
        $timezone = config('app.timezone');
        $at ??= CarbonImmutable::now($timezone);

        if ($reservation->isAttended()) {
            $attendedAt = CarbonImmutable::parse($reservation->attended_at)->timezone($timezone);

            return [
                'can_mark' => false,
                'message' => 'Presença registrada em '.$attendedAt->format('d/m/Y \à\s H:i').'.',
                'attended' => true,
                'attended_at' => $attendedAt,
            ];
        }

        $check = $this->canMarkAttendance($reservation, $at);

        return [
            'can_mark' => $check['allowed'],
            'message' => $check['message'],
            'attended' => false,
            'attended_at' => null,
        ];
    }

    /**
     * @return array{allowed: bool, message: string}
     */
    public function canMarkAttendance(Reservation $reservation, ?CarbonImmutable $at = null): array
    {
        $timezone = config('app.timezone');
        $at ??= CarbonImmutable::now($timezone);
        $startsAt = CarbonImmutable::parse($reservation->starts_at)->timezone($timezone);
        $endsAt = CarbonImmutable::parse($reservation->ends_at)->timezone($timezone);

        if (! $at->isSameDay($startsAt)) {
            if ($at->lt($startsAt->startOfDay())) {
                return [
                    'allowed' => false,
                    'message' => 'O check-in fica disponível no dia da reserva ('.$startsAt->format('d/m/Y').').',
                ];
            }

            return [
                'allowed' => false,
                'message' => 'O prazo para marcar presença nesta reserva já encerrou.',
            ];
        }

        $minutesBefore = max(0, (int) config('coworking.check_in_minutes_before', 30));
        $minutesAfter = max(0, (int) config('coworking.check_in_minutes_after', 30));
        $windowStart = $startsAt->subMinutes($minutesBefore);
        $windowEnd = $endsAt->addMinutes($minutesAfter);

        if ($at->lt($windowStart)) {
            return [
                'allowed' => false,
                'message' => 'Check-in disponível a partir das '.$windowStart->format('H:i').'.',
            ];
        }

        if ($at->gt($windowEnd)) {
            return [
                'allowed' => false,
                'message' => 'O horário para marcar presença encerrou às '.$windowEnd->format('H:i').'.',
            ];
        }

        return ['allowed' => true, 'message' => ''];
    }

    public function markAttendance(Reservation $reservation): Reservation
    {
        if ($reservation->isAttended()) {
            return $reservation->loadMissing('booker:id,name,email');
        }

        $check = $this->canMarkAttendance($reservation);
        if (! $check['allowed']) {
            throw new \InvalidArgumentException($check['message']);
        }

        $reservation->attended_at = now();
        $reservation->save();

        return $reservation->fresh(['booker:id,name,email']);
    }

    private function formatSpace(Reservation $reservation): string
    {
        $computers = $reservation->computers ?? [];

        return match ($reservation->space_type) {
            Reservation::SPACE_MEETING_ROOM => 'Sala de reunião',
            Reservation::SPACE_COMPUTER => 'Computador(es): '.implode(', ', array_map(static fn ($id) => "PC {$id}", $computers)),
            Reservation::SPACE_BOTH => 'Sala + computador(es): '.implode(', ', array_map(static fn ($id) => "PC {$id}", $computers)),
            default => 'Coworking UEMASUL',
        };
    }
}
