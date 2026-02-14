<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // S'assurer que les profils existent
        $this->call(ProfileSeeder::class);

        $users = [
            [
                'name' => 'Directeur Général',
                'email' => 'md@cofidash.com',
                'password' => 'password123',
                'profile_code' => 'MD'
            ],
            [
                'name' => 'Directeur Général Adjoint',
                'email' => 'dga@cofidash.com',
                'password' => 'password123',
                'profile_code' => 'DGA'
            ],
            [
                'name' => 'Responsable Zone Dakar',
                'email' => 'responsable.zone@cofidash.com',
                'password' => 'password123',
                'profile_code' => 'RESPONSABLE_ZONE'
            ],
            [
                'name' => 'Chef d\'Agence Nguélaw',
                'email' => 'chef.agence@cofidash.com',
                'password' => 'password123',
                'profile_code' => 'CHEF_AGENCE'
            ],
            [
                'name' => 'CAF Test',
                'email' => 'caf@cofidash.com',
                'password' => 'password123',
                'profile_code' => 'CAF'
            ],
            [
                'name' => 'Administrateur',
                'email' => 'admin@cofidash.com',
                'password' => 'password123',
                'profile_code' => 'ADMIN'
            ]
        ];

        foreach ($users as $userData) {
            $profile = Profile::where('code', $userData['profile_code'])->first();
            
            if ($profile) {
                User::updateOrCreate(
                    ['email' => $userData['email']],
                    [
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'password' => Hash::make($userData['password']),
                        'profile_id' => $profile->id
                    ]
                );
                
                $this->command->info("✅ Utilisateur créé : {$userData['name']} ({$userData['email']}) - Profil: {$userData['profile_code']}");
            } else {
                $this->command->warn("⚠️ Profil non trouvé : {$userData['profile_code']}");
            }
        }

        $this->command->info("\n📋 Résumé des utilisateurs de test créés :");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("MD (Directeur Général)          : md@cofidash.com / password123");
        $this->command->info("DGA (Directeur Général Adjoint)  : dga@cofidash.com / password123");
        $this->command->info("Responsable Zone                : responsable.zone@cofidash.com / password123");
        $this->command->info("Chef d'Agence                    : chef.agence@cofidash.com / password123");
        $this->command->info("CAF                              : caf@cofidash.com / password123");
        $this->command->info("Admin                            : admin@cofidash.com / password123");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }
}
