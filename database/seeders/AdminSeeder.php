<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Vérifie si l'admin existe déjà pour éviter les doublons
        if (!User::where('email', 'admin@lms.com')->exists()) {
            User::create([
                'prenom'          => 'Super',
                'nom'             => 'Admin',
                'email'           => 'admin@lms.com',
                'mot_de_passe'    => Hash::make('Admin000'),
                'role'            => 'admin',
                'langue_preferee' => 'fr',
            ]);

            $this->command->info('✅ Admin créé : admin@lms.com / Admin1234');
        } else {
            $this->command->info('⚠️ Admin existe déjà.');
        }
    }
}