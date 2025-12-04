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
        // 2. Tournament Entries (The Roster & Final Standings)
        // This links a User and their chosen Deck to the Tournament
        Schema::create('tournament_entries', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // The specific deck they registered with.
            // If they update their deck later, it shouldn't affect the locked list here,
            // but for simplicity, we link to the deck ID.
            $table->foreignId('deck_id')->constrained('decks'); 

            // Standings Data (Updated after every match or at end)
            $table->integer('rank')->nullable(); // Final placing (1st, 2nd...)
            $table->integer('points')->default(0); // 3 for Win, 1 for Tie, 0 for Loss
            
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('ties')->default(0);
            
            // Tie Breakers
            // OMW% (Opponent Match Win Percentage) - Strength of Schedule
            $table->decimal('omw_percentage', 5, 2)->default(0.00); 

            $table->timestamps();

            // A user can only enter a tournament once
            $table->unique(['tournament_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_entries');
    }
};
