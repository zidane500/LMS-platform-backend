<mxfile host="app.diagrams.net" modified="2026-05-11T00:00:00.000Z" agent="Laravel" version="24.7.17" type="device">
  <diagram name="Sprint 4 - Cas d'utilisation" id="sprint4-usecase">
    <mxGraphModel dx="1800" dy="1000" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1800" pageHeight="1000" math="0" shadow="0">
      <root>
        <mxCell id="0"/>
<mxCell id="1" parent="0"/>
<mxCell id="container" value="Description détaillée du CU du Sprint 4 : Progression, contact en temps réel et certification" style="rounded=0;whiteSpace=wrap;html=1;fillColor=none;strokeColor=#000000;fontColor=#111827;fontStyle=1;verticalAlign=top;align=center;spacingTop=8;fontSize=15;" vertex="1" parent="1">
  <mxGeometry x="210" y="50" width="1500" height="730" as="geometry"/>
</mxCell>
<mxCell id="utilisateur" value="Utilisateur" style="shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fillColor=#E0F2FE;strokeColor=#6B7280;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="65" y="110" width="45" height="75" as="geometry"/>
</mxCell>
<mxCell id="apprenant" value="Apprenant" style="shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fillColor=#E0F2FE;strokeColor=#6B7280;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="65" y="285" width="45" height="75" as="geometry"/>
</mxCell>
<mxCell id="formateur" value="Formateur" style="shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fillColor=#E0F2FE;strokeColor=#6B7280;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="65" y="455" width="45" height="75" as="geometry"/>
</mxCell>
<mxCell id="systeme" value="Système LMS" style="shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fillColor=#E0F2FE;strokeColor=#6B7280;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="65" y="630" width="45" height="75" as="geometry"/>
</mxCell>
<mxCell id="apprenant_to_utilisateur" value="" style="endArrow=block;endFill=0;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="apprenant" target="utilisateur">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="formateur_to_utilisateur" value="" style="endArrow=block;endFill=0;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="formateur" target="utilisateur">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="suivi_progression" value="Suivre la progression" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="285" y="140" width="220" height="60" as="geometry"/>
</mxCell>
<mxCell id="contact_temps_reel" value="Communiquer en&#xa;temps réel" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="285" y="380" width="220" height="60" as="geometry"/>
</mxCell>
<mxCell id="gestion_certification" value="Gérer la certification" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="285" y="610" width="220" height="60" as="geometry"/>
</mxCell>
<mxCell id="voir_progression" value="Voir ma progression&#xa;détaillée" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="600" y="85" width="220" height="60" as="geometry"/>
</mxCell>
<mxCell id="suivre_apprenants" value="Suivre la progression&#xa;des apprenants" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="600" y="165" width="230" height="60" as="geometry"/>
</mxCell>
<mxCell id="voir_badges" value="Voir les badges&#xa;obtenus" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="880" y="125" width="210" height="60" as="geometry"/>
</mxCell>
<mxCell id="envoyer_message" value="Envoyer un message" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="600" y="310" width="210" height="55" as="geometry"/>
</mxCell>
<mxCell id="lancer_appel_vocal" value="Lancer un appel&#xa;vocal" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="600" y="390" width="210" height="60" as="geometry"/>
</mxCell>
<mxCell id="lancer_appel_video" value="Lancer un appel&#xa;vidéo" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="600" y="475" width="210" height="60" as="geometry"/>
</mxCell>
<mxCell id="accompagnement" value="Bénéficier d&apos;un&#xa;accompagnement pédagogique" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="880" y="385" width="260" height="65" as="geometry"/>
</mxCell>
<mxCell id="generer_certificat" value="Générer automatiquement&#xa;un certificat" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="600" y="570" width="250" height="65" as="geometry"/>
</mxCell>
<mxCell id="telecharger_certificat" value="Télécharger mon&#xa;certificat" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="600" y="665" width="220" height="60" as="geometry"/>
</mxCell>
<mxCell id="scanner_qr" value="Scanner le QR Code&#xa;d&apos;un certificat" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="900" y="620" width="230" height="60" as="geometry"/>
</mxCell>
<mxCell id="verifier_certificat" value="Vérifier le certificat" style="ellipse;whiteSpace=wrap;html=1;fillColor=#DCEBFF;strokeColor=#8AB4E8;fontColor=#111827;" vertex="1" parent="1">
  <mxGeometry x="1200" y="620" width="220" height="55" as="geometry"/>
</mxCell>
<mxCell id="apprenant_suivi" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="apprenant" target="suivi_progression">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="formateur_suivi" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="formateur" target="suivi_progression">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="apprenant_contact" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="apprenant" target="contact_temps_reel">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="formateur_contact" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="formateur" target="contact_temps_reel">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="systeme_certification" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="systeme" target="gestion_certification">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="apprenant_certification" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="apprenant" target="gestion_certification">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="utilisateur_certification" value="" style="endArrow=none;html=1;rounded=0;strokeColor=#333333;" edge="1" parent="1" source="utilisateur" target="gestion_certification">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="voir_progression_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="voir_progression" target="suivi_progression">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="suivre_apprenants_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="suivre_apprenants" target="suivi_progression">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="voir_badges_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="voir_badges" target="suivi_progression">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="message_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="envoyer_message" target="contact_temps_reel">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="appel_vocal_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="lancer_appel_vocal" target="contact_temps_reel">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="appel_video_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="lancer_appel_video" target="contact_temps_reel">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="accompagnement_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="accompagnement" target="contact_temps_reel">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="generer_certificat_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="generer_certificat" target="gestion_certification">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="telecharger_certificat_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="telecharger_certificat" target="gestion_certification">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="scanner_qr_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="scanner_qr" target="gestion_certification">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
<mxCell id="verifier_certificat_ext" value="&lt;&lt;extend&gt;&gt;" style="endArrow=open;html=1;rounded=0;dashed=1;strokeColor=#333333;fontColor=#111827;" edge="1" parent="1" source="verifier_certificat" target="scanner_qr">
  <mxGeometry relative="1" as="geometry"/>
</mxCell>
      </root>
    </mxGraphModel>
  </diagram>
</mxfile>