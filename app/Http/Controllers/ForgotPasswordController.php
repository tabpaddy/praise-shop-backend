<?php

namespace App\Http\Controllers;

use App\Jobs\SendResetPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    //
    public function sendRestLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // check if the email is already in user table
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'Email not Found'], 404);
        }

        // Generate token
        $token = Str::random(60);


        // Delete expired tokens and ensure unique email
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->orWhere('created_at', '<', Carbon::now()->subMinutes(60))
            ->delete();

        // Store the token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );


        // Generate the reset link
        $resetUrl = config('app.frontend_url', 'http://localhost:5173')
            . '/reset-password?token=' . $token
            . '&email=' . urlencode($request->email);

        // Dispatch the mail job
        SendResetPasswordMail::dispatch($request->email, $resetUrl, $user->name);

        return response()->json(['message' => 'Reset password link sent to your email'], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed'
        ]);

        // validate the token
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$passwordReset) {
            return response()->json(['error' => 'Invalid Token'], 400);
        }

        // check if the token has expired
        if (Carbon::parse($passwordReset->created_at)->addMinutes(60)->isPast()) {
            return response()->json(['error' => 'Token Expired'], 400);
        }

        // update the user password
        DB::table('users')->where('email', $request->email)->update([
            'password' => bcrypt($request->password),
        ]);

        // delete the token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password Reset Successful']);
    }
}
