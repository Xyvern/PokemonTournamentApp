<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    Set,
    Card,
    CardSubtype,
    CardType,
    CardAbility,
    CardAttack,
    CardAttackCost,
    CardWeakness,
    CardRetreatCost,
    CardPokedexNumber,
    CardLegality,
    CardImage
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CardSeeder extends Seeder
{
    public function run(): void
    {
        $path = public_path('data/cards');
        $files = File::files($path);

        foreach ($files as $file) {
            $setId = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            $set = Set::where('api_id', $setId)->first();
            if (!$set) {
                $this->command->warn("Skipping {$file->getFilename()} — no matching set found.");
                continue;
            }

            $jsonData = json_decode(File::get($file), true);
            if (!is_array($jsonData)) {
                $this->command->warn("Invalid JSON in {$file->getFilename()}, skipping...");
                continue;
            }

            $this->command->info("Importing cards for set: {$set->name} ({$set->api_id})");

            foreach ($jsonData as $cardData) {
                DB::transaction(function () use ($cardData, $set) {
                    // Create main card
                    $card = Card::create([
                        'api_id' => $cardData['id'] ?? null,
                        'set_id' => $set->id,
                        'name' => $cardData['name'] ?? null,
                        'supertype' => $cardData['supertype'] ?? null,
                        'hp' => $cardData['hp'] ?? null,
                        'evolves_from' => $cardData['evolvesFrom'] ?? null,
                        'rarity' => $cardData['rarity'] ?? null,
                        'flavor_text' => $cardData['flavorText'] ?? null,
                        'number' => $cardData['number'] ?? null,
                        'artist' => $cardData['artist'] ?? null,
                        'converted_retreat_cost' => $cardData['convertedRetreatCost'] ?? null,
                    ]);

                    // --- Subtypes ---
                    foreach ($cardData['subtypes'] ?? [] as $subtype) {
                        CardSubtype::create([
                            'card_id' => $card->id,
                            'subtype' => $subtype,
                        ]);
                    }

                    // --- Types ---
                    foreach ($cardData['types'] ?? [] as $type) {
                        CardType::create([
                            'card_id' => $card->id,
                            'type' => $type,
                        ]);
                    }

                    // --- Abilities ---
                    foreach ($cardData['abilities'] ?? [] as $ability) {
                        CardAbility::create([
                            'card_id' => $card->id,
                            'name' => $ability['name'] ?? null,
                            'text' => $ability['text'] ?? null,
                            'type' => $ability['type'] ?? null,
                        ]);
                    }

                    // --- Attacks ---
                    foreach ($cardData['attacks'] ?? [] as $attackData) {
                        $attack = CardAttack::create([
                            'card_id' => $card->id,
                            'name' => $attackData['name'] ?? null,
                            'converted_energy_cost' => $attackData['convertedEnergyCost'] ?? null,
                            'damage' => $attackData['damage'] ?? null,
                            'text' => $attackData['text'] ?? null,
                        ]);

                        foreach ($attackData['cost'] ?? [] as $cost) {
                            CardAttackCost::create([
                                'card_attack_id' => $attack->id,
                                'cost' => $cost,
                            ]);
                        }
                    }

                    // --- Weaknesses ---
                    foreach ($cardData['weaknesses'] ?? [] as $weakness) {
                        CardWeakness::create([
                            'card_id' => $card->id,
                            'type' => $weakness['type'] ?? null,
                            'value' => $weakness['value'] ?? null,
                        ]);
                    }

                    // --- Retreat Cost ---
                    foreach ($cardData['retreatCost'] ?? [] as $cost) {
                        CardRetreatCost::create([
                            'card_id' => $card->id,
                            'cost' => $cost,
                        ]);
                    }

                    // --- National Pokedex Numbers ---
                    foreach ($cardData['nationalPokedexNumbers'] ?? [] as $num) {
                        CardPokedexNumber::create([
                            'card_id' => $card->id,
                            'number' => $num,
                        ]);
                    }

                    // --- Legalities ---
                    foreach ($cardData['legalities'] ?? [] as $format => $status) {
                        CardLegality::create([
                            'card_id' => $card->id,
                            'format' => $format,
                            'status' => $status,
                        ]);
                    }

                    // --- Images ---
                    if (isset($cardData['images'])) {
                        CardImage::create([
                            'card_id' => $card->id,
                            'small' => $cardData['images']['small'] ?? null,
                            'large' => $cardData['images']['large'] ?? null,
                        ]);
                    }
                });
            }
        }

        $this->command->info('✅ Card seeding complete!');
    }
}
