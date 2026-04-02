<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   // database/seeders/BadgeSeeder.php
public function run(): void
{
    $badges = [
        ['code' => 'premier_contenu',      'nom' => 'Premier pas',        'description' => 'Premier contenu consulté',          'icone' => '🎯', 'condition' => 'premier_contenu'],
        ['code' => 'module_complete',       'nom' => 'Module complété',    'description' => '100% d\'un module terminé',           'icone' => '📚', 'condition' => 'module_complete'],
        ['code' => 'quiz_reussi',           'nom' => 'Quiz réussi',        'description' => 'Quiz réussi avec succès',            'icone' => '✅', 'condition' => 'quiz_reussi'],
        ['code' => 'quiz_parfait',          'nom' => 'Score parfait',      'description' => '100% à un quiz du premier coup',     'icone' => '⭐', 'condition' => 'quiz_parfait'],
        ['code' => 'formation_complete',    'nom' => 'Formation terminée', 'description' => 'Formation complétée à 100%',         'icone' => '🏆', 'condition' => 'formation_complete'],
        ['code' => 'assidu',                'nom' => 'Assidu',             'description' => '5 contenus consultés en 1 journée',  'icone' => '🔥', 'condition' => 'assidu'],
    ];

    foreach ($badges as $badge) {
        \App\Models\Badge::updateOrCreate(['code' => $badge['code']], $badge);
    }
}
}
