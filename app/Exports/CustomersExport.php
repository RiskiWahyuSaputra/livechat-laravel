<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filter;

    public function __construct($filter)
    {
        $this->filter = $filter;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = User::whereNot(function($q) {
            $q->where('email', 'like', 'anon_%@livechat.best')
              ->where('name', 'Guest');
        });

        if ($this->filter == '1_month') {
            $query->where('created_at', '>=', now()->subMonth());
        } elseif ($this->filter == '1_year') {
            $query->where('created_at', '>=', now()->subYear());
        }

        return $query->with('conversations')->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID Pelanggan',
            'Nama Pelanggan',
            'Kontak',
            'Asal / Instansi',
            'Status',
            'Tanggal Daftar',
        ];
    }

    public function map($user): array
    {
        // Logic status yang sama dengan ReportController
        $activeConv = $user->conversations->whereIn('status', ['pending', 'queued', 'active'])->first();
        $status = $activeConv ? $activeConv->status : ($user->is_online ? 'online' : 'offline');
        
        $statusLabels = [
            'pending' => 'Menunggu',
            'queued' => 'Antrean',
            'active' => 'Aktif (Chat)',
            'online' => 'Online',
            'offline' => 'Offline',
            'no_session' => 'Selesai'
        ];

        return [
            'CUST-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
            $user->name,
            $user->contact,
            $user->origin,
            $statusLabels[$status] ?? 'Offline',
            $user->created_at->format('d M Y H:i'),
        ];
    }
}
