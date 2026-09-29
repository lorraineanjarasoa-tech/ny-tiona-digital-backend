<?php

namespace App\Services;

use App\Mail\InscriptionStatusEmail;
use App\Mail\VerificationEmail;
use App\Mail\WelcomeEmail;
use App\Models\Inscription;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthService
{
    public function createUser(array $data): User
    {
        return User::create([
            'id' => (string) Str::uuid(),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_validated' => in_array(
                $data['role'],
                ['admin', 'formateur']
            ),
            'is_active' => true,
            'verification_token' => Str::random(60),
        ]);
    }

    public function createProfile(
        User $user,
        array $data
    ): UserProfile {
        return UserProfile::create([
            'user_id' => $user->id,
            'nom_complet' => $data['nom_complet'],
            'adresse' => $data['adresse'] ?? null,
            'contact' => $data['contact'] ?? null,
            'date_naissance' => $data['date_naissance'] ?? null,
            'sexe' => $data['sexe'] ?? null,
            'cin' => $data['cin'] ?? null,
            'derniere_etude' => $data['derniere_etude'] ?? null,
            'preference_cours' => $data['preference_cours'] ?? null,
            'specialite' => $data['cours_enseignes'] ?? null,
        ]);
    }

    public function handleEtudiantRegistration(
        User $user,
        Request $request
    ): void {
        $profile = $user->profile;

        if (!$profile) {
            throw new \RuntimeException(
                'Profil utilisateur introuvable.'
            );
        }

        $updates = [];

        /*
        |--------------------------------------------------------------------------
        | CIN RECTO
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('cin_recto')) {
            $updates['cin_recto'] = $request
                ->file('cin_recto')
                ->store('inscriptions/cin', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | CIN VERSO
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('cin_verso')) {
            $updates['cin_verso'] = $request
                ->file('cin_verso')
                ->store('inscriptions/cin', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | DIPLOME
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('diplome')) {
            $diplomePath = $request
                ->file('diplome')
                ->store('inscriptions/diplomes', 'public');

            $updates['diplomes'] = [$diplomePath];
        }

        /*
        |--------------------------------------------------------------------------
        | PREUVE DE PAIEMENT
        |--------------------------------------------------------------------------
        */

        $paiementPath = null;

        if ($request->hasFile('preuve_paiement')) {
            $paiementPath = $request
                ->file('preuve_paiement')
                ->store('inscriptions/paiements', 'public');

            $updates['preuve_paiement'] = $paiementPath;
        }

        /*
        |--------------------------------------------------------------------------
        | REFERENCE BANCAIRE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('reference_bancaire')) {
            $updates['reference_bancaire'] =
                $request->input('reference_bancaire');
        }

        if (!empty($updates)) {
            $profile->update($updates);
        }

        /*
        |--------------------------------------------------------------------------
        | INSCRIPTION
        |--------------------------------------------------------------------------
        */

        if ($paiementPath) {
            Inscription::create([
                'etudiant_id' => $user->id,
                'formation_id' => $request->input('formation_id'),
                'preuve_paiement' => $paiementPath,
                'reference_bancaire' =>
                    $request->input('reference_bancaire'),
                'statut' => 'en_attente',
                'date_inscription' => now(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | EMAIL
        |--------------------------------------------------------------------------
        |
        | On met l'envoi en queue pour éviter de bloquer /register.
        |
        */

        Mail::to($user->email)
            ->queue(
                new InscriptionStatusEmail(
                    $user,
                    'en_attente'
                )
            );
    }

    public function sendVerificationEmail(User $user): void
    {
        Mail::to($user->email)
            ->queue(
                new VerificationEmail($user)
            );
    }

    public function sendWelcomeEmail(User $user): void
    {
        Mail::to($user->email)
            ->queue(
                new WelcomeEmail($user)
            );
    }

    public function verifyEmail(string $token): ?User
    {
        $user = User::where(
            'verification_token',
            $token
        )->first();

        if (!$user) {
            return null;
        }

        $user->update([
            'email_verified_at' => now(),
            'verification_token' => null,
        ]);

        $this->sendWelcomeEmail($user);

        return $user;
    }
}