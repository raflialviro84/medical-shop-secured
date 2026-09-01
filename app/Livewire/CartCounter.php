<?php

namespace App\Livewire;

use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CartCounter extends Component
{
    protected $listeners = ['cartUpdated' => 'render'];

    public function render()
    {
        $count = 0;
        
        if (Auth::check()) {
            $count = Cart::where('user_id', Auth::id())->sum('amount');
        }
        
        return view('livewire.cart-counter', [
            'count' => $count
        ]);
    }
}
