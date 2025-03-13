<?php

namespace App\Http\Controllers;

use App\Jobs\SendOrderJob;
use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Order;

class PaystackController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('x-paystack-signature');
        $secret = env('PAYSTACK_SECRET_KEY');

        // Verify the signature
        $expectedSignature = hash_hmac('sha512', $payload, $secret);
        if ($signature !== $expectedSignature) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $event = json_decode($payload);
        if ($event->event === 'charge.success') {
            $orderId = $event->data->metadata->order_id;
            $order = Order::find($orderId);
            $order->update([
                'payment_status' => 'paid',
                'order_status' => 'processing'
            ]);

            Cart::where('user_id', $order->user_id)->delete();

            // Optionally dispatch a job to send a confirmation email
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
