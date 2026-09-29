@extends('emails.layout')

@section('title', 'Vérification de votre compte')

@section('content')

    <div style="text-align:center; margin-bottom:30px;">

        <div
            style="
                width:64px;
                height:64px;
                margin:0 auto 18px;
                background:#FFF7E6;
                border-radius:50%;
                line-height:64px;
                font-size:28px;
            "
        >
            ✉️
        </div>

        <h1
            style="
                margin:0;
                color:#1E2A4A;
                font-size:26px;
                line-height:34px;
            "
        >
            Vérifiez votre adresse email
        </h1>

        <p
            style="
                margin:10px 0 0;
                color:#6B7280;
                font-size:14px;
                line-height:22px;
            "
        >
            Une dernière étape pour sécuriser votre compte.
        </p>

    </div>

    <p
        style="
            color:#374151;
            font-size:15px;
            line-height:24px;
        "
    >
        Bonjour
        <strong style="color:#1E2A4A;">
            {{ $user->name ?? $user->prenom ?? 'cher utilisateur' }}
        </strong>,
    </p>

    <p
        style="
            color:#4B5563;
            font-size:14px;
            line-height:23px;
        "
    >
        Merci d'avoir créé votre compte chez
        <strong style="color:#1E2A4A;">
            Ny Tiona Digital
        </strong>.
        Cliquez sur le bouton ci-dessous afin de vérifier
        votre adresse email.
    </p>

    <div style="text-align:center; margin:30px 0;">

        <a
            href="{{ $verificationUrl ?? '#' }}"
            style="
                display:inline-block;
                background:#F2A51B;
                color:#1E2A4A;
                text-decoration:none;
                font-size:14px;
                font-weight:700;
                padding:14px 30px;
                border-radius:10px;
            "
        >
            Vérifier mon adresse email →
        </a>

    </div>

    <div
        style="
            background:#F8FAFC;
            border-radius:12px;
            padding:16px;
            margin-top:25px;
        "
    >

        <p
            style="
                margin:0;
                color:#64748B;
                font-size:12px;
                line-height:20px;
            "
        >
            Si le bouton ne fonctionne pas, vous pouvez copier
            le lien de vérification fourni dans cet email
            et le coller dans votre navigateur.
        </p>

    </div>

    <p
        style="
            color:#9CA3AF;
            font-size:12px;
            line-height:20px;
            margin-top:25px;
        "
    >
        Si vous n'avez pas créé ce compte, vous pouvez
        simplement ignorer cet email.
    </p>

@endsection