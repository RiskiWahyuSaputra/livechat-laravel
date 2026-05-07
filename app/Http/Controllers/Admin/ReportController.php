<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Exports\CustomersExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    private function getBaseQuery(Request $request)
    {
        // Gunakan User model (Pelanggan) dengan filter yang sama seperti CustomerController
        $query = User::whereNot(function($q) {
            $q->where('email', 'like', 'anon_%@livechat.best')
              ->where('name', 'Guest');
        });

        $filter = $request->get('filter');
        if ($filter == '1_month') {
            $query->where('created_at', '>=', now()->subMonth());
        } elseif ($filter == '1_year') {
            $query->where('created_at', '>=', now()->subYear());
        }

        return $query;
    }

    private function mapUserStatus($user)
    {
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

        $statusClasses = [
            'pending' => 'bg-warning',
            'queued' => 'bg-info',
            'active' => 'bg-primary',
            'online' => 'bg-success',
            'offline' => 'bg-secondary',
            'no_session' => 'bg-light'
        ];

        return [
            'label' => $statusLabels[$status] ?? 'Offline',
            'class' => $statusClasses[$status] ?? 'bg-secondary'
        ];
    }

    public function index(Request $request)
    {
        $filter = $request->get('filter');
        $query = $this->getBaseQuery($request);
        $customers = $query->with('conversations')->latest()->get();

        // Map status untuk tampilan awal
        foreach ($customers as $customer) {
            $statusData = $this->mapUserStatus($customer);
            $customer->status_label = $statusData['label'];
            $customer->status_class = $statusData['class'];
        }

        return view('admin.reports.index', compact('customers', 'filter'));
    }

    public function exportExcel(Request $request)
    {
        try {
            $filter = $request->get('filter');
            return Excel::download(new CustomersExport($filter), 'laporan_pelanggan.xlsx');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengekspor Excel: ' . $e->getMessage());
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $filter = $request->get('filter');
            $query = $this->getBaseQuery($request);
            $customers = $query->with('conversations')->latest()->get();

            foreach ($customers as $customer) {
                $statusData = $this->mapUserStatus($customer);
                $customer->status_label = $statusData['label'];
            }

            $pdf = Pdf::loadView('admin.reports.pdf', compact('customers', 'filter'));

            return $pdf->download('laporan_pelanggan.pdf');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengekspor PDF: ' . $e->getMessage());
        }
    }

    public function apiData(Request $request)
    {
        $query = $this->getBaseQuery($request);
        $customers = $query->with('conversations')->latest()->get()->map(function($c) {
            $statusData = $this->mapUserStatus($c);
            return [
                'id' => $c->id,
                'custom_id' => 'CUST-' . str_pad($c->id, 4, '0', STR_PAD_LEFT),
                'name' => $c->name,
                'contact' => $c->contact,
                'origin' => $c->origin,
                'status' => $statusData['label'],
                'status_class' => $statusData['class'],
                'is_blocked' => $c->is_blocked,
                'created_at' => $c->created_at->format('d M Y H:i')
            ];
        });

        return response()->json($customers);
    }
}
