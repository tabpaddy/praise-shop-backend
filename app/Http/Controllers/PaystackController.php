<?php

namespace App\Http\Controllers;

use App\Jobs\SendOrderJob;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            Log::error('Paystack Webhook: Invalid signature', [
                'received' => $signature,
                'expected' => $expectedSignature,
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $event = json_decode($payload, false);
        if (!$event || !isset($event->event)) {
            Log::error('Paystack Webhook: Invalid payload', ['payload' => $payload]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $orderId = $event->data->metadata->order_id ?? null;
        $order = Order::find($orderId);

        if (!$order) {
            Log::warning('Paystack Webhook: Order not found', [
                'order_id' => $orderId,
                'event' => $event->event,
            ]);
            return response()->json(['status' => 'order not found'], 404);
        }

        switch ($event->event) {
            case 'charge.success':
                $order->update([
                    'payment_status' => 'paid',
                    'order_status' => 'processing',
                ]);
                Cart::where('user_id', $order->user_id)->delete();

                $order = $order->fresh(); // Refresh to ensure latest data is sent to the job

                SendOrderJob::dispatch(
                    $order->deliveryInformation->email,
                    $order->deliveryInformation->first_name,
                    $order->deliveryInformation->last_name,
                    $order->invoice_no,
                    $order->amount,
                    $order->order_status,
                    $order->payment_method,
                    $order->payment_status,
                    json_decode($order->items, true),
                    $order->payment_reference,
                );
                Log::info('Paystack Webhook: Order processed successfully', [
                    'order_id' => $order->id,
                ]);
                break;

            case 'charge.failed':
                $order->delete();
                Log::info('Paystack Webhook: Order deleted due to payment failure', [
                    'order_id' => $orderId,
                ]);
                break;

            default:
                Log::info('Paystack Webhook: Unhandled event type', [
                    'event' => $event->event,
                ]);
                break;
        }

        return response()->json(['status' => 'success']);
    }
}
