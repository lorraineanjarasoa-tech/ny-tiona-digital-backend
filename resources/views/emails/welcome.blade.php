@extends('emails.layout')

@section('title', 'Bienvenue chez Ny Tiona Digital')

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
            👋
        </div>

        <h1
            style="
                margin:0;
                color:#1E2A4A;
                font-size:26px;
                line-height:34px;
            "
        >
            Bienvenue chez Ny Tiona Digital !
        </h1>

        <p
            style="
                margin:10px 0 0;
                color:#6B7280;
                font-size:14px;
                line-height:22px;
            "
        >
            Votre aventure dans le numérique commence ici.
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
        Nous sommes ravis de vous accueillir au sein de
        <strong style="color:#1E2A4A;">
            Ny Tiona Digital
        </strong>.
    </p>

    <div
        style="
            background:#F8FAFC;
            border-radius:14px;
            padding:20px;
            margin:25px 0;
        "
    >

        <div
            style="
                color:#F2A51B;
                font-size:12px;
                font-weight:700;
                text-transform:uppercase;
                letter-spacing:1px;
                margin-bottom:8px;
            "
        >
            Notre mission
        </div>

        <div
            style="
                color:#1E2A4A;
                font-size:17px;
                line-height:25px;
                font-weight:700;
            "
        >
            Apprendre • Maîtriser • Transformer
        </div>

    </div>

    <p
        style="
            color:#4B5563;
            font-size:14px;
            line-height:23px;
        "
    >
        Vous pouvez maintenant accéder à votre espace et
        découvrir nos formations et services.
    </p>

    <div style="text-align:center; margin:30px 0;">

        <a
            href="{{ url('/') }}"
            style="
                display:inline-block;
                background:#F2A51B;
                color:#1E2A4A;
                text-decoration:none;
                font-size:14px;
                font-weight:700;
                padding:14px 28px;
                border-radius:10px;
            "
        >
            Accéder à Ny Tiona Digital →
        </a>

    </div>

@endsection