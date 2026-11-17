<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Jetour T2 : photo officielle fournie par MOKILI TOUR (public/images/vehicules/jetour-t2.jpg)
// a la place de la photo Wikimedia du Jetour Traveller. Site et app mobile.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('vehicles')
            ->where('brand', 'Jetour')
            ->where('model', 'like', 'T2%')
            ->where(fn ($q) => $q->whereNull('image')->orWhere('image', 'like', '%Jetour_Traveller%'))
            ->update(['image' => '/images/vehicules/jetour-t2.jpg', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('vehicles')->where('image', '/images/vehicules/jetour-t2.jpg')
            ->update(['image' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/9/97/Jetour_Traveller_001.jpg/960px-Jetour_Traveller_001.jpg']);
    }
};
