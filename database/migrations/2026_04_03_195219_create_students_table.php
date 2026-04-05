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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique(); // STUDENT ID
            $table->string('email'); // EMAIL
            $table->enum('college', ['CASE', 'CITE', 'COHME', 'CCJE']);// COLLEGE
            $table->string('first_name'); // FIRST NAME
            $table->string('middle_name')->nullable(); // MIDDLE NAME
            $table->string('last_name'); // LAST NAME
            $table->string('suffix', 10)->nullable(); // SUFFIX (Jr., III, etc.)
            $table->string('portal_code',8);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
