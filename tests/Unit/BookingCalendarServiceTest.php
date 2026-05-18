<?php

namespace Tests\Unit;

use App\Services\BookingCalendarService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BookingCalendarServiceTest extends TestCase
{
  public function test_weekend_is_not_bookable(): void
    {
        $service = new BookingCalendarService;

        $saturday = CarbonImmutable::parse('2026-05-16', 'America/Sao_Paulo');
        $closure = $service->closureForDate($saturday);

        $this->assertNotNull($closure);
        $this->assertSame('weekend', $closure['type']);
        $this->assertSame('Reservas canceladas - final de semana', $closure['message']);
    }

    public function test_christmas_holiday_message(): void
    {
        $service = new BookingCalendarService;

        $christmas = CarbonImmutable::parse('2026-12-25', 'America/Sao_Paulo');
        $closure = $service->closureForDate($christmas);

        $this->assertNotNull($closure);
        $this->assertSame('holiday', $closure['type']);
        $this->assertSame('Reservas canceladas - feriado de Natal', $closure['message']);
    }

    public function test_weekday_without_holiday_is_bookable(): void
    {
        $service = new BookingCalendarService;

        $monday = CarbonImmutable::parse('2026-05-18', 'America/Sao_Paulo');

        $this->assertTrue($service->isBookableDate($monday));
    }
}
