<!DOCTYPE html>
<html>
<head>
    <title>Data Pelanggan</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .filter-info { font-size: 14px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Data Pelanggan</h2>
        <p>LiveChat System</p>
    </div>

    <div class="filter-info">
        Filter Periode: 
        <strong>
            @if($filter == '1_month') 1 Bulan Terakhir 
            @elseif($filter == '1_year') 1 Tahun Terakhir 
            @else Semua Waktu 
            @endif
        </strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Pelanggan</th>
                <th>Kontak</th>
                <th>Asal / Instansi</th>
                <th>Status</th>
                <th>Tanggal Daftar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $customer)
            <tr>
                <td>CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->contact }}</td>
                <td>{{ $customer->origin }}</td>
                <td>{{ $customer->status_label }}</td>
                <td>{{ $customer->created_at->format('d M Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
