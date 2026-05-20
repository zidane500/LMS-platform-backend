<mxfile host="app.diagrams.net" modified="2026-05-11T00:00:00.000Z" agent="Laravel" version="24.7.17" type="device">
  <diagram name="Sprint 2 - Diagramme de classes" id="class-sprint2">
    <mxGraphModel dx="1900" dy="1400" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1900" pageHeight="1400" math="0" shadow="0">
      <root>
        <mxCell id="0"/>
<mxCell id="1" parent="0"/>
<mxCell id="container" value="Diagramme de classes - Sprint 2 : Gestion des formations" style="rounded=0;whiteSpace=wrap;html=1;fillColor=none;strokeColor=#000000;fontColor=#111827;fontStyle=1;verticalAlign=top;align=center;spacingTop=10;fontSize=18;" vertex="1" parent="1">
  <mxGeometry x="40" y="30" width="1760" height="1080" as="geometry"/>
</mxCell>
<mxCell id="user" value="&lt;b&gt;User&lt;/b&gt;&lt;hr&gt;+ id : int&lt;br&gt;+ prenom : string&lt;br&gt;+ nom : string&lt;br&gt;+ email : string&lt;br&gt;+ role : enum [admin, formateur, apprenant]&lt;hr&gt;+ getAuthPassword() : string&lt;br&gt;+ creerFormation() : Formation&lt;br&gt;+ supprimerFormation(id) : void" style="rounded=0;whiteSpace=wrap;html=1;fillColor=#E8F4F8;strokeColor=#0078D4;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=11;" vertex="1" parent="1">
  <mxGeometry x="650" y="90" width="390" height="230" as="geometry"/>
</mxCell>
<mxCell id="formateur" value="&lt;b&gt;Formateur&lt;/b&gt;&lt;hr&gt;+ id : int&lt;br&gt;+ user_id : int (FK)&lt;br&gt;+ specialite : string&lt;br&gt;+ experience_annees : int&lt;hr&gt;+ user() : User&lt;br&gt;+ formations() : Collection&lt;br&gt;+ creerFormation(titre) : Formation&lt;br&gt;+ reorganiserModules(id, order) : void" style="rounded=0;whiteSpace=wrap;html=1;fillColor=#E8F4F8;strokeColor=#0078D4;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=11;" vertex="1" parent="1">
  <mxGeometry x="160" y="390" width="410" height="250" as="geometry"/>
</mxCell>
<mxCell id="apprenant" value="&lt;b&gt;Apprenant&lt;/b&gt;&lt;hr&gt;+ id : int&lt;br&gt;+ user_id : int (FK)&lt;br&gt;+ niveau_experience : string&lt;hr&gt;+ user() : User&lt;br&gt;+ formations() : Collection&lt;br&gt;+ rechercherFormations(filtres) : Collection&lt;br&gt;+ consulterFormationDetail(id) : array&lt;br&gt;+ sinscrireFormation(id) : Inscription" style="rounded=0;whiteSpace=wrap;html=1;fillColor=#E8F4F8;strokeColor=#0078D4;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=11;" vertex="1" parent="1">
  <mxGeometry x="160" y="700" width="430" height="270" as="geometry"/>
</mxCell>
<mxCell id="formation" value="&lt;b&gt;Formation&lt;/b&gt;&lt;hr&gt;+ id : int&lt;br&gt;+ formateur_id : int (FK)&lt;br&gt;+ titre : string&lt;br&gt;+ description : text&lt;br&gt;+ categorie : string&lt;br&gt;+ niveau : enum [debutant, intermediaire, avance]&lt;br&gt;+ duree_estimee : int&lt;br&gt;+ statut : enum [brouillon, publiee, archivee]&lt;br&gt;+ is_coded : boolean&lt;br&gt;+ code : string (nullable, unique)&lt;hr&gt;+ formateur() : Formateur&lt;br&gt;+ modules() : Collection&lt;br&gt;+ inscriptions() : Collection&lt;br&gt;+ rechercher(filtres) : Collection&lt;br&gt;+ filtrer(categorie, niveau) : Collection&lt;br&gt;+ verifierAcces(userId) : boolean&lt;br&gt;+ ajouterModule(titre) : ModuleFormation&lt;br&gt;+ supprimerFormation() : void" style="rounded=0;whiteSpace=wrap;html=1;fillColor=#E8F4F8;strokeColor=#0078D4;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=11;" vertex="1" parent="1">
  <mxGeometry x="680" y="390" width="470" height="430" as="geometry"/>
</mxCell>
<mxCell id="module_formation" value="&lt;b&gt;ModuleFormation&lt;/b&gt;&lt;hr&gt;+ id : int&lt;br&gt;+ formation_id : int (FK)&lt;br&gt;+ titre : string&lt;br&gt;+ description : text&lt;br&gt;+ duree : int&lt;br&gt;+ ordre : int&lt;hr&gt;+ formation() : Formation&lt;br&gt;+ contenus() : Collection&lt;br&gt;+ quiz() : Quiz&lt;br&gt;+ reordonner(ordre) : void" style="rounded=0;whiteSpace=wrap;html=1;fillColor=#E8F4F8;strokeColor=#0078D4;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=11;" vertex="1" parent="1">
  <mxGeometry x="1260" y="120" width="410" height="280" as="geometry"/>
</mxCell>
<mxCell id="contenu" value="&lt;b&gt;Contenu&lt;/b&gt;&lt;hr&gt;+ id : int&lt;br&gt;+ module_id : int (FK)&lt;br&gt;+ titre : string&lt;br&gt;+ type : enum [video, texte, pdf]&lt;br&gt;+ url : string&lt;br&gt;+ ordre : int&lt;hr&gt;+ module() : ModuleFormation&lt;br&gt;+ progressionApprenants() : Collection" style="rounded=0;whiteSpace=wrap;html=1;fillColor=#E8F4F8;strokeColor=#0078D4;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=11;" vertex="1" parent="1">
  <mxGeometry x="1260" y="470" width="410" height="250" as="geometry"/>
</mxCell>
<mxCell id="quiz" value="&lt;b&gt;Quiz&lt;/b&gt;&lt;hr&gt;+ id : int&lt;br&gt;+ module_id : int (FK)&lt;br&gt;+ titre : string&lt;br&gt;+ nombre_questions : int&lt;br&gt;+ score_minimum : float&lt;hr&gt;+ module() : ModuleFormation&lt;br&gt;+ questions() : Collection&lt;br&gt;+ tentatives() : Collection" style="rounded=0;whiteSpace=wrap;html=1;fillColor=#E8F4F8;strokeColor=#0078D4;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=11;" vertex="1" parent="1">
  <mxGeometry x="1260" y="770" width="410" height="230" as="geometry"/>
</mxCell>
<mxCell id="inscription" value="&lt;b&gt;Inscription&lt;/b&gt;&lt;hr&gt;+ id : int&lt;br&gt;+ user_id : int (FK)&lt;br&gt;+ formation_id : int (FK)&lt;br&gt;+ statut : enum [en_cours, completee, abandonnee]&lt;br&gt;+ date_inscription : datetime&lt;br&gt;+ progression : float (0-100%)&lt;hr&gt;+ user() : User&lt;br&gt;+ formation() : Formation&lt;br&gt;+ obtenirProgression() : float" style="rounded=0;whiteSpace=wrap;html=1;fillColor=#E8F4F8;strokeColor=#0078D4;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=11;" vertex="1" parent="1">
  <mxGeometry x="650" y="870" width="430" height="250" as="geometry"/>
</mxCell>
<mxCell id="formation_acces_code" value="&lt;b&gt;FormationAccesCode&lt;/b&gt;&lt;hr&gt;+ id : int&lt;br&gt;+ user_id : int (FK)&lt;br&gt;+ formation_id : int (FK)&lt;br&gt;+ code : string&lt;br&gt;+ accessed_at : datetime&lt;br&gt;+ statut : enum [actif, expire, revoque]&lt;hr&gt;+ user() : User&lt;br&gt;+ formation() : Formation&lt;br&gt;+ validerCode(code) : boolean" style="rounded=0;whiteSpace=wrap;html=1;fillColor=#E8F4F8;strokeColor=#0078D4;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=11;" vertex="1" parent="1">
  <mxGeometry x="70" y="1010" width="430" height="260" as="geometry"/>
</mxCell>
<mxCell id="notification" value="&lt;b&gt;Notification&lt;/b&gt;&lt;hr&gt;+ id : int&lt;br&gt;+ user_id : int (FK)&lt;br&gt;+ type : enum [code_acces, formation, inscription]&lt;br&gt;+ titre : string&lt;br&gt;+ message : text&lt;br&gt;+ lu : boolean&lt;br&gt;+ created_at : datetime&lt;hr&gt;+ user() : User&lt;br&gt;+ marquerCommeLue() : void" style="rounded=0;whiteSpace=wrap;html=1;fillColor=#E8F4F8;strokeColor=#0078D4;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=11;" vertex="1" parent="1">
  <mxGeometry x="1160" y="1030" width="450" height="260" as="geometry"/>
</mxCell>
<mxCell id="note_formation" value="USER STORIES SPRINT 2:&#xa;✓ Créer une formation&#xa;✓ Organiser en modules&#xa;✓ Rechercher / filtrer formations&#xa;✓ Consulter détails formation&#xa;✓ Réorganiser modules&#xa;✓ Supprimer formation&#xa;✓ Formation codée + code accès" style="shape=note;whiteSpace=wrap;html=1;backgroundOutline=1;darkOpacity=0.05;fillColor=#FFF2CC;strokeColor=#D6B656;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=12;" vertex="1" parent="1">
  <mxGeometry x="1040" y="825" width="330" height="180" as="geometry"/>
</mxCell>
<mxCell id="note_code" value="Gestion formations codées:&#xa;• Code unique généré&#xa;• Notification envoyée&#xa;• Accès temporaire possible" style="shape=note;whiteSpace=wrap;html=1;backgroundOutline=1;darkOpacity=0.05;fillColor=#FFF2CC;strokeColor=#D6B656;fontColor=#000000;align=left;verticalAlign=top;spacing=8;fontSize=12;" vertex="1" parent="1">
  <mxGeometry x="70" y="850" width="310" height="130" as="geometry"/>
</mxCell>
<mxCell id="user_formateur" value="possède profil&#xa;si role=formateur" style="endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="user" target="formateur">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="user_apprenant" value="possède profil&#xa;si role=apprenant" style="endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="user" target="apprenant">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="user_notification" value="reçoit" style="endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="user" target="notification">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="formateur_formation" value="crée" style="endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="formateur" target="formation">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="apprenant_inscription" value="s&apos;inscrit" style="endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="apprenant" target="inscription">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="apprenant_acces_code" value="accède via code" style="endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="apprenant" target="formation_acces_code">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="formation_module" value="organisée en" style="startArrow=diamond;startFill=1;endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="formation" target="module_formation">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="module_contenu" value="contient" style="startArrow=diamond;startFill=1;endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="module_formation" target="contenu">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="module_quiz" value="inclut" style="endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="module_formation" target="quiz">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="inscription_formation" value="s&apos;inscrit à" style="endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="inscription" target="formation">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="formation_acces" value="accordé via" style="endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="formation" target="formation_acces_code">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="formation_notification" value="génère code" style="endArrow=none;html=1;rounded=0;strokeColor=#0078D4;fontColor=#000000;fontSize=11;" edge="1" parent="1" source="formation" target="notification">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
      </root>
    </mxGraphModel>
  </diagram>
</mxfile>