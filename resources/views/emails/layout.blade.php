<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'Ny Tiona Digital')
    </title>
</head>

<body
    style="
        margin:0;
        padding:0;
        background:#F3F4F6;
        font-family:Arial, Helvetica, sans-serif;
        color:#374151;
    "
>

<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        background:#F3F4F6;
        margin:0;
        padding:35px 15px;
    "
>

    <tr>
        <td align="center">

            <!-- Conteneur principal -->

            <table
                width="100%"
                cellpadding="0"
                cellspacing="0"
                border="0"
                style="
                    max-width:600px;
                    background:#FFFFFF;
                    border-radius:18px;
                    overflow:hidden;
                    box-shadow:0 8px 30px rgba(30,42,74,0.08);
                "
            >

                <!-- Header -->

                <tr>
                    <td
                        style="
                            background:#FFFFFF;
                            padding:25px 30px;
                            border-bottom:1px solid #EEF0F3;
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
                                    valign="middle"
                                    style="width:55px;"
                                >
                                    <img
                                        src="{{ url('/images/logo.png') }}"
                                        alt="Ny Tiona Digital"
                                        width="48"
                                        style="
                                            display:block;
                                            max-width:48px;
                                            height:auto;
                                        "
                                    >
                                </td>

                                <td
                                    valign="middle"
                                    style="
                                        padding-left:12px;
                                    "
                                >

                                    <div
                                        style="
                                            color:#1E2A4A;
                                            font-size:16px;
                                            font-weight:700;
                                            line-height:20px;
                                        "
                                    >
                                        Ny Tiona Digital
                                    </div>

                                    <div
                                        style="
                                            color:#9CA3AF;
                                            font-size:11px;
                                            line-height:18px;
                                            letter-spacing:1px;
                                        "
                                    >
                                        Formation professionnelle
                                    </div>

                                </td>

                            </tr>

                        </table>

                    </td>
                </tr>

                <!-- Contenu -->

                <tr>
                    <td
                        style="
                            padding:35px 30px;
                        "
                    >

                        @yield('content')

                    </td>
                </tr>

                <!-- Footer -->

                <tr>
                    <td
                        style="
                            background:#1E2A4A;
                            padding:25px 30px;
                            text-align:center;
                        "
                    >

                        <div
                            style="
                                color:#FFFFFF;
                                font-size:14px;
                                font-weight:700;
                                margin-bottom:8px;
                            "
                        >
                            Ny Tiona Digital
                        </div>

                        <div
                            style="
                                color:#CBD5E1;
                                font-size:11px;
                                line-height:18px;
                            "
                        >
                            Apprendre • Maîtriser • Transformer
                        </div>

                        <div
                            style="
                                height:1px;
                                background:#33405D;
                                margin:18px 0;
                            "
                        ></div>

                        <div
                            style="
                                color:#94A3B8;
                                font-size:11px;
                                line-height:18px;
                            "
                        >
                            Antananarivo, Madagascar
                        </div>

                        <div
                            style="
                                color:#94A3B8;
                                font-size:11px;
                                line-height:18px;
                            "
                        >
                            nytionadigital@gmail.com
                        </div>

                        <div
                            style="
                                color:#64748B;
                                font-size:10px;
                                margin-top:15px;
                            "
                        >
                            © {{ date('Y') }} Ny Tiona Digital.
                            Tous droits réservés.
                        </div>

                    </td>
                </tr>

            </table>

        </td>
    </tr>

</table>

</body>
</html>