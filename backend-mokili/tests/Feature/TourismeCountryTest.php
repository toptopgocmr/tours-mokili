<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Tourisme : page "Choisissez un pays" puis page de chaque pays. */
class TourismeCountryTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_picker_lists_countries_with_congo_first(): void
    {
        $this->get('/tourisme')->assertOk()->assertInertia(fn ($p) => $p->component('Tourisme/Pays')
            ->where('countries.0.slug', 'congo')
            ->where('countries.0.href', '/divertissement')
            ->where('countries', fn ($c) => collect($c)->pluck('slug')->contains('cameroun'))
            ->has('continents', 4));
    }

    public function test_old_tourisme_links_with_query_still_redirect(): void
    {
        $this->get('/tourisme?tab=parcs')->assertRedirect('/divertissement?tab=parcs');
    }

    public function test_congo_country_page_opens_local_catalogue(): void
    {
        $this->get('/tourisme/pays/congo')->assertRedirect('/divertissement');
    }

    public function test_country_page_shows_its_cities(): void
    {
        Http::fake(fn () => Http::response(['query' => ['pages' => [9 => ['title' => 'Douala', 'extract' => 'Ville du Cameroun.', 'coordinates' => [['lat' => 4.05, 'lon' => 9.7]]]]]]));

        $this->get('/tourisme/pays/cameroun?ville=Kribi')->assertOk()->assertInertia(fn ($p) => $p->component('Tourisme/Monde')
            ->where('country.name', 'Cameroun')
            ->where('ville', 'Kribi')
            ->where('cities.Cameroun', ['Douala', 'Yaounde', 'Kribi', 'Limbe']));

        $this->get('/tourisme/pays/inconnu')->assertNotFound();
    }

    public function test_api_lists_countries(): void
    {
        $this->getJson('/api/tourisme/pays')->assertOk()->assertJsonPath('countries.0.slug', 'congo')->assertJsonStructure(['continents', 'countries', 'local_count']);
    }

    public function test_api_country_page(): void
    {
        Http::fake(fn () => Http::response(['query' => ['pages' => [9 => ['title' => 'Kribi', 'extract' => 'Station balneaire.', 'coordinates' => [['lat' => 2.9, 'lon' => 9.9]]]]]]));

        $this->getJson('/api/tourisme/pays/cameroun?ville=Kribi')->assertOk()
            ->assertJsonPath('country.name', 'Cameroun')->assertJsonPath('ville', 'Kribi')->assertJsonStructure(['categories', 'result' => ['places']]);
        $this->getJson('/api/tourisme/pays/inconnu')->assertNotFound();
    }
}
