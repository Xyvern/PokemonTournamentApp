<?php

namespace Database\Seeders;

use App\Models\Archetype;
use App\Models\Card;
use App\Models\Deck;
use App\Models\DeckContent;
use App\Models\GlobalDeck;
use App\Models\User;
use App\Services\ArchetypeService;
use Illuminate\Database\Seeder;

class ArchetypeSeeder extends Seeder
{
    public function run(ArchetypeService $archetypeService): void
    {
        $this->command->info("--- Seeding Top 10 Archetypes ---");

        $archetypesData = [
            'Dragapult ex'           => ['TWM', '130'],
            'Gardevoir ex'           => ['SVI', '86'],
            'Gholdengo ex'           => ['PAR', '139'],
            "N's Zoroark ex"         => ['JTG', '98'],
            "Marnie's Grimmsnarl ex" => ['DRI', '136'],
            'Raging Bolt ex'            => ['TEF', '123'],
            'Crustle'                => ['DRI', '12'],
            'Mega Absol ex'          => ['MEG', '86'],
            'Charizard ex'           => ['OBF', '125'],
            'Froslass Munkidori'     => ['TWM', '53'],
        ];

        $createdArchetypes = [];
        foreach ($archetypesData as $name => $keys) {
            $keyCard = $this->getPlayableVersion($keys[0], $keys[1]);
            $createdArchetypes[$name] = Archetype::firstOrCreate(
                ['name' => $name],
                ['key_card_id' => $keyCard->id]
            );
        }

        $this->command->info("--- Processing 16 Exact Deck Templates ---");

        $jsonPath = public_path('data/decklists.json');
        
        // 2. Read it and decode it back into a PHP array (the 'true' makes it an array instead of an object)
        $decklistsData = json_decode(file_get_contents($jsonPath), true);

        // Optional: Catch JSON formatting errors so you don't pull your hair out debugging!
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("JSON Error: " . json_last_error_msg());
            return;
        }

        $processedGlobalDecks = [];

        foreach ($decklistsData as $deckData) {
            $cardsMap = []; 
            foreach ($deckData['cards'] as $item) {
                $card = $this->getPlayableVersion($item[0], $item[1]);
                $finalApiId = $card->api_id;

                if (isset($cardsMap[$finalApiId])) {
                    $cardsMap[$finalApiId]['qty'] += $item[2];
                } else {
                    $cardsMap[$finalApiId] = ['id' => $card->id, 'qty' => $item[2]];
                }
            }

            ksort($cardsMap); 
            $hashString = "";
            foreach ($cardsMap as $apiId => $data) {
                $hashString .= "{$apiId}:{$data['qty']}|";
            }
            $deckHash = hash('sha256', $hashString);

            $archetypeId = null;
            if ($deckData['archetype_name'] && isset($createdArchetypes[$deckData['archetype_name']])) {
                $archetypeId = $createdArchetypes[$deckData['archetype_name']]->id;
            }

            $globalDeck = GlobalDeck::firstOrCreate(
                ['deck_hash' => $deckHash],
                ['archetype_id' => $archetypeId]
            );

            if ($globalDeck->wasRecentlyCreated) {
                $contentToInsert = [];
                foreach ($cardsMap as $data) {
                    $contentToInsert[] = [
                        'global_deck_id' => $globalDeck->id,
                        'card_id' => $data['id'],
                        'quantity' => $data['qty']
                    ];
                }
                DeckContent::insert($contentToInsert);
            }

            $processedGlobalDecks[] = [
                'global_deck_id' => $globalDeck->id,
                'name_template' => $deckData['name']
            ];
        }

        $this->command->info("--- Fetching / Generating Players ---");
        $players = User::whereNot('role', 2)->get();
        
        if ($players->count() < 30) {
            $needed = 30 - $players->count();
            $newPlayers = User::factory()->count($needed)->create(['role' => 1]);
            $players = $players->merge($newPlayers);
        }

        $playerIds = $players->pluck('id')->toArray();

        $this->command->info("--- Generating 1500 Random User Decks ---");
        $decksToInsert = [];
        for ($i = 0; $i < 1500; $i++) {
            $randomTemplate = $processedGlobalDecks[array_rand($processedGlobalDecks)];
            $decksToInsert[] = [
                'user_id' => $playerIds[array_rand($playerIds)],
                'global_deck_id' => $randomTemplate['global_deck_id'],
                'name' => $randomTemplate['name_template'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($decksToInsert) === 500) {
                Deck::insert($decksToInsert);
                $decksToInsert = [];
            }
        }

        if (!empty($decksToInsert)) {
            Deck::insert($decksToInsert);
        }

        foreach ($createdArchetypes as $archetype) {
            $archetypeService->recalculateArchetypeStats($archetype);
        }

        $this->command->info("Seeder completed!");
    }

    /**
     * Finds playable card by resolving PTCGO code against the Sets table.
     */
    private function getPlayableVersion(string $ptcgoCode, string $number)
    {
        // 1. Look up by the Set's PTCGO Code
        $requestedCard = Card::whereHas('set', function ($query) use ($ptcgoCode) {
            $query->where('ptcgo_code', $ptcgoCode);
        })->where('number', $number)->first();

        // 2. Fallback to API ID format if the set lookup fails
        if (!$requestedCard) {
            $apiId = strtolower($ptcgoCode) . '-' . $number;
            $requestedCard = Card::where('api_id', $apiId)->first();
        }

        // Create dummy if completely missing
        if (!$requestedCard) {
            return Card::create([
                'api_id' => strtolower($ptcgoCode) . '-' . $number,
                'set_id' => 1,
                'name' => "Missing {$ptcgoCode} {$number}",
                'supertype' => 'Unknown',
                'number' => $number,
                'artist' => 'Unknown'
            ]);
        }

        // If the exact card they pasted is the min-rarity/oldest playable one, return it!
        if ($requestedCard->is_playable) {
            return $requestedCard;
        }

        // 3. Fallback: Search the DB for the card that WAS marked playable by the generator
        $query = Card::where('name', $requestedCard->name)
                     ->where('is_playable', true);
                     
        if ($requestedCard->supertype === 'Pokémon') {
            // Match exact HP
            $query->where('hp', $requestedCard->hp);
            
            // Match exact Attack Names using Laravel's whereHas
            $attackNames = $requestedCard->attacks()->pluck('name')->toArray();
            
            foreach ($attackNames as $attackName) {
                $query->whereHas('attacks', function ($q) use ($attackName) {
                    $q->where('name', $attackName);
                });
            }
            
            // Ensure the playable card doesn't have MORE attacks than the base card
            $query->has('attacks', '=', count($attackNames));
        }

        // Return the exact mechanical clone, or fallback to the requested card if something goes wrong
        return $query->first() ?? $requestedCard;
    }
}