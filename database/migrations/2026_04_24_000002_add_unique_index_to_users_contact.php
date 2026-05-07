<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deduplicate existing rows before adding unique index
        // Keep the oldest record per contact value
        $driver = \DB::getDriverName();
        if ($driver === 'sqlite') {
            \DB::statement("
                DELETE FROM users
                WHERE id NOT IN (
                    SELECT MIN(id) FROM users
                    WHERE contact IS NOT NULL AND contact != ''
                    GROUP BY contact
                ) AND contact IS NOT NULL AND contact != ''
            ");
        } else {
            \DB::statement("
                DELETE u1 FROM users u1
                INNER JOIN users u2
                WHERE u1.id > u2.id
                  AND u1.contact IS NOT NULL
                  AND u1.contact != ''
                  AND u1.contact = u2.contact
            ");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('contact');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['contact']);
        });
    }
};
