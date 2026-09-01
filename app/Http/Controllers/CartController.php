<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Display the user's cart.
     */
    public function index()
    {
        $cartItems = Auth::user()->cartItems()->with('product')->get();
        $total = $cartItems->sum(function ($item) {
            return $item->product->price * $item->amount;
        });
        
        return view('cart.index', compact('cartItems', 'total'));
    }

    /**
     * Add a product to the cart.
     */
    public function add(Request $request, Product $product)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:' . $product->stock,
        ]);

        $userId = Auth::id();
        $amount = $request->input('amount', 1);

        // Check if the product is already in the cart
        $cartItem = Cart::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            // Update the amount if the product is already in the cart
            $newAmount = $cartItem->amount + $amount;
            
            // Ensure the amount doesn't exceed the stock
            if ($newAmount > $product->stock) {
                $newAmount = $product->stock;
            }
            
            $cartItem->update(['amount' => $newAmount]);
        } else {
            // Add the product to the cart
            Cart::create([
                'user_id' => $userId,
                'product_id' => $product->id,
                'amount' => $amount,
            ]);
        }

        return redirect()->route('cart.index')
            ->with('success', 'Product added to cart successfully.');
    }

    /**
     * Update the amount of a product in the cart.
     */
    public function update(Request $request, Cart $cart)
    {
        // Ensure the cart item belongs to the authenticated user
        if ($cart->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|integer|min:1|max:' . $cart->product->stock,
        ]);

        $cart->update(['amount' => $request->amount]);

        return redirect()->route('cart.index')
            ->with('success', 'Cart updated successfully.');
    }

    /**
     * Remove a product from the cart.
     */
    public function remove(Cart $cart)
    {
        // Ensure the cart item belongs to the authenticated user
        if ($cart->user_id !== Auth::id()) {
            abort(403);
        }

        $cart->delete();

        return redirect()->route('cart.index')
            ->with('success', 'Product removed from cart successfully.');
    }

    /**
     * Clear the cart.
     */
    public function clear()
    {
        Auth::user()->cartItems()->delete();

        return redirect()->route('cart.index')
            ->with('success', 'Cart cleared successfully.');
    }

    /**
     * Decrease the amount of a product in the cart by 1.
     */
    public function decrease(Product $product)
    {
        $userId = Auth::id();
        
        // Find the cart item
        $cartItem = Cart::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();
        
        if ($cartItem) {
            if ($cartItem->amount > 1) {
                // Decrease the amount by 1
                $cartItem->update(['amount' => $cartItem->amount - 1]);
            } else {
                // Remove the item if amount would be 0
                $cartItem->delete();
            }
        }

        return redirect()->route('cart.index')
            ->with('success', 'Cart updated successfully.');
    }

    /**
     * Increase the amount of a product in the cart by 1.
     */
    public function increase(Product $product)
    {
        $userId = Auth::id();
        
        // Find the cart item
        $cartItem = Cart::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();
        
        if ($cartItem) {
            // Ensure the amount doesn't exceed the stock
            if ($cartItem->amount < $product->stock) {
                // Increase the amount by 1
                $cartItem->update(['amount' => $cartItem->amount + 1]);
            }
        }

        return redirect()->route('cart.index')
            ->with('success', 'Cart updated successfully.');
    }
}
