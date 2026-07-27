<?php

namespace Database\Factories\Divertissement;

use App\Models\Divertissement\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $events = [
            ['title' => 'Concert Fally Ipupa', 'category' => 'concert', 'venue' => 'Stade des Martyrs'],
            ['title' => 'Match TP Mazembe vs Vita Club', 'category' => 'sport', 'venue' => 'Stade Frederic Kibassa Maliba'],
            ['title' => 'Festival Amani', 'category' => 'concert', 'venue' => 'Parc Virunga'],
            ['title' => 'Nuit du Stand-up', 'category' => 'spectacle', 'venue' => 'Palais du Peuple'],
            ['title' => 'Avant-premiere cinema', 'category' => 'cinema', 'venue' => 'CanalOlympia'],
        ];
        $pick = $this->faker->randomElement($events);
        $city = $this->faker->randomElement(['Kinshasa', 'Lubumbashi', 'Goma', 'Douala']);
        $start = $this->faker->dateTimeBetween('+1 week', '+2 months');

        return [
            'title' => $pick['title'],
            'category' => $pick['category'],
            'description' => $this->faker->paragraph(),
            'venue' => $pick['venue'],
            'city' => $city,
            'country' => 'CD',
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+3 hours'),
            'price' => $this->faker->numberBetween(5, 100) * 1000,
            'currency' => 'XAF',
            'capacity' => $this->faker->numberBetween(100, 5000),
            'image' => null,
            'is_active' => true,
            'status' => 'published',
        ];
    }
}
