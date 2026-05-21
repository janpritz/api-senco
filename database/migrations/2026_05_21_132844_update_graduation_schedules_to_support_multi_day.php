<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('graduation_schedules', function (Blueprint $table) {
            $table->dateTime('start_date')->after('title')->nullable();
            $table->dateTime('end_date')->after('start_date')->nullable();
        });

        // Migrate data from existing event_date to start_date
        \Illuminate\Support\Facades\DB::table('graduation_schedules')
            ->whereNotNull('event_date')
            ->update([
                'start_date' => \Illuminate\Support\Facades\DB::raw('event_date'),
            ]);

        Schema::table('graduation_schedules', function (Blueprint $table) {
            $table->dropColumn('event_date');
            $table->dateTime('start_date')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('graduation_schedules', function (Blueprint $table) {
            $table->dateTime('event_date')->after('title')->nullable();
        });

        // Migrate back: use start_date as event_date
        \Illuminate\Support\Facades\DB::table('graduation_schedules')
            ->whereNotNull('start_date')
            ->update([
                'event_date' => \Illuminate\Support\Facades\DB::raw('start_date'),
            ]);

        Schema::table('graduation_schedules', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
            $table->dateTime('event_date')->nullable(false)->change();
        });
    }
};
