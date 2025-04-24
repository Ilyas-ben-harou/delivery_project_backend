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
        Schema::create('livreur_zone_geographic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livreur_id')->constrained()->onDelete('cascade');
            $table->foreignId('zone_geographic_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livreur_zone_geographic');
    }
};
