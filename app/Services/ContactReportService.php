<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContactReportService
{
    /**
     * Menghitung persentase perubahan antara dua periode.
     * Mengembalikan null jika $previous === 0 (ditampilkan sebagai "N/A" di UI).
     * Rumus: round(((current - previous) / previous) * 100, 1)
     *
     * Requirements: 3.8, 3.9
     */
    public function calculatePercentageChange(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return null; // Ditampilkan sebagai "N/A" di UI
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Mengembalikan semua data untuk response JSON endpoint /data.
     * Memanggil getSummary, getOvertimeData, getHourlyData, getDailyDistributionData
     * dan menggabungkan hasilnya.
     *
     * Requirements: 7.2
     *
     * @param array $filters ['timezone', 'channel', 'start_date', 'end_date']
     * @return array ['summary' => [...], 'overtime' => [...], 'hourly' => [...], 'daily_distribution' => [...]]
     */
    public function getReportData(array $filters): array
    {
        return [
            'summary'            => $this->getSummary($filters),
            'overtime'           => $this->getOvertimeData($filters),
            'hourly'             => $this->getHourlyData($filters),
            'daily_distribution' => $this->getDailyDistributionData($filters),
        ];
    }

    /**
     * Mengembalikan daftar nilai origin unik dari tabel users (non-null, non-empty).
     *
     * Requirements: 2.5
     */
    public function getAvailableChannels(): array
    {
        return User::select('origin')
            ->whereNotNull('origin')
            ->where('origin', '!=', '')
            ->distinct()
            ->orderBy('origin')
            ->pluck('origin')
            ->toArray();
    }

    /**
     * Mengembalikan kartu ringkasan statistik kontak.
     *
     * Menghitung total_contact dalam [start_date, end_date] dan tren
     * daily/weekly/monthly/quarterly relatif terhadap end_date.
     * Menggunakan CONVERT_TZ() MySQL untuk konversi timezone, dengan
     * fallback Carbon collection jika CONVERT_TZ mengembalikan NULL.
     *
     * Requirements: 2.2, 2.4, 3.1, 3.2, 3.3, 3.4, 3.5
     *
     * @param array $filters ['timezone', 'channel', 'start_date', 'end_date']
     * @return array
     */
    public function getSummary(array $filters): array
    {
        $timezone   = $filters['timezone']   ?? 'UTC';
        $channel    = $filters['channel']    ?? null;
        $startDate  = $filters['start_date'];
        $endDate    = $filters['end_date'];

        $tzOffset = $this->getTimezoneOffset($timezone);

        // ── Total Contact ────────────────────────────────────────────────────
        $totalContact = $this->countByDateRange($startDate, $endDate, $tzOffset, $timezone, $channel);

        // ── Trend period boundaries (relative to end_date) ──────────────────
        $end = Carbon::parse($endDate);

        // Daily: end_date vs end_date - 1 day
        $dailyCurrent  = $this->countByDateRange($endDate, $endDate, $tzOffset, $timezone, $channel);
        $dailyPrevDate = $end->copy()->subDay()->format('Y-m-d');
        $dailyPrev     = $this->countByDateRange($dailyPrevDate, $dailyPrevDate, $tzOffset, $timezone, $channel);

        // Weekly: [end-6, end] vs [end-13, end-7]
        $weeklyStart        = $end->copy()->subDays(6)->format('Y-m-d');
        $weeklyPrevEnd      = $end->copy()->subDays(7)->format('Y-m-d');
        $weeklyPrevStart    = $end->copy()->subDays(13)->format('Y-m-d');
        $weeklyCurrent      = $this->countByDateRange($weeklyStart, $endDate, $tzOffset, $timezone, $channel);
        $weeklyPrev         = $this->countByDateRange($weeklyPrevStart, $weeklyPrevEnd, $tzOffset, $timezone, $channel);

        // Monthly: [end-29, end] vs [end-59, end-30]
        $monthlyStart       = $end->copy()->subDays(29)->format('Y-m-d');
        $monthlyPrevEnd     = $end->copy()->subDays(30)->format('Y-m-d');
        $monthlyPrevStart   = $end->copy()->subDays(59)->format('Y-m-d');
        $monthlyCurrent     = $this->countByDateRange($monthlyStart, $endDate, $tzOffset, $timezone, $channel);
        $monthlyPrev        = $this->countByDateRange($monthlyPrevStart, $monthlyPrevEnd, $tzOffset, $timezone, $channel);

        // Quarterly: [end-89, end] vs [end-179, end-90]
        $quarterlyStart     = $end->copy()->subDays(89)->format('Y-m-d');
        $quarterlyPrevEnd   = $end->copy()->subDays(90)->format('Y-m-d');
        $quarterlyPrevStart = $end->copy()->subDays(179)->format('Y-m-d');
        $quarterlyCurrent   = $this->countByDateRange($quarterlyStart, $endDate, $tzOffset, $timezone, $channel);
        $quarterlyPrev      = $this->countByDateRange($quarterlyPrevStart, $quarterlyPrevEnd, $tzOffset, $timezone, $channel);

        return [
            'total_contact' => $totalContact,
            'daily'         => $this->buildTrendCard($dailyCurrent, $dailyPrev),
            'weekly'        => $this->buildTrendCard($weeklyCurrent, $weeklyPrev),
            'monthly'       => $this->buildTrendCard($monthlyCurrent, $monthlyPrev),
            'quarterly'     => $this->buildTrendCard($quarterlyCurrent, $quarterlyPrev),
        ];
    }

    /**
     * Menghitung jumlah Customer yang created_at (dikonversi ke timezone)
     * jatuh dalam rentang [startDate, endDate] (inklusif, format Y-m-d).
     *
     * Menggunakan CONVERT_TZ() MySQL; fallback ke Carbon collection jika
     * CONVERT_TZ mengembalikan NULL (MySQL tanpa timezone tables).
     */
    private function countByDateRange(
        string $startDate,
        string $endDate,
        string $tzOffset,
        string $timezone,
        ?string $channel
    ): int {
        $query = User::whereNot(function ($q) {
            $q->where('email', 'like', 'anon_%@livechat.best')
              ->where('name', 'Guest');
        });

        if ($channel !== null) {
            $query->where('origin', $channel);
        }

        // Coba CONVERT_TZ() — deteksi NULL dengan memilih satu baris sampel
        try {
            $sampleRaw = DB::selectOne(
                "SELECT CONVERT_TZ(NOW(), '+00:00', ?) AS tz_check",
                [$tzOffset]
            );
            $convertTzAvailable = $sampleRaw && $sampleRaw->tz_check !== null;
        } catch (\Exception $e) {
            $convertTzAvailable = false;
        }

        if ($convertTzAvailable) {
            return (clone $query)
                ->whereRaw(
                    "DATE(CONVERT_TZ(created_at, '+00:00', ?)) BETWEEN ? AND ?",
                    [$tzOffset, $startDate, $endDate]
                )
                ->count();
        }

        // Fallback: Carbon collection approach
        $start = Carbon::parse($startDate, $timezone)->startOfDay()->utc();
        $end   = Carbon::parse($endDate, $timezone)->endOfDay()->utc();

        return (clone $query)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->filter(function ($customer) use ($startDate, $endDate, $timezone) {
                $date = Carbon::parse($customer->created_at)->setTimezone($timezone)->format('Y-m-d');
                return $date >= $startDate && $date <= $endDate;
            })
            ->count();
    }

    /**
     * Membangun array kartu tren dengan count, change, dan change_label.
     */
    private function buildTrendCard(int $current, int $previous): array
    {
        $change = $this->calculatePercentageChange($current, $previous);

        if ($change === null) {
            $changeLabel = 'N/A';
        } elseif ($change >= 0) {
            $changeLabel = '+' . $change . '%';
        } else {
            $changeLabel = $change . '%';
        }

        return [
            'count'        => $current,
            'change'       => $change,
            'change_label' => $changeLabel,
        ];
    }

    /**
     * Mengembalikan data grafik garis kontak per hari (Overtime Chart).
     *
     * Mengelompokkan Customer berdasarkan tanggal created_at (dikonversi ke timezone)
     * dan mengisi gap (tanggal tanpa data) dengan nilai 0.
     *
     * Requirements: 4.2, 4.4
     *
     * @param array $filters ['timezone', 'channel', 'start_date', 'end_date']
     * @return array ['labels' => [...], 'data' => [...]]
     */
    public function getOvertimeData(array $filters): array
    {
        $timezone  = $filters['timezone']   ?? 'UTC';
        $channel   = $filters['channel']    ?? null;
        $startDate = $filters['start_date'];
        $endDate   = $filters['end_date'];

        $tzOffset = $this->getTimezoneOffset($timezone);

        // Cek apakah CONVERT_TZ() tersedia
        try {
            $sampleRaw = DB::selectOne(
                "SELECT CONVERT_TZ(NOW(), '+00:00', ?) AS tz_check",
                [$tzOffset]
            );
            $convertTzAvailable = $sampleRaw && $sampleRaw->tz_check !== null;
        } catch (\Exception $e) {
            $convertTzAvailable = false;
        }

        $query = User::whereNot(function ($q) {
            $q->where('email', 'like', 'anon_%@livechat.best')
              ->where('name', 'Guest');
        });
        if ($channel !== null) {
            $query->where('origin', $channel);
        }

        if ($convertTzAvailable) {
            // Use a subquery to avoid ONLY_FULL_GROUP_BY issues:
            // inner query computes the converted date as an alias,
            // outer query groups by that alias.
            $channelWhere = $channel !== null ? "AND origin = ?" : "";
            $channelBindings = $channel !== null ? [$channel] : [];

            $sql = "
                SELECT grp.date AS date, COUNT(*) AS count
                FROM (
                    SELECT DATE(CONVERT_TZ(created_at, '+00:00', ?)) AS date
                    FROM users
                    WHERE DATE(CONVERT_TZ(created_at, '+00:00', ?)) BETWEEN ? AND ?
                    AND NOT (email LIKE 'anon_%@livechat.best' AND name = 'Guest')
                    {$channelWhere}
                ) AS grp
                GROUP BY grp.date
            ";
            $bindings = array_merge([$tzOffset, $tzOffset, $startDate, $endDate], $channelBindings);

            $rawRows = DB::select($sql, $bindings);
            $rows = [];
            foreach ($rawRows as $row) {
                $rows[$row->date] = $row->count;
            }
        } else {
            // Fallback: Carbon collection approach
            $start = Carbon::parse($startDate, $timezone)->startOfDay()->utc();
            $end   = Carbon::parse($endDate, $timezone)->endOfDay()->utc();

            $rows = (clone $query)
                ->whereBetween('created_at', [$start, $end])
                ->get()
                ->groupBy(function ($customer) use ($timezone) {
                    return Carbon::parse($customer->created_at)->setTimezone($timezone)->format('Y-m-d');
                })
                ->filter(function ($group, $date) use ($startDate, $endDate) {
                    return $date >= $startDate && $date <= $endDate;
                })
                ->map(fn($group) => $group->count())
                ->toArray();
        }

        // Isi gap: loop dari start_date hingga end_date
        $labels = [];
        $data   = [];
        $current = Carbon::parse($startDate);
        $end     = Carbon::parse($endDate);

        while ($current->lte($end)) {
            $dateStr  = $current->format('Y-m-d');
            $labels[] = $dateStr;
            $data[]   = (int) ($rows[$dateStr] ?? 0);
            $current->addDay();
        }

        return [
            'labels' => $labels,
            'data'   => $data,
        ];
    }

    /**
     * Mengembalikan data grafik batang kontak per jam (Hourly Chart).
     *
     * Mengelompokkan Customer berdasarkan jam created_at (dikonversi ke timezone)
     * dan mengisi gap (jam tanpa data) dengan nilai 0.
     * Selalu mengembalikan tepat 24 entri (jam 0–23).
     *
     * Requirements: 5.2, 5.3
     *
     * @param array $filters ['timezone', 'channel', 'start_date', 'end_date']
     * @return array ['labels' => ['00:00', ..., '23:00'], 'data' => [...]]
     */
    public function getHourlyData(array $filters): array
    {
        $timezone  = $filters['timezone']   ?? 'UTC';
        $channel   = $filters['channel']    ?? null;
        $startDate = $filters['start_date'];
        $endDate   = $filters['end_date'];

        $tzOffset = $this->getTimezoneOffset($timezone);

        // Cek apakah CONVERT_TZ() tersedia
        try {
            $sampleRaw = DB::selectOne(
                "SELECT CONVERT_TZ(NOW(), '+00:00', ?) AS tz_check",
                [$tzOffset]
            );
            $convertTzAvailable = $sampleRaw && $sampleRaw->tz_check !== null;
        } catch (\Exception $e) {
            $convertTzAvailable = false;
        }

        $query = User::whereNot(function ($q) {
            $q->where('email', 'like', 'anon_%@livechat.best')
              ->where('name', 'Guest');
        });
        if ($channel !== null) {
            $query->where('origin', $channel);
        }

        if ($convertTzAvailable) {
            // Use a subquery to avoid ONLY_FULL_GROUP_BY issues
            $channelWhere = $channel !== null ? "AND origin = ?" : "";
            $channelBindings = $channel !== null ? [$channel] : [];

            $sql = "
                SELECT grp.hour AS hour, COUNT(*) AS count
                FROM (
                    SELECT HOUR(CONVERT_TZ(created_at, '+00:00', ?)) AS hour
                    FROM users
                    WHERE DATE(CONVERT_TZ(created_at, '+00:00', ?)) BETWEEN ? AND ?
                    AND NOT (email LIKE 'anon_%@livechat.best' AND name = 'Guest')
                    {$channelWhere}
                ) AS grp
                GROUP BY grp.hour
            ";
            $bindings = array_merge([$tzOffset, $tzOffset, $startDate, $endDate], $channelBindings);

            $rawRows = DB::select($sql, $bindings);
            $rows = [];
            foreach ($rawRows as $row) {
                $rows[(int) $row->hour] = $row->count;
            }
        } else {
            // Fallback: Carbon collection approach
            $start = Carbon::parse($startDate, $timezone)->startOfDay()->utc();
            $end   = Carbon::parse($endDate, $timezone)->endOfDay()->utc();

            $rows = (clone $query)
                ->whereBetween('created_at', [$start, $end])
                ->get()
                ->filter(function ($customer) use ($startDate, $endDate, $timezone) {
                    $date = Carbon::parse($customer->created_at)->setTimezone($timezone)->format('Y-m-d');
                    return $date >= $startDate && $date <= $endDate;
                })
                ->groupBy(function ($customer) use ($timezone) {
                    return (int) Carbon::parse($customer->created_at)->setTimezone($timezone)->format('G');
                })
                ->map(fn($group) => $group->count())
                ->toArray();
        }

        // Inisialisasi array 24 elemen (indeks 0–23) dengan nilai 0, isi dari hasil query
        $labels = [];
        $data   = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $labels[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
            $data[]   = (int) ($rows[$hour] ?? 0);
        }

        return [
            'labels' => $labels,
            'data'   => $data,
        ];
    }

    /**
     * Mengembalikan data grafik batang kontak per hari dalam seminggu (Daily Distribution Chart).
     *
     * Mengelompokkan Customer berdasarkan hari dalam seminggu created_at (dikonversi ke timezone)
     * dan memetakan ke urutan Senin–Minggu.
     * MySQL DAYOFWEEK: 1=Minggu, 2=Senin, 3=Selasa, 4=Rabu, 5=Kamis, 6=Jumat, 7=Sabtu
     * Selalu mengembalikan tepat 7 entri (Senin–Minggu).
     *
     * Requirements: 6.2, 6.3
     *
     * @param array $filters ['timezone', 'channel', 'start_date', 'end_date']
     * @return array ['labels' => ['Senin', ..., 'Minggu'], 'data' => [...]]
     */
    public function getDailyDistributionData(array $filters): array
    {
        $timezone  = $filters['timezone']   ?? 'UTC';
        $channel   = $filters['channel']    ?? null;
        $startDate = $filters['start_date'];
        $endDate   = $filters['end_date'];

        $tzOffset = $this->getTimezoneOffset($timezone);

        // Cek apakah CONVERT_TZ() tersedia
        try {
            $sampleRaw = DB::selectOne(
                "SELECT CONVERT_TZ(NOW(), '+00:00', ?) AS tz_check",
                [$tzOffset]
            );
            $convertTzAvailable = $sampleRaw && $sampleRaw->tz_check !== null;
        } catch (\Exception $e) {
            $convertTzAvailable = false;
        }

        $query = User::whereNot(function ($q) {
            $q->where('email', 'like', 'anon_%@livechat.best')
              ->where('name', 'Guest');
        });
        if ($channel !== null) {
            $query->where('origin', $channel);
        }

        if ($convertTzAvailable) {
            // Use a subquery to avoid ONLY_FULL_GROUP_BY issues
            // MySQL DAYOFWEEK: 1=Minggu, 2=Senin, ..., 7=Sabtu
            $channelWhere = $channel !== null ? "AND origin = ?" : "";
            $channelBindings = $channel !== null ? [$channel] : [];

            $sql = "
                SELECT grp.dow AS dow, COUNT(*) AS count
                FROM (
                    SELECT DAYOFWEEK(CONVERT_TZ(created_at, '+00:00', ?)) AS dow
                    FROM users
                    WHERE DATE(CONVERT_TZ(created_at, '+00:00', ?)) BETWEEN ? AND ?
                    AND NOT (email LIKE 'anon_%@livechat.best' AND name = 'Guest')
                    {$channelWhere}
                ) AS grp
                GROUP BY grp.dow
            ";
            $bindings = array_merge([$tzOffset, $tzOffset, $startDate, $endDate], $channelBindings);

            $rawRows = DB::select($sql, $bindings);
            $rows = [];
            foreach ($rawRows as $row) {
                $rows[(int) $row->dow] = $row->count;
            }
        } else {
            // Fallback: Carbon collection approach
            // Carbon dayOfWeek: 0=Minggu, 1=Senin, ..., 6=Sabtu
            // Kita konversi ke MySQL DAYOFWEEK convention: 1=Minggu, 2=Senin, ..., 7=Sabtu
            $start = Carbon::parse($startDate, $timezone)->startOfDay()->utc();
            $end   = Carbon::parse($endDate, $timezone)->endOfDay()->utc();

            $rows = (clone $query)
                ->whereBetween('created_at', [$start, $end])
                ->get()
                ->filter(function ($customer) use ($startDate, $endDate, $timezone) {
                    $date = Carbon::parse($customer->created_at)->setTimezone($timezone)->format('Y-m-d');
                    return $date >= $startDate && $date <= $endDate;
                })
                ->groupBy(function ($customer) use ($timezone) {
                    // Carbon dayOfWeek: 0=Minggu, 1=Senin, ..., 6=Sabtu
                    // Konversi ke MySQL DAYOFWEEK: 1=Minggu, 2=Senin, ..., 7=Sabtu
                    $carbonDow = (int) Carbon::parse($customer->created_at)->setTimezone($timezone)->format('w');
                    return $carbonDow + 1; // 0+1=1(Minggu), 1+1=2(Senin), ..., 6+1=7(Sabtu)
                })
                ->map(fn($group) => $group->count())
                ->toArray();
        }

        // Pemetaan MySQL DAYOFWEEK ke urutan Senin–Minggu
        // MySQL: 1=Minggu, 2=Senin, 3=Selasa, 4=Rabu, 5=Kamis, 6=Jumat, 7=Sabtu
        // Urutan output: Senin(2), Selasa(3), Rabu(4), Kamis(5), Jumat(6), Sabtu(7), Minggu(1)
        $dayMapping = [
            'Senin'  => 2,
            'Selasa' => 3,
            'Rabu'   => 4,
            'Kamis'  => 5,
            'Jumat'  => 6,
            'Sabtu'  => 7,
            'Minggu' => 1,
        ];

        $labels = [];
        $data   = [];
        foreach ($dayMapping as $label => $mysqlDow) {
            $labels[] = $label;
            $data[]   = (int) ($rows[$mysqlDow] ?? 0);
        }

        return [
            'labels' => $labels,
            'data'   => $data,
        ];
    }

    /**
     * Mengkonversi timezone string ke offset format '+HH:MM'.
     * Contoh: 'Asia/Jakarta' → '+07:00'
     */
    private function getTimezoneOffset(string $timezone): string
    {
        return Carbon::now($timezone)->format('P');
    }
}
