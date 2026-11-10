<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Tourisme > Monde : lieux a visiter d'une ville (Wikipedia). */
class WorldTourismTest extends TestCase
{
    use RefreshDatabase;

    public function test_world_page_lists_places_by_category(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'geosearch')) {
                return Http::response(['query' => ['pages' => [
                    1 => ['pageid' => 1, 'title' => 'Basilique Sainte-Anne du Congo', 'description' => 'basilique', 'extract' => 'Grande basilique.', 'thumbnail' => ['source' => 'https://img/1.jpg'], 'coordinates' => [['lat' => -4.27, 'lon' => 15.28]]],
                    2 => ['pageid' => 2, 'title' => 'Musee national du Congo', 'extract' => 'Musee.', 'thumbnail' => ['source' => 'https://img/2.jpg'], 'coordinates' => [['lat' => -4.25, 'lon' => 15.27]]],
                    3 => ['pageid' => 3, 'title' => 'Lycee Savorgnan', 'extract' => 'Ecole.', 'thumbnail' => ['source' => 'https://img/3.jpg']],
                    4 => ['pageid' => 4, 'title' => 'Sans photo', 'extract' => 'x'],
                ]]]);
            }

            return Http::response(['query' => ['pages' => [9 => ['title' => 'Brazzaville', 'extract' => 'Capitale du Congo.', 'coordinates' => [['lat' => -4.26, 'lon' => 15.28]], 'thumbnail' => ['source' => 'https://img/b.jpg']]]]]);
        });

        $this->get('/tourisme/monde?ville=Brazzaville')->assertOk()->assertInertia(fn ($p) => $p->component('Tourisme/Monde')
            ->where('result.city.name', 'Brazzaville')
            ->has('result.places', 2)
            ->where('result.places', fn ($places) => collect($places)->pluck('category')->sort()->values()->all() === ['monuments', 'musees']));
    }
}
