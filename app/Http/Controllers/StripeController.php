<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\SendOrderJob;
use App\Models\Order;
use Stripe\Webhook;

class StripeController extends Controller
{
    //
    public function handleWebhook(Request $request)
{
    $payload = $request->getContent();
    $sigHeader = $request->header('Stripe-Signature');
    
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sigHeader, env('STRIPE_WEBHOOK_SECRET')
        );
    } catch (\Exception $e) {
        return response()->json(['error' => 'Invalid signature'], 403);
    }

    if ($event->type === 'payment_intent.succeeded') {
        $paymentIntent = $event->data->object;
        $order = Order::find($paymentIntent->metadata->order_id);
        
        $order->update([
            'payment_status' => 'paid',
            'order_status' => 'processing'
        ]);
        
        SendOrderJob::dispatch($order->deliveryInformation->email, $order->product->name, $order->invoice_no, $order->amount, $order->quantity, $order->size, $order->order_status, $order->payment_method, $order->payment_status);
    }

    return response()->json(['status' => 'success']);
}
}
