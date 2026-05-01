<?php

namespace Database\Seeders;

use App\Models\Formation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormationSeeder extends Seeder
{
    public function run(): void
    {
        $formations = [
            // Formation 1 - Développement Web (Full-stack)
            [
                'formateur_id' => 1, // Super Admin
                'titre' => 'Développement Full-stack avec Laravel et React',
                'description' => 'Apprenez à développer des applications web modernes en utilisant Laravel pour le backend et React pour le frontend. Maîtrisez les API REST, l\'authentification et les bases de données.',
                'categorie' => 'Développement Web',
                'niveau' => 'intermediaire',
                'duree_estimee' => 40,
                'prerequis' => json_encode(['PHP basique', 'JavaScript ES6', 'SQL']),
                'miniature' => null,
                'statut' => 'publie',
                'is_coded' => false,
                'code' => null,
            ],
            // Formation 2 - Marketing Digital
            [
                'formateur_id' => 1,
                'titre' => 'Marketing Digital Complet 2026',
                'description' => 'Formation complète sur le marketing digital : SEO, SEA, réseaux sociaux, email marketing et stratégie de contenu.',
                'categorie' => 'Marketing',
                'niveau' => 'debutant',
                'duree_estimee' => 25,
                'prerequis' => json_encode(['Aucun prérequis']),
                'miniature' => null,
                'statut' => 'publie',
                'is_coded' => false,
                'code' => null,
            ],
            // Formation 3 - Data Science (codée)
            [
                'formateur_id' => 1,
                'titre' => 'Data Science et Intelligence Artificielle',
                'description' => 'Devenez expert en Data Science. Apprenez Python, Pandas, Matplotlib, Scikit-learn et les bases du Machine Learning.',
                'categorie' => 'Data Science',
                'niveau' => 'avance',
                'duree_estimee' => 50,
                'prerequis' => json_encode(['Python basique', 'Mathématiques', 'Statistiques']),
                'miniature' => null,
                'statut' => 'publie',
                'is_coded' => true,
                'code' => 'DATASC202', // Code à 8 caractères
            ],
            // Formation 4 - Cybersécurité
            [
                'formateur_id' => 1,
                'titre' => 'Cybersécurité pour Développeurs',
                'description' => 'Protégez vos applications contre les vulnérabilités courantes : XSS, CSRF, SQL Injection, et bonnes pratiques de sécurité.',
                'categorie' => 'Sécurité',
                'niveau' => 'intermediaire',
                'duree_estimee' => 30,
                'prerequis' => json_encode(['Développement web', 'HTTP/HTTPS']),
                'miniature' => null,
                'statut' => 'publie',
                'is_coded' => false,
                'code' => null,
            ],
            // Formation 5 - Mobile Flutter
            [
                'formateur_id' => 1,
                'titre' => 'Développement Mobile avec Flutter',
                'description' => 'Créez des applications iOS et Android avec Flutter et Dart. Apprenez les widgets, la navigation, le state management et Firebase.',
                'categorie' => 'Mobile',
                'niveau' => 'debutant',
                'duree_estimee' => 35,
                'prerequis' => json_encode(['Notions de programmation orientée objet']),
                'miniature' => null,
                'statut' => 'publie',
                'is_coded' => false,
                'code' => null,
            ],
            // Formation 6 - DevOps
            [
                'formateur_id' => 1,
                'titre' => 'DevOps : CI/CD avec Docker et Kubernetes',
                'description' => 'Maîtrisez les outils DevOps modernes : Docker, Kubernetes, Jenkins, GitHub Actions et déploiement cloud.',
                'categorie' => 'DevOps',
                'niveau' => 'avance',
                'duree_estimee' => 45,
                'prerequis' => json_encode(['Linux', 'Administration système']),
                'miniature' => null,
                'statut' => 'publie',
                'is_coded' => false,
                'code' => null,
            ],
            // Formation 7 - UI/UX Design
            [
                'formateur_id' => 1,
                'titre' => 'UI/UX Design : De la recherche au prototype',
                'description' => 'Apprenez les fondamentaux du design d\'interface utilisateur. Utilisez Figma, créez des wireframes, prototypes et conduisez des tests utilisateurs.',
                'categorie' => 'Design',
                'niveau' => 'debutant',
                'duree_estimee' => 20,
                'prerequis' => json_encode(['Aucun prérequis']),
                'miniature' => null,
                'statut' => 'publie',
                'is_coded' => false,
                'code' => null,
            ],
            // Formation 8 - Blockchain (codée)
            [
                'formateur_id' => 1,
                'titre' => 'Blockchain et Smart Contracts',
                'description' => 'Découvrez la technologie blockchain et développez des smart contracts avec Solidity sur Ethereum.',
                'categorie' => 'Blockchain',
                'niveau' => 'avance',
                'duree_estimee' => 40,
                'prerequis' => json_encode(['JavaScript', 'Notions de cryptographie']),
                'miniature' => null,
                'statut' => 'publie',
                'is_coded' => true,
                'code' => 'BLOCKH12', // Code à 8 caractères
            ],
            // Formation 9 - Angular
            [
                'formateur_id' => 1,
                'titre' => 'Angular : De zéro à expert',
                'description' => 'Formation complète sur Angular : composants, services, routing, formulaires réactifs, HTTP client et déploiement.',
                'categorie' => 'Développement Web',
                'niveau' => 'intermediaire',
                'duree_estimee' => 35,
                'prerequis' => json_encode(['HTML/CSS', 'JavaScript/TypeScript basique']),
                'miniature' => null,
                'statut' => 'publie',
                'is_coded' => false,
                'code' => null,
            ],
            // Formation 10 - Formation brouillon (non publiée)
            [
                'formateur_id' => 1,
                'titre' => 'Introduction à l\'IA Générative',
                'description' => 'Découvrez les modèles d\'IA générative : ChatGPT, DALL-E, Midjourney et comment les intégrer dans vos applications.',
                'categorie' => 'Intelligence Artificielle',
                'niveau' => 'debutant',
                'duree_estimee' => 15,
                'prerequis' => json_encode(['Aucun prérequis']),
                'miniature' => null,
                'statut' => 'brouillon',
                'is_coded' => false,
                'code' => null,
            ],
        ];

        foreach ($formations as $data) {
            // Vérifier si la formation existe déjà
            if (!Formation::where('titre', $data['titre'])->exists()) {
                Formation::create($data);
                $this->command->info("✅ Formation créée : {$data['titre']}");
            } else {
                $this->command->warn("⚠️ Formation existe déjà : {$data['titre']}");
            }
        }
    }
}