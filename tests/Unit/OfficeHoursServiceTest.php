<?php

namespace Tests\Unit;

use App\Models\HolidayDate;
use App\Services\OfficeHoursService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeHoursServiceTest extends TestCase
{
    use RefreshDatabase;

    private OfficeHoursService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OfficeHoursService();
    }

    // -------------------------------------------------------------------------
    // CS General
    // -------------------------------------------------------------------------

    public function test_cs_general_open_on_weekday_within_hours(): void
    {
        // Monday 10:00
        $now = Carbon::create(2025, 5, 5, 10, 0, 0, 'Asia/Jakarta'); // Monday
        $this->assertTrue($this->service->isOpen('cs_general', $now));
    }

    public function test_cs_general_open_on_saturday_within_hours(): void
    {
        $now = Carbon::create(2025, 5, 10, 12, 0, 0, 'Asia/Jakarta'); // Saturday
        $this->assertTrue($this->service->isOpen('cs_general', $now));
    }

    public function test_cs_general_open_on_sunday_within_hours(): void
    {
        $now = Carbon::create(2025, 5, 11, 12, 0, 0, 'Asia/Jakarta'); // Sunday
        $this->assertTrue($this->service->isOpen('cs_general', $now));
    }

    public function test_cs_general_closed_before_opening_time(): void
    {
        $now = Carbon::create(2025, 5, 5, 7, 59, 0, 'Asia/Jakarta'); // Monday 07:59
        $this->assertFalse($this->service->isOpen('cs_general', $now));
    }

    public function test_cs_general_open_at_exact_opening_time(): void
    {
        $now = Carbon::create(2025, 5, 5, 8, 0, 0, 'Asia/Jakarta'); // Monday 08:00
        $this->assertTrue($this->service->isOpen('cs_general', $now));
    }

    public function test_cs_general_open_at_exact_closing_time(): void
    {
        $now = Carbon::create(2025, 5, 5, 22, 0, 0, 'Asia/Jakarta'); // Monday 22:00
        $this->assertTrue($this->service->isOpen('cs_general', $now));
    }

    public function test_cs_general_closed_after_closing_time(): void
    {
        $now = Carbon::create(2025, 5, 5, 22, 1, 0, 'Asia/Jakarta'); // Monday 22:01
        $this->assertFalse($this->service->isOpen('cs_general', $now));
    }

    public function test_cs_general_saturday_closed_before_09(): void
    {
        $now = Carbon::create(2025, 5, 10, 8, 59, 0, 'Asia/Jakarta'); // Saturday 08:59
        $this->assertFalse($this->service->isOpen('cs_general', $now));
    }

    // -------------------------------------------------------------------------
    // CS Voucher
    // -------------------------------------------------------------------------

    public function test_cs_voucher_open_on_weekday_within_hours(): void
    {
        $now = Carbon::create(2025, 5, 5, 9, 0, 0, 'Asia/Jakarta'); // Monday
        $this->assertTrue($this->service->isOpen('cs_voucher', $now));
    }

    public function test_cs_voucher_closed_before_0830_on_weekday(): void
    {
        $now = Carbon::create(2025, 5, 5, 8, 29, 0, 'Asia/Jakarta'); // Monday 08:29
        $this->assertFalse($this->service->isOpen('cs_voucher', $now));
    }

    public function test_cs_voucher_open_at_0830_on_weekday(): void
    {
        $now = Carbon::create(2025, 5, 5, 8, 30, 0, 'Asia/Jakarta'); // Monday 08:30
        $this->assertTrue($this->service->isOpen('cs_voucher', $now));
    }

    public function test_cs_voucher_closed_after_1700_on_weekday(): void
    {
        $now = Carbon::create(2025, 5, 5, 17, 1, 0, 'Asia/Jakarta'); // Monday 17:01
        $this->assertFalse($this->service->isOpen('cs_voucher', $now));
    }

    public function test_cs_voucher_open_on_saturday_within_hours(): void
    {
        $now = Carbon::create(2025, 5, 10, 12, 0, 0, 'Asia/Jakarta'); // Saturday
        $this->assertTrue($this->service->isOpen('cs_voucher', $now));
    }

    public function test_cs_voucher_closed_on_saturday_after_1500(): void
    {
        $now = Carbon::create(2025, 5, 10, 15, 1, 0, 'Asia/Jakarta'); // Saturday 15:01
        $this->assertFalse($this->service->isOpen('cs_voucher', $now));
    }

    public function test_cs_voucher_closed_on_sunday(): void
    {
        $now = Carbon::create(2025, 5, 11, 12, 0, 0, 'Asia/Jakarta'); // Sunday
        $this->assertFalse($this->service->isOpen('cs_voucher', $now));
    }

    public function test_cs_voucher_closed_on_national_holiday(): void
    {
        // Register a holiday
        HolidayDate::create(['date' => '2025-05-05', 'name' => 'Test Holiday']);

        $now = Carbon::create(2025, 5, 5, 10, 0, 0, 'Asia/Jakarta'); // Monday (but holiday)
        $this->assertFalse($this->service->isOpen('cs_voucher', $now));
    }

    // -------------------------------------------------------------------------
    // CS Undercutting
    // -------------------------------------------------------------------------

    public function test_cs_undercutting_open_on_weekday_within_hours(): void
    {
        $now = Carbon::create(2025, 5, 5, 10, 0, 0, 'Asia/Jakarta'); // Monday
        $this->assertTrue($this->service->isOpen('cs_undercutting', $now));
    }

    public function test_cs_undercutting_closed_before_0900_on_weekday(): void
    {
        $now = Carbon::create(2025, 5, 5, 8, 59, 0, 'Asia/Jakarta'); // Monday 08:59
        $this->assertFalse($this->service->isOpen('cs_undercutting', $now));
    }

    public function test_cs_undercutting_closed_on_sunday(): void
    {
        $now = Carbon::create(2025, 5, 11, 10, 0, 0, 'Asia/Jakarta'); // Sunday
        $this->assertFalse($this->service->isOpen('cs_undercutting', $now));
    }

    public function test_cs_undercutting_closed_on_national_holiday(): void
    {
        HolidayDate::create(['date' => '2025-05-05', 'name' => 'Test Holiday']);
        $now = Carbon::create(2025, 5, 5, 10, 0, 0, 'Asia/Jakarta');
        $this->assertFalse($this->service->isOpen('cs_undercutting', $now));
    }

    public function test_cs_undercutting_closed_after_1730(): void
    {
        $now = Carbon::create(2025, 5, 5, 17, 31, 0, 'Asia/Jakarta'); // Monday 17:31
        $this->assertFalse($this->service->isOpen('cs_undercutting', $now));
    }

    public function test_cs_undercutting_open_at_exact_1730(): void
    {
        $now = Carbon::create(2025, 5, 5, 17, 30, 0, 'Asia/Jakarta'); // Monday 17:30
        $this->assertTrue($this->service->isOpen('cs_undercutting', $now));
    }

    // -------------------------------------------------------------------------
    // Unknown service
    // -------------------------------------------------------------------------

    public function test_unknown_service_is_always_closed(): void
    {
        $now = Carbon::create(2025, 5, 5, 10, 0, 0, 'Asia/Jakarta');
        $this->assertFalse($this->service->isOpen('nonexistent', $now));
    }
}
