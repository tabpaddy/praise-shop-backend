<?php

namespace App\Http\Controllers;

use App\Jobs\SendOrderJob;
use App\Models\Cart;
use App\Models\DeliveryInformation;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
            'items' => 'required|json'
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

        $items = json_decode($request->input('items'), true);
        $frontendTotal = (float) $request->input('total');

        // Calculate subtotal based on sent items
        $subtotal = collect($items)->sum(function ($item) {
            // Optionally verify price against database to prevent tampering
            $product = Product::find($item['product_id']);
            $price = $product ? $product->price : (float) $item['price'];
            return $price * $item['quantity'];
        });

        // Add any additional fees (e.g., shipping, tax)
        $shippingFee = $subtotal * 0.1; // Adjust as needed
        $calculatedTotal = $subtotal + $shippingFee;


        if (abs($calculatedTotal - $frontendTotal) > 0.01) {
            return response()->json(['error' => 'Total amount mismatch'], 400);
        }

        // Create order
        $itemsCollection = collect($items); // Convert to Collection
        $order = Order::create([
            'user_id' => $user->id,
            'delivery_information_id' => $deliveryInfo->id,
            'amount' => $calculatedTotal,
            'invoice_no' => 'ORD-' . Str::upper(Str::random(8)),
            'payment_method' => $request->paymentMethod,
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'items' => $itemsCollection->map(fn($item) => [
                'product_id' => $item['product_id'],
                'name' => Product::find($item['product_id'])->name,
                'quantity' => $item['quantity'],
                'size' => $item['size'],
                'price' => $item['price'],
            ])->toJson(),
            'created_at' => Carbon::now()
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
                Log::info('Order items before dispatch (COD):', ['items' => $order->items]);
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
                Cache::forget(('order'));
                return response()->json(['redirect' => '/orders']);

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
        $reference = Str::uuid();

        Log::info('Paystack Payment Initialization Attempt', [
            'order_id' => $order->id,
            'amount' => $order->amount * 100,
            'email' => $order->deliveryInformation->email,
            'reference' => $reference,
            'callback_url' => route('payment.callback'),
        ]);

        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'amount' => (int) ($order->amount * 100), // Ensure integer
                'email' => $order->deliveryInformation->email,
                'reference' => $reference,
                'currency' => 'NGN',
                'callback_url' => route('payment.callback'),
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ],
            ]);

        $paymentData = $response->json();

        Log::info('Paystack Response', ['response' => $paymentData]);
        // Log the full Paystack response
        Log::info('Paystack Response', [
            'status' => $paymentData['status'] ?? 'not set',
            'message' => $paymentData['message'] ?? 'no message',
            'data' => $paymentData['data'] ?? 'no data',
            'full_response' => $paymentData,
        ]);

        if (!$paymentData || !isset($paymentData['status']) || !$paymentData['status']) {
            $order->delete();
            Log::error('Paystack Payment Initialization Failed', [
                'response' => $paymentData,
                'order_id' => $order->id,
            ]);
            return response()->json([
                'error' => 'Payment initialization failed',
                'details' => $paymentData['message'] ?? 'Unknown error',
            ], 500);
        }

        $order->update(['payment_reference' => $reference]);
        Cache::forget('order');
        return response()->json([
            'payment_url' => $paymentData['data']['authorization_url'],
        ]);
    }

    /**
     * Handle Paystack's payment callback
     */

    public function handlePaymentCallback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect(env('FRONTEND_URL') . '/padp?error=' . urlencode('Invalid payment reference'));
        }

        // Verify transaction with Paystack
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->get("https://api.paystack.co/transaction/verify/$reference");

        $paymentDetails = $response->json();

        if ($paymentDetails['status'] && $paymentDetails['data']['status'] === 'success') {
            // Webhook will handle the rest, just redirect
            return redirect(env('FRONTEND_URL') . '/orders');
        } else {
            // Webhook will delete the order, redirect to failure page
            return redirect(env('FRONTEND_URL') . '/padp?error=' . urlencode('Payment failed'));
        }
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

        Cache::forget(('order'));
        // Return the client secret so the frontend can confirm the payment
        return response()->json([
            'clientSecret' => $paymentIntent->client_secret,
            'paymentMethod' => 'stripe',
            'order_id' => $order->id
        ]);
    }

    public function deliveryInformation()
    {
        $user = auth()->user();

        $deliveryInfo = DeliveryInformation::where('user_id', $user->id)->first();

        if (!$deliveryInfo) {
            return;
        } else {
            Cache::forget(('order'));
            return response()->json(['deliveryInfo' => $deliveryInfo], 200);
        }
    }

    // get all order
    public function getAllOrder()
    {
        $admin = auth('admin')->user();

        // Check if the user is an admin or sub-admin
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Fetch all orders with relationships, cached for 60 seconds
        $orders = Cache::remember('all_orders', 60, function () {
            return Order::with(['user', 'deliveryInformation'])->get();
        });

        // Transform orders to include product images
        $orders->transform(function ($order) {
            // Decode items JSON into an array
            $items = json_decode($order->items, true);
            if (!is_array($items)) {
                $items = []; // Handle invalid JSON gracefully
            }

            // Fetch product images for all items in one query
            $productIds = array_column($items, 'product_id');
            $products = Product::whereIn('id', $productIds)->pluck('image1', 'id');

            // Map items with image URLs
            $order->items = array_map(function ($item) use ($products) {
                $item['image1_url'] = isset($products[$item['product_id']])
                    ? asset(Storage::url($products[$item['product_id']]))
                    : null; // Fallback if product not found
                return $item;
            }, $items);

            return $order;
        });

        // Return orders or empty response
        return response()->json([
            'orders' => $orders->isEmpty() ? [] : $orders,
            'message' => $orders->isEmpty() ? 'No orders found' : null
        ], 200);
    }

    // count number of order
    public function countOrder()
    {
        $admin = auth('admin')->user();

        // admin
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // fetch all order
        $order = DB::table('orders')->count();

        if ($order) {
            Cache::forget(('order'));
            return response()->json(['order' => $order], 200);
        } else {
            return response()->json(['message' => 'could not count order'], 404);
        }
    }

    // get user order
    public function getUserOrder()
    {
        $user = auth()->id();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orders = Cache::remember("user_orders_{$user}", 60, function () use ($user) {
            return Order::where('user_id', $user)
                ->paginate(10);
        });

        $orders->getCollection()->transform(function ($order) {
            $items = json_decode($order->items, true) ?? [];

            $productIds = array_column($items, 'product_id');
            $products = Product::whereIn('id', $productIds)->pluck('image1', 'id');

            $order->items = array_map(function ($item) use ($products) {
                $item['image1_url'] = isset($products[$item['product_id']])
                    ? asset(Storage::url($products[$item['product_id']]))
                    : null;
                return $item;
            }, $items);

            return $order;
        });

        return response()->json([
            'orders' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
            'message' => $orders->isEmpty() ? 'No orders found' : null
        ], 200);
    }

    // cancel order if failure to connect with stripe pay
    public function cancelOrder(Request $request)
    {
        $orderId = $request->input('order_id');
        if ($orderId && $orderId !== 'unknown') {
            $order = Order::find($orderId);
            if ($order && $order->payment_status === 'pending') {
                $order->delete();
                Log::info('Order manually deleted due to client-side payment failure: ' . $orderId);
                return response()->json(['status' => 'order cancelled']);
            }
        }
        return response()->json(['status' => 'no action taken'], 400);
    }
}
