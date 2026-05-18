<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class BookingCalendarService
{
    /** @var array<string, string>|null */
    private static ?array $holidayCache = null;

    /**
     * @return array{type: 'weekend'|'holiday', message: string, label: string|null}|null
     */
    public function closureForDate(CarbonImmutable $date): ?array
    {
        $day = $date->timezone(config('app.timezone'))->startOfDay();

        if ($this->isWeekend($day)) {
            return [
                'type' => 'weekend',
                'label' => null,
                'message' => 'Reservas canceladas - final de semana',
            ];
        }

        $holiday = $this->holidayLabel($day);

        if ($holiday !== null) {
            return [
                'type' => 'holiday',
                'label' => $holiday,
                'message' => 'Reservas canceladas - feriado de '.$holiday,
            ];
        }

        return null;
    }

    public function isBookableDate(CarbonImmutable $date): bool
    {
        return $this->closureForDate($date) === null;
    }

    public function assertBookableRange(CarbonImmutable $startsAt, CarbonImmutable $endsAt): void
    {
        $timezone = config('app.timezone');
        $startDay = $startsAt->timezone($timezone)->startOfDay();
        $endDay = $endsAt->timezone($timezone)->startOfDay();

        $closure = $this->closureForDate($startDay);

        if ($closure !== null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'starts_at' => $closure['message'],
            ]);
        }

        if (! $endDay->isSameDay($startDay)) {
            $endClosure = $this->closureForDate($endDay);

            if ($endClosure !== null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'ends_at' => $endClosure['message'],
                ]);
            }
        }
    }

    public function isWeekend(CarbonImmutable $date): bool
    {
        return $date->isSaturday() || $date->isSunday();
    }

    public function holidayLabel(CarbonImmutable $date): ?string
    {
        $key = $date->toDateString();

        return $this->holidays()[$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function holidays(): array
    {
        if (self::$holidayCache !== null) {
            return self::$holidayCache;
        }

        $currentYear = (int) now()->timezone(config('app.timezone'))->year;
        $years = range($currentYear - 1, $currentYear + 2);

        $map = [];

        foreach ($years as $year) {
            $map = array_merge($map, $this->nationalHolidaysForYear($year));
        }

        $extras = config('coworking_holidays', []);

        if (is_array($extras)) {
            foreach ($extras as $date => $label) {
                if (is_string($date) && is_string($label) && $label !== '') {
                    $map[$date] = $label;
                }
            }
        }

        self::$holidayCache = $map;

        return self::$holidayCache;
    }

    /**
     * @return array<string, string>
     */
    private function nationalHolidaysForYear(int $year): array
    {
        $fixed = [
            sprintf('%04d-01-01', $year) => 'Confraternização Universal',
            sprintf('%04d-04-21', $year) => 'Tiradentes',
            sprintf('%04d-05-01', $year) => 'Dia do Trabalho',
            sprintf('%04d-09-07', $year) => 'Independência do Brasil',
            sprintf('%04d-10-12', $year) => 'Nossa Senhora Aparecida',
            sprintf('%04d-11-02', $year) => 'Finados',
            sprintf('%04d-11-15', $year) => 'Proclamação da República',
            sprintf('%04d-11-20', $year) => 'Dia da Consciência Negra',
            sprintf('%04d-12-25', $year) => 'Natal',
        ];

        $easter = CarbonImmutable::createFromTimestamp(easter_date($year), config('app.timezone'))->startOfDay();
        $ashWednesday = $easter->subDays(46);

        $movable = [
            $ashWednesday->subDays(2)->toDateString() => 'Segunda-feira de Carnaval',
            $ashWednesday->subDay()->toDateString() => 'Terça-feira de Carnaval',
            $easter->subDays(2)->toDateString() => 'Sexta-feira Santa',
            $easter->addDays(60)->toDateString() => 'Corpus Christi',
        ];

        return array_merge($fixed, $movable);
    }

    /**
     * @return array{
     *     date: string,
     *     bookable: bool,
     *     type: 'weekend'|'holiday'|null,
     *     message: string|null,
     *     label: string|null
     * }
     */
    public function dayStatus(CarbonImmutable $date): array
    {
        $day = $date->timezone(config('app.timezone'))->startOfDay();
        $closure = $this->closureForDate($day);

        return [
            'date' => $day->toDateString(),
            'bookable' => $closure === null,
            'type' => $closure['type'] ?? null,
            'message' => $closure['message'] ?? null,
            'label' => $closure['label'] ?? null,
        ];
    }
}
