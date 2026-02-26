<?php

namespace Database\Seeders;

use App\Models\Archetype;
use App\Models\Card;
use App\Models\Deck;
use App\Models\DeckContent;
use App\Models\GlobalDeck;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MetaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure User 1 exists
        $user = User::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Henry Chao',
                'email' => 'henry@limitless.test',
                'password' => bcrypt('password')
            ]
        );

        // =====================================================================
        // DECK 1: Gardevoir ex
        // =====================================================================
        $this->seedDeck(
            user: $user,
            deckName: "Henry Chao's decklist",
            archetypeName: 'Gardevoir ex',
            archetypeKey: ['sv1', '86'], // Gardevoir ex SVI 86
            deckData: [
                // Pokémon
                ['sv6', '95', 3],       // Munkidori TWM
                ['me1', '58', 2],       // Ralts MEG
                ['me1', '59', 1],       // Kirlia MEG
                ['sv1', '86', 2],       // Gardevoir ex SVI
                ['rsv10pt5', '44', 2],  // Frillish WHT
                ['rsv10pt5', '45', 1],  // Jellicent ex WHT
                ['sv9', '56', 1],       // Lillie's Clefairy ex JTG -> sv9
                ['sv8', '76', 1],       // Latias ex SSP -> sv8
                ['sv3pt5', '151', 1],   // Mew ex MEW -> sv3pt5
                ['sv6pt5', '38', 1],    // Fezandipiti ex SFA
                ['sv4', '86', 1],       // Scream Tail PAR

                // Trainers
                ['sv2', '185', 4],      // Iono PAL
                ['me1', '119', 4],      // Lillie's Determination MEG
                ['sv3', '186', 1],      // Arven OBF
                ['sv9', '155', 1],      // Professor's Research JTG
                ['sv4', '171', 1],      // Professor Turo's Scenario PAR
                ['me1', '131', 4],      // Ultra Ball MEG
                ['sv4', '163', 3],      // Earthen Vessel PAR
                ['me1', '125', 3],      // Rare Candy MEG
                ['sv1', '181', 3],      // Nest Ball SVI
                ['sv6pt5', '61', 2],    // Night Stretcher SFA
                ['sv4', '160', 2],      // Counter Catcher PAR
                ['sv6', '163', 1],      // Secret Box TWM
                ['sv2', '188', 1],      // Super Rod PAL
                ['sv4', '177', 1],      // TM: Devolution PAR
                ['sv2', '173', 1],      // Bravery Charm PAL
                ['sv2', '171', 1],      // Artazon PAL
                ['me1', '122', 1],      // Mystery Garden MEG

                // Energy
                ['sve', '5', 7],        // Psychic Energy
                ['sve', '7', 3],        // Darkness Energy
            ]
        );

        // =====================================================================
        // DECK 2: Dragapult ex
        // =====================================================================
        $this->seedDeck(
            user: $user,
            deckName: "Top 1 Lillie's Dragapult",
            archetypeName: 'Dragapult ex',
            archetypeKey: ['sv6', '130'], // Dragapult ex TWM 130
            deckData: [
                // Pokémon
                ['sv6', '128', 4],      // Dreepy TWM
                ['sv6', '129', 4],      // Drakloak TWM
                ['sv6', '130', 3],      // Dragapult ex TWM
                ['sv8pt5', '35', 2],    // Duskull PRE -> sv8pt5
                ['sv8pt5', '36', 2],    // Dusclops PRE -> sv8pt5
                ['sv8pt5', '37', 1],    // Dusknoir PRE -> sv8pt5
                ['sv8pt5', '4', 2],     // Budew PRE -> sv8pt5
                ['sv6', '141', 1],      // Bloodmoon Ursaluna ex TWM
                ['sv6pt5', '38', 1],    // Fezandipiti ex SFA
                ['sv8', '76', 1],       // Latias ex SSP -> sv8
                ['sv6', '95', 1],       // Munkidori TWM
                ['sv1', '118', 1],      // Hawlucha SVI

                // Trainers
                ['me1', '119', 4],      // Lillie's Determination MEG
                ['sv2', '185', 4],      // Iono PAL
                ['me1', '114', 3],      // Boss's Orders MEG
                ['rsv10pt5', '84', 2],  // Hilda WHT -> rsv10pt5
                ['sv4', '171', 1],      // Professor Turo's Scenario PAR
                ['sv5', '144', 4],      // Buddy-Buddy Poffin TEF -> sv5
                ['me1', '131', 4],      // Ultra Ball MEG
                ['sv4', '160', 3],      // Counter Catcher PAR
                ['sv6pt5', '61', 2],    // Night Stretcher SFA
                ['sv1', '181', 1],      // Nest Ball SVI
                ['sv6', '153', 2],      // Jamming Tower TWM

                // Energy
                ['sv2', '191', 3],      // Luminous Energy PAL
                ['sve', '5', 2],        // Psychic Energy
                ['sve', '2', 1],        // Fire Energy
                ['sv5', '162', 1],      // Neo Upper Energy TEF -> sv5
            ]
        );
    }

    /**
     * Reusable logic to seed a deck and its archetype
     */
/**
     * Reusable logic to seed a deck and its archetype
     */
    private function seedDeck(User $user, string $deckName, string $archetypeName, array $archetypeKey, array $deckData)
    {
        $this->command->info("--- Processing Deck: {$deckName} ---");

        // 1. Find the Playable version of the Archetype Key Card
        $keyCard = $this->getPlayableVersion($archetypeKey[0], $archetypeKey[1]);

        $archetype = Archetype::firstOrCreate(
            ['name' => $archetypeName],
            ['key_card_id' => $keyCard->id]
        );

        // 2. Build Card Map
        $cardsMap = []; 
        
        foreach ($deckData as $item) {
            $setCode = $item[0];
            $number = $item[1];
            $quantity = $item[2];
            
            // Get the playable (original) version of this card
            $card = $this->getPlayableVersion($setCode, $number);
            $finalApiId = $card->api_id;

            if (isset($cardsMap[$finalApiId])) {
                $cardsMap[$finalApiId]['qty'] += $quantity;
            } else {
                $cardsMap[$finalApiId] = [
                    'id' => $card->id,
                    'qty' => $quantity
                ];
            }
        }

        // 3. Generate Hash (Alphabetical sort by API ID)
        ksort($cardsMap); 
        
        $hashString = "";
        foreach ($cardsMap as $apiId => $data) {
            $hashString .= "{$apiId}:{$data['qty']}|";
        }
        $deckHash = hash('sha256', $hashString);

        // 4. Create/Find Global Deck
        $globalDeck = GlobalDeck::firstOrCreate(
            ['deck_hash' => $deckHash],
            ['archetype_id' => $archetype->id]
        );

        // 5. Insert Content
        if ($globalDeck->wasRecentlyCreated) {
            foreach ($cardsMap as $data) {
                DeckContent::create([
                    'global_deck_id' => $globalDeck->id,
                    'card_id' => $data['id'],
                    'quantity' => $data['qty']
                ]);
            }
            $this->command->info("Created new Global Deck hash.");
        }

        // 6. Create User Deck
        Deck::create([
            'user_id' => $user->id,
            'global_deck_id' => $globalDeck->id,
            'name' => $deckName
        ]);
        
        $this->command->info("Deck '{$deckName}' assigned to {$user->name}.\n");
    }

    /**
     * Helper to find the "Playable" (Original) version of a card.
     */
    private function getPlayableVersion(string $setCode, string $number)
    {
        $apiId = "{$setCode}-{$number}";
        $requestedCard = Card::where('api_id', $apiId)->first();

        // If it doesn't exist at all, create placeholder
        if (!$requestedCard) {
            return $this->createPlaceholderCard($apiId, "Missing {$apiId}");
        }

        // If it's already the Playable version, return it
        if ($requestedCard->is_playable) {
            return $requestedCard;
        }

        // If it's NOT playable, find the one that IS playable with the same functional identity
        // IDENTITY: Name + HP (for Pokemon) or just Name (for Trainers/Energy)
        $query = Card::where('name', $requestedCard->name)
            ->where('is_playable', true);

        if ($requestedCard->supertype === 'Pokémon') {
            $query->where('hp', $requestedCard->hp);
        }

        $playableVersion = $query->first();

        if ($playableVersion) {
            return $playableVersion;
        }

        // Fallback: If no playable version found, return the requested one
        return $requestedCard;
    }

    private function createPlaceholderCard($apiId, $name)
    {
        return Card::create([
            'api_id' => $apiId,
            'set_id' => 1,
            'name' => $name,
            'supertype' => 'Unknown',
            'number' => '000',
            'artist' => 'Unknown'
        ]);
    }
}