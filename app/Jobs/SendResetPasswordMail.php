<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendResetPasswordMail implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    protected $email;
    protected $token;
    protected $name;

    public function __construct($email, $token, $name)
    {
        $this->email = $email;
        $this->token = $token;
        $this->name = $name;
    }

    public function handle()
    {
        // Log the mail config for debugging
        Log::info('Mail config in queue: ', config('mail.mailers.smtp'));

        try {
            Mail::send('emails.passwordReset', [
                'resetUrl' => $this->token,
                'name' => $this->name
            ], function ($message) {
                $message->to($this->email)
                    ->subject('Password Reset Request');
            });
            Log::info('Password reset email sent to: ' . $this->email);
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email: ' . $e->getMessage());
            throw $e; // Re-throw to mark the job as failed
        }
    }
}
