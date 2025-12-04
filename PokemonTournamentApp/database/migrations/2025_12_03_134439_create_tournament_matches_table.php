<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->integer('round_number'); // Round 1, 2, 3...
            
            // Player 1
            $table->foreignId('player1_entry_id')
                  ->constrained('tournament_entries')
                  ->cascadeOnDelete();
            
            // Player 2 (Nullable for a "Bye" if odd number of players)
            $table->foreignId('player2_entry_id')
                  ->nullable()
                  ->constrained('tournament_entries')
                  ->cascadeOnDelete();

            // Results
            // null = not played yet, 1 = P1 Win, 2 = P2 Win, 3 = Tie/Draw
            $table->tinyInteger('result_code')->nullable(); 
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
