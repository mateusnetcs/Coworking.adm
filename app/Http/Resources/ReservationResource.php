<?php

namespace App\Http\Resources;

use App\Services\ReservationRulesService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Reservation */
class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rules = app(ReservationRulesService::class);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'course_period' => $this->course_period,
            'activity' => $this->activity,
            'contact_email' => $this->contact_email,
            'phone' => $this->phone,
            'institution' => $this->institution,
            'space_type' => $this->space_type,
            'computers' => $this->computers,
            'companions' => $this->companions,
            'attended_at' => $this->attended_at,
            'terms_accepted_at' => $this->terms_accepted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'booker' => $this->whenLoaded('booker'),
            'rules' => $rules->toMeta($this->resource, $request->user()),
        ];
    }
}
