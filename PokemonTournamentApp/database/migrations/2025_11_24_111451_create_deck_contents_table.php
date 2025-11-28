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
        Schema::create('deck_contents', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('global_deck_id')->constrained('global_decks')->cascadeOnDelete();
            $table->foreignId('card_id')->constrained('cards')->cascadeOnDelete();
            
            $table->integer('quantity');

            // Prevent the same card from being listed twice in the same deck list
            $table->unique(['global_deck_id', 'card_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deck_contents');
    }
};
