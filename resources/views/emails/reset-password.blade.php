<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Réinitialisation du mot de passe</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #334155;
        }

        .wrapper {
            max-width: 580px;
            margin: 40px auto;
            padding: 0 16px 40px;
        }

        .header {
            background: linear-gradient(135deg, #0a1628 0%, #0f2147 50%, #1a3a6b 100%);
            border-radius: 16px 16px 0 0;
            padding: 36px 40px;
            text-align: center;
        }

       

       
       

        .header h1 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .header p {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 4px;
        }

        .body {
            background: #ffffff;
            padding: 40px;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
        }

        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 16px;
        }

        .text {
            font-size: 15px;
            line-height: 1.7;
            color: #475569;
            margin-bottom: 20px;
        }

        .btn-wrap {
            text-align: center;
            margin: 32px 0;
        }

        .btn {
            display: inline-block;
            padding: 14px 36px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .alert {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 13px;
            color: #92400e;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert span {
            font-size: 16px;
        }

        .url-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 16px;
            word-break: break-all;
            font-size: 12px;
            color: #64748b;
            margin-top: 24px;
        }

        .url-box strong {
            display: block;
            margin-bottom: 6px;
            color: #475569;
        }

        .url-box a {
            color: #3b82f6;
        }

        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 28px 0;
        }

        .footer {
            background: #0a1628;
            border-radius: 0 0 16px 16px;
            padding: 24px 40px;
            text-align: center;
        }

        .footer p {
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.8;
        }

        .footer .brand {
            color: #ffffff;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 8px;
        }
    </style>
</head>

<body>
    <div class="wrapper">

        <div class="header">
         

            <h1>LMS Platform</h1>
            <p>Plateforme d'apprentissage en ligne</p>
        </div>

        <div class="body">

            <p class="greeting">
                Bonjour {{ $userName ?: 'Utilisateur' }},
            </p>

            <p class="text">
                Vous recevez cet email car nous avons reçu une demande de réinitialisation du mot de passe associé à votre compte.
            </p>

            <div class="alert">
                

                <div>
                    Ce lien est valable pendant
                    <strong>{{ $expireMin }} minutes</strong>.
                    Passé ce délai, vous devrez faire une nouvelle demande.
                </div>
            </div>

            <div class="btn-wrap">
                <a href="{{ $resetUrl }}" class="btn">
                    Réinitialiser mon mot de passe
                </a>
            </div>

            <hr class="divider" />

            <p class="text">
                Si vous n'avez pas demandé de réinitialisation, ignorez simplement cet email.
                Votre mot de passe restera inchangé et aucune action n'est requise.
            </p>

            
        </div>

        <div class="footer">

            <p>
                Cet email a été envoyé automatiquement, merci de ne pas y répondre.<br />
                &copy; {{ date('Y') }} LMS Platform — Tous droits réservés.
            </p>
        </div>
    </div>
</body>

</html>