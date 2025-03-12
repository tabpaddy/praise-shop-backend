<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\SendOrderJob;
use App\Models\Cart;
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

        Cart::where('user_id', $order->user_id)->delete();
        
        SendOrderJob::dispatch(
            $order->deliveryInformation->email,
            $order->deliveryInformation->first_name,
            $order->deliveryInformation->last_name,
            json_decode($order->items, true),
            $order->invoice_no,
            $order->amount,
            $order->order_status,
            $order->payment_method,
            $order->payment_status
        );
    }

    return response()->json(['status' => 'success']);
}
}
