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
        Schema::create('global_decks', function (Blueprint $table) {
            $table->id();
            $table->string('deck_hash', 64)->unique(); 
            $table->foreignId('archetype_id')->nullable()->constrained('archetypes')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_decks');
    }
};
