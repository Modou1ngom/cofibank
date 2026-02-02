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
            $table->enum('status', ['draft', 'pending_validation', 'validated', 'rejected'])->default('draft')->after('description');
            $table->unsignedBigInteger('created_by')->nullable()->after('status');
            $table->unsignedBigInteger('validated_by')->nullable()->after('created_by');
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->text('rejection_reason')->nullable()->after('validated_at');
            $table->string('zone')->nullable()->after('territory'); // Zone pour les responsables de zone
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
            $table->index('status');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['validated_by']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_by']);
            $table->dropColumn(['status', 'created_by', 'validated_by', 'validated_at', 'rejection_reason', 'zone']);
        });
    }
};
