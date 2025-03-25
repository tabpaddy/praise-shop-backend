<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\SendOrderJob;
use App\Models\Cart;
use App\Models\Order;
use Stripe\Webhook;
use Stripe\Stripe;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        Log::debug('Webhook Endpoint Hit:', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
        ]);

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        Log::debug('Webhook Raw Request:', [
            'payload' => $payload,
            'signature' => $sigHeader,
        ]);

        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');
        Log::info('Webhook Secret Value:', ['secret' => $webhookSecret]);

        if (!$webhookSecret) {
            Log::error('STRIPE_WEBHOOK_SECRET is not set in .env');
            return response()->json(['error' => 'Webhook secret missing'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            Log::error('Webhook Signature Verification Failed: ' . $e->getMessage(), [
                'signature' => $sigHeader,
                'secret' => $webhookSecret,
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        Log::info('Stripe Webhook Event Received:', [
            'type' => $event->type,
            'payment_intent_id' => $event->data->object->id,
            'status' => $event->data->object->status,
            'last_payment_error' => $event->data->object->last_payment_error ?? 'None',
        ]);

        $eventObject = $event->data->object;
        $orderId = null;

        // Extract order_id based on event type
        if ($event->type === 'charge.failed') {
            $paymentIntentId = $eventObject->payment_intent;
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            $orderId = $paymentIntent->metadata->order_id ?? null;
        } else {
            $orderId = $eventObject->metadata->order_id ?? null;
        }

        // Check if order exists before proceeding
        $order = Order::find($orderId);
        if (!$order && $event->type !== 'payment_intent.succeeded') {
            Log::warning('Order not found for event: ' . $event->type, [
                'order_id' => $orderId,
                'object_id' => $eventObject->id,
            ]);
            // Return success to acknowledge webhook, no further action needed
            return response()->json(['status' => 'success']);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
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
                    json_decode($order->items, true)
                );
                Log::info('Order processed successfully: ' . $order->id);
                break;

            case 'payment_intent.payment_failed':
                $order->delete();
                Log::info('Order deleted due to payment failure: ' . $order->id);
                break;

            case 'charge.failed':
                // Skip deletion if already handled by payment_intent.payment_failed
                Log::info('Charge failed event processed, order already deleted or not applicable: ' . $orderId);
                break;

            case 'payment_intent.updated':
                if ($eventObject->status === 'requires_payment_method' && $eventObject->last_payment_error) {
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
