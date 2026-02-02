<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Territory;

class TerritorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $territories = [
            [
                'code' => 'DAKAR_VILLE',
                'name' => 'Dakar Ville',
                'description' => 'Territoire de Dakar Ville',
                'is_active' => true,
            ],
            [
                'code' => 'DAKAR_BANLIEUE',
                'name' => 'Dakar Banlieue',
                'description' => 'Territoire de Dakar Banlieue',
                'is_active' => true,
            ],
            [
                'code' => 'PROVINCE_CENTRE_SUD',
                'name' => 'Province Centre Sud',
                'description' => 'Territoire Province Centre Sud',
                'is_active' => true,
            ],
            [
                'code' => 'PROVINCE_NORD',
                'name' => 'Province Nord',
                'description' => 'Territoire Province Nord',
                'is_active' => true,
            ],
        ];

        foreach ($territories as $territory) {
            Territory::updateOrCreate(
                ['code' => $territory['code']],
                $territory
            );
        }
    }
}
