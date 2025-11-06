<?php

namespace Database\Seeders;

use App\Models\Set;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = public_path('data/sets/en.json');
        $json = file_get_contents($path);
        $sets = json_decode($json, true);

        foreach ($sets as $data) {
            if (!isset($data['legalities']['standard']) || 
                strtolower($data['legalities']['standard']) !== 'legal') {
                continue;
            }

            $set = Set::updateOrCreate(
                ['api_id' => $data['id']],
                [
                    'name' => $data['name'],
                    'series' => $data['series'],
                    'printed_total' => $data['printedTotal'],
                    'total' => $data['total'],
                    'ptcgo_code' => $data['ptcgoCode'] ?? null,
                    'release_date' => $data['releaseDate'] ?? null,
                    'updated_at_api' => $data['updatedAt'] ?? null,
                ]
            );

            if (isset($data['legalities'])) {
                $set->legalities()->updateOrCreate([], $data['legalities']);
            }

            if (isset($data['images'])) {
                $set->images()->updateOrCreate([], $data['images']);
            }
        }
    }
}
