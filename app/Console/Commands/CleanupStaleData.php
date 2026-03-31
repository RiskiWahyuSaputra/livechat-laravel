<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Setting;
use Illuminate\Console\Command;

class CleanupStaleData extends Command
{
    protected $signature = 'chat:cleanup-stale-data {--force : Abaikan cek waktu, langsung jalankan pembersihan}';
    protected $description = 'Bersihkan user anonymous (anon_*) yang tidak aktif, beserta conversation dan pesannya.';

    public function handle()
    {
        // Cek waktu — hanya jalan jika jam sekarang cocok dengan pengaturan admin (kecuali --force)
        if (!$this->option('force')) {
            $configuredTime = Setting::get('cleanup_time', '03:00');
            $currentHour = now()->format('H:00');
            $configuredHour = substr($configuredTime, 0, 2) . ':00';

            if ($currentHour !== $configuredHour) {
                return Command::SUCCESS;
            }
        }

        $this->info('Memulai pembersihan data guest lapuk...');

        // 1. Cari semua user anonymous (anon_*)
        $staleUsers = User::where('email', 'LIKE', 'anon_%@livechat.best')->get();
        $deletedUsers = 0;
        $deletedConversations = 0;
        $deletedMessages = 0;

        foreach ($staleUsers as $user) {
            // 2. Cek apakah punya conversation yang masih aktif
            $hasActiveConversation = $user->conversations()
                ->whereIn('status', ['pending', 'active', 'queued'])
                ->exists();

            if ($hasActiveConversation) {
                $this->line("  Lewati: {$user->email} — masih punya percakapan aktif.");
                continue;
            }

            // 3. Ambil semua conversation ID (termasuk soft-deleted)
            $conversationIds = $user->conversations()->withTrashed()->pluck('id');

            if ($conversationIds->isEmpty()) {
                // Tidak ada conversation, langsung hapus user
                $user->forceDelete();
                $deletedUsers++;
                $this->line("  Hapus: {$user->email} — tidak ada percakapan.");
                continue;
            }

            // 4. Hapus semua pesan dalam conversation tersebut
            $msgCount = Message::whereIn('conversation_id', $conversationIds)->delete();
            $deletedMessages += $msgCount;

            // 5. Hapus permanent semua conversation (hard-delete)
            $convCount = Conversation::withTrashed()
                ->whereIn('id', $conversationIds)
                ->forceDelete();
            $deletedConversations += $convCount;

            // 6. Hapus user
            $user->forceDelete();
            $deletedUsers++;

            $this->line("  Hapus: {$user->email} — {$convCount} percakapan, {$msgCount} pesan.");
        }

        // 7. Bersihkan conversation orphan (tidak punya user)
        $orphanConversations = Conversation::withTrashed()
            ->whereNotIn('user_id', User::pluck('id'))
            ->get();

        foreach ($orphanConversations as $conv) {
            Message::where('conversation_id', $conv->id)->delete();
            $conv->forceDelete();
            $deletedConversations++;
        }

        if ($orphanConversations->isNotEmpty()) {
            $this->line("  Hapus {$orphanConversations->count()} conversation orphan.");
        }

        // 8. Bersihkan pesan orphan (tidak punya conversation)
        $orphanMessages = Message::whereNotIn('conversation_id', Conversation::withTrashed()->pluck('id'))->delete();
        if ($orphanMessages > 0) {
            $deletedMessages += $orphanMessages;
            $this->line("  Hapus {$orphanMessages} pesan orphan.");
        }

        // 9. Ringkasan
        $this->info('Pembersihan selesai.');
        $this->table(
            ['Tipe Data', 'Jumlah Dihapus'],
            [
                ['User anon_*', $deletedUsers],
                ['Conversation', $deletedConversations],
                ['Message', $deletedMessages],
            ]
        );

        return Command::SUCCESS;
    }
}
