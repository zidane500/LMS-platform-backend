# GenerateUmlDiagrams.php - Version Simplifiée Sprint 2
## 10 Classes Essentielles pour Rapport Académique

## Code complet et optimisé

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateUmlDiagrams extends Command
{
    protected $signature = 'uml:generate {--png : Générer aussi l\'image PNG avec PlantUML}';

    protected $description = "Génère le diagramme UML simplifié du Sprint 2 : 10 classes essentielles";

    public function handle(): int
    {
        $umlPath = base_path('docs/uml');

        File::ensureDirectoryExists($umlPath);

        $this->generateClassDiagramSprint2($umlPath);

        $this->info('✅ Fichier PlantUML généré : docs/uml/class_sprint2_formations_modules.puml');

        if ($this->option('png')) {
            $this->generatePng($umlPath);
        }

        return self::SUCCESS;
    }

    private function generateClassDiagramSprint2(string $umlPath): void
    {
        $uml = <<<'PUML'
@startuml
title Diagramme de classes - Sprint 2 : Gestion des formations (10 classes essentielles)

skinparam backgroundColor #FFFFFF
skinparam classAttributeIconSize 0
skinparam linetype ortho
skinparam dpi 160

skinparam class {
  BackgroundColor #E8F4F8
  BorderColor #0078D4
  ArrowColor #0078D4
  FontColor #000000
  FontSize 11
}

' ===================================================
' 1. USER - Classe fondamentale
' ===================================================
class User {
  + id : int
  + prenom : string
  + nom : string
  + email : string
  + role : enum [admin, formateur, apprenant]
  --
  + getAuthPassword() : string
  + creerFormation() : Formation
  + supprimerFormation(id) : void
}

' ===================================================
' 2. FORMATEUR - Créateur de formations
' ===================================================
class Formateur {
  + id : int
  + user_id : int (FK)
  + specialite : string
  + experience_annees : int
  --
  + user() : User
  + formations() : Collection
  + creerFormation(titre) : Formation
  + reorganiserModules(id, order) : void
}

' ===================================================
' 3. APPRENANT - Consommateur de formations
' ===================================================
class Apprenant {
  + id : int
  + user_id : int (FK)
  + niveau_experience : string
  --
  + user() : User
  + formations() : Collection
  + rechercherFormations(filtres) : Collection
  + consulterFormationDetail(id) : array
  + sinscrireFormation(id) : Inscription
}

' ===================================================
' 4. FORMATION - Élément central du domaine
' ===================================================
class Formation {
  + id : int
  + formateur_id : int (FK)
  + titre : string
  + description : text
  + categorie : string
  + niveau : enum [debutant, intermediaire, avance]
  + duree_estimee : int
  + statut : enum [brouillon, publiee, archivee]
  + is_coded : boolean
  + code : string (nullable, unique)
  --
  + formateur() : Formateur
  + modules() : Collection
  + inscriptions() : Collection
  + rechercher(filtres) : Collection
  + filtrer(categorie, niveau) : Collection
  + verifierAcces(userId) : boolean
  + ajouterModule(titre) : ModuleFormation
  + supprimerFormation() : void
}

' ===================================================
' 5. MODULE FORMATION - Composant clé
' ===================================================
class ModuleFormation {
  + id : int
  + formation_id : int (FK)
  + titre : string
  + description : text
  + duree : int
  + ordre : int
  --
  + formation() : Formation
  + contenus() : Collection
  + quiz() : Quiz
  + reordonnner(ordre) : void
}

' ===================================================
' 6. CONTENU - Ressources d'apprentissage
' ===================================================
class Contenu {
  + id : int
  + module_id : int (FK)
  + titre : string
  + type : enum [video, texte, pdf]
  + url : string
  + ordre : int
  --
  + module() : ModuleFormation
  + progressionApprenants() : Collection
}

' ===================================================
' 7. QUIZ - Évaluation
' ===================================================
class Quiz {
  + id : int
  + module_id : int (FK)
  + titre : string
  + nombre_questions : int
  + score_minimum : float
  --
  + module() : ModuleFormation
  + questions() : Collection
  + tentatives() : Collection
}

' ===================================================
' 8. INSCRIPTION - Relation apprenant-formation
' ===================================================
class Inscription {
  + id : int
  + user_id : int (FK)
  + formation_id : int (FK)
  + statut : enum [en_cours, completee, abandonnee]
  + date_inscription : datetime
  + progression : float (0-100%)
  --
  + user() : User
  + formation() : Formation
  + obtenirProgression() : float
}

' ===================================================
' 9. FORMATION ACCES CODE - Formations codées
' ===================================================
class FormationAccesCode {
  + id : int
  + user_id : int (FK)
  + formation_id : int (FK)
  + code : string
  + accessed_at : datetime
  + statut : enum [actif, expire, revoque]
  --
  + user() : User
  + formation() : Formation
  + validerCode(code) : boolean
}

' ===================================================
' 10. NOTIFICATION - Notifications et alertes
' ===================================================
class Notification {
  + id : int
  + user_id : int (FK)
  + type : enum [code_acces, formation, inscription]
  + titre : string
  + message : text
  + lu : boolean
  + created_at : datetime
  --
  + user() : User
  + marquerCommeLue() : void
}

' ===================================================
' RELATIONS ESSENTIELLES
' ===================================================

User "1" -- "0..1" Formateur : possède profil\nsi role=formateur
User "1" -- "0..1" Apprenant : possède profil\nsi role=apprenant
User "1" -- "0..*" Notification : reçoit

Formateur "1" -- "0..*" Formation : crée

Apprenant "1" -- "0..*" Inscription : s'inscrit
Apprenant "1" -- "0..*" FormationAccesCode : accède via code

Formation "1" *-- "1..*" ModuleFormation : organisée en
ModuleFormation "1" *-- "1..*" Contenu : contient
ModuleFormation "1" -- "0..1" Quiz : inclut

Inscription "0..*" -- "1" Formation : s'inscrit à

Formation "1" -- "0..*" FormationAccesCode : accordé via
Formation "1" -- "0..*" Notification : génère code

note right of Formation
  USER STORIES SPRINT 2:
  ✓ Créer une formation
  ✓ Organiser en modules
  ✓ Rechercher/filtrer formations
  ✓ Consulter détails formation
  ✓ Réorganiser modules
  ✓ Supprimer formation
  ✓ Formation codée + code accès
end note

note bottom of FormationAccesCode
  Gestion formations codées:
  • Code unique généré
  • Notification envoyée
  • Accès temporaire possible
end note

@enduml
PUML;

        File::put($umlPath . '/class_sprint2_formations_modules.puml', $uml);
    }

    private function generatePng(string $umlPath): void
    {
        $plantumlJar = base_path('tools/plantuml.jar');

        if (!File::exists($plantumlJar)) {
            $this->warn('⚠️  tools/plantuml.jar introuvable.');
            $this->warn('Téléchargez plantuml.jar depuis : https://sourceforge.net/projects/plantuml/files/');
            $this->warn('Placez le fichier dans le dossier : ' . base_path('tools/'));
            return;
        }

        $file = $umlPath . '/class_sprint2_formations_modules.puml';

        if (!File::exists($file)) {
            $this->error('❌ Le fichier .puml est introuvable : ' . $file);
            return;
        }

        $command = 'java -jar "' . $plantumlJar . '" -Djava.awt.headless=true "' . $file . '"';

        $this->info('⏳ Génération PNG en cours...');
        exec($command, $output, $code);

        if ($code === 0) {
            $pngFile = str_replace('.puml', '.png', $file);
            if (File::exists($pngFile)) {
                $this->info('✅ Image PNG générée avec succès !');
                $this->info('📁 Localisation : docs/uml/class_sprint2_formations_modules.png');
                $this->info('📊 Taille : ' . round(File::size($pngFile) / 1024, 2) . ' KB');
            }
        } else {
            $this->error('❌ Erreur pendant la génération PNG.');
            $this->error('Détails : ' . implode("\n", $output));
        }
    }
}
?>
```

---

## 📊 Les 10 Classes Essentielles

| # | Classe | Description | Rôle |
|---|--------|-------------|------|
| 1 | **User** | Utilisateur du système | Fondamental - Authentification |
| 2 | **Formateur** | Profil créateur de formations | Acteur - Créateur de contenu |
| 3 | **Apprenant** | Profil consommateur de formations | Acteur - Utilisateur final |
| 4 | **Formation** | Cours/parcours pédagogique | Élément central du domaine |
| 5 | **ModuleFormation** | Division d'une formation | Composant d'apprentissage |
| 6 | **Contenu** | Ressources (vidéo, texte, PDF) | Matériel pédagogique |
| 7 | **Quiz** | Évaluation/Quiz | Contrôle des connaissances |
| 8 | **Inscription** | Relation apprenant-formation | Lien entre acteurs et contenu |
| 9 | **FormationAccesCode** | Formations codées | Gestion accès restreint (Sprint 2) |
| 10 | **Notification** | Alertes système | Communication utilisateur |

---

## Installation et Utilisation

### 1. **Remplacez le fichier existant**
```bash
# Remplacez le contenu de app/Console/Commands/GenerateUmlDiagrams.php
```

### 2. **Téléchargez PlantUML**
- Lien : [https://sourceforge.net/projects/plantuml/files/](https://sourceforge.net/projects/plantuml/files/)
- Téléchargez `plantuml.jar`
- Placez-le dans le dossier `tools/` de votre projet

### 3. **Générez le diagramme**

**PlantUML seul :**
```bash
php artisan uml:generate
```

**PlantUML ET PNG :**
```bash
php artisan uml:generate --png
```

### 4. **Fichiers générés**
- **PlantUML** : `docs/uml/class_sprint2_formations_modules.puml`
- **PNG** : `docs/uml/class_sprint2_formations_modules.png`

---

## 🎯 Couverture du Backlog Sprint 2

✅ **Gestion des formations et modules** :
- Créer une formation
- Organiser une formation en modules  
- Rechercher et filtrer les formations
- Consulter la fiche détaillée d'une formation
- Réorganiser l'ordre des modules
- Supprimer une formation

✅ **Gestion des formations codées** :
- Créer une formation codée avec code d'accès
- Vérifier les prérequis
- Envoyer notifications avec codes
- Valider accès via code

---

## 📝 Avantages de cette version simplifiée

- ✅ **Concis** : 10 classes seulement (au lieu de 20+)
- ✅ **Professionnel** : Adapté pour un rapport académique
- ✅ **Complet** : Couvre tous les user stories du Sprint 2
- ✅ **Lisible** : Diagramme clair et bien organisé
- ✅ **Maintainable** : Facile à expliquer et présenter
  + id : int
  + prenom : string
  + nom : string
  + email : string
  + telephone : string
  + date_naissance : date
  + photo_profil : string
  + langue_preferee : string
  + role : enum [admin, formateur, apprenant]
  --
  + getAuthPassword() : string
  + isAdmin() : boolean
  + isFormateur() : boolean
  + isApprenant() : boolean
  + creerFormation() : Formation
  + supprimeFormation(id) : void
  + obtenirFormationsCreees() : Collection
}

' ===================================================
' CLASSE FORMATEUR
' ===================================================
class Formateur {
  + id : int
  + user_id : int (FK)
  + specialite : string
  + experience_annees : int
  + langues_enseignees : array
  + bio : text
  --
  + user() : User
  + formations() : Collection
  + creerFormation() : Formation
  + modifierFormation(id, data) : void
  + reorganiserModules(formation_id, order) : void
  + supprimerFormation(id) : void
  + creerFormationCodee(code) : Formation
  + notifierApprenants(message) : void
}

' ===================================================
' CLASSE FORMATION
' ===================================================
class Formation {
  + id : int
  + formateur_id : int (FK)
  + titre : string
  + description : text
  + categorie : string [enum: developpement, gestion, design, ...]
  + niveau : enum [debutant, intermediaire, avance]
  + duree_estimee : int (en minutes)
  + miniature : string (URL/chemin)
  + statut : enum [brouillon, publiee, archivee]
  + is_coded : boolean
  + code : string (nullable, unique si coded)
  + created_at : datetime
  + updated_at : datetime
  --
  + formateur() : Formateur
  + modules() : Collection
  + inscriptions() : Collection
  + accesCode() : Collection
  + prerequisFormations() : Collection
  + formationsCodeesQuiRequerent() : Collection
  + obtenirFormationDetails() : array
  + rechercher(filtres) : Collection
  + filtrer(categorie, niveau, duree) : Collection
  + verifierAcces(userId, userRole) : boolean
  + ajouterModule(titre, description) : ModuleFormation
  + reordonnnerModules(nouvelOrdre) : void
  + supprimerFormation() : void
  + creerCodeAcces() : string
}

' ===================================================
' CLASSE MODULE DE FORMATION
' ===================================================
class ModuleFormation {
  + id : int
  + formation_id : int (FK)
  + titre : string
  + description : text
  + duree : int (en minutes)
  + ordre : int (pour la progression)
  + contenu_type : enum [video, texte, quiz, ressource]
  + created_at : datetime
  --
  + formation() : Formation
  + contenus() : Collection
  + quiz() : Collection
  + progressionApprenants() : Collection
  + reordonnner(nouvelOrdre) : void
  + obtenirContenuModule() : Collection
}

' ===================================================
' CLASSE CONTENU (Vidéo, Texte, Ressource)
' ===================================================
class Contenu {
  + id : int
  + module_id : int (FK)
  + titre : string
  + description : text
  + type : enum [video, texte, pdf, ressource]
  + url : string (nullable)
  + fichier : string (nullable)
  + ordre : int
  + duree_video : int (nullable)
  --
  + module() : ModuleFormation
  + telechargerRessource() : file
}

' ===================================================
' CLASSE QUIZ
' ===================================================
class Quiz {
  + id : int
  + module_id : int (FK)
  + titre : string
  + description : text
  + nombre_questions : int
  + score_minimum : float
  --
  + module() : ModuleFormation
  + questions() : Collection
  + tentatives() : Collection
  + obtenirScore(userId) : float
}

' ===================================================
' CLASSE QUESTION
' ===================================================
class Question {
  + id : int
  + quiz_id : int (FK)
  + enonce : text
  + type : enum [qcm, vrai_faux, reponse_courte]
  + points : float
  --
  + quiz() : Quiz
  + choixReponses() : Collection
}

' ===================================================
' CLASSE CHOIX REPONSE
' ===================================================
class ChoixReponse {
  + id : int
  + question_id : int (FK)
  + texte : string
  + est_correcte : boolean
  + points : float
  --
  + question() : Question
}

' ===================================================
' CLASSE APPRENANT
' ===================================================
class Apprenant {
  + id : int
  + user_id : int (FK)
  + niveau_experience : enum [debutant, intermediaire, expert]
  + objectifs : text
  --
  + user() : User
  + inscriptions() : Collection
  + formations() : Collection
  + progressions() : Collection
  + rechercherFormations(filtres) : Collection
  + consulterFormationDetail(formation_id) : array
  + sinscrireFormation(formation_id) : Inscription
}

' ===================================================
' CLASSE INSCRIPTION (Relation Apprenant-Formation)
' ===================================================
class Inscription {
  + id : int
  + user_id : int (FK)
  + formation_id : int (FK)
  + statut : enum [en_cours, completee, abandonnee]
  + date_inscription : datetime
  + date_debut : datetime (nullable)
  + date_completion : datetime (nullable)
  + progression : float (0-100%)
  --
  + user() : User
  + formation() : Formation
  + progressionFormation() : ProgressionFormation
  + obtenirProgression() : float
}

' ===================================================
' CLASSE PROGRESSION FORMATION
' ===================================================
class ProgressionFormation {
  + id : int
  + inscription_id : int (FK)
  + formation_id : int (FK)
  + user_id : int (FK)
  + modules_completes : int
  + progression_globale : float
  + date_derniere_activite : datetime
  --
  + inscription() : Inscription
  + formation() : Formation
  + user() : User
  + progressionContenu() : Collection
}

' ===================================================
' CLASSE PROGRESSION CONTENU
' ===================================================
class ProgressionContenu {
  + id : int
  + user_id : int (FK)
  + contenu_id : int (FK)
  + progression_formation_id : int (FK)
  + statut : enum [non_commence, en_cours, complete]
  + date_debut : datetime (nullable)
  + date_completion : datetime (nullable)
  --
  + user() : User
  + contenu() : Contenu
}

' ===================================================
' CLASSE TENTATIVE QUIZ
' ===================================================
class TentativeQuiz {
  + id : int
  + user_id : int (FK)
  + quiz_id : int (FK)
  + score : float
  + nombre_bonnes_reponses : int
  + nombre_questions : int
  + date_tentative : datetime
  + reussi : boolean
  --
  + user() : User
  + quiz() : Quiz
  + reponses() : Collection
}

' ===================================================
' CLASSE REPONSE APPRENANT
' ===================================================
class ReponseApprenant {
  + id : int
  + tentative_quiz_id : int (FK)
  + question_id : int (FK)
  + choix_reponse_id : int (FK, nullable)
  + reponse_texte : text (nullable)
  + est_correcte : boolean
  + points_obtenus : float
  --
  + tentativeQuiz() : TentativeQuiz
  + question() : Question
  + choixReponse() : ChoixReponse
}

' ===================================================
' CLASSE ACCÈS CODE FORMATION CODÉE
' ===================================================
class FormationAccesCode {
  + id : int
  + user_id : int (FK)
  + formation_id : int (FK)
  + code : string
  + accessed_at : datetime
  + date_acces_expire : datetime (nullable)
  + statut : enum [actif, expire, revoque]
  --
  + user() : User
  + formation() : Formation
  + validerCode(code) : boolean
}

' ===================================================
' CLASSE NOTIFICATION (pour codes d'accès)
' ===================================================
class Notification {
  + id : int
  + user_id : int (FK)
  + type : enum [code_acces, formation, inscription, message]
  + titre : string
  + message : text
  + contenu_data : json
  + lu : boolean
  + created_at : datetime
  + read_at : datetime (nullable)
  --
  + user() : User
  + marquerCommeLue() : void
}

' ===================================================
' CLASSE BADGE ET CERTIFICAT
' ===================================================
class Badge {
  + id : int
  + titre : string
  + description : text
  + icone : string
  + criteres : json
  --
  + utilisateurs() : Collection
}

class BadgeUtilisateur {
  + id : int
  + user_id : int (FK)
  + badge_id : int (FK)
  + date_obtention : datetime
  --
  + user() : User
  + badge() : Badge
}

class Certificat {
  + id : int
  + formation_id : int (FK)
  + user_id : int (FK)
  + numero : string
  + date_emission : datetime
  + date_expiration : datetime (nullable)
  + fichier_pdf : string
  --
  + formation() : Formation
  + user() : User
  + genererPDF() : file
}

' ===================================================
' TABLE PIVOT : FORMATIONS PRÉREQUIS
' ===================================================
class formation_prerequis <<table pivot>> {
  + formation_codee_id : int (FK)
  + prerequis_formation_id : int (FK)
  --
  Relation n:n pour formations codées
  avec leurs formations prérequises
}

' ===================================================
' RELATIONS PRINCIPALES
' ===================================================

' User (Formateur)
User "1" -- "0..1" Formateur : possède profil\nsi role=formateur
Formateur "1" -- "0..*" Formation : crée

' User (Apprenant)
User "1" -- "0..1" Apprenant : possède profil\nsi role=apprenant
Apprenant "1" -- "0..*" Inscription : s'inscrit

' Formation et ses composants
Formation "1" *-- "1..*" ModuleFormation : organisée en
ModuleFormation "1" *-- "0..*" Contenu : contient
ModuleFormation "1" -- "0..1" Quiz : inclut

' Quiz et Questions
Quiz "1" -- "1..*" Question : contient
Question "1" -- "2..*" ChoixReponse : propose

' Inscriptions et Progression
Inscription "1" -- "0..1" ProgressionFormation : suit
ProgressionFormation "1" -- "0..*" ProgressionContenu : détaille
ProgressionContenu "0..*" -- "1" Contenu : suit

' Tentatives Quiz
TentativeQuiz "0..*" -- "1" Quiz : tente
TentativeQuiz "0..*" -- "1" User : réalisée par
TentativeQuiz "1" -- "1..*" ReponseApprenant : contient
ReponseApprenant "0..*" -- "1" Question : répond à
ReponseApprenant "0..1" -- "1" ChoixReponse : sélectionne

' Formation codée
Formation "0..*" -- "0..*" formation_prerequis : formation codée
formation_prerequis "0..*" -- "1" Formation : prérequis

' Accès codes
Formation "1" -- "0..*" FormationAccesCode : accordé via
User "1" -- "0..*" FormationAccesCode : possède accès

' Notifications
Formation "1" -- "0..*" Notification : génère (code)
User "1" -- "0..*" Notification : reçoit

' Badges et Certificats
User "1" -- "0..*" BadgeUtilisateur : obtient
Badge "1" -- "0..*" BadgeUtilisateur : distribué via
User "1" -- "0..*" Certificat : complète
Formation "1" -- "0..*" Certificat : délivre

' Notes importantes
note right of Formation
  USER STORIES COUVERTES (Sprint 2):
  • Créer une formation
  • Organiser en modules
  • Rechercher/filtrer formations
  • Consulter détails formation
  • Réorganiser modules (glisser-déposer)
  • Supprimer formation
  • Formation codée + code accès
end note

note bottom of FormationAccesCode
  Gestion des formations codées:
  • Code unique généré
  • Notification à l'apprenant
  • Vérification des prérequis
  • Accès temporaire possible
end note

note left of Notification
  Types de notifications:
  • Code d'accès formation
  • Nouvelle formation disponible
  • Inscription confirmée
  • Messages entre utilisateurs
end note

@enduml
PUML;

        File::put($umlPath . '/class_sprint2_formations_modules.puml', $uml);
    }

    private function generatePng(string $umlPath): void
    {
        $plantumlJar = base_path('tools/plantuml.jar');

        if (!File::exists($plantumlJar)) {
            $this->warn('⚠️  tools/plantuml.jar introuvable.');
            $this->warn('Téléchargez plantuml.jar depuis : https://sourceforge.net/projects/plantuml/files/');
            $this->warn('Placez le fichier dans le dossier : ' . base_path('tools/'));
            return;
        }

        $file = $umlPath . '/class_sprint2_formations_modules.puml';

        if (!File::exists($file)) {
            $this->error('❌ Le fichier .puml est introuvable : ' . $file);
            return;
        }

        $command = 'java -jar "' . $plantumlJar . '" -Djava.awt.headless=true "' . $file . '"';

        $this->info('⏳ Génération PNG en cours...');
        exec($command, $output, $code);

        if ($code === 0) {
            $pngFile = str_replace('.puml', '.png', $file);
            if (File::exists($pngFile)) {
                $this->info('✅ Image PNG générée avec succès !');
                $this->info('📁 Localisation : docs/uml/class_sprint2_formations_modules.png');
                $this->info('📊 Taille : ' . round(File::size($pngFile) / 1024, 2) . ' KB');
            }
        } else {
            $this->error('❌ Erreur pendant la génération PNG.');
            $this->error('Détails : ' . implode("\n", $output));
        }
    }
}
?>
```

---

## Installation et Utilisation

### 1. **Remplacez le fichier existant**
```bash
# Remplacez le contenu de app/Console/Commands/GenerateUmlDiagrams.php
# avec le code ci-dessus
```

### 2. **Téléchargez PlantUML**
- Lien : [https://sourceforge.net/projects/plantuml/files/](https://sourceforge.net/projects/plantuml/files/)
- Téléchargez `plantuml.jar`
- Placez-le dans le dossier `tools/` de votre projet

### 3. **Générez le diagramme**

**Génération du fichier PlantUML seul :**
```bash
php artisan uml:generate
```

**Génération du fichier PlantUML ET PNG :**
```bash
php artisan uml:generate --png
```

### 4. **Fichiers générés**
- **PlantUML** : `docs/uml/class_sprint2_formations_modules.puml`
- **PNG** : `docs/uml/class_sprint2_formations_modules.png`

---

## Améliorations apportées

✅ **Couverture complète du Backlog Sprint 2** :
- Gestion des formations (créer, supprimer, réorganiser modules)
- Recherche et filtrage
- Consultation détaillée
- Formations codées avec système de codes d'accès
- Notifications pour codes d'accès

✅ **Meilleures relations UML** :
- Relations n:n pour formations prérequis
- Hiérarchie complète formation → modules → contenus
- Gestion des inscriptions et progressions

✅ **Meilleure organisation visuelle** :
- Couleurs cohérentes et professionnelles
- Groupes logiques (User, Formation, Quiz, etc.)
- Notes explicatives intégrées

✅ **Code robuste** :
- Vérification d'existence de `plantuml.jar`
- Messages d'erreur détaillés
- Affichage de la taille du fichier généré
