<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\QuickReply;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private $categories = [
        'Pertanyaan Umum', 'Dukungan Teknis', 'Pembayaran', 'Penjualan', 
        'Keluhan', 'Akun'
    ];

    private $chatFlows = [
        'Pertanyaan Umum' => [
            [
                ['user', 'text', 'Halo min, untuk jam operasional layanan dari jam berapa ya?'],
                ['admin', 'text', 'Halo kak! Layanan kami beroperasi dari Senin-Jumat pukul 08:00 - 17:00 WIB, dan Sabtu pukul 08:00 - 13:00 WIB ya.'],
                ['user', 'text', 'Oh gitu, kalau tanggal merah libur tidak?'],
                ['admin', 'text', 'Iya kak, untuk tanggal merah nasional kami libur operasional.'],
                ['user', 'text', 'Oke min, terima kasih ya infonya.'],
                ['admin', 'text', 'Sama-sama kak! Ada lagi yang bisa kami bantu?']
            ],
            [
                ['user', 'text', 'Siang, mau tanya alamat tokonya di mana ya?'],
                ['admin', 'text', 'Halo kak, selamat siang. Untuk lokasi toko kami ada di Jl. Sudirman No 123, Jakarta Selatan ya.'],
                ['user', 'text', 'Oke, kira-kira patokannya apa min?'],
                ['admin', 'text', 'Patokannya di seberang gedung BCA cabang Sudirman kak.']
            ]
        ],
        'Dukungan Teknis' => [
            [
                ['user', 'text', 'Permisi, saya tidak bisa login ke aplikasi nih.'],
                ['admin', 'text', 'Halo kak, mohon maaf atas ketidaknyamanannya. Boleh diinfokan pesan error yang muncul seperti apa?'],
                ['user', 'text', 'Muncul tulisan "Invalid credentials" padahal password sudah benar.'],
                ['admin', 'text', 'Baik kak, apakah kakak sudah mencoba fitur lupa password untuk mengatur ulang kata sandi?'],
                ['user', 'text', 'Belum kak, saya coba dulu deh.'],
                ['admin', 'text', 'Silakan kak, jika masih terkendala jangan ragu untuk menghubungi kami kembali ya.']
            ],
            [
                ['user', 'text', 'Aplikasinya kok tiba-tiba force close ya pas buka menu profile?'],
                ['admin', 'text', 'Halo kak, mohon maaf atas kendalanya. Boleh diinfokan kakak menggunakan HP tipe apa dan OS versi berapa?'],
                ['user', 'text', 'Saya pakai Samsung A52, Android 13.'],
                ['admin', 'text', 'Baik kak, kami sarankan untuk melakukan clear cache pada aplikasi atau update ke versi terbaru di Play Store ya.'],
                ['user', 'text', 'Oke saya coba update dulu ya min.']
            ]
        ],
        'Pembayaran' => [
            [
                ['user', 'text', 'Halo, saya tadi sudah transfer untuk pesanan INV-12345 tapi statusnya kok belum berubah?'],
                ['admin', 'text', 'Halo kak, mohon maaf. Boleh dibantu kirimkan bukti transfernya agar kami bantu cek ke tim finance?'],
                ['user', 'image', 'bukti_transfer.jpg'],
                ['admin', 'text', 'Terima kasih kak. Kami akan segera lakukan pengecekan, mohon ditunggu sebentar ya.'],
                ['admin', 'text', 'Halo kak, pembayaran untuk pesanan INV-12345 sudah berhasil kami verifikasi. Status pesanan kakak sekarang sudah diproses ya.'],
                ['user', 'text', 'Syukurlah, terima kasih banyak min!']
            ]
        ],
        'Penjualan' => [
            [
                ['user', 'text', 'Halo min, produk sepatu seri X ukuran 42 warna hitam masih ready?'],
                ['admin', 'text', 'Halo kak! Untuk seri X ukuran 42 warna hitam saat ini sisa 2 pasang saja kak. Silakan bisa langsung diorder sebelum kehabisan.'],
                ['user', 'text', 'Kalau pesen sekarang bisa langsung dikirim hari ini pakai GOSEND?'],
                ['admin', 'text', 'Bisa banget kak! Untuk pesanan dengan GOSEND sebelum jam 15:00 akan kami proses hari ini juga.'],
                ['user', 'text', 'Siapp, saya checkout sekarang ya.'],
                ['admin', 'text', 'Baik kak, kami tunggu pesanannya!']
            ]
        ],
        'Keluhan' => [
            [
                ['user', 'text', 'Halo team, barang saya kok lama banget ya sampainya? Padahal estimasi kemarin.'],
                ['admin', 'text', 'Halo kak, mohon maaf sebelumnya. Boleh dibantu informasikan nomor resi atau nomor pesanannya?'],
                ['user', 'text', 'Pesanan nomor TRX-998877'],
                ['admin', 'text', 'Baik kak, kami bantu tracking ya. Ternyata ada keterlambatan dari pihak ekspedisi karena cuaca buruk di area tujuan.'],
                ['admin', 'text', 'Kami akan bantu follow up ke pihak ekspedisi agar barang kakak bisa diprioritaskan.'],
                ['user', 'text', 'Hmm ya sudah, tolong dibantu pantau terus ya min.'],
                ['admin', 'text', 'Pasti kak, kami akan usahakan yang terbaik. Mohon kesediaannya menunggu ya.']
            ]
        ],
        'Akun' => [
            [
                ['user', 'text', 'Min, saya mau ganti nomor HP di akun saya gimana caranya ya? Nomor lama sudah hangus.'],
                ['admin', 'text', 'Halo kak, untuk pergantian nomor HP yang sudah tidak aktif, kakak perlu mengirimkan foto KTP dan selfie dengan KTP untuk verifikasi keamanan.'],
                ['user', 'text', 'Waduh agak repot juga ya.'],
                ['admin', 'text', 'Betul kak, ini untuk memastikan keamanan akun kakak dari pihak yang tidak bertanggung jawab.'],
                ['user', 'file', 'ktp_dan_selfie.pdf'],
                ['admin', 'text', 'Data sudah kami terima, mohon tunggu maksimal 1x24 jam untuk proses verifikasi tim kami ya kak.']
            ]
        ]
    ];

    public function run(): void
    {
        $this->command->info('Creating realistic dummy data...');

        // ============================================
        // ADMINS (Agents)
        // ============================================
        $existingAgents = Admin::where('role', 'agent')->count();
        
        if ($existingAgents < 10) {
            $agentsToAdd = [
                ['username' => 'sarah_agent', 'email' => 'sarah@example.com', 'status' => 'online'],
                ['username' => 'budi_support', 'email' => 'budi@example.com', 'status' => 'busy'],
                ['username' => 'dian_cs', 'email' => 'dian@example.com', 'status' => 'offline'],
                ['username' => 'andi_help', 'email' => 'andi@example.com', 'status' => 'online'],
                ['username' => 'rini_care', 'email' => 'rini@example.com', 'status' => 'online'],
                ['username' => 'tony_tech', 'email' => 'tony@example.com', 'status' => 'busy'],
                ['username' => 'lisa_cs', 'email' => 'lisa@example.com', 'status' => 'offline'],
                ['username' => 'kevin_agent', 'email' => 'kevin@example.com', 'status' => 'online'],
                ['username' => 'maya_support', 'email' => 'maya@example.com', 'status' => 'busy'],
                ['username' => 'indra_help', 'email' => 'indra@example.com', 'status' => 'offline'],
            ];
            foreach ($agentsToAdd as $agent) {
                Admin::firstOrCreate(
                    ['email' => $agent['email']],
                    [
                        'username' => $agent['username'],
                        'password' => Hash::make('password'),
                        'role' => 'agent',
                        'is_superadmin' => false,
                        'status' => $agent['status'],
                        'max_active_chats' => 5,
                    ]
                );
            }
            $this->command->info('Ditambahkan agen tambahan agar simulasi lebih real.');
        }

        $agents = Admin::where('role', 'agent')->get();
        $this->command->info('Menggunakan ' . $agents->count() . ' agen.');

        // ============================================
        // CUSTOMERS (Users) 
        // ============================================
        $origins = [
            'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang', 
            'Makassar', 'Tangerang', 'Bekasi', 'Depok', 'Yogyakarta', 
            'Bali', 'Lampung', 'Kalimantan', 'Sulawesi', 'Papua'
        ];
        
        $names = [
            'Ahmad Setiawan', 'Budi Santoso', 'Citra Lestari', 'Dewi Anggraini', 
            'Eko Prasetyo', 'Fajar Nugroho', 'Gita Gutawa', 'Hendra Wijaya', 
            'Indah Permatasari', 'Joko Susilo', 'Kartika Putri', 'Lukman Hakim', 
            'Maya Sari', 'Nita Thalia', 'Oki Setiana', 'Putra Pratama',
            'Rangga Azof', 'Sari Nila', 'Tariq Halilintar', 'Umar Syarief',
            'Vina Panduwinata', 'Wira Setianagara', 'Xena Aprilia', 'Yayan Ruhian',
            'Zaskia Sungkar', 'Aditya Roy', 'Bagas Kaffa', 'Cynthia Bella',
            'Dion Wiyoko', 'El Rumi', 'Fandy Christian', 'Gisel Anastasia',
            'Hesti Purwadinata', 'Iqbaal Ramadhan', 'Jessica Mila', 'Kevin Julio',
            'Luna Maya', 'Mikha Tambayong', 'Nadine Chandrawinata', 'Oka Antara'
        ];

        $customers = [];
        for ($i = 0; $i < count($names); $i++) {
            $customers[] = User::create([
                'name' => $names[$i],
                'email' => strtolower(str_replace(' ', '.', $names[$i])) . '@gmail.com',
                'email_verified_at' => Carbon::now()->subDays(rand(1, 7)), // 7 days backwards
                'password' => Hash::make('password'),
                'is_online' => $i < 15, // 15 online users
                'is_blocked' => rand(1, 100) > 95, // 5% blocked
                'contact' => '+6281' . rand(10000000, 99999999),
                'origin' => $origins[array_rand($origins)],
            ]);
        }
        $this->command->info('Created ' . count($customers) . ' real-sounding customers');

        // ============================================
        // CONVERSATIONS & MESSAGES
        // ============================================
        $conversationCount = 150; // Higher volume for 7 days span (around 20-30 chats/day)
        $totalMessages = 0;

        for ($i = 1; $i <= $conversationCount; $i++) {
            $statusOptions = ['closed', 'closed', 'closed', 'closed', 'active', 'active', 'pending', 'queued'];
            $status = $statusOptions[array_rand($statusOptions)]; // Most are closed

            // Distributed over the last 7 days
            $daysAgo = rand(0, 7);
            $hoursAgo = rand(0, 23);
            $minutesAgo = rand(0, 59);
            $startedAt = Carbon::now()->subDays($daysAgo)->subHours($hoursAgo)->subMinutes($minutesAgo);
            
            // Random category and its flow
            $category = $this->categories[array_rand($this->categories)];
            $flowOptions = $this->chatFlows[$category];
            $flow = $flowOptions[array_rand($flowOptions)];

            $hasAdmin = in_array($status, ['closed', 'active']);
            $admin = $hasAdmin ? $agents->random() : null;
            if ($status === 'closed') $admin = $agents->random();

            $botPhase = ($status === 'closed' || $status === 'active') ? 'off' : 'awaiting_category';

            $conversation = Conversation::create([
                'user_id' => $customers[array_rand($customers)]->id,
                'admin_id' => $admin ? $admin->id : null,
                'status' => $status,
                'queue_position' => $status === 'queued' ? rand(1, 4) : null,
                'problem_category' => $category,
                'bot_phase' => $botPhase,
                'created_at' => $startedAt,
                'updated_at' => $startedAt,
                'last_message_at' => $startedAt,
                'deleted_at' => $status === 'closed' ? $startedAt : null,
            ]);

            // Determine how many messages from the flow to insert based on status
            if ($status === 'closed') {
                $messagesToInsert = count($flow); // All messages
            } elseif ($status === 'active') {
                $messagesToInsert = rand(2, count($flow)); // Partial or all
            } else { // queued, pending
                $messagesToInsert = 1; // Only user's first message
            }

            $msgTime = $startedAt->copy();
            for ($k = 0; $k < $messagesToInsert; $k++) {
                $msgData = $flow[$k];
                $senderRole = $msgData[0];
                $msgType = $msgData[1];
                $msgContent = $msgData[2];

                if ($senderRole === 'admin' && !$admin) {
                    break; // stop inserting admin messages if no admin assigned yet
                }

                $senderId = $senderRole === 'admin' ? $admin->id : $conversation->user_id;

                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $senderId,
                    'sender_type' => $senderRole,
                    'message_type' => $msgType,
                    'content' => $msgContent,
                    'is_read' => $status === 'closed' || $k < ($messagesToInsert - 1),
                    'created_at' => $msgTime,
                    'updated_at' => $msgTime,
                ]);

                $msgTime->addMinutes(rand(1, 5));
                $totalMessages++;
            }

            $updateData = [
                'last_message_at' => $msgTime,
                'updated_at' => $msgTime
            ];
            
            if ($status === 'closed') {
                $updateData['deleted_at'] = $msgTime;
            }

            $conversation->update($updateData);
        }
        $this->command->info("Created $conversationCount conversations with $totalMessages messages.");

        // ============================================
        // QUICK REPLIES
        // ============================================
        $quickReplies = [
            ['title' => 'Salam Utama', 'content' => 'Halo! Terima kasih telah menghubungi kami. Ada yang bisa saya bantu hari ini?'],
            ['title' => 'Sapaan Pagi', 'content' => 'Selamat Pagi! Ada yang bisa kami bantu?'],
            ['title' => 'Mohon Tunggu', 'content' => 'Mohon tunggu sebentar ya kak, kami sedang melakukan pengecekan data.'],
            ['title' => 'Tanya Resi', 'content' => 'Boleh dibantu informasikan nomor resi atau ID Pesanannya kak?'],
            ['title' => 'Kirim BuktiTF', 'content' => 'Mohon bantuannya untuk mengirimkan bukti transfer agar bisa kami verifikasi.'],
            ['title' => 'Masalah Teknis', 'content' => "Bisa jelaskan lebih detail kendala teknis yang dialami? Apakah ada pesan error yang muncul?"],
            ['title' => 'Tindak Lanjut', 'content' => 'Kendala kakak sudah kami teruskan ke tim terkait. Mohon kesediaannya menunggu.'],
            ['title' => 'Penutup', 'content' => 'Apakah ada hal lain yang bisa kami bantu kak?'],
            ['title' => 'Terima Kasih', 'content' => 'Terima kasih telah menghubungi kami. Semoga harinya menyenangkan!']
        ];

        foreach ($quickReplies as $reply) {
            QuickReply::firstOrCreate(
                ['title' => $reply['title']],
                ['content' => $reply['content']]
            );
        }

        $this->command->info('Created ' . count($quickReplies) . ' quick replies');

        $this->command->info('=== Ringkasan Data Dummy Realistic ===');
        $this->command->info('Admin/Agen: ' . Admin::count());
        $this->command->info('Pelanggan (User): ' . User::count());
        $this->command->info('Percakapan: ' . Conversation::count());
        $this->command->info('Pesan: ' . Message::count());
        $this->command->info('Pembuatan data dummy selesai!');
    }
}
