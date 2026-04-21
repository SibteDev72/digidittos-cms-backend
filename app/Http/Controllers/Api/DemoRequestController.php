<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\DemoRequestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class DemoRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|max:255',
            'message'    => 'nullable|string|max:2000',
        ]);

        try {
            Mail::to('info@zetaver.com')->send(new DemoRequestMail($validated));

            return response()->json(['message' => 'Demo request sent successfully.']);
        } catch (\Exception $e) {
            Log::error('Demo request email failed: ' . $e->getMessage());

            return response()->json(['message' => 'Failed to send demo request. Please try again later.'], 500);
        }
    }
}
