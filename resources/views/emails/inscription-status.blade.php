<!-- resources/views/emails/inscription-status.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statut de votre inscription</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1E2A4A;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .header {
            background: linear-gradient(135deg, #1E2A4A 0%, #2C3D6B 100%);
            padding: 30px 20px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #F2A51B;
        }
        .header .subtitle {
            color: #cfd8e3;
            font-size: 13px;
            margin-top: 5px;
            letter-spacing: 1px;
        }
        .content {
            padding: 35px 30px;
        }
        .content h2 {
            color: #1E2A4A;
            font-size: 22px;
            margin-top: 0;
        }
        .success-badge {
            display: inline-block;
            background-color: #d4edda;
            color: #155724;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .reject-badge {
            display: inline-block;
            background-color: #f8d7da;
            color: #721c24;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .pending-badge {
            display: inline-block;
            background-color: #fff3cd;
            color: #856404;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #F2A51B;
            padding: 18px 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
            min-width: 140px;
            font-size: 13px;
        }
        .info-value {
            color: #1E2A4A;
            font-weight: 500;
            font-size: 13px;
        }
        .btn {
            display: inline-block;
            padding: 14px 30px;
            background-color: #F2A51B;
            color: #1E2A4A !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            margin: 20px 0;
        }
        .btn-container {
            text-align: center;
            margin: 25px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        .footer a {
            color: #F2A51B;
            text-decoration: none;
        }
        ul {
            padding-left: 20px;
            color: #495057;
            font-size: 14px;
        }
        ul li {
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ny Tiona Digital</h1>
            <div class="subtitle">FORMATION PROFESSIONNELLE</div>
        </div>

        <div class="content">
            @php
                $userName = $user->profile->nom_complet ?? $user->name ?? 'Étudiant';
            @endphp

            @if($status === 'valide')
                <span class="success-badge">INSCRIPTION VALIDÉE</span>
                <h2>Félicitations {{ $userName }} !</h2>
                <p>
                    Nous avons le plaisir de vous informer que votre inscription a été
                    <strong>validée</strong> par l'administration de Ny Tiona Digital.
                    Votre compte étudiant est maintenant <strong>actif</strong>.
                </p>

                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{{ $user->email }}</span>
                    </div>
                </div>

                <p><strong>Ce que vous pouvez faire maintenant :</strong></p>
                <ul>
                    <li>Vous connecter à votre espace étudiant</li>
                    <li>Accéder à vos cours et supports de formation</li>
                    <li>Consulter vos échéances de paiement</li>
                    <li>Échanger avec vos formateurs via la messagerie</li>
                </ul>

                <div class="btn-container">
                    <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/login" class="btn">
                        Accéder à mon espace
                    </a>
                </div>

            @elseif($status === 'rejete')
                <span class="reject-badge">INSCRIPTION REJETÉE</span>
                <h2>Bonjour {{ $userName }},</h2>
                <p>
                    Nous vous informons que votre inscription n'a pas pu être validée
                    par l'administration de Ny Tiona Digital.
                </p>

                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{{ $user->email }}</span>
                    </div>
                </div>

                <p>
                    Pour plus d'informations, veuillez nous contacter à
                    <a href="mailto:nytionadigital@gmail.com" style="color: #F2A51B;">nytionadigital@gmail.com</a>
                </p>

            @elseif($status === 'en_attente')
                <span class="pending-badge">EN ATTENTE DE VALIDATION</span>
                <h2>Bonjour {{ $userName }},</h2>
                <p>
                    Votre inscription est en cours de traitement par l'administration.
                    Vous recevrez un email dès qu'elle sera validée.
                </p>

                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{{ $user->email }}</span>
                    </div>
                </div>
            @endif

            <p style="font-size: 13px; color: #6c757d; margin-top: 25px;">
                Si vous avez des questions, contactez-nous à
                <a href="mailto:nytionadigital@gmail.com" style="color: #F2A51B;">nytionadigital@gmail.com</a>
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Ny Tiona Digital — Tous droits réservés</p>
            <p style="margin: 5px 0;">Anjanahary 49IIS, Rue Hagamainty, Antananarivo</p>
        </div>
    </div>
</body>
</html>