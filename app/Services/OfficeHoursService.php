<?php

namespace App\Services;

use App\Models\HolidayDate;
use Carbon\Carbon;

class OfficeHoursService
{
    /**
     * Service schedules in Asia/Jakarta timezone.
     *
     * Format per day-of-week (1=Mon … 7=Sun):
     *   ['open' => 'HH:MM', 'close' => 'HH:MM']  – or null for closed.
     */
    protected array $schedules = [
        'cs_general' => [
            1 => ['open' => '08:00', 'close' => '22:00'],
            2 => ['open' => '08:00', 'close' => '22:00'],
            3 => ['open' => '08:00', 'close' => '22:00'],
            4 => ['open' => '08:00', 'close' => '22:00'],
            5 => ['open' => '08:00', 'close' => '22:00'],
            6 => ['open' => '09:00', 'close' => '21:00'],
            7 => ['open' => '09:00', 'close' => '21:00'],
        ],
        'cs_voucher' => [
            1 => ['open' => '08:30', 'close' => '17:00'],
            2 => ['open' => '08:30', 'close' => '17:00'],
            3 => ['open' => '08:30', 'close' => '17:00'],
            4 => ['open' => '08:30', 'close' => '17:00'],
            5 => ['open' => '08:30', 'close' => '17:00'],
            6 => ['open' => '09:00', 'close' => '15:00'],
            7 => null, // closed on Sunday
        ],
        'cs_undercutting' => [
            1 => ['open' => '09:00', 'close' => '17:30'],
            2 => ['open' => '09:00', 'close' => '17:30'],
            3 => ['open' => '09:00', 'close' => '17:30'],
            4 => ['open' => '09:00', 'close' => '17:30'],
            5 => ['open' => '09:00', 'close' => '17:30'],
            6 => ['open' => '09:00', 'close' => '15:00'],
            7 => null, // closed on Sunday
        ],
    ];

    /**
     * Check if a service is currently open.
     *
     * @param  string       $service  One of: cs_general, cs_voucher, cs_undercutting
     * @param  Carbon|null  $now      Defaults to now() in Asia/Jakarta
     */
    public function isOpen(string $service, ?Carbon $now = null): bool
    {
        $now = ($now ?? Carbon::now())->setTimezone('Asia/Jakarta');

        if (! isset($this->schedules[$service])) {
            return false;
        }

        $dow = (int) $now->isoFormat('E'); // 1=Mon … 7=Sun

        // cs_voucher and cs_undercutting are closed on national holidays
        if (in_array($service, ['cs_voucher', 'cs_undercutting'])) {
            if (HolidayDate::isHoliday($now)) {
                return false;
            }
        }

        $daySchedule = $this->schedules[$service][$dow] ?? null;

        if ($daySchedule === null) {
            return false;
        }

        $openTime  = Carbon::createFromFormat('H:i', $daySchedule['open'],  'Asia/Jakarta')
            ->setDate($now->year, $now->month, $now->day);
        $closeTime = Carbon::createFromFormat('H:i', $daySchedule['close'], 'Asia/Jakarta')
            ->setDate($now->year, $now->month, $now->day);

        return $now->between($openTime, $closeTime, true);
    }

    /**
     * Return human-readable schedule text for a service.
     */
    public function scheduleText(string $service): string
    {
        return match ($service) {
            'cs_general' => implode("\n", [
                '- Senin–Jumat: 08:00–22:00 WIB',
                '- Sabtu & Minggu: 09:00–21:00 WIB',
            ]),
            'cs_voucher' => implode("\n", [
                '- Senin–Jumat: 08:30–17:00 WIB',
                '- Sabtu: 09:00–15:00 WIB',
                '- Minggu & Libur Nasional: Tidak ada layanan',
            ]),
            'cs_undercutting' => implode("\n", [
                '- Senin–Jumat: 09:00–17:30 WIB',
                '- Sabtu: 09:00–15:00 WIB',
                '- Minggu & Libur Nasional: Tidak ada layanan',
            ]),
            default => '',
        };
    }
}
