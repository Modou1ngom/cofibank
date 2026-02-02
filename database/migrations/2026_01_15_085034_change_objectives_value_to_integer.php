<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convertir les valeurs décimales existantes en entiers (arrondir)
        DB::statement('UPDATE objectives SET value = ROUND(value) WHERE value IS NOT NULL');
        
        // Changer le type de colonne de decimal à bigInteger (SQL brut)
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite nécessite une recréation de table avec toutes les colonnes
            DB::statement('DROP TABLE IF EXISTS objectives_new');
            DB::statement('
                CREATE TABLE objectives_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT NOT NULL,
                    category TEXT NOT NULL,
                    territory TEXT,
                    zone TEXT,
                    agency_code TEXT NOT NULL,
                    agency_name TEXT,
                    value INTEGER NOT NULL,
                    period TEXT NOT NULL,
                    year INTEGER NOT NULL,
                    month INTEGER,
                    quarter INTEGER,
                    description TEXT,
                    status TEXT NOT NULL DEFAULT \'draft\',
                    created_by INTEGER,
                    validated_by INTEGER,
                    validated_at TEXT,
                    rejection_reason TEXT,
                    created_at TEXT,
                    updated_at TEXT
                )
            ');
            DB::statement('
                INSERT INTO objectives_new 
                (id, type, category, territory, zone, agency_code, agency_name, value, period, year, month, quarter, description, status, created_by, validated_by, validated_at, rejection_reason, created_at, updated_at)
                SELECT 
                id, type, category, territory, zone, agency_code, agency_name, ROUND(value), period, year, month, quarter, description, status, created_by, validated_by, validated_at, rejection_reason, created_at, updated_at
                FROM objectives
            ');
            DB::statement('DROP TABLE objectives');
            DB::statement('ALTER TABLE objectives_new RENAME TO objectives');
        } else {
            // MySQL/PostgreSQL
            DB::statement('ALTER TABLE objectives MODIFY COLUMN value BIGINT NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite nécessite une recréation de table
            DB::statement('
                CREATE TABLE objectives_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT NOT NULL,
                    category TEXT NOT NULL,
                    territory TEXT,
                    zone TEXT,
                    agency_code TEXT NOT NULL,
                    agency_name TEXT,
                    value NUMERIC(15,2) NOT NULL,
                    period TEXT NOT NULL,
                    year INTEGER NOT NULL,
                    month INTEGER,
                    quarter INTEGER,
                    description TEXT,
                    status TEXT NOT NULL DEFAULT \'draft\',
                    created_by INTEGER,
                    validated_by INTEGER,
                    validated_at TEXT,
                    rejection_reason TEXT,
                    created_at TEXT,
                    updated_at TEXT
                )
            ');
            DB::statement('
                INSERT INTO objectives_old 
                (id, type, category, territory, zone, agency_code, agency_name, value, period, year, month, quarter, description, status, created_by, validated_by, validated_at, rejection_reason, created_at, updated_at)
                SELECT * FROM objectives
            ');
            DB::statement('DROP TABLE objectives');
            DB::statement('ALTER TABLE objectives_old RENAME TO objectives');
        } else {
            // MySQL/PostgreSQL
            DB::statement('ALTER TABLE objectives MODIFY COLUMN value DECIMAL(15,2) NOT NULL');
        }
    }
};
