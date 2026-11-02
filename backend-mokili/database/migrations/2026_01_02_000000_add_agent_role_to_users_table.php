<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Adds the "agent" role (internal MOKILI TOUR staff) alongside the
// existing client / partner / admin roles.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return; // SQLite (tests) : pas de MODIFY
        }
        DB::statement("ALTER TABLE users MODIFY role ENUM('client', 'partner', 'agent', 'admin') NOT NULL DEFAULT 'client'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE users MODIFY role ENUM('client', 'partner', 'admin') NOT NULL DEFAULT 'client'");
    }
};
