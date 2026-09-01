<?php

namespace App\Livewire\Transactions;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ShowPage extends Component
{
    public Transaction $transaction;

    public function mount(Transaction $transaction)
    {
        $this->transaction = $transaction->load(['user', 'details.product']);

        if ($this->transaction->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403);
        }
    }

    public function pay()
    {
        if ($this->transaction->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $this->transaction->canBePaid()) {
            session()->flash('error', 'This transaction cannot be paid.');
            return;
        }

        $this->transaction->update(['status' => 'paid']);

        $this->transaction->refresh();
        session()->flash('success', 'Payment processed successfully.');
    }

    public function markAsDone()
    {
        if ($this->transaction->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $this->transaction->canBeMarkedAsDone()) {
            session()->flash('error', 'This transaction cannot be marked as done.');
            return;
        }

        $this->transaction->update(['status' => 'done']);

        $this->transaction->refresh();
        session()->flash('success', 'Transaction marked as done successfully.');
    }

    public function render()
    {
        return view('livewire.transactions.show-page');
    }
}