<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // User registration
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
        ]);

        return response()->json(['message' => 'User registered successfully!', 'user' => $user]);
    }

    // User login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'ip_address' => 'required|ip',
        ], [
            'ip_address.ip' => 'No network address found in the request',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // assigning the ipAddress
        $user->ip_address = $request->ip_address;
        $user->save();


        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
            'userId' => Auth::id(),
            'expiresIn' => 60 * 120, // 120 minutes in seconds
        ]);
    }

    // User logout
    public function logout(Request $request)
    {
        $request->validate(['userEmail' => 'required|email']);

        $user = User::where('email', $request->userEmail)->first();
        if ($user) {
            $user->ip_address = null;
            $user->save();
            $user->tokens()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        }

        return response()->json(['error' => 'User not found'], 404);
    }
}
