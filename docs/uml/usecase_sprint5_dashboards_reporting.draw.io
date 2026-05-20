<mxfile host="app.diagrams.net" modified="2026-05-11T00:00:00.000Z" agent="Laravel" version="24.7.17" type="device">
  <diagram name="Sprint 5 - Cas d'utilisation" id="sprint5-usecase">
    <mxGraphModel dx="1800" dy="1000" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1800" pageHeight="1000" math="0" shadow="0">
      <root>
        <mxCell id="0"/>
<mxCell id="1" parent="0"/>
<mxCell id="container" value="Description détaillée du CU du Sprint 5 : Dashboards et reporting" style="rounded=0;whiteSpace=wrap;html=1;fillColor=none;strokeColor=#000000;fontColor=#111827;fontStyle=1;verticalAlign=top;align=center;spacingTop=8;fontSize=15;" vertex="1" parent="1">
  <mxGeometry x="210" y="50" width="1500" height="720" as="geometry"/>
</mxCell>
<mxCell id="utilisateur" value="Utilisateur" style="shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fillColor=#E0F2FE;strokeColor=#6B7280;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="65" y="110" width="45" height="75" as="geometry"/>
</mxCell>
<mxCell id="apprenant" value="Apprenant" style="shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fillColor=#E0F2FE;strokeColor=#6B7280;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="65" y="280" width="45" height="75" as="geometry"/>
</mxCell>
<mxCell id="formateur" value="Formateur" style="shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fillColor=#E0F2FE;strokeColor=#6B7280;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="65" y="450" width="45" height="75" as="geometry"/>
</mxCell>
<mxCell id="admin" value="Administrateur" style="shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fillColor=#E0F2FE;strokeColor=#6B7280;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="65" y="620" width="45" height="75" as="geometry"/>
</mxCell>
<mxCell id="apprenant_to_utilisateur" value="" style="endArrow=block;endFill=0;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="apprenant" target="utilisateur">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="formateur_to_utilisateur" value="" style="endArrow=block;endFill=0;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="formateur" target="utilisateur">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="admin_to_utilisateur" value="" style="endArrow=block;endFill=0;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="admin" target="utilisateur">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="dashboard_apprenant" value="Consulter le tableau&#xa;de bord apprenant" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="285" y="135" width="230" height="65" as="geometry"/>
</mxCell>
<mxCell id="dashboard_formateur" value="Consulter le tableau&#xa;de bord formateur" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="285" y="340" width="230" height="65" as="geometry"/>
</mxCell>
<mxCell id="dashboard_admin" value="Consulter le tableau&#xa;de bord administrateur" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="285" y="555" width="250" height="65" as="geometry"/>
</mxCell>
<mxCell id="temps_apprentissage" value="Visualiser le temps&#xa;d&apos;apprentissage" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="675" y="250" width="230" height="65" as="geometry"/>
</mxCell>
<mxCell id="voir_inscriptions" value="Voir mes inscriptions" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="620" y="75" width="210" height="55" as="geometry"/>
</mxCell>
<mxCell id="voir_formations_completees" value="Voir formations&#xa;complétées" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="620" y="145" width="210" height="60" as="geometry"/>
</mxCell>
<mxCell id="voir_formations_cours" value="Voir formations&#xa;en cours" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="865" y="110" width="210" height="60" as="geometry"/>
</mxCell>
<mxCell id="evolution_resultats" value="Voir l&apos;évolution&#xa;mensuelle des résultats" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="1110" y="110" width="250" height="65" as="geometry"/>
</mxCell>
<mxCell id="voir_mes_formations" value="Voir mes formations" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="620" y="335" width="210" height="55" as="geometry"/>
</mxCell>
<mxCell id="inscriptions_semaine" value="Voir les inscriptions&#xa;par semaine" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="865" y="315" width="230" height="60" as="geometry"/>
</mxCell>
<mxCell id="progression_apprenants" value="Voir la progression&#xa;des apprenants" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="1130" y="315" width="230" height="60" as="geometry"/>
</mxCell>
<mxCell id="efficacite_pedagogique" value="Évaluer l&apos;efficacité&#xa;pédagogique" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="1130" y="395" width="230" height="60" as="geometry"/>
</mxCell>
<mxCell id="repartition_utilisateurs" value="Voir la répartition&#xa;des utilisateurs" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="620" y="520" width="230" height="60" as="geometry"/>
</mxCell>
<mxCell id="top_formations" value="Voir les formations&#xa;les plus suivies" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="880" y="520" width="230" height="60" as="geometry"/>
</mxCell>
<mxCell id="indicateurs_plateforme" value="Voir les indicateurs&#xa;de la plateforme" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="1140" y="520" width="230" height="60" as="geometry"/>
</mxCell>
<mxCell id="stats_ia" value="Consulter les statistiques&#xa;IA" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="620" y="620" width="230" height="60" as="geometry"/>
</mxCell>
<mxCell id="stats_certifications" value="Consulter les statistiques&#xa;des certifications" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="880" y="620" width="250" height="60" as="geometry"/>
</mxCell>
<mxCell id="progression_categorie" value="Voir la progression&#xa;par catégorie" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="1160" y="620" width="230" height="60" as="geometry"/>
</mxCell>
<mxCell id="temps_par_formation" value="Voir le temps passé&#xa;par formation" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="960" y="230" width="230" height="60" as="geometry"/>
</mxCell>
<mxCell id="mesurer_engagement" value="Mesurer l&apos;engagement&#xa;dans le parcours" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="1220" y="230" width="250" height="60" as="geometry"/>
</mxCell>
<mxCell id="apprenant_dashboard" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="apprenant" target="dashboard_apprenant">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="apprenant_temps" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="apprenant" target="temps_apprentissage">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="formateur_dashboard" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="formateur" target="dashboard_formateur">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="formateur_temps" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="formateur" target="temps_apprentissage">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="admin_dashboard" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="admin" target="dashboard_admin">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="inscriptions_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="voir_inscriptions" target="dashboard_apprenant">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="completees_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="voir_formations_completees" target="dashboard_apprenant">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="cours_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="voir_formations_cours" target="dashboard_apprenant">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="evolution_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="evolution_resultats" target="dashboard_apprenant">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="mes_formations_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="voir_mes_formations" target="dashboard_formateur">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="inscriptions_semaine_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="inscriptions_semaine" target="dashboard_formateur">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="progression_apprenants_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="progression_apprenants" target="dashboard_formateur">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="efficacite_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="efficacite_pedagogique" target="dashboard_formateur">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="repartition_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="repartition_utilisateurs" target="dashboard_admin">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="top_formations_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="top_formations" target="dashboard_admin">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="indicateurs_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="indicateurs_plateforme" target="dashboard_admin">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="stats_ia_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="stats_ia" target="dashboard_admin">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="stats_certifications_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="stats_certifications" target="dashboard_admin">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="progression_categorie_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="progression_categorie" target="dashboard_admin">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="temps_formation_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="temps_par_formation" target="temps_apprentissage">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="engagement_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="mesurer_engagement" target="temps_apprentissage">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
      </root>
    </mxGraphModel>
  </diagram>
</mxfile>