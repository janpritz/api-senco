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
        Schema::create('graduation_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->dateTime('event_date');
            $table->text('notice_text')->nullable(); // For any updates or announcements regarding the event
            $table->boolean('is_important')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('graduation_schedules');
    }
};
