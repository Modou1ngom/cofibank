<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier le type de base de données
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // Pour SQLite, recréer la table avec les nouveaux types
            DB::statement("
                CREATE TABLE objectives_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT NOT NULL CHECK(type IN ('CLIENT', 'PRODUCTION', 'PRODUCTION_VOLUME', 'ENCOURS_CREDIT', 'PRODUCTION_ENCOURS')),
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
            
            // Copier les données
            DB::statement("
                INSERT INTO objectives_new 
                SELECT * FROM objectives
            ");
            
            // Supprimer l'ancienne table et renommer la nouvelle
            Schema::dropIfExists('objectives');
            DB::statement("ALTER TABLE objectives_new RENAME TO objectives");
            
            // Recréer les index
            DB::statement("CREATE INDEX idx_objectives_type_category ON objectives(type, category, agency_code, year, month)");
            DB::statement("CREATE INDEX idx_objectives_agency_year_month ON objectives(agency_code, year, month)");
            
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            // Pour MySQL/MariaDB, modifier l'enum directement
            DB::statement("ALTER TABLE objectives MODIFY COLUMN type ENUM('CLIENT', 'PRODUCTION', 'PRODUCTION_VOLUME', 'ENCOURS_CREDIT', 'PRODUCTION_ENCOURS') NOT NULL");
        } elseif ($driver === 'pgsql') {
            // Pour PostgreSQL, utiliser ALTER TYPE
            DB::statement("ALTER TYPE objectives_type_enum ADD VALUE IF NOT EXISTS 'PRODUCTION_VOLUME'");
            DB::statement("ALTER TYPE objectives_type_enum ADD VALUE IF NOT EXISTS 'ENCOURS_CREDIT'");
            DB::statement("ALTER TYPE objectives_type_enum ADD VALUE IF NOT EXISTS 'PRODUCTION_ENCOURS'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // Pour SQLite, recréer la table avec les anciens types
            DB::statement("
                CREATE TABLE objectives_old (
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
            
            // Copier seulement les données avec les anciens types
            DB::statement("
                INSERT INTO objectives_old 
                SELECT * FROM objectives
                WHERE type IN ('CLIENT', 'PRODUCTION')
            ");
            
            Schema::dropIfExists('objectives');
            DB::statement("ALTER TABLE objectives_old RENAME TO objectives");
            
            DB::statement("CREATE INDEX idx_objectives_type_category ON objectives(type, category, agency_code, year, month)");
            DB::statement("CREATE INDEX idx_objectives_agency_year_month ON objectives(agency_code, year, month)");
            
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE objectives MODIFY COLUMN type ENUM('CLIENT', 'PRODUCTION') NOT NULL");
        }
        // Pour PostgreSQL, on ne peut pas supprimer des valeurs d'un enum facilement
    }
};
