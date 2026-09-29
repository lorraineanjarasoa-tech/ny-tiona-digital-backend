

<?php $__env->startSection('title', 'Code de récupération'); ?>

<?php $__env->startSection('content'); ?>

    <div style="text-align:center; margin-bottom:30px;">

        <div
            style="
                width:64px;
                height:64px;
                margin:0 auto 18px;
                background:#FFF7E6;
                border-radius:50%;
                text-align:center;
                line-height:64px;
                font-size:28px;
            "
        >
            🔐
        </div>

        <h1
            style="
                margin:0;
                color:#1E2A4A;
                font-size:26px;
                line-height:34px;
                font-weight:700;
            "
        >
            Code de récupération
        </h1>

        <p
            style="
                margin:10px 0 0;
                color:#6B7280;
                font-size:14px;
                line-height:22px;
            "
        >
            Nous avons reçu une demande de réinitialisation
            de votre mot de passe.
        </p>

    </div>

    <p
        style="
            color:#374151;
            font-size:15px;
            line-height:24px;
            margin:0 0 20px;
        "
    >
        Bonjour
        <strong style="color:#1E2A4A;">
            <?php echo e($user->name ?? $user->prenom ?? 'cher utilisateur'); ?>

        </strong>,
    </p>

    <p
        style="
            color:#4B5563;
            font-size:14px;
            line-height:23px;
            margin:0 0 25px;
        "
    >
        Utilisez le code ci-dessous pour continuer la procédure
        de récupération de votre compte.
    </p>

    <div
        style="
            background:#F8FAFC;
            border:2px dashed #F2A51B;
            border-radius:14px;
            padding:24px 15px;
            text-align:center;
            margin:25px 0;
        "
    >

        <div
            style="
                color:#6B7280;
                font-size:11px;
                text-transform:uppercase;
                letter-spacing:2px;
                margin-bottom:10px;
            "
        >
            Votre code
        </div>

        <div
            style="
                color:#1E2A4A;
                font-size:34px;
                line-height:40px;
                font-weight:800;
                letter-spacing:8px;
            "
        >
            <?php echo e($code); ?>

        </div>

    </div>

    <div
        style="
            background:#FFF7E6;
            border-radius:12px;
            padding:15px;
            margin:25px 0;
        "
    >
        <p
            style="
                margin:0;
                color:#92400E;
                font-size:13px;
                line-height:21px;
            "
        >
            ⏱️ Ce code est valable pendant
            <strong>10 minutes</strong>.
        </p>
    </div>

    <p
        style="
            color:#6B7280;
            font-size:13px;
            line-height:21px;
            margin:25px 0 0;
        "
    >
        Si vous n'êtes pas à l'origine de cette demande,
        vous pouvez ignorer cet email.
        Votre compte reste sécurisé.
    </p>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('emails.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\ny-tiona-digital\backend\resources\views/emails/password-reset-code.blade.php ENDPATH**/ ?>