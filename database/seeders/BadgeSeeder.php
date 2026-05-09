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
            // ─── PROGRESSION ───────────────────────────────
            [
                'code' => 'premier_contenu',
                'nom' => 'Premier Pas',
                'icone' => '🟢',
                'description' => 'Premier contenu consulté',
                'condition' => 'Consulter votre premier contenu',
            ],
            [
                'code' => 'module_complete',
                'nom' => 'Module Complété',
                'icone' => '📘',
                'description' => "100% d'un module terminé avec succès",
                'condition' => 'Terminer un module à 100%',
            ],
            [
                'code' => 'formation_complete',
                'nom' => 'Formation Terminée',
                'icone' => '📚',
                'description' => 'Une formation complète terminée',
                'condition' => 'Compléter entièrement une formation',
            ],
            [
                'code' => 'apprenant_actif',
                'nom' => 'Apprenant Actif',
                'icone' => '🔥',
                'description' => '5 modules enchaînés en progression',
                'condition' => 'Compléter 5 modules consécutifs',
            ],
            [
                'code' => 'progression_rapide',
                'nom' => 'Progression Rapide',
                'icone' => '🚀',
                'description' => 'Formation terminée en un temps record',
                'condition' => 'Terminer une formation dans le délai minimum',
            ],

            // ─── PERFORMANCE ───────────────────────────────
            [
                'code' => 'quiz_reussi',
                'nom' => 'Quiz Réussi',
                'icone' => '🎯',
                'description' => 'Seuil de réussite atteint au quiz',
                'condition' => "Dépasser le seuil de réussite d'un quiz",
            ],
            [
                'code' => 'quiz_parfait',
                'nom' => 'Score Parfait',
                'icone' => '💯',
                'description' => '100% au quiz — perfection absolue',
                'condition' => 'Obtenir un score de 100% à un quiz',
            ],
            [
                'code' => 'premier_coup',
                'nom' => 'Réussite du Premier Coup',
                'icone' => '⚡',
                'description' => 'Quiz validé sans tentative multiple',
                'condition' => 'Réussir un quiz à la première tentative',
            ],
            [
                'code' => 'en_amelioration',
                'nom' => 'En Amélioration',
                'icone' => '📈',
                'description' => 'Score amélioré entre deux tentatives',
                'condition' => 'Améliorer son score entre deux tentatives',
            ],
            [
                'code' => 'top_score',
                'nom' => 'Top Score',
                'icone' => '🏆',
                'description' => 'Meilleur score de la promotion',
                'condition' => 'Avoir le meilleur score dans une formation',
            ],

            // ─── ENGAGEMENT ────────────────────────────────
            [
                'code' => 'assidu',
                'nom' => 'Assidu',
                'icone' => '📅',
                'description' => '5 contenus consultés en 1 journée',
                'condition' => 'Se connecter et consulter 5 contenus par jour',
            ],
            [
                'code' => 'temps_investi',
                'nom' => 'Temps Investi',
                'icone' => '⏳',
                'description' => 'X heures passées sur la plateforme',
                'condition' => 'Passer 20 heures sur la plateforme',
            ],
            [
                'code' => 'perseverant',
                'nom' => 'Persévérant',
                'icone' => '🔁',
                'description' => 'Plusieurs tentatives sur un même quiz',
                'condition' => 'Tenter un quiz plus de 3 fois',
            ],
            [
                'code' => 'objectif_atteint',
                'nom' => 'Objectif Atteint',
                'icone' => '🎯',
                'description' => 'Formation commencée et terminée',
                'condition' => 'Terminer une formation commencée',
            ],

            // ─── ACCOMPLISSEMENT ───────────────────────────
            [
                'code' => 'certifie',
                'nom' => 'Certifié',
                'icone' => '🎓',
                'description' => 'Certificat officiel obtenu',
                'condition' => 'Obtenir un certificat de formation',
            ],
            [
                'code' => 'mention_excellent',
                'nom' => 'Excellence',
                'icone' => '🥇',
                'description' => 'Mention Excellent — moyenne ≥ 95%',
                'condition' => 'Obtenir la mention Excellent',
            ],
            [
                'code' => 'mention_tres_bien',
                'nom' => 'Très Bien',
                'icone' => '🥈',
                'description' => 'Mention Très Bien — moyenne ≥ 85%',
                'condition' => 'Obtenir la mention Très Bien',
            ],
            [
                'code' => 'mention_bien',
                'nom' => 'Bien',
                'icone' => '🥉',
                'description' => 'Mention Bien — moyenne ≥ 70%',
                'condition' => 'Obtenir la mention Bien',
            ],

            // ─── CONTENUS ──────────────────────────────────
            [
                'code' => 'amateur_videos',
                'nom' => 'Amateur de Vidéos',
                'icone' => '🎥',
                'description' => 'Plusieurs vidéos de formation complétées',
                'condition' => 'Regarder 10 vidéos de formation',
            ],
            [
                'code' => 'lecteur_assidu',
                'nom' => 'Lecteur Assidu',
                'icone' => '📄',
                'description' => 'Plusieurs PDF lus et complétés',
                'condition' => 'Lire 5 documents PDF',
            ],
            [
                'code' => 'explorateur_media',
                'nom' => 'Explorateur Multimédia',
                'icone' => '🎧',
                'description' => 'Tous types de contenus consultés',
                'condition' => 'Consulter vidéo, PDF, audio et SCORM',
            ],

            // ─── FORMATEURS ────────────────────────────────
            [
                'code' => 'premier_cours',
                'nom' => 'Premier Cours Publié',
                'icone' => '🧑‍🏫',
                'description' => 'Votre première formation publiée',
                'condition' => 'Publier une première formation',
            ],
            [
                'code' => 'formateur_apprecie',
                'nom' => 'Formateur Apprécié',
                'icone' => '⭐',
                'description' => 'Évaluations excellentes de vos apprenants',
                'condition' => 'Obtenir une note moyenne ≥ 4.5/5',
            ],
            [
                'code' => 'formateur_perf',
                'nom' => 'Formateur Performant',
                'icone' => '📊',
                'description' => 'Taux de réussite élevé dans vos formations',
                'condition' => 'Avoir un taux de réussite ≥ 80%',
            ],
            [
                'code' => 'formateur_actif',
                'nom' => 'Formateur Actif',
                'icone' => '🔥',
                'description' => 'Plusieurs formations créées et actives',
                'condition' => 'Créer et publier 5 formations',
            ],

            // ─── SPÉCIAUX ──────────────────────────────────
            [
                'code' => 'explorateur',
                'nom' => 'Explorateur',
                'icone' => '🕵️',
                'description' => 'Découvrez de nouvelles formations',
                'condition' => 'Consulter 5 formations différentes',
            ],
            [
                'code' => 'curieux',
                'nom' => 'Curieux',
                'icone' => '🧪',
                'description' => 'Tester différents domaines de formation',
                'condition' => "S'inscrire dans 3 domaines distincts",
            ],
            [
                'code' => 'early_adopter',
                'nom' => 'Early Adopter',
                'icone' => '💡',
                'description' => 'Parmi les premiers utilisateurs',
                'condition' => 'Être parmi les 100 premiers inscrits',
            ],
            [
                'code' => 'objectif_perso',
                'nom' => 'Objectif Personnel',
                'icone' => '🎯',
                'description' => 'Objectif personnel atteint',
                'condition' => 'Atteindre un objectif personnel défini',
            ],

            // ─── NIVEAUX ───────────────────────────────────
            [
                'code' => 'niveau_bronze',
                'nom' => 'Niveau Bronze',
                'icone' => '🥉',
                'description' => 'Premiers modules complétés',
                'condition' => 'Compléter 5 modules',
            ],
            [
                'code' => 'niveau_argent',
                'nom' => 'Niveau Argent',
                'icone' => '🥈',
                'description' => 'Vous progressez régulièrement',
                'condition' => 'Compléter 15 modules',
            ],
            [
                'code' => 'niveau_or',
                'nom' => 'Niveau Or',
                'icone' => '🥇',
                'description' => 'Excellent parcours',
                'condition' => 'Compléter 30 modules',
            ],
            [
                'code' => 'niveau_expert',
                'nom' => 'Niveau Expert',
                'icone' => '💎',
                'description' => 'Le summum de la maîtrise',
                'condition' => 'Compléter 50 modules',
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['code' => $badge['code']],
                $badge
            );
        }

        $this->command->info(count($badges) . ' badges créés/mis à jour.');
    }
}