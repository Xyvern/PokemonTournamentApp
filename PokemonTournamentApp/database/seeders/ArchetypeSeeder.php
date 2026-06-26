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
            'Raging Bolt ex'         => ['TEF', '123'],
            'Crustle'                => ['DRI', '12'],
            'Mega Absol ex'          => ['MEG', '86'],
            'Charizard ex'           => ['OBF', '125'],
            'Froslass Munkidori'     => ['TWM', '53'],
        ];

        // NEW: Create a ranking weight system (Dragapult = 10, Froslass = 1)
        $archetypeWeights = [];
        $currentWeight = count($archetypesData);

        $createdArchetypes = [];
        foreach ($archetypesData as $name => $keys) {
            $keyCard = $this->getPlayableVersion($keys[0], $keys[1]);
            $createdArchetypes[$name] = Archetype::firstOrCreate(
                ['name' => $name],
                ['key_card_id' => $keyCard->id]
            );
            
            $archetypeWeights[$name] = $currentWeight;
            $currentWeight--;
        }

        $this->command->info("--- Processing 16 Exact Deck Templates ---");

        $jsonPath = public_path('data/decklists.json');
        
        $decklistsData = json_decode(file_get_contents($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("JSON Error: " . json_last_error_msg());
            return;
        }

        $processedGlobalDecks = [];
        $weightedTemplatePool = []; // NEW: Pool for selecting decks based on popularity

        foreach ($decklistsData as $index => $deckData) {
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
            $weight = 1; // Default fallback weight
            
            if ($deckData['archetype_name'] && isset($createdArchetypes[$deckData['archetype_name']])) {
                $archetypeId = $createdArchetypes[$deckData['archetype_name']]->id;
                $weight = $archetypeWeights[$deckData['archetype_name']];
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

            $processedGlobalDecks[$index] = [
                'global_deck_id' => $globalDeck->id,
                'name_template' => $deckData['name']
            ];

            // NEW: Add this deck index to the pool X times based on its archetype weight
            for ($w = 0; $w < $weight; $w++) {
                $weightedTemplatePool[] = $index;
            }
        }

        $this->command->info("--- Fetching / Generating Players ---");
        $players = User::whereNot('role', 2)->get();
        
        if ($players->count() < 30) {
            $needed = 30 - $players->count();
            $newPlayers = User::factory()->count($needed)->create(['role' => 1]);
            $players = $players->merge($newPlayers);
        }

        $playerIds = $players->pluck('id')->toArray();
        $userCreationDates = $players->pluck('created_at', 'id')->toArray();
        $nowTimestamp = time();

        $this->command->info("--- Generating exactly 3 Decks per User ---");
        $decksToInsert = [];
        
        // NEW: Track the absolute earliest timestamp generated for each Global Deck
        $earliestGlobalDeckDates = [];
        
        foreach ($playerIds as $userId) {
            for ($d = 0; $d < 3; $d++) {
                // NEW: Pick a random index from the weighted pool instead of directly from the array
                $poolIndex = $weightedTemplatePool[array_rand($weightedTemplatePool)];
                $randomTemplate = $processedGlobalDecks[$poolIndex];
                
                $userCreatedAt = strtotime($userCreationDates[$userId]);
                $upperBound = max($nowTimestamp, $userCreatedAt + 1); 
                $randomDeckTimestamp = mt_rand($userCreatedAt, $upperBound);
                $randomDeckDate = date('Y-m-d H:i:s', $randomDeckTimestamp);

                $globalDeckId = $randomTemplate['global_deck_id'];
                
                // NEW: Record the earliest date we've ever seen for this specific Global Deck
                if (!isset($earliestGlobalDeckDates[$globalDeckId]) || $randomDeckTimestamp < $earliestGlobalDeckDates[$globalDeckId]) {
                    $earliestGlobalDeckDates[$globalDeckId] = $randomDeckTimestamp;
                }

                $decksToInsert[] = [
                    'user_id' => $userId,
                    'global_deck_id' => $globalDeckId,
                    'name' => $randomTemplate['name_template'],
                    'created_at' => $randomDeckDate,
                    'updated_at' => $randomDeckDate,
                ];
            }
        }

        foreach (array_chunk($decksToInsert, 500) as $chunk) {
            Deck::insert($chunk);
        }
        
        // NEW: Retroactively update Global Decks to match their earliest user deck timestamp
        $this->command->info("--- Updating Global Deck Creation Dates ---");
        foreach ($earliestGlobalDeckDates as $gId => $earliestTimestamp) {
            $dateString = date('Y-m-d H:i:s', $earliestTimestamp);
            GlobalDeck::where('id', $gId)->update([
                'created_at' => $dateString,
                'updated_at' => $dateString
            ]);
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