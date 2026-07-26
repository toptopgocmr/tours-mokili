<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The demo rows seeded by 2026_01_05_000001/000002 all had `image => null`,
// so every Logement/Voiture/Divertissement/Marketplace/Fret card rendered a
// solid color placeholder block instead of a photo ("merci de mettre des
// vraies images"). This migration backfills each existing demo row with a
// real photo URL (Unsplash, free-to-use, hotlinked directly - see the
// getImageUrlAttribute() change on each model that now passes full URLs
// through unchanged instead of treating `image` as a local Storage path).
//
// Rows are matched by slug (stable, set at seed time) rather than id, and
// only updated when `image` is still null, so this is safe to leave in
// place permanently: it will never overwrite a real partner-uploaded photo.
return new class extends Migration
{
    public function up(): void
    {
        $this->applyImages('lodging_listings', [
            'appartement-moderne-au-bord-de-mer-1' => 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?auto=format&fit=crop&w=1200&q=70',
            'villa-avec-piscine-privee-2' => 'https://images.unsplash.com/photo-1613977257365-aaae5a9817ff?auto=format&fit=crop&w=1200&q=70',
            'studio-cosy-centre-ville-3' => 'https://images.unsplash.com/photo-1629140727571-9b5c6f6267b4?auto=format&fit=crop&w=1200&q=70',
            'suite-executive-avec-vue-4' => 'https://images.unsplash.com/photo-1631048730670-ff5cd0d08f15?auto=format&fit=crop&w=1200&q=70',
            'maison-familiale-avec-jardin-5' => 'https://images.unsplash.com/photo-1596178067639-5c6e68aea6dc?auto=format&fit=crop&w=1200&q=70',
            'appartement-design-pres-de-la-tour-eiffel-6' => 'https://images.unsplash.com/photo-1611971263023-105938ce12ed?auto=format&fit=crop&w=1200&q=70',
            'loft-avec-terrasse-7' => 'https://images.unsplash.com/photo-1670589953903-b4e2f17a70a9?auto=format&fit=crop&w=1200&q=70',
            'residence-meublee-proche-aeroport-8' => 'https://images.unsplash.com/photo-1621891333819-00c206ec8994?auto=format&fit=crop&w=1200&q=70',
        ]);

        $this->applyImages('vehicles', [
            'toyota-corolla-2022-1' => 'https://images.unsplash.com/photo-1781120358201-8034aa10aa70?auto=format&fit=crop&w=1200&q=70',
            'hyundai-tucson-2023-2' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1200&q=70',
            'kia-picanto-2021-3' => 'https://images.unsplash.com/photo-1782229060245-32b66532a324?auto=format&fit=crop&w=1200&q=70',
            'toyota-hilux-2022-4' => 'https://images.unsplash.com/photo-1601252300554-4ad551483bd2?auto=format&fit=crop&w=1200&q=70',
            'mercedes-classe-c-2023-5' => 'https://images.unsplash.com/photo-1654159866298-e3c8ee93e43b?auto=format&fit=crop&w=1200&q=70',
            'renault-duster-2021-6' => 'https://images.unsplash.com/photo-1618353482480-61ca5a9a7879?auto=format&fit=crop&w=1200&q=70',
            'volkswagen-golf-2020-7' => 'https://images.unsplash.com/photo-1763745989942-bdb1b6ea04f1?auto=format&fit=crop&w=1200&q=70',
        ]);

        $this->applyImages('events', [
            'concert-fally-ipupa-live-1' => 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?auto=format&fit=crop&w=1200&q=70',
            'match-lions-indomptables-vs-ghana-2' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?auto=format&fit=crop&w=1200&q=70',
            'festival-amani-3' => 'https://images.unsplash.com/photo-1603910234616-3b5f4a6be2b4?auto=format&fit=crop&w=1200&q=70',
            'spectacle-humour-le-grand-bal-4' => 'https://images.unsplash.com/photo-1524368535928-5b5e00ddc76b?auto=format&fit=crop&w=1200&q=70',
            'avant-premiere-cinema-wakanda-3-5' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?auto=format&fit=crop&w=1200&q=70',
            'nuit-electro-douala-6' => 'https://images.unsplash.com/photo-1603190287605-e6ade32fa852?auto=format&fit=crop&w=1200&q=70',
        ]);

        $this->applyImages('marketplace_products', [
            // Original 5 categories
            'smartphone-samsung-galaxy-a54-1' => 'https://images.unsplash.com/photo-1707485122968-56916bd2c464?auto=format&fit=crop&w=1200&q=70',
            'ordinateur-portable-hp-pavilion-15-2' => 'https://images.unsplash.com/photo-1636115305669-9096bffe87fd?auto=format&fit=crop&w=1200&q=70',
            'chaussures-nike-air-max-occasion-bon-etat-3' => 'https://images.unsplash.com/photo-1656103743123-b9990915d523?auto=format&fit=crop&w=1200&q=70',
            'refrigerateur-samsung-350l-4' => 'https://images.unsplash.com/photo-1586878340978-f9ca47ad7d72?auto=format&fit=crop&w=1200&q=70',
            'sac-a-main-en-cuir-5' => 'https://images.unsplash.com/photo-1575201046471-082b5c1a1e79?auto=format&fit=crop&w=1200&q=70',
            'television-led-43-pouces-6' => 'https://images.unsplash.com/photo-1615655406736-b37c4fabf923?auto=format&fit=crop&w=1200&q=70',
            'velo-vtt-tout-terrain-7' => 'https://images.unsplash.com/photo-1523754865311-b886113bb8de?auto=format&fit=crop&w=1200&q=70',
            'canape-3-places-occasion-8' => 'https://images.unsplash.com/photo-1572177215152-32f247303126?auto=format&fit=crop&w=1200&q=70',
            'console-playstation-5-9' => 'https://images.unsplash.com/photo-1547489401-fcada4966052?auto=format&fit=crop&w=1200&q=70',
            'montre-connectee-sport-10' => 'https://images.unsplash.com/photo-1519335553051-96f1218cd5fa?auto=format&fit=crop&w=1200&q=70',
            // Accessoires
            'coque-de-protection-iphone-14-1' => 'https://images.unsplash.com/photo-1611254666354-d75bfe3cadbc?auto=format&fit=crop&w=1200&q=70',
            'lunettes-de-soleil-polarisees-2' => 'https://images.unsplash.com/photo-1756725520224-8fe4bdd87983?auto=format&fit=crop&w=1200&q=70',
            'montre-bracelet-cuir-homme-3' => 'https://images.unsplash.com/photo-1571974096035-bc3568627608?auto=format&fit=crop&w=1200&q=70',
            'sac-a-dos-pour-ordinateur-portable-4' => 'https://images.unsplash.com/photo-1575201046471-082b5c1a1e79?auto=format&fit=crop&w=1200&q=70',
            'ceinture-en-cuir-veritable-5' => 'https://images.unsplash.com/photo-1603805752838-aa579d77da72?auto=format&fit=crop&w=1200&q=70',
            'casquette-brodee-mokili-6' => 'https://images.unsplash.com/photo-1586878341340-1971696a9b71?auto=format&fit=crop&w=1200&q=70',
            'chargeur-rapide-cable-usb-c-7' => 'https://images.unsplash.com/photo-1526406915894-7bcd65f60845?auto=format&fit=crop&w=1200&q=70',
            'bracelet-en-perles-fait-main-8' => 'https://images.unsplash.com/photo-1631050164355-822f8ab7dcb9?auto=format&fit=crop&w=1200&q=70',
        ]);

        $this->applyImages('freight_offers', [
            'fret-aerien-express-douala-paris-1' => 'https://images.unsplash.com/photo-1784280017556-225ca299f206?auto=format&fit=crop&w=1200&q=70',
            'fret-maritime-douala-marseille-2' => 'https://images.unsplash.com/photo-1670121180530-cfcba4438038?auto=format&fit=crop&w=1200&q=70',
            'transport-routier-douala-yaounde-3' => 'https://images.unsplash.com/photo-1605504835488-e8c6d37beb43?auto=format&fit=crop&w=1200&q=70',
            'fret-aerien-kinshasa-bruxelles-4' => 'https://images.unsplash.com/photo-1781027607658-61afc93f07e3?auto=format&fit=crop&w=1200&q=70',
            'transport-routier-libreville-franceville-5' => 'https://images.unsplash.com/photo-1624339024061-b435d9261c1d?auto=format&fit=crop&w=1200&q=70',
            'fret-maritime-pointe-noire-anvers-6' => 'https://images.unsplash.com/photo-1601311852860-1d8f42381551?auto=format&fit=crop&w=1200&q=70',
        ]);
    }

    /**
     * Set `image` for each slug => url pair, but only where the row exists
     * and its image is still null - never touches rows a partner (or a
     * previous run) already gave a real image.
     */
    private function applyImages(string $table, array $images): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        foreach ($images as $slug => $url) {
            DB::table($table)
                ->where('slug', $slug)
                ->whereNull('image')
                ->update(['image' => $url]);
        }
    }

    public function down(): void
    {
        // Intentionally a no-op: this migration only backfills a cosmetic
        // field on demo rows and shouldn't destroy anything on rollback.
    }
};
