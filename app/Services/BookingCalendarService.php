<?php

namespace App\Services;

use App\Models\ClosedBookingDay;
use Carbon\CarbonImmutable;
use Throwable;

class BookingCalendarService
{
    /** @var array<string, string>|null */
    private static ?array $holidayCache = null;

    /** @var array<string, string>|null */
    private static ?array $closedDayCache = null;

    public static function forgetClosedDaysCache(): void
    {
        self::$closedDayCache = null;
    }

    /**
     * @return array{type: 'weekend'|'holiday'|'closed', message: string, label: string|null, reopenable?: bool}|null
     */
    public function closureForDate(CarbonImmutable $date): ?array
    {
        $day = $date->timezone(config('app.timezone'))->startOfDay();

        if ($this->isSunday($day)) {
            return [
                'type' => 'weekend',
                'label' => null,
                'message' => 'Reservas canceladas - domingo',
                'reopenable' => false,
            ];
        }

        $holiday = $this->holidayLabel($day);

        if ($holiday !== null) {
            return [
                'type' => 'holiday',
                'label' => $holiday,
                'message' => 'Reservas canceladas - feriado de '.$holiday,
                'reopenable' => false,
            ];
        }

        $closedReason = $this->closedDayReason($day);

        if ($closedReason !== null) {
            return [
                'type' => 'closed',
                'label' => $closedReason,
                'message' => 'Reservas canceladas - '.$closedReason,
                'reopenable' => true,
            ];
        }

        return null;
    }

    public function closedDayReason(CarbonImmutable $date): ?string
    {
        $key = $date->toDateString();

        return $this->closedDays()[$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function closedDays(): array
    {
        if (self::$closedDayCache !== null) {
            return self::$closedDayCache;
        }

        self::$closedDayCache = ClosedBookingDay::query()
            ->get(['date', 'reason'])
            ->mapWithKeys(static fn (ClosedBookingDay $row): array => [
                CarbonImmutable::parse($row->date)->toDateString() => $row->reason,
            ])
            ->all();

        return self::$closedDayCache;
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

    public function isSunday(CarbonImmutable $date): bool
    {
        return $date->isSunday();
    }

    /** @deprecated Use isSunday() — sábados estão liberados para reserva. */
    public function isWeekend(CarbonImmutable $date): bool
    {
        return $this->isSunday($date);
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

        $timezone = config('app.timezone');
        $currentYear = (int) now()->timezone($timezone)->year;
        $years = range($currentYear - 2, $currentYear + 5);

        $map = [];

        foreach ($years as $year) {
            $map = array_merge($map, $this->fixedNationalHolidaysForYear($year));
        }

        foreach ($years as $year) {
            $map = array_merge($map, $this->movableNationalHolidaysForYear($year));
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
    private function fixedNationalHolidaysForYear(int $year): array
    {
        return [
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
    }

    /**
     * @return array<string, string>
     */
    private function movableNationalHolidaysForYear(int $year): array
    {
        try {
            $easter = $this->easterSunday($year);
            $ashWednesday = $easter->subDays(46);

            return [
                $ashWednesday->subDays(2)->toDateString() => 'Segunda-feira de Carnaval',
                $ashWednesday->subDay()->toDateString() => 'Terça-feira de Carnaval',
                $easter->subDays(2)->toDateString() => 'Sexta-feira Santa',
                $easter->addDays(60)->toDateString() => 'Corpus Christi',
            ];
        } catch (Throwable) {
            return [];
        }
    }

    private function easterSunday(int $year): CarbonImmutable
    {
        $timezone = config('app.timezone');
        $timestamp = $this->easterSundayTimestamp($year);

        return CarbonImmutable::createFromTimestamp($timestamp, $timezone)->startOfDay();
    }

    private function easterSundayTimestamp(int $year): int
    {
        if (function_exists('easter_date')) {
            return easter_date($year);
        }

        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return mktime(0, 0, 0, $month, $day, $year);
    }

    /**
     * @return array{
     *     date: string,
     *     bookable: bool,
     *     type: 'weekend'|'holiday'|'closed'|null,
     *     message: string|null,
     *     label: string|null,
     *     reopenable: bool
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
            'reopenable' => $closure['reopenable'] ?? false,
        ];
    }
}
