<?php

namespace App\Services;

use App\Models\Reservation;
use App\Support\PublicAppUrl;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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

    public function qrCodeBase64(Reservation $reservation): string
    {
        $png = QrCode::format('png')
            ->size(220)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($this->verificationUrl($reservation));

        return base64_encode($png);
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
            'qrCodeBase64' => $this->qrCodeBase64($reservation),
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
