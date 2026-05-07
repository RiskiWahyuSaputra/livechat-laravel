<?php

namespace Database\Seeders;

use App\Models\QuickReply;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuickReplySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $replies = [
            [
                'title'   => 'CSO Compress',
                'command' => 'cso_compress',
                'content' => 'Untuk ukuran fotonya jangan lebih dari 2mb ya. Silakan kompres terlebih dahulu ukuran fotonya melalui link http://compressjpeg.com/id/',
            ],
            [
                'title'   => 'CSO Cache',
                'command' => 'cso_cache',
                'content' => 'Baik, apabila kode keamanan terus muncul keterangan salah meskipun sudah diinput sesuai dengan yang tertera, silakan lakukan clear cache terlebih dahulu pada perangkat yang digunakan. Langkah-langkahnya dapat Anda cari melalui Google, karena setiap tipe handphone memiliki cara yang sedikit berbeda.',
            ],
            [
                'title'   => 'API OTP',
                'command' => 'api_otp',
                'content' => 'Kode OTP Transaksi {{transactionid}} Anda adalah {{otp}}. Silakan masukkan Kode OTP untuk menyelesaikan Transaksi Anda.',
            ],
            [
                'title'   => 'Aduan',
                'command' => 'aduan',
                'content' => 'Mohon maaf atas ketidaknyamanannya, untuk komplainnya saat ini sedang dalam proses tim terkait. Untuk mengecek progress komplainnya bisa dicek secara berkala di menu Activity kemudian pilih tiket aduan. Untuk kode tiket aduannya: ....',
            ],
            [
                'title'   => 'Add On Tebet',
                'command' => 'addontebet',
                'content' => 'Untuk transaksi add on dengan metode pick up di kantor tebet, silakan hubungi +62 812-9219-5489',
            ],
            [
                'title'   => 'Add On Bandung',
                'command' => 'addonbdg',
                'content' => 'Untuk transaksi add on dengan metode pengiriman dan pick up kantor bandung, silakan hubungi +62 821-1656-5653',
            ],
            [
                'title'   => 'Add On',
                'command' => 'addon',
                'content' => 'Add on bisa diproses dengan menyesuaikan dengan FO/RO yang sudah selesai transaksinya. Misalnya bapak/Ibu udah transaksi FO 14pv, bisa ajukan add on maksimal 14pv dengan harga 50.000/pv (harga menyesuaikan untuk produk yang ada penambahan biaya). Transaksi yang dipakai untuk promo adalah transaksi yang dilakukan di tahun 2024. 1 transaksi untuk 1x transaksi add on.',
            ],
            [
                'title'   => 'Sumber Poin',
                'command' => 'sumber_poin',
                'content' => 'Sumber poin ada 5 : 1. Dari belanja ulang (id penerima bonus diisi id Reseller di grup) 2. Dari mendaftarkan & FO Reseller baru 3. Dari pendaftaran & FO Reseller baru yang didaftarkan oleh Reseller di grup 4. Dari belanja ulang Reseller di grup 5. Dari upgrade Reseller di grup',
            ],
        ];

        foreach ($replies as $reply) {
            QuickReply::updateOrCreate(
                ['command' => $reply['command']],
                [
                    'title'   => $reply['title'],
                    'content' => $reply['content'],
                ]
            );
        }

        $this->command->info('✓ Seeded ' . count($replies) . ' quick replies.');
    }
}
