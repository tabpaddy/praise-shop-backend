<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderReceipt;

class SendOrderJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public $email;
    public $first_name;
    public $last_name;
    public $invoice_no;
    public $amount;
    public $order_status;
    public $payment_method;
    public $payment_status;
    public $items;
    public $payment_reference;

    public function __construct($email, $first_name, $last_name, $invoice_no, $amount, $order_status, $payment_method, $payment_status, $items, $payment_reference)
    {
        $this->email = $email;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->invoice_no = $invoice_no;
        $this->amount = $amount;
        $this->order_status = $order_status;
        $this->payment_method = $payment_method;
        $this->payment_status = $payment_status;
        $this->items = $items;
        $this->payment_reference = $payment_reference;

        Log::info('SendOrderJob constructed:', [
            'email' => $this->email,
            'items' => $this->items,
            'payment_reference' => $this->payment_reference
        ]);
    }

    public function handle(): void
    {
        $data = [
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'invoice_no' => $this->invoice_no,
            'amount' => $this->amount,
            'order_status' => $this->order_status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'items' => $this->items,
            'payment_reference' => $this->payment_reference,
            'url' => env('FRONTEND_URL')
        ];

        Log::info('SendOrderJob properties:', $data);

        $mailable = new OrderReceipt(
            $this->email, // Pass email first
            $this->first_name,
            $this->last_name,
            $this->invoice_no,
            $this->amount,
            $this->order_status,
            $this->payment_method,
            $this->payment_status,
            $this->items,
            $this->payment_reference,
            env('FRONTEND_URL')
        );

        // Set the recipient email dynamically
        //$mailable->to($this->email);

        Mail::send($mailable);

        Log::info('Mail data sent:', $data);
    }
}
