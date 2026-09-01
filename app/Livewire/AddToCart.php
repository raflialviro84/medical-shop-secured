<?php

namespace App\Livewire;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AddToCart extends Component
{
    public Product $product;
    public int $quantity = 1;
    
    public function mount(Product $product)
    {
        $this->product = $product;
    }
    
    public function increment()
    {
        if ($this->quantity < $this->product->stock) {
            $this->quantity++;
        }
    }
    
    public function decrement()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }
    
    public function updatedQuantity()
    {
        // Ensure quantity is within valid range
        if ($this->quantity < 1) {
            $this->quantity = 1;
        } elseif ($this->quantity > $this->product->stock) {
            $this->quantity = $this->product->stock;
        }
    }
    
    public function addToCart()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        if ($this->quantity > $this->product->stock) {
            session()->flash('error', 'Not enough stock available.');
            return;
        }
        
        $cart = Cart::where('user_id', Auth::id())
            ->where('product_id', $this->product->id)
            ->first();
            
        if ($cart) {
            // Update existing cart item
            $newAmount = $cart->amount + $this->quantity;
            
            if ($newAmount > $this->product->stock) {
                session()->flash('error', 'Cannot add more of this product to your cart.');
                return;
            }
            
            $cart->amount = $newAmount;
            $cart->save();
        } else {
            // Create new cart item
            Cart::create([
                'user_id' => Auth::id(),
                'product_id' => $this->product->id,
                'amount' => $this->quantity
            ]);
        }
        
        $this->dispatch('cartUpdated');
        session()->flash('success', 'Product added to cart successfully.');
    }
    
    public function render()
    {
        return view('livewire.add-to-cart');
    }
}
