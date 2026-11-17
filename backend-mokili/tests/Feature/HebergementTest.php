<?php

namespace Tests\Feature;

use App\Models\Logement\LodgingListing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Rubrique "Hébergement" (ex-Logement) : nom, alias et API de l'app. */
class HebergementTest extends TestCase
{
    use RefreshDatabase;

    public function test_rubric_is_named_hebergement_and_alias_redirects(): void
    {
        DB::table('service_categories')->updateOrInsert(['slug' => 'logement'], ['name' => 'Logement', 'icon' => 'building-2', 'color' => '#1E8A3E', 'position' => 2]);
        (require database_path('migrations/2026_11_17_000002_rename_logement_to_hebergement.php'))->up();
        $this->assertSame('Hébergement', DB::table('service_categories')->where('slug', 'logement')->value('name'));
        $this->get('/hebergement?city=Douala')->assertRedirect('/logement?city=Douala');
    }

    public function test_mobile_home_and_search_match_web(): void
    {
        $this->getJson('/api/logement/accueil')->assertOk()
            ->assertJsonStructure(['types' => [['key', 'label', 'count']], 'popular_cities']);

        $l = LodgingListing::query()->active()->firstOrFail();
        $this->getJson('/api/logement/listings?city='.urlencode($l->city).'&adults=1&children=0&rooms=1&check_in=2030-01-10&check_out=2030-01-12')
            ->assertOk()->assertJsonPath('data.0.booked_on_dates', false);
    }
}
