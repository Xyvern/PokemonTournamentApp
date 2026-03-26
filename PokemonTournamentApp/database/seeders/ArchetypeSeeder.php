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
            'Dragapult ex'           => ['TWM', '130'], // Updated to use PTCGO codes
            'Gardevoir ex'           => ['SVI', '86'],
            'Gholdengo ex'           => ['PAR', '139'],
            "N's Zoroark ex"         => ['JTG', '98'],
            "Marnie's Grimmsnarl ex" => ['DRI', '136'],
            'Raging Bolt'            => ['TEF', '123'],
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

        // Your 15 Dragapult Lists + 1 Charizard Baseline exactly as provided
        $decklistsData = [
            ['name' => 'Dragapult List 1', 'archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 2], ['PAF', '7', 3], ['PFL', '12', 1], ['OBF', '125', 2], ['ASC', '16', 1], ['TWM', '95', 1], ['SVI', '118', 1], ['PAR', '29', 1], ['ASC', '142', 1], ['MEG', '119', 4], ['MEG', '114', 3], ['OBF', '186', 3], ['PAL', '185', 3], ['MEG', '113', 1], ['TEF', '144', 4], ['MEG', '131', 4], ['MEG', '125', 2], ['PAL', '188', 1], ['ASC', '196', 1], ['PAR', '160', 1], ['TWM', '165', 1], ['ASC', '181', 1], ['PAR', '178', 1], ['MEE', '2', 5], ['PAL', '191', 4]]],
            ['name' => 'Dragapult List 2', 'archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 1], ['PRE', '37', 1], ['ASC', '16', 2], ['OBF', '118', 1], ['PAR', '17', 1], ['ASC', '142', 1], ['SSP', '76', 1], ['TWM', '141', 1], ['SVI', '118', 1], ['TWM', '95', 1], ['MEG', '119', 4], ['PAL', '185', 4], ['MEG', '114', 3], ['JTG', '155', 1], ['WHT', '84', 1], ['MEG', '131', 4], ['TEF', '144', 4], ['PAR', '160', 3], ['ASC', '196', 3], ['TWM', '153', 2], ['PAL', '191', 3], ['MEE', '5', 2], ['MEE', '2', 1], ['TEF', '162', 1]]],
            ['name' => 'Dragapult List 3', 'archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 2], ['PRE', '37', 1], ['PRE', '4', 2], ['TWM', '141', 1], ['SFA', '38', 1], ['SSP', '76', 1], ['TWM', '95', 1], ['SVI', '118', 1], ['MEG', '119', 4], ['PAL', '185', 4], ['MEG', '114', 3], ['WHT', '84', 2], ['PAR', '171', 1], ['TEF', '144', 4], ['MEG', '131', 4], ['PAR', '160', 3], ['SFA', '61', 2], ['SVI', '181', 1], ['TWM', '153', 2], ['PAL', '191', 3], ['MEE', '5', 2], ['MEE', '2', 1], ['TEF', '162', 1]]],
            ['name' => 'Dragapult List 4', 'archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 2], ['PRE', '37', 1], ['PRE', '4', 2], ['TWM', '95', 1], ['SVI', '118', 1], ['PFL', '14', 1], ['TWM', '141', 1], ['SFA', '38', 1], ['SSP', '76', 1], ['MEG', '119', 4], ['PAL', '185', 4], ['MEG', '114', 3], ['JTG', '155', 1], ['WHT', '84', 1], ['PFL', '87', 1], ['MEG', '131', 4], ['TEF', '144', 4], ['PAR', '160', 3], ['SFA', '61', 2], ['TWM', '153', 2], ['PAL', '191', 3], ['MEE', '2', 2], ['MEE', '5', 1], ['TEF', '162', 1]]],
            ['name' => 'Dragapult List 5', 'archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 2], ['PRE', '37', 1], ['PRE', '4', 2], ['SSP', '76', 1], ['SFA', '38', 1], ['TWM', '141', 1], ['TWM', '95', 1], ['SVI', '118', 1], ['MEG', '119', 4], ['PAL', '185', 4], ['MEG', '114', 3], ['WHT', '84', 2], ['MEG', '131', 4], ['TEF', '144', 4], ['PAR', '160', 3], ['SFA', '61', 2], ['SVI', '181', 1], ['MEG', '125', 1], ['TWM', '153', 2], ['PAL', '191', 3], ['MEE', '5', 2], ['MEE', '2', 1], ['TEF', '162', 1]]],
            ['name' => 'Dragapult List 6', 'archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 2], ['PRE', '35', 2], ['PRE', '36', 2], ['PRE', '37', 1], ['ASC', '16', 2], ['SSP', '76', 1], ['ASC', '142', 1], ['TWM', '141', 1], ['SVI', '118', 1], ['TWM', '95', 1], ['PFL', '14', 1], ['PAL', '185', 4], ['MEG', '119', 4], ['MEG', '114', 3], ['WHT', '84', 1], ['PAR', '171', 1], ['MEG', '131', 4], ['TEF', '144', 4], ['PAR', '160', 3], ['ASC', '196', 3], ['SVI', '181', 1], ['TWM', '153', 2], ['PAL', '191', 3], ['MEE', '2', 2], ['MEE', '5', 1], ['TEF', '162', 1]]],
            ['name' => 'Dragapult List 7', 'archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 2], ['PRE', '37', 1], ['ASC', '16', 2], ['PFL', '14', 1], ['TWM', '141', 1], ['ASC', '142', 1], ['SSP', '76', 1], ['SVI', '118', 1], ['PAL', '185', 4], ['MEG', '119', 4], ['MEG', '114', 3], ['PFL', '87', 2], ['WHT', '84', 1], ['PAR', '171', 1], ['TEF', '144', 4], ['MEG', '131', 4], ['PAR', '160', 3], ['ASC', '196', 2], ['TWM', '153', 2], ['MEE', '2', 2], ['MEE', '5', 2], ['PAL', '191', 2], ['TEF', '162', 1]]],
            ['name' => 'Dragapult List 8', 'archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 2], ['PRE', '37', 1], ['PRE', '4', 2], ['SSP', '76', 1], ['SVI', '118', 1], ['SFA', '38', 1], ['TWM', '141', 1], ['TWM', '95', 1], ['PAL', '185', 4], ['MEG', '119', 4], ['MEG', '114', 3], ['WHT', '84', 2], ['TEF', '144', 4], ['MEG', '131', 4], ['PAR', '160', 3], ['SFA', '61', 3], ['SVI', '181', 1], ['TWM', '153', 2], ['PAL', '191', 3], ['MEE', '5', 2], ['MEE', '2', 1], ['TEF', '162', 1]]],
            ['name' => 'Dragapult List 9', 'archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 1], ['PRE', '37', 1], ['ASC', '16', 2], ['SVI', '118', 1], ['TWM', '95', 1], ['ASC', '142', 1], ['TWM', '141', 1], ['SSP', '76', 1], ['PAL', '185', 4], ['MEG', '119', 4], ['MEG', '114', 3], ['WHT', '84', 1], ['PFL', '87', 1], ['JTG', '155', 1], ['TEF', '144', 4], ['MEG', '131', 4], ['PAR', '160', 3], ['ASC', '196', 2], ['MEG', '125', 2], ['TWM', '153', 2], ['PAL', '191', 3], ['MEE', '5', 2], ['MEE', '2', 1], ['TEF', '162', 1]]],
            ['name' => 'Dragapult List 10','archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 2], ['PAF', '7', 3], ['PFL', '12', 1], ['OBF', '125', 2], ['PAR', '29', 1], ['TWM', '95', 1], ['SVI', '118', 1], ['PRE', '4', 1], ['SFA', '38', 1], ['MEG', '119', 4], ['MEG', '114', 3], ['PAL', '185', 3], ['OBF', '186', 3], ['TEF', '144', 4], ['MEG', '131', 4], ['MEG', '125', 2], ['PAR', '160', 2], ['SFA', '61', 1], ['PAL', '188', 1], ['TWM', '165', 1], ['PAR', '178', 1], ['BLK', '79', 1], ['MEE', '2', 5], ['PAL', '191', 4]]],
            ['name' => 'Dragapult List 11','archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 2], ['PRE', '37', 1], ['PRE', '4', 2], ['SVI', '118', 1], ['SFA', '38', 1], ['TWM', '141', 1], ['SSP', '76', 1], ['TWM', '95', 1], ['PAL', '185', 4], ['MEG', '119', 4], ['MEG', '114', 2], ['PFL', '87', 2], ['WHT', '84', 1], ['PAR', '171', 1], ['MEG', '131', 4], ['TEF', '144', 4], ['PAR', '160', 3], ['SFA', '61', 2], ['MEG', '125', 1], ['TWM', '153', 2], ['PAL', '191', 3], ['MEE', '5', 2], ['MEE', '2', 1], ['TEF', '162', 1]]],
            ['name' => 'Dragapult List 12','archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 2], ['PRE', '37', 1], ['ASC', '16', 2], ['SSP', '76', 1], ['TWM', '95', 1], ['ASC', '142', 1], ['TWM', '141', 1], ['SVI', '118', 1], ['PAL', '185', 4], ['MEG', '119', 4], ['MEG', '114', 3], ['PFL', '87', 1], ['WHT', '84', 1], ['PAR', '171', 1], ['TEF', '144', 4], ['MEG', '131', 4], ['PAR', '160', 3], ['ASC', '196', 2], ['MEG', '125', 1], ['SVI', '181', 1], ['TWM', '153', 1], ['PAL', '191', 3], ['MEE', '5', 2], ['TEF', '162', 1], ['MEE', '2', 1]]],
            ['name' => 'Dragapult List 13','archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 2], ['PRE', '37', 1], ['PRE', '4', 2], ['TWM', '141', 1], ['SFA', '38', 1], ['SSP', '76', 1], ['TWM', '95', 1], ['SVI', '118', 1], ['MEG', '119', 4], ['PAL', '185', 4], ['MEG', '114', 3], ['WHT', '84', 2], ['PAR', '171', 1], ['TEF', '144', 4], ['MEG', '131', 4], ['PAR', '160', 3], ['SFA', '61', 2], ['SVI', '181', 1], ['TWM', '153', 2], ['PAL', '191', 3], ['MEE', '5', 2], ['MEE', '2', 1], ['TEF', '162', 1]]],
            ['name' => 'Dragapult List 14','archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 2], ['PRE', '37', 1], ['PRE', '4', 2], ['SFA', '38', 1], ['TWM', '141', 1], ['TWM', '95', 1], ['SVI', '118', 1], ['SSP', '76', 1], ['PAL', '185', 4], ['MEG', '119', 4], ['MEG', '114', 3], ['WHT', '84', 1], ['JTG', '155', 1], ['PAR', '171', 1], ['TEF', '144', 4], ['MEG', '131', 4], ['PAR', '160', 3], ['SFA', '61', 2], ['SVI', '181', 1], ['TWM', '153', 2], ['PAL', '191', 3], ['MEE', '5', 2], ['TEF', '162', 1], ['MEE', '2', 1]]],
            ['name' => 'Dragapult List 15','archetype_name' => 'Dragapult ex', 'cards' => [['TWM', '128', 4], ['TWM', '129', 4], ['TWM', '130', 3], ['PRE', '35', 2], ['PRE', '36', 2], ['PRE', '37', 1], ['PRE', '4', 2], ['TWM', '141', 1], ['TWM', '95', 1], ['SVI', '118', 1], ['SFA', '38', 1], ['SSP', '76', 1], ['PAL', '185', 4], ['MEG', '119', 4], ['MEG', '114', 3], ['WHT', '84', 1], ['JTG', '155', 1], ['TEF', '144', 4], ['MEG', '131', 4], ['PAR', '160', 3], ['SFA', '61', 3], ['SVI', '181', 1], ['TWM', '153', 2], ['PAL', '191', 3], ['MEE', '5', 2], ['TEF', '162', 1], ['MEE', '2', 1]]],
        ];

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
                'name' => $randomTemplate['name_template'] . ' (V' . rand(1, 99) . ')',
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

        if ($requestedCard->is_playable) return $requestedCard;

        $query = Card::where('name', $requestedCard->name)->where('is_playable', true);
        if ($requestedCard->supertype === 'Pokémon') {
            $query->where('hp', $requestedCard->hp);
        }
        return $query->first() ?? $requestedCard;
    }
}