<?php

namespace App\Livewire\Transactions;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class IndexPage extends Component
{
    use WithPagination;

    public function render()
    {
        $transactions = Auth::user()->transactions()->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.transactions.index-page', compact('transactions'));
    }
}