<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Adds the "agent" role (internal MOKILI TOUR staff) alongside the
// existing client / partner / admin roles.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('client', 'partner', 'agent', 'admin') NOT NULL DEFAULT 'client'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('client', 'partner', 'admin') NOT NULL DEFAULT 'client'");
    }
};
