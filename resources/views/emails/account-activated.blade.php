<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte activé</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow: 0 4px 24px rgba(15,23,42,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #1E2A4A 0%, #2C3D6B 100%); padding: 40px 30px; text-align:center;">
                            <div style="display:inline-block; width:60px; height:60px; background-color:#F2A51B; border-radius:16px; line-height:60px; font-size:28px; font-weight:900; color:#1E2A4A; margin-bottom:16px;">✓</div>
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:800; letter-spacing:-0.02em;">
                                Votre compte est activé !
                            </h1>
                            <p style="margin:10px 0 0; color:rgba(255,255,255,0.75); font-size:14px;">
                                Ny Tiona Digital — Formation professionnelle
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 40px 30px;">

                            <p style="margin:0 0 16px; color:#1E2A4A; font-size:16px; font-weight:600;">
                                Bonjour {{ $fullName }},
                            </p>

                            <p style="margin:0 0 20px; color:#475569; font-size:15px; line-height:1.7;">
                                Excellente nouvelle ! Votre inscription en tant que
                                <strong style="color:#1E2A4A;">{{ $roleLabel }}</strong>
                                sur la plateforme Ny Tiona Digital a été
                                <strong style="color:#10b981;">validée par notre équipe</strong>.
                            </p>

                            <p style="margin:0 0 28px; color:#475569; font-size:15px; line-height:1.7;">
                                Vous pouvez dès maintenant vous connecter à votre espace personnel
                                pour accéder à vos formations, vos cours et bien plus encore.
                            </p>

                            {{-- Info box --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border-left:4px solid #F2A51B; border-radius:12px; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="margin:0; color:#64748b; font-size:12px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase;">
                                            Vos identifiants
                                        </p>
                                        <p style="margin:8px 0 0; color:#1E2A4A; font-size:14px;">
                                            <strong>Email :</strong> {{ $user->email }}
                                        </p>
                                        <p style="margin:4px 0 0; color:#1E2A4A; font-size:14px;">
                                            <strong>Mot de passe :</strong> celui que vous avez choisi lors de votre inscription
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA Button --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius:12px; background-color:#F2A51B;">
                                        <a href="{{ $loginUrl }}"
                                           style="display:inline-block; padding:14px 32px; color:#1E2A4A; font-size:15px; font-weight:800; text-decoration:none; border-radius:12px;">
                                            Accéder à mon espace →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 32px 0 0; color:#94a3b8; font-size:13px; line-height:1.6;">
                                Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :
                            </p>
                            <p style="margin: 8px 0 0; word-break:break-all;">
                                <a href="{{ $loginUrl }}" style="color:#F2A51B; font-size:12px;">{{ $loginUrl }}</a>
                            </p>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#f8fafc; padding: 24px 30px; text-align:center; border-top:1px solid #e2e8f0;">
                            <p style="margin:0 0 8px; color:#64748b; font-size:12px;">
                                Une question ? Contactez-nous :
                            </p>
                            <p style="margin:0; color:#1E2A4A; font-size:12px; font-weight:600;">
                                📞 +261 38 59 014 56 &nbsp;•&nbsp; ✉️ nytionadigital@gmail.com
                            </p>
                            <p style="margin: 16px 0 0; color:#94a3b8; font-size:11px;">
                                © {{ date('Y') }} Ny Tiona Digital — Tous droits réservés.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>