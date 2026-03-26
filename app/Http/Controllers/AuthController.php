<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid email or password.',
                    'errors' => [
                        'email' => ['Invalid email or password.'],
                    ],
                ], 422);
            }

            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged in successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'role' => ['required', Rule::in(['customer', 'provider'])],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'min:6'],
            'bio' => ['nullable', 'string'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'service_area' => ['nullable', 'string', 'max:255'],
            'availability' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => $data['role'],
            'city' => $data['city'],
            'address' => $data['address'],
            'password' => Hash::make($data['password']),
        ]);

        if ($user->isProvider()) {
            Provider::create([
                'user_id' => $user->id,
                'bio' => $data['bio'] ?? 'Newly onboarded service provider.',
                'experience_years' => $data['experience_years'] ?? 0,
                'hourly_rate' => $data['hourly_rate'] ?? 0,
                'service_area' => $data['service_area'] ?? $data['city'],
                'availability' => $data['availability'] ?? 'Mon-Sat, 10 AM - 7 PM',
                'approved_at' => null,
                'is_featured' => false,
            ]);
        }

        Auth::login($user);

        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your account has been created successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Your account has been created successfully.');
    }

    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged out successfully.',
                'redirect' => route('home'),
            ]);
        }

        return redirect()->route('home');
    }
}
