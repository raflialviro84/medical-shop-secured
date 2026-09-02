<?php

use Illuminate\Support\Facades\Route;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\User;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CryptographicSessionBindingController;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/search', [HomeController::class, 'search'])->name('products.search');

// Authentication routes
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');

Route::post('/logout', function () {
    auth()->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->name('logout');

// Authenticated user routes
Route::middleware(['auth'])->group(function () {

    Route::get('/security/test', function () {
        return response()->json([
            'message' => 'Cryptographic protected route berhasil diakses.',
        ]);
    })->middleware('cryptographic.session');

    Route::get('/security/baseline', function () {
        return response()->json([
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'message' => 'Baseline protected by Laravel session only.',
        ]);
    })->middleware('auth');

    // Cryptographic Session Binding
    Route::post(
        '/security/session-binding',
        [CryptographicSessionBindingController::class, 'store']
    )->name('security.session-binding.store');

    Route::post(
        '/security/session-proof',
        [CryptographicSessionBindingController::class, 'verify']
    )->name('security.session-proof.verify');

    // Cart routes
    Route::view('/cart', 'cart.index')->name('cart.index');

    Route::post(
        '/cart/add/{product}',
        [CartController::class, 'add']
    )->name('cart.add');

    Route::post(
        '/cart/decrease/{product}',
        [CartController::class, 'decrease']
    )->name('cart.decrease');

    Route::post(
        '/cart/increase/{product}',
        [CartController::class, 'increase']
    )->name('cart.increase');

    Route::put(
        '/cart/{cart}',
        [CartController::class, 'update']
    )->name('cart.update');

    Route::delete(
        '/cart/{cart}',
        [CartController::class, 'remove']
    )->name('cart.remove');

    Route::delete(
        '/cart',
        [CartController::class, 'clear']
    )->name('cart.clear');

    // Transaction routes
    Route::view('/transactions', 'transactions.index')
        ->name('transactions.index');

    Route::get('/transactions/{transaction}', function (Transaction $transaction) {
        return view('transactions.show', compact('transaction'));
    })->name('transactions.show');

    Route::get(
        '/transactions/{transaction}/invoice',
        [TransactionController::class, 'invoice']
    )->name('transactions.invoice');

    Route::post(
        '/transactions',
        [TransactionController::class, 'checkout']
    )->name('transactions.store');

    Route::post(
        '/checkout',
        [TransactionController::class, 'checkout']
    )->name('checkout');

    Route::post(
        '/transactions/{transaction}/pay',
        [TransactionController::class, 'pay']
    )->name('transactions.pay');

    Route::post(
        '/transactions/{transaction}/done',
        [TransactionController::class, 'markAsDone']
    )->name('transactions.done');
});

// Admin routes
Route::middleware(['admin', 'auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get(
            '/',
            [AdminController::class, 'dashboard']
        )->name('dashboard');

        // Admin user management
        Route::view(
            '/users',
            'admin.users.index'
        )->name('users.index');

        Route::get('/users/{user}/edit', function (User $user) {
            return view('admin.users.edit', compact('user'));
        })->name('users.edit');

        Route::put(
            '/users/{user}',
            [AdminController::class, 'updateUser']
        )->name('users.update');

        Route::delete(
            '/users/{user}',
            [AdminController::class, 'destroyUser']
        )->name('users.destroy');

        // Admin product management
        Route::view(
            '/products',
            'admin.products.index'
        )->name('products.index');

        Route::get(
            '/products/create',
            fn () => view('admin.products.create')
        )->name('products.create');

        Route::post(
            '/products',
            [ProductController::class, 'store']
        )->name('products.store');

        Route::get('/products/{product}/edit', function (Product $product) {
            return view('admin.products.edit', compact('product'));
        })->name('products.edit');

        Route::put(
            '/products/{product}',
            [ProductController::class, 'update']
        )->name('products.update');

        Route::delete(
            '/products/{product}',
            [ProductController::class, 'destroy']
        )->name('products.destroy');

        // Admin transaction management
        Route::get(
            '/transactions',
            [AdminController::class, 'transactions']
        )->name('transactions.index');

        Route::get(
            '/transactions/{transaction}',
            [AdminController::class, 'showTransaction']
        )->name('transactions.show');

        Route::get(
            '/transactions/{transaction}/invoice',
            [TransactionController::class, 'invoice']
        )->name('transactions.invoice');

        Route::post(
            '/transactions/{transaction}/ship',
            [TransactionController::class, 'ship']
        )->name('transactions.ship');
    });