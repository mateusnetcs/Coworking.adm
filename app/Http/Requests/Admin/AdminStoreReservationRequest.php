<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\StoreReservationRequest;

class AdminStoreReservationRequest extends StoreReservationRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'user_id.required' => 'Selecione o usuário da reserva.',
            'user_id.exists' => 'Usuário não encontrado.',
        ]);
    }
}
