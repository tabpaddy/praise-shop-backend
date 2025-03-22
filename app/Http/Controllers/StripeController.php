<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\SendOrderJob;
use App\Models\Cart;
use App\Models\Order;
use Stripe\Webhook;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        // Use the webhook secret from your .env
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            Log::error('Webhook Signature Verification Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Log the event for debugging
        Log::info('Stripe Webhook Event Received:', [
            'type' => $event->type,
            'payment_intent_id' => $event->data->object->id,
            'status' => $event->data->object->status,
        ]);

        // Process the event (your existing logic)
        $paymentIntent = $event->data->object;
        $order = Order::find($paymentIntent->metadata->order_id);

        if (!$order) {
            Log::warning('Order not found for PaymentIntent: ' . $paymentIntent->id);
            return response()->json(['error' => 'Order not found'], 404);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                // Handle success
                $order->update(['payment_status' => 'paid', 'order_status' => 'processing']);
                Cart::where('user_id', $order->user_id)->delete();
                SendOrderJob::dispatch(
                    $order->deliveryInformation->email,
                    $order->deliveryInformation->first_name,
                    $order->deliveryInformation->last_name,
                    $order->invoice_no,
                    $order->amount,
                    $order->order_status,
                    $order->payment_method,
                    $order->payment_status,
                    json_decode($order->items, true), // Pass items array
                );
                Log::info('Order processed successfully: ' . $order->id);
                break;

            case 'payment_intent.payment_failed':
            case 'charge.failed':
                $order->delete();
                Log::info('Order deleted due to payment/charge failure: ' . $order->id);
                break;

            case 'payment_intent.updated':
                if ($paymentIntent->status === 'requires_payment_method' && $paymentIntent->last_payment_error) {
                    $order->delete();
                    Log::info('Order deleted due to payment error: ' . $order->id);
                }
                break;

            default:
                Log::info('Unhandled event type: ' . $event->type);
                break;
        }

        return response()->json(['status' => 'success']);
    }
}
