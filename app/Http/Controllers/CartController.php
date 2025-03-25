<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CartController extends Controller
{
    //  Add Item to Cart (Guest or Authenticated User)
    public function addToCart(Request $request)
    {

        // \Log::info('Request headers: ' . json_encode($request->headers->all()));
        // \Log::info('Request cookies: ' . json_encode($request->cookies->all()));
        // \Log::info('addToCart called with data: ' . json_encode($request->all()));
        try {
            $userId = Auth::id();
            $validationRules = [
                'product_id' => 'required|exists:products,id',
                'size' => 'required|string',
            ];

            // Only require cart_id if the user is not authenticated
            if (!$userId) {
                $validationRules['cart_id'] = 'required|string';
            }

            $request->validate($validationRules);

            // $sessionId = $request->session()->getId();
            $cartId = $request->cart_id;
            // \Log::info("Cart ID: " . ($cartId ?? 'null') . ", User ID: " . ($userId ?? 'null'));
            // \Log::info("Auth check: " . (Auth::check() ? 'authenticated' : 'not authenticated'));
            // \Log::info("Request headers: " . json_encode($request->headers->all()));

            $existingCart = Cart::where(function ($query) use ($userId, $cartId) {
                $query->where('user_id', $userId)->orWhere('cart_id', $cartId);
            })->where('product_id', $request->product_id)->where('size', $request->size)->first();

            if (!$existingCart) {
                Cart::create([
                    'user_id' => $userId,
                    'cart_id' => $cartId, // Use existing or generate new cart_id for guests
                    'product_id' => $request->product_id,
                    'size' => $request->size,
                    'created_at' => now(),
                ]);
                // \Log::info('Cart item created');
            } else {
                \Log::info('Item already in cart');
            }

            return response()->json(['message' => 'Item added to cart successfully']);
        } catch (\Exception $e) {
            \Log::error('Add to cart failed: ' . $e->getMessage() . "\nStack: " . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //  Get Cart Items (Merge Guest & User Cart)
    public function getCart($cartId)
    {
        $userId = Auth::id();
        $query = Cart::query();
        // Log::info("Fetching cart - User ID: " . ($userId ?? 'null') . ", Cart ID: " . $cartId);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('user_id', null)->where('cart_id', $cartId);
        }
        $cartItems = $query->with('product')->get()->filter(function ($item) {
            return $item->product !== null;
        });
        $cartItems->transform(function ($item) {
            $item->image1_url = asset(Storage::url($item->product->image1));
            return $item;
        });
        // Log::info("Cart items fetched: " . $cartItems->toJson());
        return response()->json($cartItems);
    }

    // count cart items
    public function countCart(Request $request)
    {
        try {
            $request->validate([
                'cart_id' => 'nullable|string'
            ]);
            $userId = Auth::id();
            $cartId = $request->cart_id;
            // Log::info("Counting cart - Cart ID: $cartId, User ID: " . ($userId ?? 'null'));

            $countCart = Cart::where(function ($query) use ($userId, $cartId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('cart_id', $cartId);
                }
            })->count();

            // Log::info("Cart count: $countCart");
            return response()->json(['count' => $countCart ?: 0]);
        } catch (\Exception $e) {
            Log::error('Count cart failed: ' . $e->getMessage() . "\nStack: " . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    //  Merge Guest Cart After Login
    public function mergeCartAfterLogin(Request $request)
    {
        try {
            $request->validate([
                'cart_id' => 'required|string',
            ]);

            $userId = Auth::id();
            $cartId = $request->cart_id;

            // Get guest cart items
            $guestCartItems = Cart::where('cart_id', $cartId)->get();

            foreach ($guestCartItems as $guestItem) {
                // Check if this item already exists in the user's cart
                $existingItem = Cart::where('user_id', $userId)
                    ->where('product_id', $guestItem->product_id)
                    ->where('size', $guestItem->size)
                    ->first();

                if ($existingItem) {
                    // Item already exists in user's cart, remove the guest duplicate
                    $guestItem->delete();
                } else {
                    // Merge unique item by updating user_id and clearing cart_id
                    $guestItem->update(['user_id' => $userId, 'cart_id' => null]);
                }
            }

            return response()->json(['message' => 'Cart merged successfully']);
        } catch (\Exception $e) {
            Log::error('Cart merge failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //  Remove Item from Cart
    public function removeFromCart(Request $request, $id)
    {
        $userId = Auth::id();
        $cartId = $request->input('cart_id'); // Get cart_id from request body
        $cartItem = Cart::where('id', $id)
            ->where(function ($query) use ($userId, $cartId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('cart_id', $cartId);
                }
            })
            ->first();

        if (!$cartItem) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        $cartItem->delete();
        return response()->json(['message' => 'Item removed from cart']);
    }


    //  Clear Cart (Optional)
    public function clearCart()
    {
        $userId = Auth::id();
        $sessionId = request()->session()->getId();

        Cart::where(function ($query) use ($userId, $sessionId) {
            $query->where('user_id', $userId)
                ->orWhere('session_id', $sessionId);
        })->delete();

        return response()->json(['message' => 'Cart cleared successfully']);
    }
}
