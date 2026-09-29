<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\ContactMail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        Log::info('=== CONTACT FORM ===');
        Log::info('Données reçues:', $request->all());

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
            ],
            'subject' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'message' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
        ]);

        if ($validator->fails()) {
            Log::error(
                'Erreurs validation:',
                $validator->errors()->toArray()
            );

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Nettoyage des données
        $name = trim($request->input('name'));
        $email = strtolower(trim($request->input('email')));
        $subject = trim($request->input('subject'));
        $message = trim($request->input('message'));

        try {
            Log::info("Tentative d'envoi d'email", [
                'from' => $email,
                'name' => $name,
                'to' => 'nytionadigital@gmail.com',
            ]);

            Mail::to('nytionadigital@gmail.com')
                ->send(new ContactMail(
                    $name,
                    $email,
                    $subject,
                    $message
                ));

            Log::info('Email envoyé avec succès');

            return response()->json([
                'success' => true,
                'message' => 'Votre message a été envoyé avec succès.',
            ]);

        } catch (\Throwable $e) {
            Log::error('Mail error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi du message.',
            ], 500);
        }
    }
}