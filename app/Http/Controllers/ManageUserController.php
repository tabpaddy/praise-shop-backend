<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class ManageUserController extends Controller
{
    // add a user
    public function addUser(Request $request)
    {
        // Debug the authenticated user
        $admin = auth('admin')->user();
        if (!$admin) {
            \Log::error('Unauthenticated admin.');
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Debug the isAdminOrSubAdmin method
        if (!$admin->isAdminOrSubAdmin()) {
            \Log::error('Unauthorized admin.', ['admin' => $admin]);
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create the user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
        ]);

        \Log::info('User created successfully.', ['user' => $user]);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 200);
    }


    // get all users
    public function user()
{
    $admin = auth('admin')->user(); // Authenticate admin

    // Check if admin is authorized
    if (!$admin || !$admin->isAdminOrSubAdmin()) {
        \Log::error('Unauthorized admin access.', ['admin' => $admin]);
        return response()->json(['message' => 'Unauthorized.'], 403);
    }

    // Fetch all users
    $users = User::all();

    return response()->json(['users' => $users]); // Note: Changed key to 'users'
}


    //delete a user
    public function deleteUser(Request $request)
    {
        // Ensure the request is from an authenticated admin (not sub-admin)
        if (!auth('admin')->check() || !auth('admin')->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->id);

        if ($user) {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully']);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }
}
