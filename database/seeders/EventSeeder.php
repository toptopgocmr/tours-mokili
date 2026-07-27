<?php

namespace Database\Seeders;

use App\Models\Divertissement\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $organizerId = User::where('email', 'partner@mokili-tour.com')->value('id');

        Event::factory()
            ->count(10)
            ->create(['organizer_id' => $organizerId]);
    }
}
