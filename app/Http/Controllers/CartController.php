<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'size' => 'required|string',
                'cart_id' => 'required|string',
            ]);

            // if (!$request->hasSession()) {
            //     // \Log::warning('No session available on request');
            //     // Force session start if middleware failed
            //     $request->session()->start();
            // }


            // $sessionId = $request->session()->getId();
            $userId = Auth::id();
            $cartId = $request->cart_id;
            \Log::info("Session ID: $sessionId, User ID: " . ($userId ?? 'null'));

            $existingCart = Cart::where(function ($query) use ($userId, $cartId) {
                $query->where('user_id', $userId)->orWhere('session_id', $cartId);
            })->where('product_id', $request->product_id)->where('size', $request->size)->first();

            if (!$existingCart) {
                Cart::create([
                    'user_id' => $userId,
                    'session_id' => $userId ? null : $cartId,
                    'product_id' => $request->product_id,
                    'size' => $request->size,
                    'created_at' => now(),
                ]);
                \Log::info('Cart item created');
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


        $cartItems = Cart::where(function ($query) use ($userId, $cartId) {
            $query->where('user_id', $userId)->orWhere('cart_id', $cartId);
        })->with('products')->get();

        return response()->json($cartItems);
    }

    // count cart items
    public function countCart(Request $request)
    {
        try {
            $request->validate([
                'cart_id' => 'required|string',
            ]);

            $cartId = $request->cart_id;
            $userId = Auth::id();
            Log::debug($userId);


            $countCart = Cart::where(function ($query) use ($userId, $cartId) {
                $query->where('user_id', $userId)->orWhere('cart_id', $cartId);
            })->count();

            if ($countCart) {
                return response()->json(['count' => $countCart]);
            } else {
                return response()->json(['count' => 0]);
            }
        } catch (\Exception $e) {
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

            // Update guest carts to associate with the user
            Cart::where('cart_id', $cartId)
                ->update(['user_id' => $userId, 'cart_id' => null]);

            return response()->json(['message' => 'Cart merged successfully']);
        } catch (\Exception $e) {
            Log::error('Cart merge failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //  Remove Item from Cart
    public function removeFromCart($id)
    {
        $userId = Auth::id();
        $sessionId = request()->session()->getId();

        $cartItem = Cart::where('id', $id)
            ->where(function ($query) use ($userId, $sessionId) {
                $query->where('user_id', $userId)
                    ->orWhere('session_id', $sessionId);
            })->first();

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
