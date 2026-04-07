<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class BadgeSeeder extends Seeder
{
    
   // database/seeders/BadgeSeeder.php
public function run(): void
{
    $badges = [
        ['code' => 'premier_contenu',   'nom' => 'Premier Pas',        'description' => 'Premier contenu consulté',         'icone' => '🎯', 'condition' => 'premier_contenu'],
        ['code' => 'module_complete',   'nom' => 'Module Complété',    'description' => '100% d\'un module terminé',          'icone' => '📘', 'condition' => 'module_complete'],
        ['code' => 'formation_complete','nom' => 'Formation Terminée', 'description' => 'Formation complétée à 100%',        'icone' => '📚', 'condition' => 'formation_complete'],
        ['code' => 'assidu',            'nom' => 'Assidu',             'description' => '5 contenus consultés en 1 journée', 'icone' => '🔥', 'condition' => 'assidu'],
        ['code' => 'quiz_reussi',       'nom' => 'Quiz Réussi',        'description' => 'Quiz réussi avec succès',           'icone' => '🎯', 'condition' => 'quiz_reussi'],
        ['code' => 'quiz_parfait',      'nom' => 'Score Parfait',      'description' => '100% au quiz premier coup',         'icone' => '💯', 'condition' => 'quiz_parfait'],
        ['code' => 'premier_coup',      'nom' => 'Réussite Premier Coup','description' => 'Quiz réussi à la 1ère tentative', 'icone' => '⚡', 'condition' => 'premier_coup'],
    ];

    foreach ($badges as $badge) {
        \App\Models\Badge::updateOrCreate(['code' => $badge['code']], $badge);
    }
}
}
