<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom command sebagai nullable dulu (agar data lama bisa diisi)
        Schema::table('quick_replies', function (Blueprint $table) {
            $table->string('command', 50)->nullable()->after('title');
        });

        // 2. Isi kolom command dari nilai title yang ada
        DB::table('quick_replies')->get()->each(function ($reply) {
            $command = Str::lower($reply->title);
            $command = preg_replace('/\s+/', '_', $command);
            $command = preg_replace('/[^a-z0-9_]/', '', $command);
            $command = trim($command, '_');

            // Fallback ke reply_{id} jika hasil konversi kosong
            if (empty($command)) {
                $command = 'reply_' . $reply->id;
            }

            // Potong ke 50 karakter (base untuk handle duplikat)
            $command = substr($command, 0, 50);

            // Handle duplikat: tambahkan suffix _1, _2, dst.
            $base = $command;
            $i = 1;
            while (DB::table('quick_replies')
                ->where('command', $command)
                ->where('id', '!=', $reply->id)
                ->exists()
            ) {
                $suffix = '_' . $i++;
                $command = substr($base, 0, 50 - strlen($suffix)) . $suffix;
            }

            DB::table('quick_replies')
                ->where('id', $reply->id)
                ->update(['command' => $command]);
        });

        // 3. Jadikan not null dan tambah unique index
        Schema::table('quick_replies', function (Blueprint $table) {
            $table->string('command', 50)->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_replies', function (Blueprint $table) {
            $table->dropUnique(['command']);
            $table->dropColumn('command');
        });
    }
};
