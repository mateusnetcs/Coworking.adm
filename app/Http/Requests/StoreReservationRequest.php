<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use App\Support\BrazilPhone;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

        $computers = collect($this->input('computers', []))
            ->map(static fn ($value): int => (int) $value)
            ->filter(static fn (int $value): bool => in_array($value, [1, 2], true))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $spaceType = (string) $this->input('space_type', '');

        if (! in_array($spaceType, [Reservation::SPACE_COMPUTER, Reservation::SPACE_BOTH], true)) {
            $computers = [];
        }

        $this->merge([
            'activity' => Str::limit(trim(strip_tags((string) $this->input('activity', ''))), 500, ''),
            'contact_email' => Str::lower(trim((string) $this->input('contact_email', ''))),
            'phone' => BrazilPhone::digitsOnly((string) $this->input('phone', '')),
            'institution' => Str::limit(trim(strip_tags((string) $this->input('institution', ''))), 120, ''),
            'companions' => $companions,
            'computers' => $computers,
            'terms_accepted' => filter_var($this->input('terms_accepted'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $startHour = (int) config('coworking.booking_start_hour', 14);
            $endHour = (int) config('coworking.booking_end_hour', 22);

            $startsAt = CarbonImmutable::parse($this->input('starts_at'));
            $endsAt = CarbonImmutable::parse($this->input('ends_at'));

            $windowStart = $startsAt->copy()->setTime($startHour, 0);
            $windowEnd = $startsAt->copy()->setTime($endHour, 0);

            if ($startsAt->lt($windowStart) || $endsAt->gt($windowEnd) || $startsAt->gte($windowEnd)) {
                $validator->errors()->add(
                    'starts_at',
                    "As reservas são permitidas apenas entre {$startHour}:00 e {$endHour}:00.",
                );
            }

            $spaceType = (string) $this->input('space_type');
            $computers = $this->input('computers', []);

            if (in_array($spaceType, [Reservation::SPACE_COMPUTER, Reservation::SPACE_BOTH], true) && count($computers) === 0) {
                $validator->errors()->add('computers', 'Selecione ao menos um computador.');
            }

            if ($spaceType === Reservation::SPACE_MEETING_ROOM && count($computers) > 0) {
                $validator->errors()->add('computers', 'Computadores não se aplicam à reserva apenas da sala.');
            }
        });
    }

    public function rules(): array
    {
        $maxComputers = (int) config('coworking.computer_count', 2);
        $computerIds = range(1, max(1, $maxComputers));

        return [
            'starts_at' => ['required', 'date', 'after_or_equal:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'course_period' => ['required', 'integer', 'between:1,8'],
            'activity' => ['required', 'string', 'min:3', 'max:500'],
            'contact_email' => ['required', 'email', 'max:255'],
            'phone' => [
                'required',
                'string',
                'size:11',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! BrazilPhone::isValidMobile((string) $value)) {
                        $fail('Informe um celular válido com DDD (ex.: (99) 9999-9999).');
                    }
                },
            ],
            'institution' => ['required', 'string', 'min:2', 'max:120'],
            'space_type' => [
                'required',
                'string',
                Rule::in([
                    Reservation::SPACE_COMPUTER,
                    Reservation::SPACE_MEETING_ROOM,
                    Reservation::SPACE_BOTH,
                ]),
            ],
            'computers' => ['nullable', 'array', 'max:'.$maxComputers],
            'computers.*' => ['integer', Rule::in($computerIds)],
            'terms_accepted' => ['required', 'accepted'],
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
            'contact_email.required' => 'Informe seu e-mail.',
            'contact_email.email' => 'Informe um e-mail válido.',
            'phone.required' => 'Informe seu telefone (WhatsApp).',
            'phone.size' => 'O telefone deve ter DDD + 9 dígitos (ex.: (99) 9999-9999).',
            'institution.required' => 'Informe sua instituição.',
            'space_type.required' => 'Selecione qual espaço deseja reservar.',
            'space_type.in' => 'Tipo de espaço inválido.',
            'terms_accepted.accepted' => 'É necessário aceitar o termo de responsabilidade.',
            'companions.*.name.required' => 'Informe o nome de cada colega adicionado.',
            'companions.*.course_period.required' => 'Informe o período do curso de cada colega.',
            'companions.*.course_period.between' => 'O período do curso deve ser entre 1 e 8.',
            'companions.*.activity.required' => 'Descreva a atividade de cada colega no coworking.',
            'companions.*.activity.min' => 'A atividade de cada colega deve ter pelo menos :min caracteres.',
        ];
    }
}
