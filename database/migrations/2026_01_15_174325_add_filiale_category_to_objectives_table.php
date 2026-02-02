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
        // Pour SQLite, on doit recréer la table avec le nouvel enum
        if (DB::getDriverName() === 'sqlite') {
            // Supprimer la table temporaire si elle existe
            DB::statement("DROP TABLE IF EXISTS objectives_new");
            
            // Créer une nouvelle table avec le nouvel enum
            DB::statement("
                CREATE TABLE objectives_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT NOT NULL CHECK(type IN ('CLIENT', 'PRODUCTION')),
                    category TEXT NOT NULL CHECK(category IN ('FILIALE', 'TERRITOIRE', 'POINT SERVICES', 'GRAND COMPTE')),
                    territory TEXT,
                    agency_code TEXT NOT NULL DEFAULT '',
                    agency_name TEXT,
                    value INTEGER NOT NULL,
                    period TEXT NOT NULL CHECK(period IN ('month', 'quarter', 'year')),
                    year INTEGER NOT NULL,
                    month INTEGER,
                    quarter INTEGER,
                    description TEXT,
                    status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'pending_validation', 'validated', 'rejected')),
                    created_by INTEGER,
                    validated_by INTEGER,
                    validated_at TIMESTAMP,
                    rejection_reason TEXT,
                    zone TEXT,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP
                )
            ");
            
            // Copier les données en gérant les valeurs NULL
            DB::statement("
                INSERT INTO objectives_new 
                SELECT 
                    id,
                    type,
                    category,
                    territory,
                    COALESCE(agency_code, '') as agency_code,
                    agency_name,
                    value,
                    period,
                    year,
                    month,
                    quarter,
                    description,
                    COALESCE(status, 'draft') as status,
                    created_by,
                    validated_by,
                    validated_at,
                    rejection_reason,
                    zone,
                    created_at,
                    updated_at
                FROM objectives
            ");
            
            // Supprimer l'ancienne table
            DB::statement("DROP TABLE objectives");
            
            // Renommer la nouvelle table
            DB::statement("ALTER TABLE objectives_new RENAME TO objectives");
            
            // Recréer les index
            DB::statement("CREATE INDEX idx_objectives_type_category ON objectives(type, category, agency_code, year, month)");
            DB::statement("CREATE INDEX idx_objectives_agency_year_month ON objectives(agency_code, year, month)");
        } else {
            // Pour MySQL/PostgreSQL, utiliser ALTER TABLE
            DB::statement("ALTER TABLE objectives MODIFY COLUMN category ENUM('FILIALE', 'TERRITOIRE', 'POINT SERVICES', 'GRAND COMPTE') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Recréer la table avec l'ancien enum
            DB::statement("
                CREATE TABLE objectives_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT NOT NULL CHECK(type IN ('CLIENT', 'PRODUCTION')),
                    category TEXT NOT NULL CHECK(category IN ('TERRITOIRE', 'POINT SERVICES', 'GRAND COMPTE')),
                    territory TEXT,
                    agency_code TEXT NOT NULL,
                    agency_name TEXT,
                    value INTEGER NOT NULL,
                    period TEXT NOT NULL CHECK(period IN ('month', 'quarter', 'year')),
                    year INTEGER NOT NULL,
                    month INTEGER,
                    quarter INTEGER,
                    description TEXT,
                    status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'pending_validation', 'validated', 'rejected')),
                    created_by INTEGER,
                    validated_by INTEGER,
                    validated_at TIMESTAMP,
                    rejection_reason TEXT,
                    zone TEXT,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP
                )
            ");
            
            // Copier les données (sauf FILIALE)
            DB::statement("
                INSERT INTO objectives_old 
                SELECT * FROM objectives WHERE category != 'FILIALE'
            ");
            
            DB::statement("DROP TABLE objectives");
            DB::statement("ALTER TABLE objectives_old RENAME TO objectives");
            
            DB::statement("CREATE INDEX idx_objectives_type_category ON objectives(type, category, agency_code, year, month)");
            DB::statement("CREATE INDEX idx_objectives_agency_year_month ON objectives(agency_code, year, month)");
        } else {
            DB::statement("ALTER TABLE objectives MODIFY COLUMN category ENUM('TERRITOIRE', 'POINT SERVICES', 'GRAND COMPTE') NOT NULL");
        }
    }
};
