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
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('api_id')->unique();
            $table->foreignId('set_id')->constrained('sets')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('supertype')->nullable();
            $table->string('hp')->nullable();
            $table->string('evolves_from')->nullable();
            $table->string('rarity')->nullable();
            $table->text('flavor_text')->nullable();
            $table->string('number')->nullable();
            $table->string('artist')->nullable();
            $table->integer('converted_retreat_cost')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
