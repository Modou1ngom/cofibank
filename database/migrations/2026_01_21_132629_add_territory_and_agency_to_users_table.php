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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('territory_id')->nullable()->after('profile_id')->constrained('territories')->onDelete('set null');
            $table->foreignId('agency_id')->nullable()->after('territory_id')->constrained('agencies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['territory_id']);
            $table->dropForeign(['agency_id']);
            $table->dropColumn(['territory_id', 'agency_id']);
        });
    }
};
