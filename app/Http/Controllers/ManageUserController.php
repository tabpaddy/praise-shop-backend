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
            // \Log::error('Unauthenticated admin.');
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Debug the isAdminOrSubAdmin method
        if (!$admin->isAdminOrSubAdmin()) {
            // \Log::error('Unauthorized admin.', ['admin' => $admin]);
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

        // \Log::info('User created successfully.', ['user' => $user]);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 200);
    }


    // get all users
    public function user()
    {
        $admin = auth('admin')->user(); // Authenticate admin

        // Check if admin is authorized
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            // \Log::error('Unauthorized admin access.', ['admin' => $admin]);
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Fetch all users
        $users = User::all();

        return response()->json(['users' => $users]); // Note: Changed key to 'users'
    }


    // delete a user
    public function deleteUser(Request $request, $userId)
    {
        // Log the incoming request and authentication details
        // Log::debug('Incoming request token:', ['Authorization' => $request->header('Authorization')]);

        $admin = auth('admin')->user();
        // Log::debug('Authenticated admin:', ['admin' => $admin]);

        // Check if the authenticated admin is authorized
        if (!$admin || !$admin->isAdmin()) {
            // Log::debug('Unauthorized admin access.', ['admin' => $admin]);
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Find the user by ID
        $user = User::find($userId);

        if ($user) {
            $user->delete();
            // Log::info('User deleted successfully.', ['user_id' => $userId]);
            return response()->json(['message' => 'User deleted successfully'], 200);
        } else {
            // Log::error('User not found.', ['user_id' => $userId]);
            return response()->json(['error' => 'User not found'], 404);
        }
    }
}
