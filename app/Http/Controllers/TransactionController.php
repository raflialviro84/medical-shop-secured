<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\TransactionCreated;

class TransactionController extends Controller
{
    /**
     * Display a listing of the user's transactions.
     */
    public function index()
    {
        $transactions = Auth::user()->transactions()->orderBy('created_at', 'desc')->paginate(10);
        
        return view('transactions.index', compact('transactions'));
    }

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction)
    {
        // Ensure the transaction belongs to the authenticated user or the user is an admin
        if ($transaction->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $details = $transaction->details()->with('product')->get();
        
        return view('transactions.show', compact('transaction', 'details'));
    }

    /**
     * Create a new transaction from the cart.
     */
    public function checkout()
    {
        $user = Auth::user();
        $cartItems = $user->cartItems()->with('product')->get();
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        // Calculate total price
        $totalPrice = $cartItems->sum(function ($item) {
            return $item->product->price * $item->amount;
        });

        // Begin transaction
        DB::beginTransaction();

        try {
            // Create transaction
            $transaction = Transaction::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'status' => 'pending',
                'total_price' => $totalPrice,
                'timestamp' => now(),
            ]);

            // Create transaction details
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                
                // Check if product is in stock
                if ($product->stock < $cartItem->amount) {
                    throw new \Exception("Product '{$product->product_name}' is out of stock.");
                }

                // Create transaction detail
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'amount' => $cartItem->amount,
                    'price' => $product->price,
                ]);

                // Update product stock
                $product->update([
                    'stock' => $product->stock - $cartItem->amount,
                ]);
            }

            // Clear the cart
            $user->cartItems()->delete();

            // Generate PDF
            $pdf = Pdf::loadView('transactions.invoice', [
                'transaction' => $transaction,
                'details' => $transaction->details()->with('product')->get(),
                'user' => $user,
            ]);

            // Send email
            Mail::to($user->email)->send(new TransactionCreated($transaction, $pdf));

            DB::commit();

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction created successfully. Please check your email for payment instructions.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('cart.index')
                ->with('error', 'Failed to create transaction: ' . $e->getMessage());
        }
    }

    /**
     * Process payment for a transaction.
     */
    public function pay(Transaction $transaction)
    {
        // Ensure the transaction belongs to the authenticated user
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure the transaction can be paid
        if (!$transaction->canBePaid()) {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'This transaction cannot be paid.');
        }

        // Update transaction status
        $transaction->update([
            'status' => 'paid',
        ]);

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Payment processed successfully.');
    }

    /**
     * Mark a transaction as shipped.
     */
    public function ship(Transaction $transaction)
    {
        // Ensure the user is an admin
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        // Ensure the transaction can be shipped
        if (!$transaction->canBeShipped()) {
            return redirect()->route('admin.transactions.show', $transaction)
                ->with('error', 'This transaction cannot be shipped.');
        }

        // Update transaction status
        $transaction->update([
            'status' => 'shipped',
        ]);

        return redirect()->route('admin.transactions.show', $transaction)
            ->with('success', 'Transaction marked as shipped successfully.');
    }

    /**
     * Mark a transaction as done.
     */
    public function markAsDone(Transaction $transaction)
    {
        // Ensure the transaction belongs to the authenticated user
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure the transaction can be marked as done
        if (!$transaction->canBeMarkedAsDone()) {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'This transaction cannot be marked as done.');
        }

        // Update transaction status
        $transaction->update([
            'status' => 'done',
        ]);

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Transaction marked as done successfully.');
    }

    /**
     * Generate and display an invoice for a transaction.
     */
    public function invoice(Transaction $transaction)
    {
        // Ensure the transaction belongs to the authenticated user or the user is an admin
        if ($transaction->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $details = $transaction->details()->with('product')->get();
        $user = $transaction->user;
        
        $pdf = Pdf::loadView('transactions.invoice', [
            'transaction' => $transaction,
            'details' => $details,
            'user' => $user,
        ]);
        
        return $pdf->stream('invoice-' . substr($transaction->id, 0, 8) . '.pdf');
    }
}
