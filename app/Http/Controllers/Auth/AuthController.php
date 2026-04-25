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
        /**
         * 1. Reconstruct the Signed URL for Verification
         * Just like in the verifySignature method, we must validate 
         * against the full original signed path, not just the POST body.
         */
        $queryString = $request->getQueryString();
        $originalPath = route('password.setup', ['user' => $user->id], false);
        $fullOriginalUrl = config('app.url') . $originalPath . '?' . $queryString;

        if (! Request::create($fullOriginalUrl)->hasValidSignature()) {
            return response()->json([
                'message' => 'Unauthorized password setup attempt.',
                'debug' => 'Signature mismatch or expired.'
            ], 403);
        }

        // 2. Validation
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 3. Update User
        $user->update([
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        return response()->json(['message' => 'Your account is now ready! You can log in.']);
    }

    public function verifySignature(Request $request, User $user)
    {
        /**
         * 1. Reconstruct the base URL accurately.
         * Ensure your .env APP_URL matches the URL being used in the email.
         * If the email uses 'https', your APP_URL must be 'https'.
         */
        $originalPath = route('password.setup', ['user' => $user->id], false);
        $baseUrl = config('app.url') . $originalPath;

        // 2. Attach the query parameters exactly as they arrived
        $fullOriginalUrl = $baseUrl . '?' . $request->getQueryString();

        // 3. Create the temporary request for validation
        $originalRequest = Request::create($fullOriginalUrl);

        if (! $originalRequest->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid signature.',
                'debug' => [
                    'reconstructed' => $fullOriginalUrl,
                    'app_url_setting' => config('app.url'),
                    'query_string' => $request->getQueryString()
                ]
            ], 403);
        }

        /**
         * 4. Check User State
         * If the signature is valid, check if they have already completed the process.
         */
        if ($user->email_verified_at !== null) {
            return response()->json([
                'valid' => true,
                'is_active' => true
            ]);
        }

        return response()->json([
            'valid' => true,
            'is_active' => false
        ]);
    }
}
