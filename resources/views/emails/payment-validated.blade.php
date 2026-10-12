<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement validé</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8fafc; padding: 20px; margin: 0;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">

        <div style="background: linear-gradient(135deg, #1E2A4A 0%, #27365F 100%); padding: 30px 24px; text-align: center;">
            <h1 style="color: #F2A51B; margin: 0; font-size: 22px;">Ny Tiona Digital</h1>
            <p style="color: #cbd5e1; margin: 8px 0 0 0; font-size: 13px;">Espace étudiant</p>
        </div>

        <div style="padding: 30px 24px;">
            <h2 style="color: #1E2A4A; margin: 0 0 16px 0; font-size: 20px;">Paiement validé ✅</h2>

            <p style="color: #475569; font-size: 15px; line-height: 1.6;">
                Bonjour <strong>{{ $userName }}</strong>,
            </p>

            <p style="color: #475569; font-size: 15px; line-height: 1.6;">
                Votre paiement pour <strong>{{ $moisLabel }}</strong> a bien été validé par l'administration.
            </p>

            <div style="background: #ecfdf5; border-left: 4px solid #10b981; padding: 16px; border-radius: 8px; margin: 20px 0;">
                <p style="margin: 0; color: #065f46; font-size: 14px;">
                    <strong>Montant :</strong> {{ $montant }} Ar
                    @if($reference)
                        <br><strong>Référence :</strong> {{ $reference }}
                    @endif
                </p>
            </div>

            <p style="color: #475569; font-size: 15px; line-height: 1.6;">
                Vous pouvez maintenant continuer votre formation en accédant à vos cours.
            </p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/etudiant/paiements"
                   style="display: inline-block; background: #F2A51B; color: #1E2A4A; padding: 14px 28px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 14px;">
                    Voir mes paiements
                </a>
            </div>
        </div>

        <div style="background: #f1f5f9; padding: 20px 24px; text-align: center;">
            <p style="color: #94a3b8; font-size: 12px; margin: 0;">
                Cet email est automatique, merci de ne pas y répondre.
            </p>
            <p style="color: #94a3b8; font-size: 12px; margin: 8px 0 0 0;">
                © {{ date('Y') }} Ny Tiona Digital
            </p>
        </div>

    </div>
</body>
</html>