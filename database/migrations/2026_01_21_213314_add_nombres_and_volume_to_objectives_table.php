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
        Schema::table('objectives', function (Blueprint $table) {
            // Ajouter les champs pour NOMBRES et VOLUME
            $table->integer('value_nombres')->nullable()->after('value');
            $table->bigInteger('value_volume')->nullable()->after('value_nombres');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropColumn(['value_nombres', 'value_volume']);
        });
    }
};
