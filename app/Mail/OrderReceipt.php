<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public $email; // Add this property
    public $first_name;
    public $last_name;
    public $invoice_no;
    public $amount;
    public $order_status;
    public $payment_method;
    public $payment_status;
    public $items;
    public $payment_reference;
    public $url;


    public function __construct($email, $first_name, $last_name, $invoice_no, $amount, $order_status, $payment_method, $payment_status, $items, $payment_reference, $url)
    {
        $this->email = $email; // Assign email
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->invoice_no = $invoice_no;
        $this->amount = $amount;
        $this->order_status = $order_status;
        $this->payment_method = $payment_method;
        $this->payment_status = $payment_status;
        $this->items = $items;
        $this->payment_reference = $payment_reference;
        $this->url = $url;
    }

    public function build()
    {
        return $this->to($this->email) // Now this works because $email is a property
                    ->subject('Your Payment Receipt')
                    ->view('emails.receptMail');
    }
}
