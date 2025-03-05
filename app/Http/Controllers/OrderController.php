<?php

namespace App\Http\Controllers;

use App\Models\DeliveryInformation;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class OrderController extends Controller
{
    //
    public function store(Request $request)
    {
        $user = auth()->user();

        // Get or create delivery information
        $deliveryInfo = DeliveryInformation::firstOrNew(
            ['user_id' => $user->id],
        );

        $deliveryInfo->fill(
            [
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'email' => $request->email,
                'street' => $request->street,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zipCode,
                'country' => $request->country,
                'phone' => $request->phone
            ]
        );

        $deliveryInfo->save();

        // Create order
        $order = Order::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
            'delivery_information_id' => $deliveryInfo->id,
            'amount' => $request->total,
            'qty' => $request->quantity,
            'size' => $request->size,
            'invoice_no' => 'ORD-' . Str::upper(Str::random(8)),
            'payment_method' => $request->paymentMethod,
            'order_status' => 'pending'
        ]);

        // Kick off the payment flow
        return $this->handlePayment($order, $request->paymentMethod);
    }

    /**
     * Decide how to handle the chosen payment method.
     */
    private function handlePayment(Order $order, $method)
    {
        switch ($method) {
            case 'cod':
                $order->update(['payment_status' => 'pending']);
                return response()->json(['redirect' => '/order-success']);

            case 'paystack':
                return $this->handlePaystackPayment($order);

            case 'stripe':
                // Keep existing Stripe implementation
                return $this->handleStripePayment($order);

            default:
                return response()->json([
                    'error' => 'Unsupported payment method'
                ], 400);
        }
    }

    /**
     * Paystack Payment Logic
     */

    private function handlePaystackPayment(Order $order)
    {
        $paymentData = [
            'amount' => $order->amount * 100, // Paystack expects kobo
            'email' => $order->deliveryInformation->email,
            'reference' => Str::uuid(),
            'currency' => 'NGN',
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id
            ]
        ];

        return response()->json([
            'payment_url' => 'https://checkout.paystack.com/' . env('PAYSTACK_PUBLIC_KEY'),
            'payment_data' => $paymentData,
            'callback_url' => route('payment.callback')
        ]);
    }

    /**
     * Handle Paystack's payment callback
     */

    public function handlePaymentCallback(Request $request)
    {
        $paymentDetails = Paystack::getPaymentData();

        // For demonstration:
        // $paymentDetails = [
        //     'data' => [
        //         'status'   => 'success',
        //         'metadata' => ['order_id' => 1]
        //     ]
        // ];

        if ($paymentDetails['data']['status'] === 'success') {
            $order = Order::find($paymentDetails['data']['metadata']['order_id']);
            $order->update([
                'payment_status' => 'paid',
                'order_status' => 'processing'
            ]);

            return redirect('/order-success');
        }

        return redirect('/payment-failed');
    }

    /**
     * Stripe Payment Logic
     */
    private function handleStripePayment(Order $order)
    {
        // Set your Stripe secret key
        Stripe::setApiKey(config('services.stripe.secret'));

        // Create a PaymentIntent
        $paymentIntent = PaymentIntent::create([
            'amount' => $order->amount * 100, // Convert to kobo
            'currency' => 'ngn', // or 'usd',
            'payment_method_types' => ['card'],
            'metadata' => [
                'order_id' => $order->id
            ]
        ]);

        // Return the client secret so the frontend can confirm the payment
        return response()->json([
            'clientSecret' => $paymentIntent->client_secret,
            'paymentMethod' => 'stripe'
        ]);
    }

    public function deliveryInformation()
    {
        $user = auth()->user();

        $deliveryInfo = DeliveryInformation::where('user_id', $user->id)->first();

        if (!$deliveryInfo) {
            return response()->json(['message' => 'No delivery information found'], 404);
        } else {
            return response()->json(['deliveryInfo' => $deliveryInfo], 200);
        }
    }
}
