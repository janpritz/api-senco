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
        Schema::create('receipt_claims', function (Blueprint $table) {
            $table->id(); // This is your "Filing Number"
            $table->string('student_id')->unique(); // STUDENT ID (Unique)
            $table->boolean('is_claimed')->default(false);
            $table->boolean('is_exported')->default(false);
            $table->string('released_by')->nullable(); // Name of the person who claimed the receipt
            $table->timestamp('claimed_at')->nullable(); // When the receipt was claimed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_claim');
    }
};
