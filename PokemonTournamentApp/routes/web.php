<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSiteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PlayerSiteController;
use App\Http\Controllers\SiteController;
use App\Models\Card;
use App\Models\Set;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::redirect('/', '/login');

Route::get('/login',[AuthController::class, 'login'])->name('login');
Route::get('/register',[AuthController::class, 'register'])->name('register');

Route::post('/login', [AuthController::class, 'doLogin'])->name('dologin');
Route::post('/register', [AuthController::class, 'doRegister'])->name('doregister');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('sets')->name('sets.')->group(function () {
    Route::get('/', [PlayerSiteController::class, 'playerSets'])->name('index');
    Route::get('/{id}', [PlayerSiteController::class, 'playerSetDetail'])->name('detail');
});

Route::prefix('tournaments')->name('tournaments.')->group(function () {
    Route::get('/', [SiteController::class, 'tournaments'])->name('index');
    Route::get('/{id}', [SiteController::class, 'tournamentDetail'])->name('detail');
    Route::post('/{id}/register', [PlayerController::class, 'registerTournament'])->name('register');
    Route::post('/{id}/drop', [PlayerController::class, 'dropTournament'])->name('drop');
    Route::get('{id}/matches', [PlayerController::class, 'fetchRoundMatches'])->name('matches.fetch');
});

Route::prefix('cards')->name('cards.')->group(function () {
    // fix batch to prevent overloading, filter ga jalan
    Route::get('/', [SiteController::class, 'cards'])->name('index');
    Route::get('/{id}', [SiteController::class, 'cardDetail'])->name('detail');
});

Route::prefix('archetypes')->name('archetypes.')->group(function () {
    Route::get('/', [SiteController::class, 'archetypes'])->name('index');
    Route::get('/{id}', [SiteController::class, 'archetypeDetail'])->name('detail');
});

Route::prefix('player')->name('player.')->group(function () {
    Route::get('/home', [PlayerSiteController::class, 'playerHome'])->name('home');
    Route::get('/leaderboard', [PlayerSiteController::class, 'leaderboard'])->name('leaderboard');
    Route::get('/profile/{id}', [PlayerSiteController::class, 'playerProfile'])->name('profile');

    // check lagi decknya jalan ato ga, filter ga jalan, jangan overload kartu
    Route::get('/my-decks', [PlayerSiteController::class, 'myDecks'])->name('mydecks');
    Route::get('/decks/create', [PlayerSiteController::class, 'createDeck'])->name('createDeck');
    Route::post('/decks/store', [PlayerController::class, 'storeDeck'])->name('storeDeck');
});

Route::get('/decks/{deck}', [SiteController::class, 'showDeck'])->name('showDeck');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminSiteController::class, 'adminDashboard'])->name('dashboard');
    Route::prefix('tournaments')->name('tournaments.')->group(function () {
        Route::get('/', [AdminSiteController::class, 'adminTournaments'])->name('index');
        Route::get('/create', [AdminSiteController::class, 'createTournament'])->name('create');
        Route::post('/store', [AdminController::class, 'storeTournament'])->name('store');
        Route::get('/{id}/edit', [AdminSiteController::class, 'editTournament'])->name('edit');
        Route::post('/{id}/update', [AdminController::class, 'updateTournament'])->name('update');
        Route::post('/{id}/delete', [AdminController::class, 'deleteTournament'])->name('delete');
    });
});

// create archetype after tournament
// pikiran laporan nya admin
// middleware

Route::get('/play', function (Request $request) {
    // We don't need to fetch data here, we just need to load the view!
    // The URL parameters (?match_id=X) automatically pass through to the browser bar.
    return view('game.play'); 
})->middleware('auth'); // Ensure they are logged in!

// Route::get('/export-all-cards-combined', function () {
//     // 1. Setup environment (Processing thousands of cards requires more resources)
//     ini_set('memory_limit', '-1');
//     ini_set('max_execution_time', 600);

//     $allSets = Set::all();
//     $masterCollection = [];
//     $totalCardsImported = 0;
//     $setsProcessed = 0;

//     foreach ($allSets as $set) {
//         // Path matches public/data/cards/<set_api_id>.json
//         $filePath = public_path("data/cards/{$set->api_id}.json");

//         if (File::exists($filePath)) {
//             $cardsInSet = json_decode(File::get($filePath), true);
            
//             if (is_array($cardsInSet)) {
//                 // Merge this set's cards into the master collection
//                 $masterCollection = array_merge($masterCollection, $cardsInSet);
                
//                 $totalCardsImported += count($cardsInSet);
//                 $setsProcessed++;
//             }
//         }
//     }

//     // 2. Save the final combined JSON
//     $outputPath = public_path('data/all_cards_combined_master.json');
//     File::put($outputPath, json_encode($masterCollection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

//     // 3. Final Output Report
//     return response()->json([
//         'status' => 'Complete',
//         'sets_processed' => $setsProcessed,
//         'total_cards_imported' => $totalCardsImported,
//         'file_path' => $outputPath
//     ]);
// });

// Route::get('/export-minimal-original-cards', function () {
//     ini_set('memory_limit', '-1');
//     ini_set('max_execution_time', 600);

//     // 1. Define Rarity Weights (Lower is "more basic")
//     $rarityWeights = [
//         'Common' => 1,
//         'Uncommon' => 2,
//         'Rare' => 3,
//         'Rare Holo' => 3,
//         'Double Rare' => 4,
//         'Ultra Rare' => 5,
//         'Shiny Rare' => 5,
//         'Illustration Rare' => 6,
//         'Special Illustration Rare' => 7,
//         'Hyper Rare' => 8,
//         'Secret Rare' => 9,
//         'Promo' => 10,
//     ];

//     $allSets = Set::orderBy('release_date', 'asc')->get();
//     $functionalGroups = [];

//     foreach ($allSets as $set) {
//         $filePath = public_path("data/cards/{$set->api_id}.json");

//         if (File::exists($filePath)) {
//             $cardsInSet = json_decode(File::get($filePath), true);
            
//             foreach ($cardsInSet as $card) {
//                 // Create a functional fingerprint to identify "the same card"
//                 $name = $card['name'] ?? 'Unknown';
//                 $hp = $card['hp'] ?? '0';
//                 $supertype = $card['supertype'] ?? '';
                
//                 // For Pokémon, identity is Name + HP. For Trainers/Energy, just Name.
//                 $fingerprint = ($supertype === 'Pokémon') ? "{$name}|{$hp}" : "{$supertype}|{$name}";

//                 // Get Rarity Weight
//                 $rarity = $card['rarity'] ?? 'Common';
//                 $weight = $rarityWeights[$rarity] ?? 1; // Default to 1 if unknown

//                 $currentReleaseDate = strtotime(str_replace('/', '-', $set->release_date));

//                 // If we haven't seen this card, or if this one is EARLIER, 
//                 // or if it's the SAME DATE but LOWER RARITY, update the group.
//                 if (!isset($functionalGroups[$fingerprint])) {
//                     $functionalGroups[$fingerprint] = [
//                         'data' => $card,
//                         'date' => $currentReleaseDate,
//                         'weight' => $weight
//                     ];
//                 } else {
//                     $existing = $functionalGroups[$fingerprint];
                    
//                     // Logic: Update if (Earlier Date) OR (Same Date AND Lower Weight)
//                     if ($currentReleaseDate < $existing['date'] || 
//                        ($currentReleaseDate === $existing['date'] && $weight < $existing['weight'])) {
                        
//                         $functionalGroups[$fingerprint] = [
//                             'data' => $card,
//                             'date' => $currentReleaseDate,
//                             'weight' => $weight
//                         ];
//                     }
//                 }
//             }
//         }
//     }

//     // 2. Extract final data and save
//     $finalCollection = array_column($functionalGroups, 'data');
    
//     $outputPath = public_path('data/minimal_original_cards.json');
//     File::put($outputPath, json_encode($finalCollection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

//     return response()->json([
//         'status' => 'Success',
//         'total_unique_cards' => count($finalCollection),
//         'file_path' => $outputPath
//     ]);
// });

// Route::match(['get', 'post'], '/generate-final-unity-json', function (Request $request) {
//     ini_set('memory_limit', '-1');
//     ini_set('max_execution_time', 900);

//     $rawInput = $request->input('decklists', '');
//     $finalCards = [];
//     $errors = [];

//     if ($request->isMethod('post') && !empty($rawInput)) {
//         // --- STEP 1: Load Minimal Originals for Comparison ---
//         $minimalPath = public_path('data/minimal_original_cards.json');
//         if (!File::exists($minimalPath)) {
//             return "Error: minimal_original_cards.json not found. Run the previous export route first.";
//         }
//         $minimalOriginals = json_decode(File::get($minimalPath), true);

//         // --- STEP 2: Pre-load Set Mapping (PTCGO Code -> API ID) ---
//         $setMap = Set::pluck('api_id', 'ptcgo_code')->toArray();
//         $setMap['MEE'] = 'sve'; // Requirement 2: Force MEE to SVE

//         // --- STEP 3: Parse the List and Fetch Raw Data ---
//         $lines = explode("\n", $rawInput);
//         $tempCollection = [];
//         $loadedFiles = [];

//         foreach ($lines as $line) {
//             $line = trim($line);
//             if (empty($line) || preg_match('/^(Pokémon|Trainer|Energy)/i', $line)) continue;

//             // Regex captures: [Quantity] [Name] [PTCGO Code] [Number]
//             if (preg_match('/^\d+(\.\d+)?\s+(.+?)\s+([A-Z0-9\-]{2,6})\s+([A-Z0-9a-z\-]+)$/i', $line, $matches)) {
//                 $name = trim($matches[2]);
//                 $ptcgo = strtoupper(trim($matches[3]));
//                 $number = trim($matches[4]);

//                 $apiSetId = $setMap[$ptcgo] ?? strtolower($ptcgo);

//                 if (!isset($loadedFiles[$apiSetId])) {
//                     $path = public_path("data/cards/{$apiSetId}.json");
//                     $loadedFiles[$apiSetId] = File::exists($path) ? json_decode(File::get($path), true) : null;
//                 }

//                 if ($loadedFiles[$apiSetId]) {
//                     foreach ($loadedFiles[$apiSetId] as $card) {
//                         if ((string)$card['number'] === (string)$number) {
//                             $tempCollection[] = $card;
//                             break;
//                         }
//                     }
//                 } else {
//                     $errors[] = "Set File Missing: {$apiSetId}.json for {$name}";
//                 }
//             }
//         }

//         // --- STEP 4 & 5: Compare and Harmonize with Minimal Originals ---
//         foreach ($tempCollection as $recentCard) {
//             $foundMatch = false;
//             $rName = $recentCard['name'];
//             $rHP = $recentCard['hp'] ?? null;
//             $rSuper = $recentCard['supertype'];

//             foreach ($minimalOriginals as $original) {
//                 // Comparison Logic
//                 $namesMatch = ($original['name'] === $rName);
//                 $typesMatch = ($original['supertype'] === $rSuper);
                
//                 if ($namesMatch && $typesMatch) {
//                     if ($rSuper === 'Pokémon') {
//                         // For Pokémon, match HP and Attacks (simplified name check)
//                         $hpMatch = ($original['hp'] ?? null) === $rHP;
//                         if ($hpMatch) {
//                             $finalCards[$rName . $rHP] = $original;
//                             $foundMatch = true;
//                             break;
//                         }
//                     } else {
//                         // For Trainers/Energy, name and supertype are enough
//                         $finalCards[$rName] = $original;
//                         $foundMatch = true;
//                         break;
//                     }
//                 }
//             }

//             // If no match in minimal, use the data from the recent set
//             if (!$foundMatch) {
//                 $key = ($rSuper === 'Pokémon') ? $rName . $rHP : $rName;
//                 if (!isset($finalCards[$key])) {
//                     $finalCards[$key] = $recentCard;
//                 }
//             }
//         }

//         // Save to final.json
//         $outputData = array_values($finalCards);
//         File::put(public_path('data/final.json'), json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
//     }

//     // --- Simple UI ---
//     $html = '<div style="font-family:sans-serif; padding:40px;">';
//     $html .= '<h2>Final Card Harmonizer</h2>';
//     if (!empty($finalCards)) $html .= '<p style="color:green">Success! Saved ' . count($finalCards) . ' cards to final.json</p>';
//     $html .= '<form method="POST">' . csrf_field();
//     $html .= '<textarea name="decklists" rows="20" style="width:100%; font-family:monospace;">'.htmlspecialchars($rawInput).'</textarea><br>';
//     $html .= '<button type="submit" style="padding:10px 20px; background:blue; color:white; margin-top:10px;">Process & Harmonize</button>';
//     $html .= '</form></div>';
//     return $html;
// });