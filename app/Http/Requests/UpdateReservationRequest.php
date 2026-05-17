<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use App\Services\ReservationRulesService;

class UpdateReservationRequest extends StoreReservationRequest
{
    public function authorize(): bool
    {
        $reservation = $this->route('reservation');

        if (! $reservation instanceof Reservation) {
            return false;
        }

        return $this->user()?->id === $reservation->user_id
            && app(ReservationRulesService::class)->canEdit($reservation);
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $reservation = $this->route('reservation');
        if ($reservation instanceof Reservation && $reservation->terms_accepted_at !== null) {
            $rules['terms_accepted'] = ['sometimes', 'boolean'];
        }

        return $rules;
    }
}
