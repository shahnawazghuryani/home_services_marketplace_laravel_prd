<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\User;
use App\Services\ContentSafety;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(private readonly ContentSafety $contentSafety)
    {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function state(Request $request): JsonResponse
    {
        return response()->json([
            'logged_in' => (bool) $request->user(),
            'role' => $request->user()?->role,
        ]);
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'redirect_to' => ['nullable', 'string', 'max:500'],
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

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

        $redirectTo = $this->safeRedirect($request->input('redirect_to'));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged in successfully.',
                'redirect' => $redirectTo ?? route('dashboard'),
            ]);
        }

        return redirect()->to($redirectTo ?? route('dashboard'));
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
            'redirect_to' => ['nullable', 'string', 'max:500'],
        ]);

        if (($data['role'] ?? null) === 'provider') {
            $this->contentSafety->ensureCleanText([
                'bio' => $data['bio'] ?? '',
                'service_area' => $data['service_area'] ?? '',
            ]);
        }

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
            $provider = Provider::create([
                'user_id' => $user->id,
                'bio' => $data['bio'] ?? 'Newly onboarded service provider.',
                'experience_years' => $data['experience_years'] ?? 0,
                'hourly_rate' => $data['hourly_rate'] ?? 0,
                'service_area' => $data['service_area'] ?? $data['city'],
                'availability' => $data['availability'] ?? 'Mon-Sat, 10 AM - 7 PM',
                'approved_at' => null,
                'is_featured' => false,
            ]);

            $this->notifyAdminForProviderApproval($provider);
        }

        Auth::login($user);

        $request->session()->regenerate();

        $redirectTo = $this->safeRedirect($request->input('redirect_to'));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your account has been created successfully.',
                'redirect' => $redirectTo ?? route('dashboard'),
            ]);
        }

        return redirect()->to($redirectTo ?? route('dashboard'))->with('success', 'Your account has been created successfully.');
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

    private function safeRedirect(?string $redirectTo): ?string
    {
        if (! $redirectTo) {
            return null;
        }

        if (! str_starts_with($redirectTo, '/')) {
            return null;
        }

        if (str_starts_with($redirectTo, '//')) {
            return null;
        }

        return $redirectTo;
    }

    private function notifyAdminForProviderApproval(Provider $provider): void
    {
        $adminEmail = (string) env('ADMIN_APPROVAL_EMAIL', 'shahnawazghuryani@gmail.com');
        if ($adminEmail === '') {
            return;
        }

        $provider->loadMissing('user');

        $approveUrl = URL::temporarySignedRoute(
            'admin.providers.approval-link',
            now()->addDays(7),
            ['provider' => $provider->id, 'action' => 'approve']
        );

        $deactivateUrl = URL::temporarySignedRoute(
            'admin.providers.approval-link',
            now()->addDays(7),
            ['provider' => $provider->id, 'action' => 'deactivate']
        );

        Mail::send('emails.provider-approval-request', [
            'provider' => $provider,
            'user' => $provider->user,
            'approveUrl' => $approveUrl,
            'deactivateUrl' => $deactivateUrl,
        ], function ($message) use ($adminEmail, $provider): void {
            $message->to($adminEmail)
                ->subject('New provider approval request: ' . $provider->user->name);
        });
    }
}
