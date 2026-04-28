<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_menus', function (Blueprint $table) {
            $table->string('flow_type')->default('office_hour')->after('id')
                ->comment('office_hour | outside_office_hour | closed');
        });

        // Set existing menus to office_hour
        DB::table('bot_menus')->whereNull('flow_type')->update(['flow_type' => 'office_hour']);
    }

    public function down(): void
    {
        Schema::table('bot_menus', function (Blueprint $table) {
            $table->dropColumn('flow_type');
        });
    }
};
