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
        Schema::create('collection_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique(); // Ensures only one narrative per day
            $table->decimal('total_collected', 15, 2)->default(0);
            $table->integer('transaction_count')->default(0);

            // The Narrative Fields
            $table->text('summary')->nullable();
            $table->text('issues')->nullable();
            $table->text('resolutions')->nullable();

            $table->string('status')->default('draft'); // draft, submitted, finalized
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Who wrote it

            //Todays Collector
            $table->integer('collector');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_reports');
    }
};
