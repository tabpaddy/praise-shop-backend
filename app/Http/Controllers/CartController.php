<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartController extends Controller
{
    //  Add Item to Cart (Guest or Authenticated User)
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'size' => 'required|string',
        ]);

        $sessionId = $request->session()->getId(); // Get session ID for guest users
        $userId = Auth::id(); // Get logged-in user ID if available

        // Check if product is already in cart
        $existingCart = Cart::where(function ($query) use ($userId, $sessionId) {
            $query->where('user_id', $userId)->orWhere('session_id', $sessionId);
        })->where('product_id', $request->product_id)->where('size', $request->size)->first();

        if (!$existingCart) {
            Cart::create([
                'user_id' => $userId,
                'session_id' => $userId ? null : $sessionId,
                'product_id' => $request->product_id,
                'size' => $request->size,
                'created_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Item added to cart successfully']);
    }

    //  Get Cart Items (Merge Guest & User Cart)
    public function getCart()
    {
        $userId = Auth::id();
        $sessionId = request()->session()->getId();

        $cartItems = Cart::where(function ($query) use ($userId, $sessionId) {
            $query->where('user_id', $userId)->orWhere('session_id', $sessionId);
        })->with('products')->get();

        return response()->json($cartItems);
    }

    // count cart items
    public function countCart()
    {
        $userId = Auth::id();
        $sessionId = request()->session()->getId();

        $countCart = Cart::where(function ($query) use ($userId, $sessionId) {
            $query->where('user_id', $userId)->orWhere('session_id', $sessionId);
        })->count();

        if ($countCart) {
            return response()->json($countCart);
        }else{
            return response()->json(['count' => 0]);
        }
    }


    //  Merge Guest Cart After Login
    public function mergeCartAfterLogin()
    {
        $userId = Auth::id();
        $sessionId = request()->session()->getId();

        // Update guest cart to belong to the user
        Cart::where('session_id', $sessionId)->update(['user_id' => $userId, 'session_id' => null]);

        return response()->json(['message' => 'Cart merged successfully']);
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
