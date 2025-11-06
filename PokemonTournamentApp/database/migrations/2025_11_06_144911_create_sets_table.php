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
        Schema::create('sets', function (Blueprint $table) {
            $table->id();
            $table->string('api_id')->unique(); // 'me1'
            $table->string('name');
            $table->string('series');
            $table->integer('printed_total');
            $table->integer('total');
            $table->string('ptcgo_code')->nullable();
            $table->date('release_date')->nullable();
            $table->timestamp('updated_at_api')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sets');
    }
};
