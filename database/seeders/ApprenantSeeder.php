<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Apprenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApprenantSeeder extends Seeder
{
    public function run(): void
    {
        $apprenants = [
            [
                'prenom' => 'Mohamed',
                'nom' => 'Ben Ali',
                'email' => 'mohamed.benali@example.com',
                'mot_de_passe' => 'Apprenant123',
                'telephone' => '20123456',
                'date_naissance' => '1995-05-15',
                'langue_preferee' => 'fr',
                'domaines_cibles' => ['Développement Web', 'React', 'Laravel'],
                'technologies' => ['JavaScript', 'PHP', 'React']
            ],
            [
                'prenom' => 'Syrine',
                'nom' => 'Trabelsi',
                'email' => 'syrine.trabelsi@example.com',
                'mot_de_passe' => 'Apprenant123',
                'telephone' => '22345678',
                'date_naissance' => '1998-08-22',
                'langue_preferee' => 'fr',
                'domaines_cibles' => ['Data Science', 'Python', 'IA'],
                'technologies' => ['Python', 'Pandas', 'TensorFlow']
            ],
            [
                'prenom' => 'Ahmed',
                'nom' => 'Feki',
                'email' => 'ahmed.feki@example.com',
                'mot_de_passe' => 'Apprenant123',
                'telephone' => '23456789',
                'date_naissance' => '2000-01-10',
                'langue_preferee' => 'en',
                'domaines_cibles' => ['Mobile', 'Flutter', 'Kotlin'],
                'technologies' => ['Flutter', 'Dart', 'Firebase']
            ],
            [
                'prenom' => 'Nour',
                'nom' => 'Chennoufi',
                'email' => 'nour.chennoufi@example.com',
                'mot_de_passe' => 'Apprenant123',
                'telephone' => '24567890',
                'date_naissance' => '1997-11-30',
                'langue_preferee' => 'fr',
                'domaines_cibles' => ['UI/UX', 'Design', 'Frontend'],
                'technologies' => ['Figma', 'Adobe XD', 'CSS', 'Tailwind']
            ],
            [
                'prenom' => 'Oussema',
                'nom' => 'Dridi',
                'email' => 'oussema.dridi@example.com',
                'mot_de_passe' => 'Apprenant123',
                'telephone' => '25678901',
                'date_naissance' => '1999-03-25',
                'langue_preferee' => 'fr',
                'domaines_cibles' => ['DevOps', 'Cloud', 'CI/CD'],
                'technologies' => ['Docker', 'Kubernetes', 'Jenkins', 'AWS']
            ],
            [
                'prenom' => 'Yassine',
                'nom' => 'Mansouri',
                'email' => 'yassine.mansouri@example.com',
                'mot_de_passe' => 'Apprenant123',
                'telephone' => '26789012',
                'date_naissance' => '1996-07-19',
                'langue_preferee' => 'fr',
                'domaines_cibles' => ['Cybersécurité', 'Réseaux'],
                'technologies' => ['Linux', 'Wireshark', 'Metasploit']
            ],
            [
                'prenom' => 'Rania',
                'nom' => 'Bouazizi',
                'email' => 'rania.bouazizi@example.com',
                'mot_de_passe' => 'Apprenant123',
                'telephone' => '27890123',
                'date_naissance' => '2001-12-03',
                'langue_preferee' => 'fr',
                'domaines_cibles' => ['Marketing Digital', 'SEO'],
                'technologies' => ['WordPress', 'Google Analytics', 'SEMrush']
            ],
            [
                'prenom' => 'Khaled',
                'nom' => 'Sassi',
                'email' => 'khaled.sassi@example.com',
                'mot_de_passe' => 'Apprenant123',
                'telephone' => '28901234',
                'date_naissance' => '1994-09-14',
                'langue_preferee' => 'en',
                'domaines_cibles' => ['Blockchain', 'Web3', 'Cryptomonnaies'],
                'technologies' => ['Solidity', 'Ethereum', 'Smart Contracts']
            ],
            [
                'prenom' => 'Amira',
                'nom' => 'Hamdi',
                'email' => 'amira.hamdi@example.com',
                'mot_de_passe' => 'Apprenant123',
                'telephone' => '29012345',
                'date_naissance' => '2002-05-21',
                'langue_preferee' => 'fr',
                'domaines_cibles' => ['Intelligence Artificielle', 'Machine Learning'],
                'technologies' => ['Python', 'Scikit-learn', 'PyTorch']
            ],
            [
                'prenom' => 'Aziz',
                'nom' => 'Mhadhbi',
                'email' => 'aziz.mhadhbi@example.com',
                'mot_de_passe' => 'Apprenant123',
                'telephone' => '20123457',
                'date_naissance' => '1993-11-11',
                'langue_preferee' => 'fr',
                'domaines_cibles' => ['Gestion de projet', 'Agile', 'Scrum'],
                'technologies' => ['Jira', 'Trello', 'ClickUp']
            ]
        ];

        foreach ($apprenants as $data) {
            // Vérifier si l'utilisateur existe déjà
            if (!User::where('email', $data['email'])->exists()) {
                $user = User::create([
                    'prenom' => $data['prenom'],
                    'nom' => $data['nom'],
                    'email' => $data['email'],
                    'mot_de_passe' => Hash::make($data['mot_de_passe']),
                    'telephone' => $data['telephone'],
                    'date_naissance' => $data['date_naissance'],
                    'langue_preferee' => $data['langue_preferee'],
                    'role' => 'apprenant',
                ]);

                // Créer l'entrée dans la table apprenants
                Apprenant::create([
                    'user_id' => $user->id,
                    'domaines_cibles' => $data['domaines_cibles'],
                    'technologies' => $data['technologies'],
                ]);

                $this->command->info("✅ Apprenant créé : {$data['email']} / {$data['mot_de_passe']}");
            } else {
                $this->command->warn("⚠️ Apprenant existe déjà : {$data['email']}");
            }
        }
    }
}