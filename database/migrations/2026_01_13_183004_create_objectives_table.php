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
        Schema::create('objectives', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['CLIENT', 'PRODUCTION']);
            $table->enum('category', ['TERRITOIRE', 'POINT SERVICES', 'GRAND COMPTE']);
            $table->string('territory')->nullable();
            $table->string('agency_code');
            $table->string('agency_name')->nullable();
            $table->decimal('value', 15, 2);
            $table->enum('period', ['month', 'quarter', 'year']);
            $table->integer('year');
            $table->integer('month')->nullable();
            $table->integer('quarter')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Index pour les recherches rapides
            $table->index(['type', 'category', 'agency_code', 'year', 'month']);
            $table->index(['agency_code', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objectives');
    }
};
