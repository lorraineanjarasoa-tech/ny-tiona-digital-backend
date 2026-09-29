<?php
// app/Http/Controllers/Api/NewsletterController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\NewsletterSubscriber;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255|unique:newsletter_subscribers,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email est déjà inscrit ou invalide.'
            ], 422);
        }

        try {
            $subscriber = NewsletterSubscriber::create([
                'email' => $request->email,
                'subscribed_at' => now(),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vous êtes maintenant abonné à notre newsletter.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Newsletter error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer.'
            ], 500);
        }
    }
}