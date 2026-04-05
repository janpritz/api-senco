<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Jobs\SendInvitationEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function store(UserStoreRequest $request)
    {
        $fields = $request->validated();

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'role' => $fields['role'],
            'created_by' => Auth::id(),
        ]);

        //Send email to user as invitation to be in finance committee.
        SendInvitationEmail::dispatch($user);

        return response()->json([
            'message' => 'New committee member added successfully',
            'user' => $user
        ], 201);
    }

    public function index()
    {
        // Return all users with specific roles or all users
        return response()->json(User::all());
    }

    public function resendInvite(User $user)
    {
        // Safety check: Don't resend if already verified
        if ($user->email_verified_at) {
            return response()->json(['message' => 'User is already verified.'], 422);
        }

        // Dispatch the job again
        SendInvitationEmail::dispatch($user);

        return response()->json(['message' => 'Invitation resent successfully.']);
    }

    // Toggle Suspension
    public function suspend(User $user)
    {
        // Guard: ID 1 is protected
        if ($user->id === 1) {
            return response()->json(['error' => 'Root admin cannot be suspended'], 403);
        }

        $user->suspended_at = $user->suspended_at ? null : now();
        $user->save();

        return response()->json(['success' => true, 'suspended' => !!$user->suspended_at]);
    }

    // Soft Delete User
    public function destroy(User $user)
    {
        if ($user->id === 1) {
            return response()->json(['error' => 'Root admin cannot be deleted'], 403);
        }

        $user->delete(); // This sets deleted_at, doesn't wipe the row
        return response()->json(['success' => true]);
    }
}
