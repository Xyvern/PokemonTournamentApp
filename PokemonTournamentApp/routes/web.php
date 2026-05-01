<?php

use App\Http\Controllers\AdminController;   
use App\Http\Controllers\AdminSiteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PlayerSiteController;
use App\Http\Controllers\SiteController;
use App\Http\Middleware\IsAdmin;
use App\Models\Card;
use App\Models\Set;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| 1. DYNAMIC ROOT REDIRECT
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (Auth::check() && Auth::user()->role == 2) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('player.home');
});

// The Midtrans webhook must remain outside of all auth/csrf middleware
Route::post('/midtrans/callback', [PaymentController::class, 'callback']);

/*
|--------------------------------------------------------------------------
| 2. PUBLIC FRONTEND ROUTES (Blocked for Admins)
|--------------------------------------------------------------------------
*/
Route::middleware(['block.admin'])->group(function () {
    
    Route::get('/decks/{deck}', [SiteController::class, 'showDeck'])->name('showDeck');
    Route::get('/play', function (Request $request) { return view('game.play'); })->name('play');

    Route::prefix('sets')->name('sets.')->group(function () {
        Route::get('/', [PlayerSiteController::class, 'playerSets'])->name('index');
        Route::get('/{id}', [PlayerSiteController::class, 'playerSetDetail'])->name('detail');
    });

    Route::prefix('tournaments')->name('tournaments.')->group(function () {
        Route::get('/', [SiteController::class, 'tournaments'])->name('index');
        Route::get('/{id}', [SiteController::class, 'tournamentDetail'])->name('detail');
        Route::get('/{id}/matches', [PlayerController::class, 'fetchRoundMatches'])->name('matches.fetch');
    });

    Route::prefix('cards')->name('cards.')->group(function () {
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
    });
});

/*
|--------------------------------------------------------------------------
| 3. GUEST ROUTES (Only for users who are NOT logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',[AuthController::class, 'login'])->name('login');
    Route::get('/register',[AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'doLogin'])->name('dologin');
    Route::post('/register', [AuthController::class, 'doRegister'])->name('doregister');
});

/*
|--------------------------------------------------------------------------
| 4. AUTHENTICATED PLAYER ROUTES (Must be logged in, NO ADMINS)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// 4B. Protected Player Routes (Must be logged in, NO ADMINS)
Route::middleware(['auth', 'block.admin'])->group(function () {
    
    // Tournament Actions
    Route::post('/tournaments/{id}/register', [PlayerController::class, 'registerTournament'])->name('tournaments.register');
    Route::post('/tournaments/{id}/drop', [PlayerController::class, 'dropTournament'])->name('tournaments.drop');

    Route::prefix('player')->name('player.')->group(function () {
        Route::get('/upgrade', [PlayerSiteController::class, 'upgrade'])->name('upgrade');
        Route::get('/my-decks', [PlayerSiteController::class, 'myDecks'])->name('mydecks');
        Route::get('/decks/create', [PlayerSiteController::class, 'createDeck'])->name('createDeck');
        Route::post('/decks/store', [PlayerController::class, 'storeDeck'])->name('storeDeck');
        Route::get('/edit-profile', [PlayerSiteController::class, 'editProfile'])->name('editProfile');
        Route::post('/edit-profile', [PlayerController::class, 'updateProfile'])->name('updateProfile');
    });
});


/*
|--------------------------------------------------------------------------
| 4. ADMIN ROUTES (Must be logged in AND be an Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', IsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [AdminSiteController::class, 'index'])->name('dashboard');
    Route::get('/unassigned-decks', [AdminSiteController::class, 'unassignedDecks'])->name('unassignedDecks');
    Route::post('/assign-archetype', [AdminController::class, 'assignArchetype'])->name('assignArchetype');
    
    Route::prefix('tournaments')->name('tournaments.')->group(function () {
        Route::get('/', [AdminSiteController::class, 'tournaments'])->name('index');
        Route::get('/create', [AdminSiteController::class, 'createTournament'])->name('create');
        Route::post('/store', [AdminController::class, 'storeTournament'])->name('store');
        Route::get('/{id}', [AdminSiteController::class, 'tournamentDetail'])->name('detail');
        Route::get('/{id}/edit', [AdminSiteController::class, 'editTournament'])->name('edit');
        Route::post('/{id}/update', [AdminController::class, 'updateTournament'])->name('update');
        Route::post('/{id}/delete', [AdminController::class, 'deleteTournament'])->name('delete');
        Route::post('/{id}/cancel', [AdminController::class, 'cancelTournament'])->name('cancel');
        
        // Action Routes
        Route::post('/{id}/start', [AdminController::class, 'startTournament'])->name('start');
        Route::post('/{id}/next-round', [AdminController::class, 'generateNextRound'])->name('nextRound');
        Route::put('/{id}/update-match', [AdminController::class, 'updateMatchResult'])->name('updateMatch');
        Route::post('/{id}/finalize', [AdminController::class, 'finalizeTournament'])->name('finalize');
        Route::post('/{id}/drop-player', [AdminController::class, 'dropPlayer'])->name('dropPlayer');
        
        // AJAX
        Route::get('/{id}/matches', [AdminController::class, 'fetchRoundMatches'])->name('matches.fetch');
    });

    Route::prefix('archetypes')->name('archetypes.')->group(function () {
        Route::get('/', [AdminSiteController::class, 'archetypes'])->name('index');
        Route::get('/create', [AdminSiteController::class, 'createArchetype'])->name('create');
        Route::get('/{id}', [AdminSiteController::class, 'archetypeDetail'])->name('detail');
        Route::post('/store', [AdminController::class, 'storeArchetype'])->name('store');
        Route::get('/{id}/edit', [AdminSiteController::class, 'editArchetype'])->name('edit');
        Route::post('/{id}/update', [AdminController::class, 'updateArchetype'])->name('update');
        Route::post('/{id}/delete', [AdminController::class, 'deleteArchetype'])->name('delete');
    });

    Route::prefix('cards')->name('cards.')->group(function () {
        Route::get('/', [AdminSiteController::class, 'cardDatabase'])->name('index');
        Route::get('/{id}', [AdminSiteController::class, 'cardDetail'])->name('detail');
        Route::post('/sync', [AdminController::class, 'syncCards'])->name('sync');
        Route::post('/{id}/toggle-playable', [AdminController::class, 'togglePlayable'])->name('togglePlayable');
    });

    Route::prefix('players')->name('players.')->group(function () {
        Route::get('/', [AdminSiteController::class, 'managePlayers'])->name('index');
        Route::post('/{id}/toggle', [AdminController::class, 'togglePlayerStatus'])->name('toggle');
    });

});

Route::match(['get', 'post'], '/generate-playable-json', function (Request $request) {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 900);

    $successResults = [];
    $errorResults = [];

    if ($request->isMethod('post')) {
        
        // 1. Translation Dictionary
        $ptcgoToApiId = [
            'PR-SV' => 'svp', 'PR-SW' => 'swshp', 'PR-SM' => 'smp', 'MEE' => 'sve'
        ];
        $apiIdToDate = [];
        
        foreach (Set::all() as $set) {
            if ($set->ptcgo_code) {
                $code = strtoupper($set->ptcgo_code);
                if (!isset($ptcgoToApiId[$code])) $ptcgoToApiId[$code] = $set->api_id;
            }
            $ptcgoToApiId[strtoupper($set->api_id)] = $set->api_id;
            $apiIdToDate[$set->api_id] = $set->release_date;
        }

        // --- EXPANDED Rarity Weights (Lowest to Highest) ---
        $rarityWeights = [
            'Common' => 1, 'Uncommon' => 2, 'Rare' => 3, 'Rare Holo' => 4,
            'Double Rare' => 5, 'Rare Holo V' => 5, 'Rare Holo EX' => 5, 'Rare Holo GX' => 5,
            'Radiant Rare' => 6, 'Amazing Rare' => 6,
            'Ultra Rare' => 7, 'Rare Holo VMAX' => 7, 'Rare Holo VSTAR' => 7, 'Shiny Rare' => 7,
            'Illustration Rare' => 8, 'Shiny Ultra Rare' => 8,
            'Special Illustration Rare' => 9, 'Hyper Rare' => 10, 'Secret Rare' => 11, 'Promo' => 12,
        ];

        // 2. Read from the JSON File
        $jsonPath = public_path('data/decklists.json');
        if (!File::exists($jsonPath)) {
            return "<h2 style='color:red;'>Error: decklists.json not found in public/data/</h2>";
        }
        
        $decklistsData = json_decode(file_get_contents($jsonPath), true);
        $targetNames = [];
        $parsedCards = [];

        // Extract all unique cards from the decks
        foreach ($decklistsData as $deck) {
            foreach ($deck['cards'] as $cardArray) {
                $ptcgo = strtoupper(trim($cardArray[0]));
                $number = trim($cardArray[1]);
                $parsedCards[$ptcgo . '-' . $number] = [
                    'ptcgo' => $ptcgo,
                    'number' => $number
                ];
            }
        }

        // 3. Scan Local JSON Files
        $relevantCards = [];
        $files = File::files(public_path('data/cards'));

        foreach ($files as $file) {
            $apiId = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            if ($apiId === 'playable') continue;

            $releaseDate = $apiIdToDate[$apiId] ?? '9999-12-31'; 
            $jsonData = json_decode(file_get_contents($file->getPathname()), true);
            if (!is_array($jsonData)) continue;

            foreach ($jsonData as $card) {
                $cardName = $card['name'] ?? '';
                $normCardName = trim(preg_replace('/^basic\s+/i', '', strtolower($cardName)));
                $normCardName = str_replace('’', "'", $normCardName);

                $relevantCards[] = [
                    'id' => $card['id'],
                    'name' => $cardName,
                    'normalized_name' => $normCardName,
                    'supertype' => $card['supertype'] ?? '',
                    'hp' => $card['hp'] ?? null,
                    'attacks' => isset($card['attacks']) ? collect($card['attacks'])->pluck('name')->sort()->values()->toArray() : [],
                    'api_id' => $apiId,
                    'number' => $card['number'] ?? '',
                    'release_date' => $releaseDate,
                    'rarity' => $card['rarity'] ?? 'Common' 
                ];
            }
        }

        // 4. Find the Best Match (Min Rarity -> Oldest Date)
        $playableOutputArray = [];

        foreach ($parsedCards as $pc) {
            $apiId = $ptcgoToApiId[$pc['ptcgo']] ?? strtolower($pc['ptcgo']);

            // A. Find the specific BASE card the user requested
            $baseCard = null;
            foreach ($relevantCards as $rc) {
                if ($rc['api_id'] === $apiId && (string)$rc['number'] === (string)$pc['number']) {
                    $baseCard = $rc;
                    break;
                }
            }

            if (!$baseCard) {
                $errorResults[] = "<strong>Missing Base Card:</strong> {$pc['ptcgo']} {$pc['number']} (Searched API ID: {$apiId})";
                continue;
            }

            // B. Find all mechanical clones
            $candidates = [];
            foreach ($relevantCards as $rc) {
                if ($rc['normalized_name'] === $baseCard['normalized_name'] && $rc['supertype'] === $baseCard['supertype']) {
                    if ($baseCard['supertype'] === 'Pokémon') {
                        // STRICT MATCH: HP and exact Attack Names
                        if ($rc['hp'] === $baseCard['hp'] && $rc['attacks'] === $baseCard['attacks']) {
                            $candidates[] = $rc;
                        }
                    } else {
                        // Trainers/Energy just need the same name/supertype
                        $candidates[] = $rc;
                    }
                }
            }

            // C. NEW SORTING LOGIC: Sort candidates by Rarity Weight (Asc), then by Release Date (Asc)
            usort($candidates, function($a, $b) use ($rarityWeights) {
                $weightA = $rarityWeights[$a['rarity']] ?? 50; 
                $weightB = $rarityWeights[$b['rarity']] ?? 50;

                // 1. Compare Rarity First
                if ($weightA !== $weightB) {
                    return $weightA <=> $weightB;
                }

                // 2. If Rarities are identical, tie-break with Release Date
                $dateA = strtotime(str_replace('/', '-', $a['release_date']));
                $dateB = strtotime(str_replace('/', '-', $b['release_date']));
                
                return $dateA <=> $dateB;
            });

            if (count($candidates) > 0) {
                $bestMatch = $candidates[0];
                $playableOutputArray[$bestMatch['id']] = ['id' => $bestMatch['id']];
                $successResults[] = "<span style='color:green'>Matched <strong>{$baseCard['name']}</strong> -> Assigned Playable: {$bestMatch['id']} ({$bestMatch['api_id']} - {$bestMatch['rarity']})</span>";
            } else {
                $errorResults[] = "<strong>Failed to find any candidates for:</strong> {$baseCard['name']}";
            }
        }

        // 5. Generate playable.json
        $finalPlayableData = array_values($playableOutputArray);
        File::put(public_path('data/cards/playable.json'), json_encode($finalPlayableData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        $successResults[] = "<br><h3 style='color:blue;'>✅ Successfully generated playable.json with " . count($finalPlayableData) . " unique playable cards!</h3>";
    }

    // --- Simple UI ---
    $html = '<div style="font-family:sans-serif; padding:40px; max-width: 900px; margin: auto;">';
    $html .= '<h2>Playable.json Generator (Min Rarity Priority)</h2>';
    $html .= '<p>Click below to scan your <code>public/data/decklists.json</code> file, locate the mechanical matches with the <strong>lowest rarity</strong> (tie-breaking with oldest release date), and update <code>playable.json</code>.</p>';
    
    if (!empty($errorResults)) {
        $html .= '<div style="background: #ffe6e6; border: 2px solid #ff4d4d; padding: 15px; border-radius: 6px; margin-bottom: 20px;">';
        $html .= '<h3 style="color: #cc0000; margin-top: 0;">❌ Warning: Missing Cards!</h3>';
        $html .= '<ul style="color: #cc0000; font-family: monospace; font-size: 13px;">';
        foreach ($errorResults as $err) { $html .= "<li>{$err}</li>"; }
        $html .= '</ul></div>';
    } elseif ($request->isMethod('post')) {
        $html .= '<div style="background: #e6ffe6; border: 2px solid #28a745; padding: 15px; border-radius: 6px; margin-bottom: 20px;">';
        $html .= '<h3 style="color: #1e7e34; margin-top: 0; margin-bottom: 0;">🎉 All Cards Successfully Validated!</h3>';
        $html .= '</div>';
    }

    $html .= '<form method="POST">' . csrf_field();
    $html .= '<button type="submit" style="padding:15px 30px; background:#28a745; color:white; border:none; border-radius:4px; margin-top:10px; cursor:pointer; font-weight:bold; font-size: 16px;">Scan Decklists.json & Generate</button>';
    $html .= '</form>';
    
    if (!empty($successResults)) {
        $html .= '<div style="margin-top: 30px; background: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 13px; line-height: 1.6;">';
        foreach ($successResults as $res) { $html .= $res . '<br>'; }
        $html .= '</div>';
    }
    
    $html .= '</div>';
    return $html;
});