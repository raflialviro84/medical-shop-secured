<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the landing page.
     */
    public function index()
    {
        $featuredProducts = Product::where('stock', '>', 0)
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();
            
        return view('home.index', compact('featuredProducts'));
    }

    /**
     * Search for products.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        $products = Product::where('product_name', 'like', "%{$query}%")
            ->orWhere('product_description', 'like', "%{$query}%")
            ->paginate(12);
            
        return view('products.search', compact('products', 'query'));
    }
}
