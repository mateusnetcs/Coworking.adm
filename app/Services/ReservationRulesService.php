<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ReservationRulesService
{
    public function editWindowSeconds(): ?int
    {
        $seconds = config('coworking.edit_window_seconds');

        if ($seconds === null || $seconds === '') {
            return null;
        }

        return max(1, (int) $seconds);
    }

    public function editWindowMinutes(): int
    {
        return max(1, (int) config('coworking.edit_window_minutes', 10));
    }

    public function editWindowLabel(): string
    {
        $seconds = $this->editWindowSeconds();
        if ($seconds !== null) {
            return $seconds === 1 ? '1 segundo' : "{$seconds} segundos";
        }

        $minutes = $this->editWindowMinutes();

        return $minutes === 1 ? '1 minuto' : "{$minutes} minutos";
    }

    public function cancelHoursBefore(): int
    {
        return max(1, (int) config('coworking.cancel_hours_before', 3));
    }

    public function editableUntil(Reservation $reservation): CarbonImmutable
    {
        $created = CarbonImmutable::parse($reservation->created_at);
        $seconds = $this->editWindowSeconds();

        if ($seconds !== null) {
            return $created->addSeconds($seconds);
        }

        return $created->addMinutes($this->editWindowMinutes());
    }

    public function cancelDeadline(Reservation $reservation): CarbonImmutable
    {
        return CarbonImmutable::parse($reservation->starts_at)
            ->subHours($this->cancelHoursBefore());
    }

    public function isInEditWindow(Reservation $reservation, ?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $at->lt($this->editableUntil($reservation));
    }

    public function canEdit(Reservation $reservation, ?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $at->lt($this->editableUntil($reservation))
            && $at->lt($reservation->starts_at);
    }

    public function canCancel(Reservation $reservation, ?User $user = null, ?CarbonInterface $at = null): bool
    {
        if ($user?->isAdministrator()) {
            return true;
        }

        $at ??= now();

        if ($at->gte($reservation->starts_at)) {
            return false;
        }

        return $at->lte($this->cancelDeadline($reservation));
    }

    public function cancelBlockedReason(Reservation $reservation, ?User $user = null): ?string
    {
        if ($user?->isAdministrator()) {
            return null;
        }

        if ($this->canCancel($reservation, $user)) {
            return null;
        }

        if (now()->gte($reservation->starts_at)) {
            return 'Não é possível cancelar uma reserva que já começou ou terminou.';
        }

        return 'Cancelamento permitido apenas até '.$this->cancelHoursBefore().' horas antes do início da reserva.';
    }

    /**
     * @return array<string, mixed>
     */
    public function toMeta(Reservation $reservation, ?User $user = null): array
    {
        $now = CarbonImmutable::now();
        $editableUntil = $this->editableUntil($reservation);
        $cancelDeadline = $this->cancelDeadline($reservation);
        $inEditWindow = $now->lt($editableUntil);
        $cancelCountdownVisible = ! $inEditWindow;

        $editSecondsRemaining = $inEditWindow
            ? max(0, $editableUntil->getTimestamp() - $now->getTimestamp())
            : 0;

        $cancelSecondsRemaining = null;
        if ($cancelCountdownVisible) {
            $cancelSecondsRemaining = max(0, $cancelDeadline->getTimestamp() - $now->getTimestamp());
        }

        $isAdmin = $user?->isAdministrator() ?? false;

        return [
            'edit_window_minutes' => $this->editWindowMinutes(),
            'edit_window_seconds' => $this->editWindowSeconds(),
            'cancel_hours_before' => $this->cancelHoursBefore(),
            'starts_at' => CarbonImmutable::parse($reservation->starts_at)->toIso8601String(),
            'editable_until' => $editableUntil->toIso8601String(),
            'cancel_deadline' => $cancelDeadline->toIso8601String(),
            'admin_can_cancel' => $isAdmin,
            'can_edit' => $this->canEdit($reservation, $now) && ($user === null || $user->id === $reservation->user_id),
            'can_cancel' => $this->canCancel($reservation, $user, $now),
            'in_edit_window' => $inEditWindow,
            'cancel_countdown_visible' => $cancelCountdownVisible,
            'edit_seconds_remaining' => $editSecondsRemaining,
            'cancel_seconds_remaining' => $cancelSecondsRemaining,
            'cancel_blocked_reason' => $this->cancelBlockedReason($reservation, $user),
        ];
    }
}
