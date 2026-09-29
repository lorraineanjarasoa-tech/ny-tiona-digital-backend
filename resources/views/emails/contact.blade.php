@extends('emails.layout')

@section('title', 'Nouveau message de contact')

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
            💬
        </div>

        <h1
            style="
                margin:0;
                color:#1E2A4A;
                font-size:26px;
                line-height:34px;
            "
        >
            Nouveau message
        </h1>

        <p
            style="
                margin:10px 0 0;
                color:#6B7280;
                font-size:14px;
                line-height:22px;
            "
        >
            Vous avez reçu un nouveau message depuis votre site.
        </p>

    </div>

    <div
        style="
            background:#F8FAFC;
            border-radius:14px;
            padding:20px;
            margin-bottom:25px;
        "
    >

        <table
            width="100%"
            cellpadding="0"
            cellspacing="0"
            border="0"
        >

            <tr>
                <td
                    style="
                        padding:8px 0;
                        color:#6B7280;
                        font-size:12px;
                        width:100px;
                    "
                >
                    Nom
                </td>

                <td
                    style="
                        padding:8px 0;
                        color:#1E2A4A;
                        font-size:13px;
                        font-weight:700;
                    "
                >
                    {{ $name ?? $nom ?? 'Non renseigné' }}
                </td>
            </tr>

            <tr>
                <td
                    style="
                        padding:8px 0;
                        color:#6B7280;
                        font-size:12px;
                    "
                >
                    Email
                </td>

                <td
                    style="
                        padding:8px 0;
                        color:#1E2A4A;
                        font-size:13px;
                    "
                >
                    {{ $email ?? 'Non renseigné' }}
                </td>
            </tr>

            <tr>
                <td
                    style="
                        padding:8px 0;
                        color:#6B7280;
                        font-size:12px;
                    "
                >
                    Sujet
                </td>

                <td
                    style="
                        padding:8px 0;
                        color:#1E2A4A;
                        font-size:13px;
                        font-weight:700;
                    "
                >
                    {{ $subject ?? $sujet ?? 'Sans sujet' }}
                </td>
            </tr>

        </table>

    </div>

    <div>

        <div
            style="
                color:#1E2A4A;
                font-size:14px;
                font-weight:700;
                margin-bottom:10px;
            "
        >
            Message
        </div>

        <div
            style="
                background:#FFFFFF;
                border:1px solid #E5E7EB;
                border-radius:12px;
                padding:18px;
                color:#4B5563;
                font-size:14px;
                line-height:23px;
                white-space:pre-line;
            "
        >
            {{ $message ?? $content ?? 'Aucun message.' }}
        </div>

    </div>

    <div style="text-align:center; margin-top:30px;">

        <a
            href="mailto:{{ $email ?? '' }}"
            style="
                display:inline-block;
                background:#F2A51B;
                color:#1E2A4A;
                text-decoration:none;
                font-size:14px;
                font-weight:700;
                padding:13px 25px;
                border-radius:10px;
            "
        >
            Répondre au message →
        </a>

    </div>

@endsection