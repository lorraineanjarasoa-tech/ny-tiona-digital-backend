<?php

use Illuminate\Support\Facades\Route;

// ===================================================
// CONTROLLERS
// ===================================================

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\FormateurController;

use App\Http\Controllers\Api\FormationController;
use App\Http\Controllers\Api\CoursController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\PartageController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\NewsletterController;

// ===================================================
// CONTROLLERS ETUDIANT
// ===================================================

use App\Http\Controllers\Api\Etudiant\DashboardController as EtudiantDashboardController;
use App\Http\Controllers\Api\Etudiant\FormationController as EtudiantFormationController;
use App\Http\Controllers\Api\Etudiant\InscriptionController as EtudiantInscriptionController;
use App\Http\Controllers\Api\Etudiant\CoursController as EtudiantCoursController;
use App\Http\Controllers\Api\Etudiant\PaiementController;
use App\Http\Controllers\Api\Etudiant\PartageController as EtudiantPartageController;
use App\Http\Controllers\Api\Etudiant\ProfileController;
use App\Http\Controllers\Api\Etudiant\AiAssistantController;
use App\Http\Controllers\Api\Etudiant\FriendController;
use App\Http\Controllers\Api\Etudiant\ExamenController;
use App\Http\Controllers\Api\Etudiant\NotificationController;

// ===================================================
// CONTROLLERS FORMATEUR
// ===================================================

use App\Http\Controllers\Api\Formateur\ExamenController as FormateurExamenController;
use App\Http\Controllers\Api\Formateur\DashboardController;
use App\Http\Controllers\Api\Formateur\CoursController as FormateurCoursController;
use App\Http\Controllers\Api\Formateur\PublicationsController as FormateurPublicationsController;

// ===================================================
// CONTROLLERS ADMIN
// ===================================================

use App\Http\Controllers\Api\Admin\InscriptionController;

// ===================================================
// AUTRES CONTROLLERS
// ===================================================

use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\PublicController;


// ===================================================
// ROUTES PUBLIQUES
// ===================================================

Route::get('/vagues-ouvertes', [PublicController::class, 'vaguesOuvertes']);
Route::get('/formations-publiques', [PublicController::class, 'formationsPubliques']);

// MOT DE PASSE OUBLIÉ
Route::post('/password/forgot', [ForgotPasswordController::class, 'sendCode']);
Route::post('/password/verify-code', [ForgotPasswordController::class, 'verifyCode']);
Route::post('/password/reset', [ForgotPasswordController::class, 'resetPassword']);

// STATISTIQUES PUBLIQUES
Route::get('/stats', [StatsController::class, 'index']);

// FORMATIONS PUBLIQUES
Route::get('/formations', [FormationController::class, 'index']);
Route::get('/formations/{id}', [FormationController::class, 'show']);

// NEWSLETTER
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);

// AUTHENTIFICATION
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);

// CONTACT
Route::post('/contact', [ContactController::class, 'send']);

// PROCHAINE VAGUE — PUBLIC
Route::get('/next-vague', [StatsController::class, 'nextVague']);

// PARTAGES — CONSULTATION PUBLIQUE (lecture seule)
Route::get('/partages', [PartageController::class, 'index']);
Route::get('/partages/{id}', [PartageController::class, 'show']);


// ===================================================
// ROUTES PROTÉGÉES (auth:api)
// ===================================================

Route::middleware(['auth:api'])->group(function () {

    // =================================================
    // AUTHENTIFICATION
    // =================================================

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh-token', [AuthController::class, 'refresh']);
    Route::get('/check', [AuthController::class, 'check']);


    // =================================================
    // AMIS — ACCESSIBLE À TOUS (étudiants ET formateurs)
    // =================================================

    Route::prefix('amis')->group(function () {
        Route::get('/', [FriendController::class, 'index']);
        Route::get('/suggestions', [FriendController::class, 'suggestions']);
        Route::get('/demandes', [FriendController::class, 'demandes']);
        Route::get('/envoyees', [FriendController::class, 'envoyees']);
        Route::post('/demander/{userId}', [FriendController::class, 'demander']);
        Route::post('/accepter/{userId}', [FriendController::class, 'accepter']);
        Route::post('/refuser/{userId}', [FriendController::class, 'refuser']);
        Route::delete('/annuler/{userId}', [FriendController::class, 'annuler']);
        Route::delete('/{userId}', [FriendController::class, 'retirer']);
    });


    // =================================================
    // PARTAGES — ACTIONS
    // =================================================

    Route::prefix('partages')->group(function () {
        Route::post('/{id}/like', [EtudiantPartageController::class, 'like']);
        Route::post('/{id}/commenter', [EtudiantPartageController::class, 'commenter']);
        Route::get('/{id}/commentaires', [EtudiantPartageController::class, 'commentaires']);
        Route::post('/{id}/view', [EtudiantPartageController::class, 'view']);
    });


    // =================================================
    // ADMIN
    // =================================================

    Route::middleware(['role:admin'])
        ->prefix('admin')
        ->group(function () {

            Route::get('/dashboard/stats', [InscriptionController::class, 'stats']);

            // Inscriptions
            Route::get('/inscriptions', [InscriptionController::class, 'index']);
            Route::get('/inscriptions/en-attente', [InscriptionController::class, 'enAttente']);
            Route::get('/inscriptions/recentes', [InscriptionController::class, 'recentes']);
            Route::get('/inscriptions/stats', [InscriptionController::class, 'stats']);
            Route::put('/inscriptions/{id}/valider', [InscriptionController::class, 'valider']);
            Route::put('/inscriptions/{id}/rejeter', [InscriptionController::class, 'rejeter']);
            Route::post('/inscriptions/{id}/notifier-paiement', [InscriptionController::class, 'notifierPaiement']);

            // Utilisateurs
            Route::get('/utilisateurs', [AdminController::class, 'listeUtilisateurs']);
            Route::get('/utilisateurs/non-valides', [AdminController::class, 'utilisateursNonValides']);
            Route::get('/utilisateurs/{id}', [AdminController::class, 'showUtilisateur']);
            Route::put('/utilisateurs/{id}/valider', [AdminController::class, 'validerUtilisateur']);
            Route::delete('/utilisateurs/{id}', [AdminController::class, 'supprimerUtilisateur']);

            // Vagues
            Route::get('/vagues', [AdminController::class, 'listeVagues']);
            Route::post('/vagues', [AdminController::class, 'creerVague']);
            Route::put('/vagues/{id}', [AdminController::class, 'modifierVague']);
            Route::delete('/vagues/{id}', [AdminController::class, 'supprimerVague']);

            // Formations
            Route::get('/formations', [AdminController::class, 'listeFormations']);
            Route::post('/formations', [AdminController::class, 'creerFormation']);
            Route::put('/formations/{id}', [AdminController::class, 'modifierFormation']);
            Route::delete('/formations/{id}', [AdminController::class, 'supprimerFormation']);

            // Profil admin
            Route::get('/profile', [AdminController::class, 'getProfil']);
            Route::put('/profile', [AdminController::class, 'updateProfil']);
            Route::put('/profile/password', [AdminController::class, 'updatePassword']);
            Route::middleware('auth:api')->group(function () {
            Route::post('/profile/photo', [\App\Http\Controllers\Api\ProfileController::class, 'uploadPhoto']);
            Route::delete('/profile/photo', [\App\Http\Controllers\Api\ProfileController::class, 'deletePhoto']);
            Route::put('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
        });

            // Notifications
            Route::get('/notifications', [AdminController::class, 'notifications']);
            Route::post('/notifications/read-all', [AdminController::class, 'markAllNotificationsAsRead']);
            Route::post('/notifications/{id}/read', [AdminController::class, 'markNotificationAsRead']);

            // Settings
            Route::get('/settings', [AdminController::class, 'getSettings']);
            Route::put('/settings', [AdminController::class, 'updateSettings']);

            // Meetings
            Route::get('/meetings', [AdminController::class, 'meetings']);
            Route::post('/meetings', [AdminController::class, 'createMeeting']);
            Route::delete('/meetings/{id}', [AdminController::class, 'deleteMeeting']);

            // Reports
            Route::get('/reports', [AdminController::class, 'reports']);
            Route::patch('/reports/{id}', [AdminController::class, 'updateReport']);

            // Cache
            Route::post('/cache/clear', [AdminController::class, 'clearCache']);

            // Export
            Route::get('/export', [AdminController::class, 'exportData']);
        });


    // =================================================
    // FORMATEUR
    // =================================================

    Route::middleware(['role:formateur'])
        ->prefix('formateur')
        ->group(function () {

            // Dashboard
            Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
            Route::get('/dashboard/activites', [DashboardController::class, 'activites']);
            Route::get('/profil', [ProfileController::class, 'show']);

            // Cours
            Route::get('/mes-cours', [FormateurCoursController::class, 'index']);
            Route::post('/cours', [FormateurCoursController::class, 'store']);
            Route::put('/cours/{id}', [FormateurCoursController::class, 'update']);
            Route::delete('/cours/{id}', [FormateurCoursController::class, 'destroy']);

            // Étudiants par cours
            Route::get('/etudiants-cours/{coursId}', [FormateurController::class, 'etudiantsParCours']);

            // Publications
            Route::get('/publications', [FormateurPublicationsController::class, 'index']);
            Route::post('/publications', [FormateurPublicationsController::class, 'store']);
            Route::put('/publications/{id}', [FormateurPublicationsController::class, 'update']);
            Route::delete('/publications/{id}', [FormateurPublicationsController::class, 'destroy']);

            // Ancienne route publication (à garder si utilisée ailleurs)
            Route::post('/publication', [FormateurController::class, 'publier']);

            // Examens
            Route::prefix('examens')->group(function () {
                Route::get('/', [FormateurExamenController::class, 'index']);
                Route::post('/', [FormateurExamenController::class, 'store']);
                Route::get('/{id}', [FormateurExamenController::class, 'show']);
                Route::put('/{id}', [FormateurExamenController::class, 'update']);
                Route::delete('/{id}', [FormateurExamenController::class, 'destroy']);

                Route::post('/{id}/publier', [FormateurExamenController::class, 'publier']);
                Route::post('/{id}/fermer', [FormateurExamenController::class, 'fermer']);
                Route::get('/{id}/resultats', [FormateurExamenController::class, 'resultats']);

                Route::post('/{id}/questions', [FormateurExamenController::class, 'ajouterQuestion']);
                Route::put('/questions/{questionId}', [FormateurExamenController::class, 'updateQuestion']);
                Route::delete('/questions/{questionId}', [FormateurExamenController::class, 'deleteQuestion']);
            });
        });


    // =================================================
    // ETUDIANT
    // =================================================

    Route::middleware(['role:etudiant'])
        ->prefix('etudiant')
        ->group(function () {

            // Dashboard
            Route::get('/dashboard/stats', [EtudiantDashboardController::class, 'stats']);

            // Formations
            Route::get('/mes-formations', [EtudiantFormationController::class, 'mesFormations']);

            // Inscriptions
            Route::post('/inscription', [EtudiantInscriptionController::class, 'store']);

            // Cours
            Route::get('/cours/{id}', [EtudiantCoursController::class, 'show']);

            // Paiements
            Route::get('/paiements', [PaiementController::class, 'index']);
            Route::get('/paiements/statut', [PaiementController::class, 'statut']);
            Route::post('/paiements', [PaiementController::class, 'store']);

            // Profil
            Route::get('/profil', [ProfileController::class, 'show']);
            Route::put('/profil', [ProfileController::class, 'update']);
            Route::put('/profil/password', [ProfileController::class, 'updatePassword']);

            // Partages
            Route::post('/partage', [EtudiantPartageController::class, 'store']);
            Route::get('/mes-partages', [EtudiantPartageController::class, 'mesPartages']);
            Route::put('/partage/{id}', [EtudiantPartageController::class, 'update']);
            Route::delete('/partage/{id}', [EtudiantPartageController::class, 'destroy']);

            // Assistant IA
            Route::post('/ai/chat', [AiAssistantController::class, 'chat']);

            // ⚠️ Routes "amis" SUPPRIMÉES d'ici — elles sont maintenant au niveau global

            // Examens
            Route::prefix('examens')->group(function () {
                Route::get('/', [ExamenController::class, 'index']);
                Route::get('/{id}', [ExamenController::class, 'show']);
                Route::post('/{id}/soumettre', [ExamenController::class, 'soumettre']);
                Route::get('/{id}/resultat', [ExamenController::class, 'resultat']);
            });

            // Notifications
            Route::prefix('notifications')->group(function () {
                Route::get('/', [NotificationController::class, 'index']);
                Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
                Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
                Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
                Route::delete('/{id}', [NotificationController::class, 'destroy']);
            });
        });


    // =================================================
    // COURS — UTILISATEURS CONNECTÉS
    // =================================================

    Route::get('/cours', [CoursController::class, 'index']);
    Route::get('/cours/{id}', [CoursController::class, 'show']);


    // =================================================
    // CHAT
    // =================================================

    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [ChatController::class, 'conversations']);
        Route::get('/messages/{userId}', [ChatController::class, 'messages']);
        Route::post('/send', [ChatController::class, 'sendMessage']);
        Route::put('/message/{id}/read', [ChatController::class, 'markAsRead']);
        Route::get('/non-lus', [ChatController::class, 'messagesNonLus']);
    });


    // =================================================
    // MEETINGS
    // =================================================

    Route::prefix('meetings')->group(function () {
        Route::get('/', [MeetingController::class, 'index']);
        Route::post('/', [MeetingController::class, 'store']);
        Route::get('/{id}', [MeetingController::class, 'show']);
        Route::put('/{id}', [MeetingController::class, 'update']);
        Route::delete('/{id}', [MeetingController::class, 'destroy']);
        Route::post('/{id}/inviter', [MeetingController::class, 'inviter']);
        Route::post('/{id}/repondre', [MeetingController::class, 'repondreInvitation']);
    });
});