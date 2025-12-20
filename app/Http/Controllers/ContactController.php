<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        $validated['status'] = 'new';

        $contactMessage = ContactMessage::create($validated);

        // Check if this is an API request
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you for your message! We will get back to you within 24-48 hours.',
                'data' => $contactMessage,
            ], 201);
        }

        return redirect()->back()->with('success', 'Thank you for your message! We will get back to you within 24-48 hours.');
    }
}
