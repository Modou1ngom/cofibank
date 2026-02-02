<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un utilisateur admin par défaut
        $adminProfile = Profile::where('code', 'ADMIN')->first();
        
        if ($adminProfile) {
            User::updateOrCreate(
                ['email' => 'admin@cofi.com'],
                [
                    'name' => 'Administrateur',
                    'email' => 'admin@cofi.com',
                    'password' => Hash::make('password123'),
                    'profile_id' => $adminProfile->id
                ]
            );
        }
    }
}

