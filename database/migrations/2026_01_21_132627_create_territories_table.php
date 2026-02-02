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
        Schema::create('territories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Ex: DAKAR_VILLE, DAKAR_BANLIEUE, PROVINCE_CENTRE_SUD, PROVINCE_NORD
            $table->string('name'); // Ex: Dakar Ville, Dakar Banlieue, Province Centre Sud, Province Nord
            $table->text('description')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->onDelete('set null'); // Responsable de Zone
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('territories');
    }
};
