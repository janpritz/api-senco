<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Log, Auth};

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first(); // ✅ Works: Accessing array key

        // Check user exists and password is correct
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Incorrect Password!'
            ], 401);
        }

        if ($user?->suspended_at) {
            return response()->json([
                'message' => 'Your account has been suspended. Please contact the Admin.'
            ], 403); // 403 Forbidden
        }

        Auth::login($user);
        // Create a plain text token for the Next.js app to store
        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function showPasswordSetupForm(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired link'], 403);
        }

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        // Check if there is already a query string, then append user
        $queryString = $request->getQueryString();
        $redirectUrl = $frontendUrl . '/setup-password?' . $queryString . '&user=' . $user->id;

        return redirect($redirectUrl);
    }

    public function setupPassword(Request $request, User $user)
    {
        // 3. CRITICAL: Re-verify the signature before allowing the password change
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Unauthorized password reset attempt.'], 403);
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
            'email_verified_at' => now(), // Mark email as verified when password is set    
        ]);

        return response()->json(['message' => 'Your account is now ready! You can log in.']);
    }

    public function verifySignature(Request $request, User $user)
    {
        // 1. Get the current query string (expires, signature, etc.)
        $queryString = $request->getQueryString();

        // 2. Manually reconstruct the ORIGINAL URL that was in the email
        // This must match the route name 'password.setup'
        $originalPath = url("/api/password/setup/{$user->id}");
        $fullOriginalUrl = $originalPath . '?' . $queryString;

        // 3. Create a temporary request object to validate that specific URL
        $originalRequest = Request::create($fullOriginalUrl);

        if (! $originalRequest->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid signature.',
                'debug_original_url' => $fullOriginalUrl // Compare this to your email link!
            ], 403);
        }
        $params = $request->query();
        unset($params['user']);
        $originalUrl = url("/api/password/setup/{$user->id}") . '?' . http_build_query($params);

        if (! Request::create($originalUrl)->hasValidSignature()) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        // NEW: Check if user already set their password/verified their email
        if ($user->email_verified_at !== null) {
            return response()->json([
                'valid' => true,
                'is_active' => true // Tell frontend to skip the form
            ]);
        }

        return response()->json(['valid' => true, 'is_active' => false]);
    }
}
