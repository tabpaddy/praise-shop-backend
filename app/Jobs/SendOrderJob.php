<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    protected $email;
    protected $first_name;
    protected $last_name;
    protected $product_name;
    protected $invoice_no;
    protected $amount;
    protected $quantity;
    protected $size;
    protected $order_status;
    protected $payment_method;
    protected $payment_status;

    /**
     * Create a new job instance.
     */
    public function __construct($email, $first_name, $last_name, $product_name, $invoice_no, $amount, $quantity, $size, $order_status, $payment_method, $payment_status)
    {
        //
        $this->email = $email;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->product_name = $product_name;
        $this->invoice_no = $invoice_no;
        $this->amount = $amount;
        $this->quantity = $quantity;
        $this->size = $size;
        $this->order_status = $order_status;
        $this->payment_method = $payment_method;
        $this->payment_status = $payment_status;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        Mail::send('emails.receptMail', [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'product_name' => $this->product_name,
            'invoice_no' => $this->invoice_no,
            'amount' => $this->amount,
            'quantity' => $this->quantity,
            'size' => $this->size,
            'order_status' => $this->order_status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status
        ], function ($message) {
            $message->to($this->email)->subject('Your Payment Recept');
        });
    }
}
