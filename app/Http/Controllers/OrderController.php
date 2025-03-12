<?php

namespace App\Http\Controllers;

use App\Jobs\SendOrderJob;
use App\Models\Cart;
use App\Models\DeliveryInformation;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class OrderController extends Controller
{
    //
    public function store(Request $request)
    {
        $user = auth()->user();

        // Validate request data
        $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zipCode' => 'required|string|max:20',
            'country' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'paymentMethod' => 'required|in:cod,paystack,stripe',
            'total' => 'required|numeric|min:0',
        ]);

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

        // Fetch cart items
        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        // Validate total
        $subtotal = $cartItems->sum(fn($item) => $item->product->price * $item->quantity);
        $shippingFee = $subtotal * 0.1; // Matches frontend
        $calculatedTotal = $subtotal + $shippingFee;

        if (abs($calculatedTotal - $request->total) > 0.01) {
            return response()->json(['error' => 'Total amount mismatch'], 400);
        }

        // Create order
        $order = Order::create([
            'user_id' => $user->id,
            'delivery_information_id' => $deliveryInfo->id,
            'amount' => $calculatedTotal,
            'invoice_no' => 'ORD-' . Str::upper(Str::random(8)),
            'payment_method' => $request->paymentMethod,
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'items' => $cartItems->map(fn($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'size' => $item->size,
                'price' => $item->product->price,
            ])->toJson(),
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
                // Clear cart
                Cart::where('user_id', $order->user_id)->delete();
                SendOrderJob::dispatch(
                    $order->deliveryInformation->email,
                    $order->deliveryInformation->first_name,
                    $order->deliveryInformation->last_name,
                    json_decode($order->items, true), // Pass items array
                    $order->invoice_no,
                    $order->amount,
                    $order->order_status,
                    $order->payment_method,
                    $order->payment_status
                );
                return response()->json(['redirect' => '/order-success']);

            case 'paystack':
                return $this->handlePaystackPayment($order);

            case 'stripe':
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
        $reference = Str::uuid(); // Generate unique reference

        // Initialize Paystack transaction
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'amount' => $order->amount * 100, //to support kobo
                'email' => $order->deliveryInformation->email,
                'reference' => $reference,
                'currency' => 'NGN',
                'callback_url' => route('payment.callback'),
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id
                ]
            ]);

        $paymentData = $response->json();

        if (!$paymentData['status']) {
            return response()->json(['error' => 'Payment initialization failed'], 500);
        }

        // Store Paystack reference in order
        $order->update(['payment_reference' => $reference]);

        return response()->json([
            'payment_url' => $paymentData['data']['authorization_url']
        ]);
    }

    /**
     * Handle Paystack's payment callback
     */

    public function handlePaymentCallback(Request $request)
    {
        $reference = $request->query('reference');

        // Verify transaction with Paystack
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->get("https://api.paystack.co/transaction/verify/$reference");

        $paymentDetails = $response->json();

        if ($paymentDetails['data']['status'] === 'success') {
            $order = Order::where('payment_reference', $reference)->first();
            $order->update([
                'payment_status' => 'paid',
                'order_status' => 'processing'
            ]);

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

            return redirect('/order-success');
        }

        return redirect('/payment-failed');
    }

    // public function handlePaymentCallback(Request $request)
    // {
    //     $paymentDetails = Paystack::getPaymentData();

    //     // For demonstration:
    //     // $paymentDetails = [
    //     //     'data' => [
    //     //         'status'   => 'success',
    //     //         'metadata' => ['order_id' => 1]
    //     //     ]
    //     // ];

    //     if ($paymentDetails['data']['status'] === 'success') {
    //         $order = Order::find($paymentDetails['data']['metadata']['order_id']);
    //         $order->update([
    //             'payment_status' => 'paid',
    //             'order_status' => 'processing'
    //         ]);

    //         SendOrderJob::dispatch($order->deliveryInformation->email, $order->deliveryInformation->first_name, $order->deliveryInformation->last_name, $order->product->name, $order->invoice_no, $order->amount, $order->quantity, $order->size, $order->order_status, $order->payment_method, $order->payment_status);

    //         return redirect('/order-success');
    //     }

    //     return redirect('/payment-failed');
    // }

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
            return;
        } else {
            return response()->json(['deliveryInfo' => $deliveryInfo], 200);
        }
    }
}
