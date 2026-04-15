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
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('name');
            // 1 = Priority (Seniors/PWD/Pregnant), 2 = Regular
            $table->integer('priority_group')->default(2);
            // waiting, serving, completed, cancelled
            $table->string('status')->default('waiting');
            $table->integer('processed_by');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
