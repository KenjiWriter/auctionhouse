<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function loginPage()
    {
        return Inertia::render('Auth/Login');
    }

    public function sendMagicLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['name' => 'New User', 'password' => null] // Temporary name
        );

        // Generate signed URL
        $url = URL::temporarySignedRoute(
            'login.verify',
            now()->addMinutes(30),
            ['user' => $user->id]
        );

        if (app()->environment('local')) {
            return Inertia::location($url);
        }

        // In a real app, send this via email.
        // Mail::to($user->email)->send(new MagicLink($url));
        
        return back()->with('status', 'We have emailed you a magic link! (Check logs/dev)');
    }

    public function verifyMagicLink(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        Auth::login($user);

        if (!$user->password || !$user->phone) {
            return redirect()->route('register.complete');
        }

        return redirect()->intended('/dashboard');
    }

    public function completeProfilePage()
    {
        return Inertia::render('Auth/CompleteProfile');
    }

    public function completeProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|confirmed|min:8',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:255',
            'avatar' => 'nullable|image|max:2048', // Max 2MB
            'avatar_preset' => 'nullable|string',
        ]);

        $user = $request->user();
        $user->update([
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->storePublicly(
                'avatars/' . $user->id,
                ['disk' => 'public']
            );
            $user->update([
                'avatar_path' => $path,
                'avatar_preset' => null,
            ]);
        } elseif ($request->avatar_preset) {
             // Delete old avatar if exists
             if ($user->avatar_path) {
                 Storage::disk('public')->delete($user->avatar_path);
             }
             $user->update([
                 'avatar_path' => null,
                 'avatar_preset' => $request->avatar_preset,
             ]);
        }

        return redirect()->route('home');
    }
}
