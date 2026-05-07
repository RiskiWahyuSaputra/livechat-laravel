<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('feedback_status', 20)
                ->default('not_requested')
                ->after('problem_category');
            $table->timestamp('feedback_requested_at')
                ->nullable()
                ->after('feedback_status');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn([
                'feedback_status',
                'feedback_requested_at',
            ]);
        });
    }
};
