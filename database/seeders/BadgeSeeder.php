<?php
// database/seeders/BadgeSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            ['code' => 'premier_contenu',    'nom' => 'Premier Pas',        'icone' => '🎯', 'description' => 'Premier contenu consulté',                  'condition' => 'Consulter votre premier contenu'],
            ['code' => 'module_complete',     'nom' => 'Module Complété',    'icone' => '📚', 'description' => 'Un module entièrement terminé',              'condition' => 'Terminer tous les contenus d\'un module'],
            ['code' => 'formation_complete',  'nom' => 'Formation Complète', 'icone' => '🏆', 'description' => 'Formation entièrement terminée',             'condition' => 'Terminer tous les contenus de la formation'],
            ['code' => 'quiz_reussi',         'nom' => 'Quiz Réussi',        'icone' => '✅', 'description' => 'Un quiz réussi avec succès',                 'condition' => 'Obtenir un score >= au seuil de réussite'],
            ['code' => 'quiz_parfait',        'nom' => 'Score Parfait',      'icone' => '💯', 'description' => 'Score parfait au premier essai',             'condition' => 'Obtenir 100% au premier essai'],
            ['code' => 'premier_coup',        'nom' => 'Premier Coup',       'icone' => '🎯', 'description' => 'Quiz réussi du premier coup',                'condition' => 'Réussir un quiz dès la première tentative'],
            ['code' => 'certifie',            'nom' => 'Certifié',           'icone' => '🎓', 'description' => 'Certificat obtenu pour une formation',       'condition' => 'Obtenir un certificat de formation'],
            ['code' => 'assidu',              'nom' => 'Assidu',             'icone' => '⚡', 'description' => '5 contenus complétés dans la même journée',  'condition' => 'Compléter 5 contenus en une journée'],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['code' => $badge['code']], $badge);
        }

        $this->command->info(count($badges) . ' badges créés/mis à jour.');
    }
}