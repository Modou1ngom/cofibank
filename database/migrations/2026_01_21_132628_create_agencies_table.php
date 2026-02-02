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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Code de l'agence
            $table->string('name'); // Nom de l'agence
            $table->text('description')->nullable();
            $table->foreignId('territory_id')->nullable()->constrained('territories')->onDelete('set null'); // Territoire auquel appartient l'agence
            $table->foreignId('chef_agence_user_id')->nullable()->constrained('users')->onDelete('set null'); // Chef d'Agence
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
