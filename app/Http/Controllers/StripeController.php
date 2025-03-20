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

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                env('STRIPE_WEBHOOK_SECRET')
            );
        } catch (\Exception $e) {
            Log::error('Stripe Webhook Signature Error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        Log::info('Stripe Webhook Event Received: ' . $event->type, [
            'payment_intent_id' => $event->data->object->id,
            'status' => $event->data->object->status ?? 'N/A'
        ]);

        $paymentIntent = $event->data->object;
        $order = Order::find($paymentIntent->metadata->order_id);

        if (!$order) {
            Log::warning('Order not found for PaymentIntent: ' . $paymentIntent->id);
            return response()->json(['error' => 'Order not found'], 404);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $order->update([
                    'payment_status' => 'paid',
                    'order_status' => 'processing'
                ]);
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
            case 'charge.failed':
                // Delete order if payment fails or charge fails
                $order->delete();
                Log::info('Order deleted due to payment/charge failure: ' . $order->id);
                break;

            case 'payment_intent.created':
            case 'payment_intent.updated':
                // Check status and last_payment_error for failure
                if ($paymentIntent->status === 'requires_payment_method' && $paymentIntent->last_payment_error) {
                    $order->delete();
                    Log::info('Order deleted due to payment error (requires_payment_method): ' . $order->id);
                }
                break;

            default:
                Log::info('Unhandled Stripe event type: ' . $event->type);
                break;
        }

        return response()->json(['status' => 'success']);
    }
}