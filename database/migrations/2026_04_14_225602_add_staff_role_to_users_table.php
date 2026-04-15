<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Redefine the enum to include 'Staff'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'Adviser', 'Treasurer', 'Auditor', 'Staff') DEFAULT 'Auditor'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original roles
        // Note: If any users were assigned 'Staff', you should change them first 
        // or this statement might fail depending on your MySQL mode.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'Adviser', 'Treasurer', 'Auditor') DEFAULT 'Auditor'");
    }
};
