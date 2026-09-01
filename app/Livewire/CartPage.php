<?php

namespace App\Livewire;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CartPage extends Component
{
    public function increase(Product $product)
    {
        $cartItem = Cart::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if (! $cartItem) {
            return;
        }

        if ($cartItem->amount < $product->stock) {
            $cartItem->update(['amount' => $cartItem->amount + 1]);
        }
    }

    public function decrease(Product $product)
    {
        $cartItem = Cart::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if (! $cartItem) {
            return;
        }

        if ($cartItem->amount > 1) {
            $cartItem->update(['amount' => $cartItem->amount - 1]);
            return;
        }

        $cartItem->delete();
    }

    public function remove(Cart $cart)
    {
        if ($cart->user_id !== Auth::id()) {
            abort(403);
        }

        $cart->delete();
    }

    public function clear()
    {
        Auth::user()->cartItems()->delete();
    }

    public function render()
    {
        $cartItems = Auth::user()->cartItems()->with('product')->get();
        $total = $cartItems->sum(function ($item) {
            return $item->product->price * $item->amount;
        });

        return view('livewire.cart-page', compact('cartItems', 'total'));
    }
}