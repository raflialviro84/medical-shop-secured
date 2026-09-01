<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function dashboard()
    {
        $totalUsers = User::where('role', 'customer')->count();
        $totalProducts = Product::count();
        $totalTransactions = Transaction::count();
        $recentTransactions = Transaction::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        $lowStockProducts = Product::where('stock', '<', 10)
            ->orderBy('stock', 'asc')
            ->take(5)
            ->get();
        $totalRevenue = Transaction::sum('total_price');
            
        return view('admin.dashboard', compact(
            'totalUsers',
            'totalProducts',
            'totalTransactions',
            'recentTransactions',
            'lowStockProducts',
            'totalRevenue'
        ));
    }

    /**
     * Display a listing of the users.
     */
    public function users()
    {
        $users = User::paginate(15);
        
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function editUser(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'full_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,customer',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroyUser(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Display a listing of the products.
     */
    public function products()
    {
        $products = Product::paginate(15);
        
        return view('admin.products.index', compact('products'));
    }

    /**
     * Display a listing of the transactions.
     */
    public function transactions()
    {
        $transactions = Transaction::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('admin.transactions.index', compact('transactions'));
    }

    /**
     * Display the specified transaction.
     */
    public function showTransaction(Transaction $transaction)
    {
        $details = $transaction->details()->with('product')->get();
        
        return view('admin.transactions.show', compact('transaction', 'details'));
    }
}
