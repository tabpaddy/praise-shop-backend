<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

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
        Mail::send('emails.passwordReset', [
            'resetUrl' => $this->token,
            'name' => $this->name
        ], function ($message) {
            $message->to($this->email)
                ->subject('Password Reset Request');
        });
    }
}
