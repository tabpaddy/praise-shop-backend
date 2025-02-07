<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuthAdminController extends Controller
{
    // Admin Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => $admin,
        ]);
    }

    // Admin Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    // Create Sub-Admin
    public function createSubAdmin(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        if (!$admin->isAdmin()) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        // Ensure only Super Admin can create Sub-Admins
        if ($request->user()->subAdmin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins',
            'password' => 'required|string|min:8',
        ], [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.unique' => 'This email is already in use.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters long.',
        ]);

        $subAdmin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'subAdmin' => true, // Sub-admin status
        ]);

        Cache::forget('admin');

        return response()->json(['message' => 'Sub-admin created successfully', 'subAdmin' => $subAdmin], 200);
    }

    // get all SubAdmin
    public function subAdmin()
    {
        $admin = auth('admin')->user(); // authenticated admin

        // check if admin is authorized
        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        // fetch all subAdmin
        $subAdmin = Cache::remember('admin', 60, function () {
            return Admin::where('subAdmin', 1)->get();
        });

        return response()->json(['subAdmin' => $subAdmin]);
    }

    public function deleteSubAdmin($userId)
    {
        $admin = auth('admin')->user();

        // check if the authenticated admin is authorized
        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $subAdmin = Admin::find($userId);

        if ($subAdmin) {
            $subAdmin->delete();
            Cache::forget('admin');
            return response()->json(['message' => 'SubAdmin deleted successfully'], 200);
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }
    }
}
