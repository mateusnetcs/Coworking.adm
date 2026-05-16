<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $companions = collect($this->input('companions', []))
            ->filter(static fn ($value): bool => is_array($value))
            ->map(static function (array $companion): array {
                return [
                    'name' => Str::limit(trim(strip_tags((string) ($companion['name'] ?? ''))), 120, ''),
                    'course_period' => (int) ($companion['course_period'] ?? 0),
                    'activity' => Str::limit(trim(strip_tags((string) ($companion['activity'] ?? ''))), 500, ''),
                ];
            })
            ->filter(static fn (array $companion): bool => $companion['name'] !== '')
            ->values()
            ->all();

        $this->merge([
            'activity' => Str::limit(trim(strip_tags((string) $this->input('activity', ''))), 500, ''),
            'companions' => $companions,
        ]);
    }

    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date', 'after_or_equal:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'course_period' => ['required', 'integer', 'between:1,8'],
            'activity' => ['required', 'string', 'min:3', 'max:500'],
            'companions' => ['nullable', 'array', 'max:40'],
            'companions.*.name' => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\p{L}\p{N}\s.\'-]+$/u'],
            'companions.*.course_period' => ['required', 'integer', 'between:1,8'],
            'companions.*.activity' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.after_or_equal' => 'O horário de início deve ser igual ou posterior ao horário atual.',
            'ends_at.after' => 'O horário de término deve ser depois do horário de início.',
            'course_period.required' => 'Informe o período do curso.',
            'course_period.between' => 'O período do curso deve ser entre 1 e 8.',
            'activity.required' => 'Descreva a atividade que será desenvolvida no coworking.',
            'activity.min' => 'A atividade deve ter pelo menos :min caracteres.',
            'companions.*.name.required' => 'Informe o nome de cada colega adicionado.',
            'companions.*.course_period.required' => 'Informe o período do curso de cada colega.',
            'companions.*.course_period.between' => 'O período do curso deve ser entre 1 e 8.',
            'companions.*.activity.required' => 'Descreva a atividade de cada colega no coworking.',
            'companions.*.activity.min' => 'A atividade de cada colega deve ter pelo menos :min caracteres.',
        ];
    }
}
