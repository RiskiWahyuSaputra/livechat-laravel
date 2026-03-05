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

    public function run(): void
    {
        $this->command->info('Creating dummy data...');

        // ============================================
        // ADMINS (Agents) - Table: admins
        // Columns: id, username, email, password, role, is_superadmin, permissions, status, max_active_chats, remember_token, created_at, updated_at
        // ============================================
        
        // Check if agents already exist
        $existingAgents = Admin::where('role', 'agent')->count();
        
        if ($existingAgents === 0) {
            // Create agents if none exist
            $agents = collect([
                Admin::create([
                    'username' => 'agent1',
                    'email' => 'agent1@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'agent',
                    'is_superadmin' => false,
                    'permissions' => null,
                    'status' => 'online',
                    'max_active_chats' => 5,
                ]),
                Admin::create([
                    'username' => 'agent2',
                    'email' => 'agent2@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'agent',
                    'is_superadmin' => false,
                    'permissions' => null,
                    'status' => 'busy',
                    'max_active_chats' => 4,
                ]),
                Admin::create([
                    'username' => 'agent3',
                    'email' => 'agent3@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'agent',
                    'is_superadmin' => false,
                    'permissions' => null,
                    'status' => 'offline',
                    'max_active_chats' => 6,
                ]),
                Admin::create([
                    'username' => 'agent4',
                    'email' => 'agent4@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'agent',
                    'is_superadmin' => false,
                    'permissions' => null,
                    'status' => 'online',
                    'max_active_chats' => 3,
                ]),
                Admin::create([
                    'username' => 'agent5',
                    'email' => 'agent5@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'agent',
                    'is_superadmin' => false,
                    'permissions' => null,
                    'status' => 'offline',
                    'max_active_chats' => 5,
                ]),
            ]);
            $this->command->info('Created ' . $agents->count() . ' agents');
        } else {
            $agents = Admin::where('role', 'agent')->get();
            $this->command->info('Agents already exist (' . $agents->count() . ' found), using existing data');
        }

        // ============================================
        // CUSTOMERS (Users) - Table: users
        // Columns: id, name, email, email_verified_at, password, is_online, is_blocked, remember_token, created_at, updated_at, contact, origin
        // ============================================
        
        $origins = [
            'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang', 
            'Makassar', 'Tangerang', 'Bekasi', 'Depok', 'Yogyakarta', 
            'Bali', 'Lampung', 'Kalimantan', 'Sulawesi', 'Papua', 'Unknown'
        ];
        
        $firstNames = [
            'Budi', 'Ani', 'Dedi', 'Siti', 'Joko', 'Rina', 'Ahmad', 'Dewi', 
            'Hendra', 'Lina', 'Wahyu', 'Putri', 'Rizal', 'Yuni', 'Fajar', 
            'Nisa', 'Bayu', 'Sari', 'Doni', 'Mega', 'Tono', 'Maya', 'Ivan', 
            'Grace', 'Dimas', 'Annisa', 'Reza', 'Cantika', 'Feri', 'Sindy'
        ];

        $customers = [];
        for ($i = 1; $i <= 50; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $customer = User::create([
                'name' => $firstName . ' ' . chr(65 + ($i % 26)) . chr(65 + (($i + 5) % 26)),
                'email' => 'customer' . $i . '@example.com',
                'email_verified_at' => Carbon::now()->subDays(rand(1, 30)),
                'password' => Hash::make('password'),
                'is_online' => $i <= 15, // 15 online users
                'is_blocked' => rand(0, 10) > 9, // ~10% blocked
                'contact' => '+6281' . rand(10000000, 99999999),
                'origin' => $origins[array_rand($origins)],
            ]);
            $customers[] = $customer;
        }

        $this->command->info('Created ' . count($customers) . ' customers (users)');

        // ============================================
        // CONVERSATIONS - Table: conversations
        // Columns: id, user_id (FK), admin_id (FK nullable), status, queue_position, problem_category, bot_phase, last_message_at, created_at, updated_at, deleted_at
        // ============================================
        
        $statuses = [
            'closed' => 60,   // 60% closed
            'active' => 20,   // 20% active
            'pending' => 10,   // 10% pending
            'queued' => 10    // 10% queued
        ];
        
        $categories = [
            'Pertanyaan Umum', 'Dukungan Teknis', 'Pembayaran', 'Penjualan', 
            'Keluhan', 'Umpan Balik', 'Akun', 'Produk', 'Layanan', 'Lainnya'
        ];

        $conversations = [];
        $conversationCount = 60;

        for ($i = 1; $i <= $conversationCount; $i++) {
            // Determine status based on weighted random
            $rand = rand(1, 100);
            if ($rand <= 60) $status = 'closed';
            elseif ($rand <= 80) $status = 'active';
            elseif ($rand <= 90) $status = 'pending';
            else $status = 'queued';

            // Random date within last 30 days
            $daysAgo = rand(0, 30);
            $hoursAgo = rand(0, 23);
            $minutesAgo = rand(0, 59);
            $createdAt = Carbon::now()->subDays($daysAgo)->subHours($hoursAgo)->subMinutes($minutesAgo);

            // 85% have admin assigned
            $hasAdmin = rand(1, 100) <= 85;
            $admin = $hasAdmin ? $agents->random() : null;

            // For closed conversations, admin must be assigned
            if ($status === 'closed' && !$admin) {
                $admin = $agents->random();
            }

            // For closed conversations, bot should be disabled
            // For active/pending/queued, bot should start fresh
            $botPhase = null;
            if ($status === 'closed') {
                $botPhase = 'off'; // Disable bot for closed conversations
            } elseif ($status === 'active') {
                // If active and has admin, bot might be off or awaiting_category
                $botPhase = $admin ? 'off' : 'awaiting_category';
            } elseif ($status === 'pending' || $status === 'queued') {
                $botPhase = 'awaiting_category'; // Bot waiting for category
            }

            $conversation = Conversation::create([
                'user_id' => $customers[array_rand($customers)]->id,
                'admin_id' => $admin ? $admin->id : null,
                'status' => $status,
                'queue_position' => $status === 'queued' ? rand(1, 5) : null,
                'problem_category' => $status === 'closed' ? $categories[array_rand($categories)] : null,
                'bot_phase' => $botPhase,
                'created_at' => $createdAt,
                'updated_at' => $status === 'closed' 
                    ? $createdAt->copy()->addMinutes(rand(5, 120)) 
                    : $createdAt->copy()->addMinutes(rand(1, 30)),
                'last_message_at' => $createdAt->copy()->addMinutes(rand(1, 60)),
            ]);

            $conversations[] = $conversation;
        }

        $this->command->info('Created ' . count($conversations) . ' conversations');

        // ============================================
        // MESSAGES - Table: messages
        // Columns: id, conversation_id (FK), sender_id, sender_type (user/admin/system), message_type, content, is_read, created_at, updated_at
        // ============================================
        
        $messageId = 1;
        $totalMessages = 0;

        foreach ($conversations as $conversation) {
            // Random number of messages per conversation (2-12)
            $messageCount = rand(2, 12);
            $conversationTime = $conversation->created_at;
            
            // If admin exists, they reply after first user message
            $adminReplied = false;
            
            for ($j = 1; $j <= $messageCount; $j++) {
                // First message always from user
                if ($j === 1) {
                    $senderType = 'user';
                    $senderId = $conversation->user_id;
                } elseif (!$adminReplied && $conversation->admin_id) {
                    // Second message from admin if exists
                    $senderType = 'admin';
                    $senderId = $conversation->admin_id;
                    $adminReplied = true;
                } else {
                    // Alternate between user and admin
                    $senderType = $j % 2 == 0 ? 'admin' : 'user';
                    $senderId = $senderType === 'admin' ? $conversation->admin_id : $conversation->user_id;
                }

                // Skip if no admin for admin messages
                if ($senderType === 'admin' && !$conversation->admin_id) {
                    $senderType = 'user';
                    $senderId = $conversation->user_id;
                }

                $messageTypes = ['text', 'text', 'text', 'text', 'image', 'file'];
                $content = $this->generateMessageContent($senderType, $j, $messageCount);

                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => (int) $senderId,
                    'sender_type' => $senderType,
                    'message_type' => $messageTypes[array_rand($messageTypes)],
                    'content' => $content,
                    'is_read' => $j < $messageCount || $conversation->status === 'closed',
                    'created_at' => $conversationTime,
                    'updated_at' => $conversationTime,
                ]);

                // Add 1-8 minutes between messages
                $conversationTime = $conversationTime->copy()->addMinutes(rand(1, 8));
                $totalMessages++;
            }

            // Update last_message_at
            $conversation->update(['last_message_at' => $conversationTime]);
        }

        $this->command->info('Created ' . $totalMessages . ' messages');

        // ============================================
        // QUICK REPLIES - Table: quick_replies
        // Columns: id, title, content, created_at, updated_at
        // ============================================
        
        $quickReplies = [
            ['title' => 'Salam Utama', 'content' => 'Halo! Terima kasih telah menghubungi kami. Ada yang bisa saya bantu hari ini?'],
            ['title' => 'Penutup', 'content' => 'Apakah ada hal lain yang bisa saya bantu? Jika tidak, terima kasih telah menghubungi kami!'],
            ['title' => 'Eskalasi', 'content' => 'Saya mengerti kendala Anda. Izinkan saya meneruskan ini ke tim spesialis kami yang dapat membantu Anda dengan lebih baik.'],
            ['title' => 'Mohon Tunggu', 'content' => 'Mohon tunggu sebentar, saya akan memeriksa data tersebut untuk Anda.'],
            ['title' => 'Tindak Lanjut', 'content' => 'Kami akan menindaklanjuti permintaan Anda melalui email dalam waktu 24 jam.'],
            ['title' => 'Masalah Teknis', 'content' => "Saya melihat Anda mengalami kendala teknis. Bisa jelaskan lebih detail mengenai pesan error yang muncul?"],
            ['title' => 'Pertanyaan Pembayaran', 'content' => 'Untuk pertanyaan pembayaran, saya bisa membantu memeriksa akun Anda. Bisa konfirmasi alamat email akun Anda?'],
            ['title' => 'Terima Kasih', 'content' => 'Terima kasih atas kesabaran Anda. Kami sangat menghargai pengertiannya.'],
            ['title' => 'Bantuan Akun', 'content' => 'Saya bisa membantu masalah akun Anda. Kendala spesifik apa yang sedang Anda hadapi?'],
            ['title' => 'Info Produk', 'content' => 'Tim kami akan segera memberikan informasi detail produk yang Anda butuhkan.'],
        ];

        foreach ($quickReplies as $reply) {
            QuickReply::create([
                'title' => $reply['title'],
                'content' => $reply['content'],
            ]);
        }

        $this->command->info('Created ' . QuickReply::count() . ' quick replies');

        // ============================================
        // SUMMARY
        // ============================================
        
        $this->command->info('');
        $this->command->info('=== Ringkasan Data Dummy ===');
        $this->command->info('Admin/Agen: ' . Admin::count());
        $this->command->info('  - Super Admin: 1');
        $this->command->info('  - Agen: ' . ($agents->count()));
        $this->command->info('Pelanggan (User): ' . User::count());
        $this->command->info('  - Online: ' . User::where('is_online', true)->count());
        $this->command->info('  - Diblokir: ' . User::where('is_blocked', true)->count());
        $this->command->info('Percakapan: ' . Conversation::count());
        $this->command->info('  - Selesai: ' . Conversation::where('status', 'closed')->count());
        $this->command->info('  - Aktif: ' . Conversation::where('status', 'active')->count());
        $this->command->info('  - Pending: ' . Conversation::where('status', 'pending')->count());
        $this->command->info('  - Antrean: ' . Conversation::where('status', 'queued')->count());
        $this->command->info('Pesan: ' . Message::count());
        $this->command->info('Balasan Cepat: ' . QuickReply::count());
        $this->command->info('=========================');
        $this->command->info('Pembuatan data dummy selesai!');
    }

    private function generateMessageContent($senderType, $index, $total)
    {
        $userMessages = [
            "Halo, saya butuh bantuan dengan akun saya",
            "Hai, saya punya pertanyaan mengenai layanan Anda",
            "Apakah ada orang di sini?",
            "Saya butuh dukungan teknis, tolong",
            "Bisakah Anda membantu saya?",
            "Saya ingin tahu lebih banyak tentang produk Anda",
            "Saya ada masalah dengan pesanan saya",
            "Bagaimana cara reset kata sandi saya?",
            "Kapan jam operasional kantor?",
            "Saya ingin mengajukan komplain mengenai pembayaran",
            "Terima kasih atas bantuannya!",
            "Bagus sekali, terima kasih banyak!",
            "Saya akan mencoba solusi tersebut",
            "Satu pertanyaan lagi, tolong",
            "Apakah ada cara untuk upgrade akun saya?",
            "Bisakah saya mendapatkan pengembalian dana?",
            "Kapan pesanan saya akan dikirim?",
            "Saya butuh bantuan untuk instalasi",
            "Aplikasinya tidak berjalan dengan benar",
            "Saya ingin membatalkan langganan saya"
        ];

        $adminMessages = [
            "Halo! Selamat datang di layanan chat kami. Ada yang bisa saya bantu hari ini?",
            "Terima kasih telah menghubungi kami. Saya akan dengan senang hati membantu Anda.",
            "Saya mengerti kendala Anda. Izinkan saya memeriksanya terlebih dahulu.",
            "Tentu, saya bisa langsung membantu Anda untuk hal tersebut.",
            "Mohon tunggu sebentar, saya sedang memeriksa detailnya.",
            "Apakah ada hal lain yang bisa saya bantu?",
            "Masalah Anda telah diselesaikan. Beritahu saya jika butuh bantuan lebih lanjut.",
            "Saya telah melakukan perubahan pada akun Anda sesuai permintaan.",
            "Bisakah Anda memberikan detail lebih lanjut mengenai kendalanya?",
            "Saya mengerti. Izinkan saya menyelidiki hal ini lebih lanjut.",
            "Bagus! Apakah ada hal lain yang Anda perlukan?",
            "Sama-sama! Semoga hari Anda menyenangkan!",
            "Saya telah meneruskan permintaan Anda ke tim teknis kami.",
            "Mohon berikan saya waktu beberapa menit untuk memproses ini.",
            "Permintaan Anda sedang diproses. Kami akan memberi tahu Anda melalui email."
        ];

        if ($senderType === 'user') {
            return $userMessages[array_rand($userMessages)];
        } elseif ($senderType === 'admin') {
            // First admin message should be greeting
            if ($index === 2 || $index === 1) {
                return "Halo! Selamat datang. Ada yang bisa saya bantu hari ini?";
            }
            return $adminMessages[array_rand($adminMessages)];
        } else {
            // System message
            $systemMessages = [
                "Percakapan dimulai",
                "Agen bergabung ke percakapan",
                "Percakapan dialihkan",
                "Chat ditutup oleh agen"
            ];
            return $systemMessages[array_rand($systemMessages)];
        }
    }
}
