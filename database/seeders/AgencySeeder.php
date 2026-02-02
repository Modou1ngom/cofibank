<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Agency;
use App\Models\Territory;

class AgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Note: Les agences seront créées dynamiquement depuis les données Oracle
        // Ce seeder peut être utilisé pour créer des agences de base si nécessaire
        
        // Exemple d'agence (à adapter selon vos besoins)
        // $territory = Territory::where('code', 'DAKAR_VILLE')->first();
        // if ($territory) {
        //     Agency::updateOrCreate(
        //         ['code' => 'AG001'],
        //         [
        //             'name' => 'Agence Exemple',
        //             'territory_id' => $territory->id,
        //             'is_active' => true,
        //         ]
        //     );
        // }
    }
}
